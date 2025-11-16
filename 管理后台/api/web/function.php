<?php

function getClientIP()
{
  $ip = '';

  if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
  } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $ip = $_SERVER['HTTP_CLIENT_IP'];
  } else {
    $ip = $_SERVER['REMOTE_ADDR'];
  }

  // 处理多个IP
  if (strpos($ip, ',') !== false) {
    $ipList = array_map('trim', explode(',', $ip));
    $ip = $ipList[0];
  }

  // IPv6地址可能包含端口号，需要处理
  if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
    // 如果是IPv6且有端口号，去除端口号
    if (preg_match('/\[([0-9a-fA-F:]+)\]:[0-9]+/', $ip, $matches)) {
      $ip = $matches[1];
    }
  }

  return $ip;
}

/**
 * 返回JSON格式的响应
 */
function returnJson($code, $msg, $data = null)
{
  $result = [
    'code' => $code,
    'msg' => $msg
  ];

  if ($data !== null) {
    $result['data'] = $data;
  }

  echo json_encode($result);
  exit;
}

/**
 * 获取任务信息
 */
function getTaskById($db, $tid)
{
  $tasks = $db->select('tasks', ['*'], ['id' => $tid], '', 1);
  return $tasks[0] ?? null;
}

/**
 * 获取小程序信息
 */
function getXcxInfo($db)
{
  $xcxList = $db->select('xcx', ['type', 'appid', 'secret', 'path'], ['status' => 1], '', 1);
  return $xcxList[0] ?? null;
}

/**
 * 获取历史观看记录
 */
function getViewInfo($db, $tid, $did)
{
  $viewList = $db->select(
    'task_view',
    ['*'],
    [
      'task_id' => $tid,
      'did' => $did
    ],
    '',
    1
  );
  return $viewList[0] ?? null;
}

/**
 * 处理奖励逻辑
 */
function processAward($db, $task, $viewInfo, $tid, $viewId)
{
  // 如果已有奖励，直接返回
  if (!empty($viewInfo['award'])) {
    return $viewInfo['award'];
  }

  $award = null;

  // 处理卡密类型奖励
  if ($task['type'] == 3) {
    $codeArr = explode("|", $task['award']);
    if (count($codeArr) > 0) {
      $award = $codeArr[0];
      $remainder = implode("|", array_slice($codeArr, 1));

      // 更新剩余卡密
      $db->update(
        'tasks',
        ['award' => $remainder],
        ['id' => $tid]
      );
    }
  } else {
    $award = $task['award'];
  }

  // 更新奖励到数据库
  $db->update(
    'task_view',
    [
      'award_type' => $task['type'],
      'award' => $award
    ],
    ['id' => $viewId]
  );

  return $award;
}

/**
 * 创建新的观看记录
 */
function createNewViewRecord($db, $tid, $ip, $did)
{
  return $db->insert(
    'task_view',
    [
      'task_id' => $tid,
      'ip' => $ip,
      'did' => $did
    ]
  );
}

/**
 * 更新任务统计
 */
function updateTaskStats($db, $tid)
{
  $today = date('Y-m-d');
  $statsList = $db->select(
    'task_stats',
    ['id', 'did'],
    ['task_id' => $tid, 'created_at' => $today]
  );
  $statsInfo = $statsList[0] ?? null;

  if ($statsInfo) {
    $db->update(
      'task_stats',
      ['did' => $statsInfo['did'] + 1],
      ['id' => $statsInfo['id']]
    );
  } else {
    $db->insert(
      'task_stats',
      [
        'task_id' => $tid,
        'did' => 1,
        'created_at' => $today
      ]
    );
  }
}

/**
 * 生成小程序访问数据
 */
function generateXcxData($xcxType, $appid, $secret, $path, $viewId)
{
  // 个体小程序使用urlScheme
  if ($xcxType === 1) {
    $dataType = 'url';
    $data = "weixin://dl/business/?appid={$appid}&path={$path}&query=" . urlencode("vid={$viewId}");
    return [$dataType, $data];
  }

  // 普通小程序生成小程序码
  $dataType = 'image';

  // 获取access_token
  $tokenUrl = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$secret}";
  $tokenResponse = file_get_contents($tokenUrl);
  $tokenData = json_decode($tokenResponse, true);
  $accessToken = $tokenData['access_token'] ?? null;

  if (!$accessToken) {
    returnJson(500, '获取access_token失败');
  }

  // 获取小程序码
  $codeUrl = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token={$accessToken}";
  $codeData = [
    'scene' => "{$viewId}",
    'page' => $path
  ];

  $ch = curl_init($codeUrl);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($codeData));
  curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
  $codeResponse = curl_exec($ch);
  curl_close($ch);

  if (!$codeResponse) {
    returnJson(500, '获取小程序码失败');
  }

  // 编码为base64字符串
  $data = 'data:image/png;base64,' . base64_encode($codeResponse);

  return [$dataType, $data];
}
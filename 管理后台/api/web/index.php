<?php

// 获取原始输入并解析JSON
$rawInput = file_get_contents('php://input');
$inputData = json_decode($rawInput, true);
$tid = $inputData['id'] ?? null;
$did = $inputData['did'] ?? null;

include './function.php';

// 获取当前客户端IP
$ip = getClientIP();

// 验证参数
if (!is_numeric($tid) || empty($did)) {
  returnJson(500, '参数错误');
}

// 引入配置文件
include '../config.php';

// 重写数据库信息
$dbConfig = [
  'dsn' => 'mysql:host=' . $dbConfig['host'] . ';port=' . $dbConfig['port'] . ';dbname=' . $dbConfig['dbname'] . ';charset=' . $dbConfig['charset'],
  'user' => $dbConfig['username'],
  'pass' => $dbConfig['password'],
];

// 引入DB类
include '../admin/common/DB.php';

// 实例化DB
$_DB = new DB($dbConfig);

// 获取任务详情
$task = getTaskById($_DB, $tid);
if (!$task || $task['status'] != 1) {
  returnJson(500, '任务不存在');
}

// 获取小程序信息
$xcxInfo = getXcxInfo($_DB);
if (!$xcxInfo) {
  returnJson(500, '未配置小程序');
}

// 提取小程序信息
$xcxType = $xcxInfo['type'];
$appid = $xcxInfo['appid'];
$secret = $xcxInfo['secret'];
$path = $xcxInfo['path'];

// 检查小程序配置
if ($xcxType === 0 && empty($secret)) {
  returnJson(500, '未配置小程序密钥');
}

// 获取历史观看记录
$viewInfo = getViewInfo($_DB, $tid, $did);

// 处理已存在记录的情况
if ($viewInfo) {
  $viewId = $viewInfo['id'];
  
  // 检查任务是否完成
  if ($viewInfo['status'] > $task['click']) {
    $award = processAward($_DB, $task, $viewInfo, $tid, $viewId);
    
    returnJson(200, '任务已完成', [
      'title' => $task['name'],
      'click' => $task['click'],
      'type' => $task['type'],
      'award' => $award
    ]);
  } else if ($viewInfo['status'] == 1) {
    $msg = "未点击并体验广告超过5秒，请重试";
  }else {
    $msg = "任务未完成";
  }
} else {
  // 创建新记录并更新统计
  $viewId = createNewViewRecord($_DB, $tid, $ip, $did);
  updateTaskStats($_DB, $tid);
}

// 生成小程序访问数据
[$dataType, $data] = generateXcxData($xcxType, $appid, $secret, $path, $viewId);

// 返回任务未完成结果
returnJson(400, $msg, [
  'title' => $task['name'],
  'click' => $task['click'],
  'url_type' => $dataType,
  'url' => $data
]);

?>

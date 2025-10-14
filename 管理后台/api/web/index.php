<?php

// 获取原始输入并解析JSON
$rawInput = file_get_contents('php://input');
$inputData = json_decode($rawInput, true);
$tid = $inputData['id'] ?? null;
$did = $inputData['did'] ?? null;

include './function.php';

// 获取当前客户端IP
$ip = getClientIP();

// 检验参数
if (!is_numeric($tid) || empty($did)) {
  $result = [
    'code' => 500,
    'msg' => '参数错误'
  ];
  echo json_encode($result);
  exit;
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
$tasks = $_DB->select('tasks', ['*'], ['id' => $tid], '', 1);
$task = $tasks[0] ?? null;

// 检查任务是否存在/启用
if (!$task || $task['status'] != 1) {
  $result = [
    'code' => 500,
    'msg' => '任务不存在'
  ];
  echo json_encode($result);
  exit;
}

// 随机选取一个小程序
$xcxList = $_DB->select('xcx', ['type', 'appid', 'secret', 'path'], ['status' => 1], '', 1);
$xcxInfo = $xcxList[0] ?? null;

// 检查有可用小程序
if (!$xcxInfo) {
  $result = [
    'code' => 500,
    'msg' => '未配置小程序'
  ];
  echo json_encode($result);
  exit;
}

// 提取appid字段值
$xcxType = $xcxInfo['type'];
$appid = $xcxInfo['appid'];
$secret = $xcxInfo['secret'];
$path = $xcxInfo['path'];

if ($xcxType === 0 && empty($secret)) {
  $result = [
    'code' => 500,
    'msg' => '未配置小程序密钥'
  ];
  echo json_encode($result);
  exit;
}

// 获取历史观看记录
$viewList = $_DB->select(
  'task_view',
  ['*'],
  [
    'task_id' => $tid,
    'did' => $did
  ],
  '',
  1
);
$viewInfo = $viewList[0] ?? null;

// 检查任务完成状态
if ($viewInfo && $viewInfo['status'] > $task['click']) {
  // 已完成任务
  $result = [
    'code' => 200,
    'msg' => '任务已完成',
    'data' => [
      'title' => $task['name'],
      'click' => $task['click'],
      'type' => $task['type'],
      'award' => $task['award']
    ]
  ];
  echo json_encode($result);
  exit;
}

// 获取或创建任务视图ID
if ($viewInfo) {
  $viewId = $viewInfo['id'];
} else {
  $viewId = $_DB->insert(
    'task_view',
    [
      'task_id' => $tid,
      'ip' => $ip,
      'did' => $did
    ]
  );
  // 修改统计信息
  $today = date('Y-m-d');
  $statsList = $_DB->select(
    'task_stats',
    ['id', 'did'],
    ['task_id' => $tid, 'created_at' => $today]
  );
  $statsInfo = $statsList[0] ?? null;
  if ($statsInfo) {
    $_DB->update(
      'task_stats',
      ['did' => $statsInfo['did'] + 1],
      ['id' => $statsInfo['id']]
    );
  } else {
    $_DB->insert(
      'task_stats',
      [
        'task_id' => $tid,
        'did' => 1,
        'created_at' => $today
      ]
    );
  }
}


// 个体小程序无法使用urlscheme，只能使用小程序码
if ($xcxType === 1) {
  $dataType = 'url';
  // 拼接urlScheme字符串
  $data = "weixin://dl/business/?appid={$appid}&path={$path}&query=" . urlencode("vid={$viewId}");
} else {
  $dataType = 'image';
  // 获取access_token
  $tokenUrl = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$secret}";
  $tokenResponse = file_get_contents($tokenUrl);
  $tokenData = json_decode($tokenResponse, true);
  $accessToken = $tokenData['access_token'] ?? null;
  if (!$accessToken) {
    $result = [
      'code' => 500,
      'msg' => '获取access_token失败'
    ];
    echo json_encode($result);
    exit;
  }

  // 调用获取小程序码接口
  $codeUrl = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token={$accessToken}";
  $codeData = [
    'scene' => "{$viewId}",
    'page' => $path
  ];
  // 使用CURL发送POST请求
  $ch = curl_init($codeUrl);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($codeData));
  curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
  $codeResponse = curl_exec($ch);
  // 关闭CURL会话
  curl_close($ch);
  if (!$codeResponse) {
    $result = [
      'code' => 500,
      'msg' => '获取小程序码失败'
    ];
    echo json_encode($result);
    exit;
  }
  
  // 编码为base64字符串
  $data = 'data:image/png;base64,' . base64_encode($codeResponse);
}


// 返回任务未完成结果
$result = [
  'code' => 400,
  'msg' => '任务未完成',
  'data' => [
    'title' => $task['name'],
    'click' => $task['click'],
    'url_type' => $dataType,
    'url' => $data
  ]
];
echo json_encode($result);
exit;

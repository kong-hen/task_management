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
$xcxList = $_DB->select('xcx', ['appid', 'path'], ['status' => 1], '', 1);
$xcx = $xcxList[0] ?? null;

// 检查有可用小程序
if (!$xcx) {
  $result = [
    'code' => 500,
    'msg' => '未配置小程序'
  ];
  echo json_encode($result);
  exit;
}

// 提取appid字段值
$appid = $xcx['appid'];
$path = $xcx['path'];

// 获取历史观看记录
$taskViewList = $_DB->select(
  'task_view',
  ['*'],
  [
    'task_id' => $tid,
    'did' => $did
  ],
  '',
  1
);
$taskView = $taskViewList[0] ?? null;

// 检查任务完成状态
if ($taskView && $taskView['status'] > $task['click']) {
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
if ($taskView) {
  $taskViewId = $taskView['id'];
} else {
  $taskViewId = $_DB->insert(
    'task_view',
    [
      'task_id' => $tid,
      'ip' => $ip,
      'did' => $did
    ]
  );
  // 修改统计信息
  $today = date('Y-m-d');
  $taskStatsList = $_DB->select(
    'task_stats',
    ['id', 'did'],
    ['task_id' => $tid, 'created_at' => $today]
  );
  $taskStats = $taskStatsList[0] ?? null;
  if ($taskStats) {
    $_DB->update(
      'task_stats',
      ['did' => $taskStats['did'] + 1],
      ['id' => $taskStats['id']]
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

// 拼接urlScheme字符串
$urlScheme = "weixin://dl/business/?appid={$appid}&path={$path}&query=" . urlencode("vid={$taskViewId}");

// 返回任务未完成结果
$result = [
  'code' => 400,
  'msg' => '任务未完成',
  'data' => [
    'title' => $task['name'],
    'click' => $task['click'],
    'urlScheme' => $urlScheme
  ]
];
echo json_encode($result);
exit;

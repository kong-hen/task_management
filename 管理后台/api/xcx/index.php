<?php

// 获取参数
$vid = $_POST['vid'] ?? null;
$status = $_POST['status'] ?? null;

// 检验参数
if (!is_numeric($tid) || !in_array($status, [1, 2])) {
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

// 获取观看信息
$taskViewList = $_DB->select(
  'task_view',
  ['task_id'],
  ['id' => $vid]
);
$tid = $taskViewList[0]['task_id'];
// 修改广告状态
$_DB->update(
  'task_view',
  ['status' => $status],
  ['id' => $vid]
);

// 修改统计信息
$today = date('Y-m-d');

// 如果点击了广告
if ($status == 2) {
  $sql = "UPDATE `task_stats` SET `view` = `view` + 1, `click` = `click` + 1 WHERE `task_id` = :tid AND `created_at` = :created_at";
} else {
  $sql = "UPDATE `task_stats` SET `view` = `view` + 1 WHERE `task_id` = :tid AND `created_at` = :created_at";
}
$params = ['tid' => $tid, 'created_at' => $today];
$_DB->query($sql, $params);


// 返回结果
$result = [
  'code' => 200,
  'msg' => '解锁成功'
];
echo json_encode($result);
exit;

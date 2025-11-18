<?php

// 引入配置文件
include '../config.php';
include './function.php';
require_once "SignatureUtil.php";


// 获取参数
$vid = $_POST['vid'] ?? null;
$status = $_POST['status'] ?? null;
$appid = $_POST['appid'] ?? null;
$js_code = $_POST['js_code'] ?? null;
$version = $_POST['version'] ?? null;

// 检验参数
if (!is_numeric($vid) || !in_array($status, [1, 2]) || empty($appid) || empty($js_code) || empty($version)) {
  returnJson(500, '参数错误');
}


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

// 获取小程序信息
$appInfo = getAppInfo($_DB, $appid);
$appid = $appInfo['appid'];
$secret = $appInfo['secret'];

// 获取用户登录session_key
$sessionData = getSessionKey($appid, $secret, $js_code);

// 获取微信access_token
$accessToken = getAccessToken($appid, $secret);

// 使用session_key对空字串签名
$signature = hash_hmac('sha256', "", $sessionData['session_key']);
$openid = $sessionData['openid'] ?? null;

// 获取用户encryptKey
$encryptKey = getSignatureKey($accessToken, $openid, $signature, $version);

// 校验签名
if (!SignatureUtil::verify($_POST, $encryptKey)) {
  returnJson(400, "签名不一致");
}

// 获取观看信息
$viewList = $_DB->select(
  'task_view',
  ['task_id'],
  ['id' => $vid]
);
$tid = $viewList[0]['task_id'];
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

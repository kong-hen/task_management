<?php

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
 *  获取小程序信息
 */ 
function getAppInfo($db, $appid) {
  $appInfo = $db->select(
    'xcx',
    ['id', 'appid', 'secret'],
    ['appid' => $appid],
    '',
    1
  );
  if (empty($appInfo) || empty($appInfo[0]['appid']) || empty($appInfo[0]['secret'])) {
    returnJson(400, "小程序不存在");
  }
  return $appInfo[0];
}


/**
 * 获取微信access_token
 */
function getAccessToken($appid, $secret) {
  //获取微信access_token
  $tokenUrl = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$secret}";
  $tokenResponse = file_get_contents($tokenUrl);
  $tokenData = json_decode($tokenResponse, true);
  $accessToken = $tokenData['access_token'] ?? null;
  if (empty($accessToken)) {
    returnJson(400, "小程序信息错误");
  }
  return $accessToken;
}


/**
 * 获取用户session_key
 */
function getSessionKey($appid, $secret, $js_code) {
  $sessionUrl = "https://api.weixin.qq.com/sns/jscode2session?appid={$appid}&secret={$secret}&js_code={$js_code}&grant_type=authorization_code";
  $sessionResponse = file_get_contents($sessionUrl);
  $sessionData = json_decode($sessionResponse, true);
  $errCode = $sessionData['errcode'] ?? null;
  if ($errCode != 0) {
    returnJson(400, $sessionData['errmsg'] ?? '用户登录失败');
  }
  return $sessionData;
}


/**
 * 获取用户encryptKey
 */
function getSignatureKey($accessToken, $openid, $signature, $version) {
  $keyUrl = "https://api.weixin.qq.com/wxa/business/getuserencryptkey?access_token={$accessToken}&openid={$openid}&signature={$signature}&sig_method=hmac_sha256";
  $keyResponse = file_get_contents($keyUrl);
  $keyData = json_decode($keyResponse, true);
  $errCode = $keyData['errcode'] ?? null;
  if ($errCode != 0 || count($keyData['key_info_list']) == 0) {
    returnJson(400, $keyData['errmsg'] ?? '获取用户密钥失败');
  }
  $encryptkey = "";
  foreach ($keyData['key_info_list'] as $item) {
    if ($item['version'] == $version) {
      $encryptkey = $item['encrypt_key'] ?? "";
      break;
    }
  }
  return $encryptkey;
}
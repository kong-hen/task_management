<?php

class SignatureUtil
{
  /** 校验签名 */
  public static function verify($data, $secretKey)
  {
    if (
      !isset($data['signature']) ||
      !isset($data['timestamp'])
    ) {
      return false;
    }

    $clientSig = $data['signature'];
    $timestamp = $data['timestamp'];

    unset($data['signature']);

    /** 1. 检查时间戳是否过期（5分钟内有效） */
    $now = round(microtime(true) * 1000);
    if (abs($now - $timestamp) > 5 * 60 * 1000) {
      return false;
    }

    /** 2. 扁平化 */
    $flat = self::flatten($data);

    /** 3. 排序拼接 */
    $sortedStr = self::sortAndJoin($flat);

    /** 4. 服务端生成签名 */
    $serverSig = hash_hmac('sha256', $sortedStr, $secretKey);

    /** 5. 固定时间比较 */
    return hash_equals($serverSig, $clientSig);
  }

  /** 扁平化 key 结构 */
  private static function flatten($data, $parent = '', &$result = [])
  {
    if (!is_array($data)) {
      $result[$parent] = $data;
      return $result;
    }

    foreach ($data as $key => $value) {
      $newKey = ($parent === '')
        ? $key
        : (is_numeric($key) ? "{$parent}[{$key}]" : "{$parent}.{$key}");

      if (is_array($value)) {
        self::flatten($value, $newKey, $result);
      } else {
        $result[$newKey] = $value;
      }
    }

    return $result;
  }

  /** 排序拼接 */
  private static function sortAndJoin($params)
  {
    ksort($params);
    $pairs = [];
    foreach ($params as $k => $v) {
      $pairs[] = "{$k}={$v}";
    }
    return implode('&', $pairs);
  }
}

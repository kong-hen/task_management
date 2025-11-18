import CryptoJS from 'crypto-js'

export default class SignatureUtil {

  /** 返回：保持原结构 + timestamp + signature */
  static genSignedParams(params, secretKey, encodeType = 'hex') {
    const timestamp = Date.now()   // 毫秒时间戳

    // 克隆避免修改原数据
    const data = JSON.parse(JSON.stringify(params))

    // 注入时间戳
    data.timestamp = timestamp

    // 扁平化参与签名的参数
    const flatParams = this.flattenParams(data)
    const sortedStr = this.sortAndJoin(flatParams)

    // 计算签名
    const signature = this.hmacSha256(sortedStr, secretKey, encodeType)

    // 添加 signature
    data.signature = signature

    return data
  }

  /** 扁平化对象用于签名 */
  static flattenParams(data, parentKey = '', result = {}) {
    if (data === null || data === undefined) return result

    if (typeof data !== 'object') {
      result[parentKey] = data
      return result
    }

    if (Array.isArray(data)) {
      data.forEach((item, index) => {
        const key = parentKey ? `${parentKey}[${index}]` : `${index}`
        this.flattenParams(item, key, result)
      })
    } else {
      Object.keys(data).forEach(key => {
        const newKey = parentKey ? `${parentKey}.${key}` : key
        this.flattenParams(data[key], newKey, result)
      })
    }

    return result
  }

  /** 排序并拼接 key=value 网络签名格式 */
  static sortAndJoin(params) {
    const keys = Object.keys(params).sort()
    return keys.map(k => `${k}=${params[k]}`).join('&')
  }

  /** HMAC-SHA256 */
  static hmacSha256(str, key, type = 'hex') {
    const hash = CryptoJS.HmacSHA256(str, key)
    return type === 'base64'
      ? CryptoJS.enc.Base64.stringify(hash)
      : CryptoJS.enc.Hex.stringify(hash)
  }
}

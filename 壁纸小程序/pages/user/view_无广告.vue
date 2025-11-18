<template>
  <view class="body">
    <!-- 页面内容 -->
    <view class="tip">解锁后，即可获取</view>
    <button class="button" @click="lookOk">点击按钮解锁获取</button>
    <view class="sss">已有<text>899**</text>位用户获取</view>
  </view>
</template>

<script>
	import SignatureUtil from '@/utils/signature.js'
  export default {
    data() {
      return {
        // 后台接口
        baseUrl: 'https://task.dev.xma.run',
        web: false,
        vid: null,
        img: "https://c-ssl.duitang.com/uploads/blog/202503/16/5zSNqaEXuOqgmzg.jpg"
      }
    },
    onLoad(options) {
      if (options.vid) {
        this.web = true;
        this.vid = options.vid;
      }
      if (options.scene) {
        this.web = true;
        this.vid = decodeURIComponent(options.scene);
      }
      console.log('vid', this.vid)
      this.getPhoto();
    },

    onHide() {
      console.log("页面隐藏")
    },
    methods: {

      // 获取随机图片
      getPhoto() {
        uni.request({
          url: 'https://www.duitang.com/napi/blog/list/by_search/?kw=%E9%AB%98%E6%B8%85%E5%A3%81%E7%BA%B8&start=0',
          method: 'GET',
          success: (res) => {
            const data = res.data;
            if (data.status === 1) {
              const list = data.data.object_list;
              if (list.length > 0) {
                const item = list[Math.floor(Math.random() * list.length)];
                this.img = item.photo.path;
              }
            }
          }
        });
      },

      // 跳转到图片下载页
      toDown() {
        uni.navigateTo({
          url: '/pages/index/down?src=' + this.img
        })
      },

      // 广告观看完毕，下发奖励
      lookOk() {
        // 向服务器发送广告信息
        if (this.web) {
          uni.showLoading({
            title: '检测广告观看状态中',
            mask: true
          })
          // 获取登录code
          uni.login({
          	onlyAuthorize: true,
            success: (loginRes) => {
          		// 从微信获取用户密钥
          		const userCryptoManager = wx.getUserCryptoManager()
          		userCryptoManager.getLatestUserKey({
          			success: (cryptRes) => {
                  let signatureParams = null; // 在外层声明变量
                  try {
                    const js_code = loginRes.code
                    const { encryptKey, iv, version, expireTime } = cryptRes
                    const appid = uni.getAccountInfoSync().miniProgram.appId;
                    signatureParams = SignatureUtil.genSignedParams({
                      appid: appid,
                      js_code: js_code,
                      vid: this.vid,
                      status: status
                    }, encryptKey)
                    console.log("加密结果：", signatureParams)
                  } catch (err) {
                    uni.hideLoading()
                    console.error("请求签名失败：", err)
                    uni.showToast({
                      title: '请求签名失败, 请联系客服'
                    })
                    return
                  }

                  // 检查签名参数是否成功生成
                  if (!signatureParams) {
                    uni.hideLoading()
                    uni.showToast({
                      title: '签名参数生成失败'
                    })
                    return
                  }
          				
          				// 发送广告观看结果
          				uni.request({
          					url: this.baseUrl + "/api/xcx/",
          					method: 'POST',
          					header: {
          						'content-type': 'application/x-www-form-urlencoded'
          					},
          					data: signatureParams,
          					success: (reqRes) => {
          						uni.hideLoading();
          						if(reqRes.data.code === 200){
          							uni.showToast({
          								title: '解锁成功',
          								icon: "none",
          								duration: 500
          							})
          							setTimeout(()=> {
          								this.toDown();
          							}, 200)
          						}else{
          							uni.showToast({
          								title: reqRes.data.msg,
          								icon: "none"
          							})
          						}
          					},
          					fail: (err) => {
          						console.error('上传广告信息到服务器失败：', err)
          						uni.hideLoading()
          						uni.showToast({
          							title: '获取广告状态失败'
          						})
          					}
          				})
          			},
          			fail: (err) => {
          				uni.hideLoading()
          				console.error("生成密钥失败：", err)
          				uni.showToast({
          					title: '生成密钥失败, 请联系客服'
          				})
          			}
          		})
          	},
          	fail: (err) => {
          		uni.hideLoading()
          		console.error("用户登录失败：", err)
          		uni.showToast({
          			title: '用户登录失败'
          		})
          	},
          })
        } else {
          // 正常使用当前页面
          this.toDown();
        }
      }
    }
  }
</script>

<style>
  page {
    background: #f7f7f7;
  }

  .body {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
  }

  .tip {
    box-sizing: border-box;
    margin: 30rpx auto;
    width: 300rpx;
    text-align: center;
    font-size: 26rpx;
    font-weight: bold;
  }

  .button {
    padding: 0;
    width: 500rpx;
    height: 100rpx;
    line-height: 100rpx;
    border-radius: 60rpx;
    background-color: #000;
    text-align: center;
    font-weight: 600;
    color: #fff;
    font-size: 32rpx;
  }

  .button::after {
    border: none;
  }

  .sss {
    margin: 50rpx auto;
    text-align: center;
    color: #9c9c9c;
    font-size: 30rpx;
  }

  .sss text {
    margin: 0 10rpx;
    font-size: 40rpx;
    color: #ffc800;
    font-weight: bold;
  }
</style>
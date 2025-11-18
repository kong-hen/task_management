<template>
	<view class="body">
		<!-- 页面内容 -->
		<view class="tip">解锁后，即可获取</view>
		<button class="button" :loading="_isLoading" @click="adPlay">{{ btnText }}</button>
		<view class="sss">已有<text>899**</text>位用户获取</view>
	</view>
</template>

<script>
	import SignatureUtil from '@/utils/signature.js'
	
	export default{
		data() {
			return{
				// 后台接口
				baseUrl: 'https://task.dev.xma.run',
				_isLoading: true,
				btnText: '广告加载中',
				_rewardedVideoAd: null,
				web: false,
				vid: null,
				start: 0,
				stop: 0,
				img: "https://c-ssl.duitang.com/uploads/blog/202503/16/5zSNqaEXuOqgmzg.jpg"
			}
		},
		onLoad(options) {
			if(options.vid){
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
			// 不告诉你是干什么的
			this.start = Date.now();
			console.log("页面隐藏")
		},
		onShow() {
			// 这个也不告诉你
			this.stop = Date.now();
			console.log("页面显示")
			if(this._rewardedVideoAd == null) {
				this._isLoading = true;
				this.btnText = '广告加载中';
				let rewardedVideoAd = this._rewardedVideoAd = uni.createRewardedVideoAd({
					adUnitId: "adunit-4208cc3976c045d8"
				})
				rewardedVideoAd.onLoad(() => {
					console.log("广告加载完毕")
					this.btnText = '看视频广告，解锁获取'
					this._isLoading = false;
				})
				rewardedVideoAd.onError((err) => {
					console.error('广告加载失败：', err)
					this.btnText = '广告加载失败'
					this._isLoading = false;
				})
				rewardedVideoAd.onClose((res) => {
					console.log('用户关闭广告：', res)
					if (res && res.isEnded) {
						// 正常播放结束
						console.log("观看结束");
						this.lookOk();
					} else {
						// 播放中途退出
						console.log("播放中途退出");
						uni.showToast({
							title: "需要完整观看广告才能获得奖励",
							icon: "none"
						})
					}
				})
			}
		},
		methods: {
			
			// 获取随机图片
			getPhoto() {
			  uni.request({
			    url: 'https://www.duitang.com/napi/blog/list/by_search/?kw=%E9%AB%98%E6%B8%85%E5%A3%81%E7%BA%B8&start=0',
			    method: 'GET',
			    success: (res) => {
			      const data = res.data;
			      if (data.status ===  1) {
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
				if(this.web) {
					uni.showLoading({
						title: '检测广告观看状态中',
						mask: true
					})
					let status = 1
					if((this.stop - this.start) > 3000) {
						status = 2
					}
					
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
											version: version,
											vid: this.vid,
											status: status
										}, encryptKey)
										console.log("加密结果：", signatureParams)
									} catch(err) {
										uni.hideLoading()
										console.error("请求签名失败：", err)
										uni.showToast({
											title: "请求签名失败, 请联系客服",
											icon: "none"
										})
										return
									}
									
									// 检查签名参数是否成功生成
									if (!signatureParams) {
										uni.hideLoading()
										uni.showToast({
											title: "签名参数生成失败",
											icon: "none"
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
											console.log("请求结果：", reqRes)
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
												title: "获取广告状态失败",
												icon: "none"
											})
										}
									})
								},
								fail: (err) => {
									uni.hideLoading()
									console.error("生成密钥失败：", err)
									uni.showToast({
										title: "生成密钥失败, 请联系客服",
										icon: "none"
									})
								}
							})
						},
						fail: (err) => {
							uni.hideLoading()
							console.error("用户登录失败：", err)
							uni.showToast({
								title: "用户登录失败",
								icon: "none"
							})
						},
					})
				}else{
					// 正常使用当前页面
					this.toDown();
				}
			},
			
			// 检测
			adPlay() {
				// 广告加载中
				if(this._isLoading) {
					return;
				}
				if(this.btnText == '广告加载失败') {
					uni.showToast({
						title: '广告加载失败，请稍后重试'
					})
					return
				}
				uni.showToast({
					title: '加载中',
					icon: 'loading',
					mask: true
				})
				// 获取广告开始播放时间戳
				this.start = Date.now();
				// 播放广告
				let rewardedVideoAd = this._rewardedVideoAd;
				rewardedVideoAd.show()
				.catch(() => {
				    rewardedVideoAd.load()
				    .then(() => rewardedVideoAd.show())
				    .catch(err => {
				      console.error('激励视频 广告显示失败', err)
							this.btnText = '广告显示失败'
							uni.showToast({
								title: '广告显示失败，请稍后重试'
							})
				    })
				})
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
<template>
	<view class="page f-x-bt h100 f18">
		<view class="f-1">

		</view>
		<view v-if="!isReset" class="right bs10 bf f-y-bt login_media">
			<view>
				<u-tabs :current="current" :scrollable="false" :lineWidth="45" :lineHeight="4" lineColor="#4275F4"
					:itemStyle="{height:`${pc?'80px':'10.4166vh'}`}" :activeStyle="{fontWeight:'bold'}" :inactiveStyle="{color:'#888'}"
					:list="[{name:'账户登录'}]" @change="changeTab"></u-tabs>
				<view class="bd1" style="position:relative;top:-2px;"></view>
			</view>
			<view class="accBox f-1">
				<u--form labelPosition="left" :model="form" :rules="rules" ref="uForm">
					<view v-if="current===1" class="acount f-c-c">
						<!-- <view class="imgBox mb20">
							<u--image class="image" src="@/static/imgs/r-gzh.jpg" width="200px" height="200px"
								@click="click"></u--image>
							<view class="wrapper"></view>
						</view> -->
						<view class="qr-scanner">
							<view class="box">
								<view class="line"></view>
								<view class="angle"></view>
							</view>
						</view>
						<view class="f16">请使用微信扫描二维码登录</view>
					</view>
					<view v-else class="acount">
						<view class="mb20">
							<u-form-item label=" " prop="username" ref="item1" labelWidth="0px">
								<u--input :clearable="true" placeholder="请输入您的账号或者手机号码" v-model="form.username"
									prefixIcon="account" prefixIconStyle="font-size: 22px;color: #909399"
									style="height:48px"></u--input>
							</u-form-item>
						</view>
						<view class="mb20">
							<u-form-item label=" " prop="password" ref="item1" labelWidth="0px">
								<u--input :clearable="true" placeholder="请输入密码" type="password" v-model="form.password"
									prefixIcon="lock" prefixIconStyle="font-size: 22px;color: #909399"
									style="height:48px"></u--input>
							</u-form-item>
						</view>
						<view class="f-x-bt mb50">
							<u-checkbox-group v-model="isRemember" size="18" placement="column">
								<u-checkbox :customStyle="{marginRight: '10px'}" labelSize="18" activeColor="#4275F4"
									iconSize="18" iconColor="#fff" label="记住密码" :name="0">
								</u-checkbox>
							</u-checkbox-group>
							<!-- <view class="c9 f16" @click="isReset=true">忘记密码</view> -->
						</view>
						<view class="mb25">
							<u-button color="linear-gradient(to bottom, #4275F4, #4275F4)"
								:customStyle="{height:'55px',borderRadius:'10px'}" @click="login" @keyup.enter="login">
								<text class="cf f20">立即登录</text></u-button>
						</view>
						<!-- 	<view class="dfa mb10">
							<u-checkbox-group v-model="isDeal" size="16" placement="column">
								<u-checkbox :customStyle="{marginRight: '10px'}" labelSize="15" activeColor="#4275F4"
									iconSize="18" iconColor="#000" label="已阅读并同意" :name="0">
								</u-checkbox>
							</u-checkbox-group>
							<u--text type="warning" text="《用户使用协议》"></u--text>
							<u--text type="warning" text="《隐私协议》"></u--text>
						</view> -->
					</view>
				</u--form>
			</view>
		</view>
		<view v-else class="right bs10 bf f-y-bt p20">
			<view class="tar"><text class="iconfont icon-cuowu" @click="isReset=false"></text></view>
		</view>
		<u-toast ref="uToast"></u-toast>
		<domeUrl ref="domeRef" @auth="auth" />
	</view>
</template>

<script>
	import {
		mapState,
		mapMutations,
		mapActions
	} from 'vuex'
	import domeUrl from './components/domeUrl.vue';
	export default {
		components: {
			domeUrl,
		},
		data() {
			return {
				isReset: false,
				current: 0,
				operat: '',
				isRemember: [],
				isDeal: [],
				operats: [{
						value: 0,
						text: "设置本机为主收银"
					},
					{
						value: 1,
						text: "解绑副收银"
					},
				],
				form: {
					number: '',
					username: '',
					verifyCode: '',
					password: ''
				},
				rules: {
					username: [{
						required: true,
						message: '请输入手机号',
						trigger: ['change', 'blur'],
					}, ],
					password: [{
						required: true,
						message: '请输入密码',
						trigger: ['blur', 'change']
					}]
				}
			}
		},
		created() {
			this.keydown()
		},
		computed: {
			...mapState({
				siteroot: state => state.siteroot,
			}),
		},
		onReady() {
			this.$refs.uForm.setRules(this.rules)
			// #ifdef APP-PLUS
			if (!this.siteroot) {
				this.$refs['domeRef'].open()
			}
			// #endif
			// #ifdef H5
			if (process.env.NODE_ENV !== 'development') {
				this.setSiteroot(location.origin)
			} else {
				console.log('development')
				this.setSiteroot('https://ybv3.b-ke.cn')
			}
			// #endif
		},
		onLoad() {
			uni.setStorageSync('subject_color', '#FDDA34')
			let token = uni.getStorageSync('token'),
				storeId = uni.getStorageSync('storeId'),
				uniacid = uni.getStorageSync('uniacid');
			if (token && !storeId && !uniacid) {
				uni.reLaunch({
					url: '/pages/login/selectStore'
				})
			} else if (token && !storeId && uniacid) {
				uni.reLaunch({
					url: '/pages/login/selectShop'
				})
			} else if (token && storeId && uniacid) {
				uni.reLaunch({
					url: '/pages/home/index'
				})
			}
		},
		methods: {
			...mapMutations(["setSiteroot"]),
			...mapActions(["getLogin"]),
			changeTab(e) {
				this.current = e.index
			},
			keydown(e) {
				document.onkeydown = e => {
					if (e.keyCode === 13) {
						this.login()
					}
				}
			},
			auth(e) {
				if (e) this.setSiteroot(e.domain)
			},
			login() {
				// #ifdef APP-PLUS
				if (!this.siteroot) {
					return this.$refs['domeRef'].open()
				}
				// #endif
				this.$refs.uForm.validate().then(res => {
					this.getLogin(this.form)
				}).catch(errors => {
					// uni.$u.toast('请填写完整的登录信息')
				})

				// if (!this.isDeal.includes(0)) {
				// 	this.$refs.uToast.show({
				// 		message: '请先勾选协议'
				// 	})
				// } else {

				// 	// uni.reLaunch({
				// 	// 	url: `/pages/home/index`
				// 	// })
				// }
			}
		}
	}
</script>

<style lang="scss" scoped>
	.page {
		width: 100%;
		height: 100vh;
		-webkit-box-sizing: border-box;
		box-sizing: border-box;
		background: url('@/static/imgs/lbg.png') no-repeat 50%;
		background-size: cover;
		position: relative;

		.right {
			position: absolute;
			top: 20.1830vh;
			right: 12.5695vw;
			width: 36.6032vw;
			height: 65.1041vh;

			/deep/.u-tabs__wrapper__nav__item__text {
				font-size: 18px;
				color: #000;
			}

			.qr-scanner {
				position: relative;
				height: 200px;
				width: 200px;
				margin: 0px auto;
				margin-top: 120rpx;
				/*此处为了居中*/
				background: url("@/static/imgs/r-gzh.jpg");
				background-repeat: no-repeat;
			}

			.qr-scanner .box {
				width: 250px;
				height: 250px;
				max-height: 250px;
				max-width: 250px;
				position: relative;
				left: 50%;
				top: 50%;
				transform: translate(-50%, -50%);
				overflow: hidden;
				border: 0.1rem solid rgba(0, 255, 51, 0.2);
			}

			.qr-scanner .line {
				height: calc(100% - 2px);
				width: 100%;
				background: linear-gradient(180deg, rgba(0, 255, 51, 0) 43%, #00ff33 211%);
				border-bottom: 3px solid #00ff33;
				transform: translateY(-100%);
				animation: radar-beam 2s infinite;
				animation-timing-function: cubic-bezier(0.53, 0, 0.43, 0.99);
				animation-delay: 1.4s;
			}

			.qr-scanner .box:after,
			.qr-scanner .box:before,
			.qr-scanner .angle:after,
			.qr-scanner .angle:before {
				content: '';
				display: block;
				position: absolute;
				width: 3vw;
				height: 3vw;

				border: 0.2rem solid transparent;
			}

			.qr-scanner .box:after,
			.qr-scanner .box:before {
				top: 0;
				border-top-color: #00ff33;
			}

			.qr-scanner .angle:after,
			.qr-scanner .angle:before {
				bottom: 0;
				border-bottom-color: #00ff33;
			}

			.qr-scanner .box:before,
			.qr-scanner .angle:before {
				left: 0;
				border-left-color: #00ff33;
			}

			.qr-scanner .box:after,
			.qr-scanner .angle:after {
				right: 0;
				border-right-color: #00ff33;
			}

			@keyframes radar-beam {
				0% {
					transform: translateY(-100%);
				}

				100% {
					transform: translateY(0);
				}
			}

			.imgBox {
				background-image: url('@/static/imgs/mask.png');
				background-size: cover;
				width: 250px;
				height: 250px;
				display: flex;
				-webkit-box-align: center;
				align-items: center;
				-webkit-box-pack: center;
				justify-content: center;
				position: relative;

				.image {
					position: absolute;
				}

				.wrapper {
					width: 200px;
					height: 200px;
					background:
						linear-gradient(#1a98ca, #1a98ca),
						linear-gradient(90deg, #ffffff33 1px, transparent 0, transparent 19px),
						linear-gradient(#ffffff33 1px, transparent 0, transparent 19px),
						linear-gradient(transparent, #1a98ca);
					background-size: 100% 1.5%, 10% 100%, 100% 8%, 100% 100%;
					background-repeat: no-repeat, repeat, repeat, no-repeat;
					background-position: 0% 0%, 0 0, 0 0, 0 0;
					/* 初始位置 */
					clip-path: polygon(0% 0%, 100% 0%, 100% 1.5%, 0% 1.5%);
					/* 添加动画效果 */
					animation: move 2.7s infinite linear;
				}

				@keyframes move {
					to {
						background-position: 0 100%, 0 0, 0 0, 0 0;
						/* 终止位置 */
						clip-path: polygon(0% 0%, 100% 0%, 100% 100%, 0% 100%);
					}
				}
			}


			.acount {
				padding: 2.1961vw 2.1961vw 0 2.1961vw;
			}

			.qrcode {
				width: 246px;
				height: 246px;
				display: inline-block;
				margin-bottom: 18px;
				position: relative;

				.mask {
					position: absolute;
					z-index: 10;
					left: 22px;
					top: 22px;
					right: 22px;
					bottom: 22px;
					background-color: rgba(0, 0, 0, .7);
					-webkit-box-align: center;
					align-items: center;
					-webkit-box-pack: center;
					-ms-flex-pack: center;
					justify-content: center
				}

				.qr_box {
					background: url('@/static/imgs/mask.png');
					background-size: cover;
					width: 100%;
					height: 100%;
					display: flex;
					-webkit-box-align: center;
					align-items: center;
					-webkit-box-pack: center;
					justify-content: center;
					position: relative;
				}

				.slide-line {
					position: absolute;
					top: 0;
					left: 50%;
					transform: translateX(-50%);
					width: 280px;
					height: 4px;
					background: linear-gradient(270deg, rgba(0, 244, 255, 0), #00edff 55%, rgba(0, 229, 255, 0));
					animation: slideLine-data-v-e95d689c 3s ease-in-out infinite alternate;
					z-index: 100;
				}
			}
		}
	}

	.mb50 {
		margin-bottom: 6.5104vh;
	}

	@media (min-width: 1500px) and (max-width: 3280px) {
		.page {
			.right {
				position: absolute;
				top: 30%;
				right: 22%;
				width: 500px;
				height: 500px;

				.acount {
					padding: 30px 30px 0 30px;
				}
			}
		}
	}
	@media (min-width: 500px) and (max-width: 900px) {
		.page {
			.right {
				position: absolute;
				top: 6%;
				right: 18%;
				width: 300px;
				// height: 340px;
				min-height: 340px;
	
				.acount {
					padding: 20px 20px 0 20px;
				}
			}
		}
	}
</style>
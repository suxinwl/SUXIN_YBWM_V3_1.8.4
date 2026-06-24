<template>
	<view class="">
		<u-popup :show="show" :round="10" :closeable="true" mode="center" @close="close">
			<view class="cash">
				<view class="f-c f-y-c pt20">
					<view class="tac wei6 f24">{{tx}}</view>
					<view class="f18 f-c f-y-c l-h1 ml20 zchy p10 bs5" style="color: #4275F4;" @click="zc">
						<view class="mr5">注册会员</view>
					</view>
				</view>
				<view class="f18 f-c f-y-c l-h1 mt20" style="color: #4275F4;" @click="handScan">
					<view class="mr5">点击使用摄像头扫码</view>
					<u-icon name="scan" color="#4275F4" size="20"></u-icon>
				</view>
				<view class="p20">
					<u--input class="f20" :focus="focus" v-model="value" placeholder="扫描会员码/手机号/手机号后四位" type="number"
						style="height: 70px;" placeholderStyle="fontWeight:normal" @change="scan"></u--input>
				</view>
				<keybored type="digit" v-model="value" confirmText="确认" @doneClear="doneClear" @doneAdd="doneAdd"
					@input="cInput">
				</keybored>
			</view>
		</u-popup>
	</view>
</template>

<script>
	import keybored from '@/components/liujto-keyboard/keybored.vue';
	export default {
		props: {
			tx: {
				type: String,
				default: '会员登录'
			},
		},
		components: {
			keybored,
		},
		data() {
			return {
				show: false,
				value: '',
				type: '',
				focus: false,
			}
		},
		methods: {
			open(m, t) {
				this.show = true
				// #ifdef APP-PLUS
				this.hide()
				// #endif
				this.focus = true
			},
			hide() {
				this.focus = true
				var interval = setInterval(function() {
					uni.hideKeyboard()
				}, 60);
				setTimeout(() => {
					clearInterval(interval)
				}, 2000)
			},
			close() {
				this.value = ''
				this.show = false
			},
			doneClear() {
				this.value = ''
			},
			cInput(e) {
				this.value = e
			},
			zc() {
				this.$emit('zc')
			},
			handScan() {
				var that = this
				uni.scanCode({
					onlyFromCamera: true,
					success: function(res) {
						uni.showLoading({
							title: 'loading...'
						})
						if (res.result) {
							that.value = res.result
							that.$emit('changeValue', res.result)
						} else {
							uni.hideLoading()
						}
					},
					complete: function(res) {
						uni.hideLoading()
					}
				})
			},
			scan(e) {
				if (e && e.length >= 18) {
					this.$emit('changeValue', e)
				}
			},
			doneAdd() {
				this.$emit('changeValue', this.value)
			}
		}
	}
</script>

<style lang="scss" scoped>
	.cash {
		width: 450px;
		// height: 416px;

		/deep/.u-input {
			padding: 9px !important;
		}

		/deep/.ljt-keyboard-body {
			border: 1px solid #e5e5e5;

			.ljt-keyboard-number-body {
				width: 450px !important;
				height: 275px !important;
			}

			.ljt-number-btn-ac {
				// width: 100px !important;
			}

			.ljt-number-btn-confirm-2 {
				// width: 100px !important;
				background: #4275F4 !important;
			}
		}

		/deep/.u-input__content__field-wrapper__field {
			font-size: 22px !important;
			font-weight: bold;
		}

		.zchy {
			background: #f0f0f0;
		}
	}
	@media (min-width: 500px) and (max-width: 900px) {
		.cash { 
			/deep/.ljt-keyboard-body {
				.ljt-keyboard-number-body {
					height: 150px !important;
				}
			}
		}
	}
</style>
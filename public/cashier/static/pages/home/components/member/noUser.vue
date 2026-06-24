<template>
	<view class="cash">
		<view class="f22 f-c f-y-c l-h1 mt20 mb40" style="color: #4275F4;">
			<view class="mr5 f24" @click="handScan">点击扫描会员码</view>
			<u-icon name="scan" color="#4275F4" size="24" @click="handScan"></u-icon>
		</view>
		<view class="mb20">
			<u--input class="f20" :focus="focus" v-model="value" placeholder="扫描会员码/手机号/手机号后四位" type="number"
				:style="{height:`${pc?'70px':'9.114vh'}`}" placeholderStyle="fontWeight:normal"  @change="scan"></u--input>
		</view>
		<view class="mb20">
			<u-button color="#4275F4" :customStyle="{height:`${pc?'48px':'6.25vh'}`}" @click="addMember"><text
					class="wei6 f20">注册会员</text></u-button>
		</view>
		<keybored type="number" v-model="value" confirmText="确认" @doneClear="doneClear" @doneAdd="doneAdd"
			@input="cInput">
		</keybored>
	</view>
</template>

<script>
	import keybored from '@/components/liujto-keyboard/keybored.vue';
	export default {
		props: {
			// form: {
			// 	type: Object,
			// 	default: {},
			// }
		},
		components: {
			keybored,
		},
		data() {
			return {
				value:'',
				focus: false,
			}
		},
		methods: {
			open(m, t) {
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
			addMember(){
				this.$emit('addMember')
			},
			close() {
				this.value = ''
			},
			doneClear() {
				this.value = ''
			},
			cInput(e) {
				this.value = e
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
		width: 32.9428vw;

		/deep/.u-input {
			padding: 9px !important;
		}

		/deep/.ljt-keyboard-body {
			border: 1px solid #e5e5e5;

			.ljt-keyboard-number-body {
				width: 32.9428vw !important;
				height: 35.8072vh !important;
			}

			.ljt-number-btn-confirm-2 {
				background: #4275F4 !important;
			}
		}

		/deep/.u-input__content__field-wrapper__field {
			font-size: 1.6105vw !important;
			font-weight: bold;
		}

		.zchy {
			background: #e5e5e5;
		}
	}

	@media (min-width: 1500px) and (max-width: 3280px) {
		.cash {
			width: 450px;

			/deep/.u-input {
				padding: 9px !important;
			}

			/deep/.ljt-keyboard-body {
				border: 1px solid #e5e5e5;

				.ljt-keyboard-number-body {
					width: 450px !important;
					height: 275px !important;
				}

			}

			/deep/.u-input__content__field-wrapper__field {
				font-size: 22px !important;
			}

		}
	}
</style>
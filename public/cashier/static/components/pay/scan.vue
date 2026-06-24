<template>
	<view class="">
		<u-popup :show="isCode" :round="10" :closeable="true" mode="center" :closeOnClickOverlay="false" @close="close">
			<view class="code p15">
				<view class="f24 wei6 mb20">请使用扫码枪/小白盒</view>
				<view class="f20 mb30 f-c f-y-c l-h1" style="color: #4275F4;" @click="handScan">
					<view class="mr5">点击使用摄像头扫码</view>
					<u-icon name="scan" color="#4275F4" size="22"></u-icon>
				</view>
				<view class="mb10">
					<text class="f45">￥{{moneyAll}}</text>
					<!-- <text class="cfd f16 pl10 wei6">修改金额</text> -->
				</view>
				<view class="f-c mb10 zfm">
					<u-image src="@/static/imgs/code.png" width="230px" height="230px"></u-image>
				</view>
				<view class="f-c mb30">
					<view class="dfa mr30">
						<u-image src="@/static/imgs/wx.png" width="26px" height="26px" :radius="5"></u-image>
						<text class="ml5 wei6 f15">微信扫码</text>
					</view>
					<view class="dfa">
						<u-image src="@/static/imgs/zfb.png" width="26px" height="26px" :radius="5"></u-image>
						<text class="ml5 wei6 f15">支付宝扫码</text>
					</view>
				</view>
				<view class="mb15"><u-button text="手动输入付款码" @click="manualInput"
						:customStyle="{width:'180px',border:'1px solid #ddd',borderRadius: '6px'}"
						color="linear-gradient(to bottom, #fdfdfd, #f2f2f2)"></u-button>
				</view>
				<!-- <view class="f-c">打印规则<text class="iconfont icon-wenhao wei5 c6 pl5" style="font-size: 16px;"></text>
				</view> -->
				<!-- <u--input placeholder="请输入付款码编号" v-model="number" @change="Pay" ref="barCodeInput"
					class="barCodeInput" id="barCodeInput"></u--input> -->
				<input class="uni-input" :focus="focus" placeholder="请输入付款码编号" v-model="number" @input="Pay"
					ref="barCodeInput" inputmode="none" style="width: 0;height: 0;border: none" />
			</view>
		</u-popup>
		<cash ref="codeRef" :t='2' tx="手动输入付款码" @changeMoney="changeMoney" />

	</view>
</template>

<script>
	import cash from '@/components/pay/cash.vue';
	export default {
		components: {
			cash,
		},
		props: {
			// moneyAll: {
			// 	type: String,
			// 	default: ''
			// }
		},
		data() {
			return {
				phone: '',
				isCode: false,
				number: '',
				timer: "",
				moneyAll: '',
				focus: false,
			}
		},
		methods: {
			open(m) {
				this.number = ''
				this.moneyAll = m
				this.isCode = true
				// #ifdef APP-PLUS
				this.hide()
				// #endif
				this.focus = true
				// this.$nextTick(()=> {
				// 	this.timer = setTimeout(() => {
				// 		// this.$refs['barCodeInput'].focus()
				// 		// this.$refs['barCodeInput'].focus();
				// 	}, 100);
				// });
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
							that.$emit('savePay', res.result)
						} else {
							uni.hideLoading()
						}
					},
					complete: function(res) {
						uni.hideLoading()
					}
				})
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
				this.isCode = false
			},
			manualInput() {
				this.close()
				this.$refs['codeRef'].open(0,'inputCode')
			},
			changeMoney(e) {
				if (e) this.$emit('savePay', e)
			},
			Pay(e) {
				console.log(e, e)
				this.timer = setTimeout(() => {
					if (e.detail.value && e.detail.value.length > 17) this.$emit('savePay', e.detail.value)
				}, 1000);

			},
		}
	}
</script>

<style lang="scss" scoped>
	.code {
		width: 400px;
		height: 550px;

		/deep/.u-button {
			span {
				font-size: 16px;
				color: #000;
			}
		}
	}

	.barCodeInput .input__inner {
		border: none !important;
		height: 0;
	}
	
	@media (min-width: 500px) and (max-width: 900px) {
		.code {
			height: 350px;
		}
		.zfm{
			/deep/.u-image {
			   height: 110px !important;
			}
		}
	}
</style>
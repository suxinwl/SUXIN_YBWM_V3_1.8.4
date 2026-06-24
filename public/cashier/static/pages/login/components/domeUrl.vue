<template>
	<u-overlay :show="getOrder" :opacity="0.2">
		<view class="reduce bf f18 f-y-bt" @tap.stop>
			<view class="dfbc p20">
				<view class="wei6 f20">校验码</view>
				<text class="iconfont icon-cuowu" @click="close"></text>
			</view>
			<u--form labelPosition="left" :model="form" :rules="rules" ref="uForm">
				<view class="acount">
					<view class="mb20">
						<u-form-item label=" " prop="username" ref="item1" labelWidth="0px">
							<u--input :clearable="true" placeholder="请输入您的8位数字校验码" type="number" v-model="form.url" prefixIcon="lock"
								prefixIconStyle="font-size: 24px;color: #909399" style="height:48px"></u--input>
						</u-form-item>
						<div class="c9">初次登录需要输入校验码验证,获取校验码请联系店铺管理员！</div>
					</view>
					<view class="mt50">
						<u-button color="linear-gradient(to bottom, #4275F4, #4275F4)" :disabled="!form.url"
							:customStyle="{height:'55px',borderRadius:'10px'}" @click="authCode">
							<text class="cf f20">确定</text></u-button>
					</view>
				</view>
			</u--form>
		</view>
	</u-overlay>
</template>

<script>
	import config from '@/custom/config.js';
	export default {
		props: {

		},
		data() {
			return {
				getOrder: false,
				form: {
					url: '',
				},
				rules: {
					url: [{
						required: true,
						message: '请输入8位数字校验码',
						trigger: ['change', 'blur'],
					}, ],
				}
			}
		},
		methods: {
			async open() {
				// await this.getUpOrder()
				this.getOrder = true
			},
			close() {
				this.getOrder = false
			},
			async authCode() {
				uni.request({
					url: 'https://up.y-bei.cn/cloud/code',
					data: {
						code: this.form.url
					},
					method: 'GET',
					header: {
						contentType: config.contentType,
						appType: 'cashier',
					},
					complete: (res) => {
						if (res?.data?.code == 200) {
							config.tokenErrorMessage(res.data.msg || res.msg)
							this.$emit('auth', res.data.data)
							this.close()
						} else {
							config.tokenErrorMessage(res.data.msg || res.msg)
						}
					}
				})
			},
		}
	}
</script>

<style lang="scss" scoped>
	.reduce {
		position: absolute;
		transform: translateX(-50%);
		top: 30vh;
		left: 53vw;
		width: 45.5651vw;
		// height: 78.125vh;
		border-radius: 10px;

	}

	.acount {
		padding: 4.1961vw;
	}
</style>
<template>
	<view class="f-y-bt h100">
		<view class="right f-1 p15 bf f20 f-y-bt">
			<view class="mb20 f20">收款设置</view>
			<u--form labelPosition="left" labelAlign="right" :model="form" ref="uForm" labelWidth="210px">
				<u-form-item label="优惠减现：" prop="reduce" ref="item1">
					<u-radio-group v-model="form.reduce" size="22" iconSize="18" iconColor="#000" activeColor="#4275F4">
						<u-radio :customStyle="{marginRight: '20px'}" label="启用" :name="1" />
						<u-radio label="关闭" :name="0" />
					</u-radio-group>
				</u-form-item>
				<u-form-item label="积分抵扣：" prop="integral" ref="item1">
					<u-radio-group v-model="form.integral" size="22" iconSize="18" iconColor="#000"
						activeColor="#4275F4">
						<u-radio :customStyle="{marginRight: '20px'}" label="启用" :name="1" />
						<u-radio label="关闭" :name="0" />
					</u-radio-group>
				</u-form-item>
				<view class="mb15 f16 c9" style="padding-left: 190px;">积分抵扣需要平台开启，同时配置积分抵扣金额比率</view>
				<u-form-item label="使用余额：" prop="balance" ref="item1">
					<u-radio-group v-model="form.balance" size="22" iconSize="18" iconColor="#000"
						activeColor="#4275F4">
						<u-radio :customStyle="{marginRight: '20px'}" label="启用" :name="1" />
						<u-radio label="关闭" :name="0" />
					</u-radio-group>
				</u-form-item>
				<u-form-item v-if="form.balance==1" label="余额使用安全验证：" prop="verify" ref="item1">
					<u-radio-group v-model="form.verify" size="22" iconSize="18" iconColor="#000" activeColor="#4275F4">
						<u-radio :customStyle="{marginRight: '20px'}" label="启用" :name="1" />
						<u-radio label="关闭" :name="0" />
					</u-radio-group>
				</u-form-item>
				<view v-if="form.balance==1" class="mb15 f16 c9" style="padding-left: 190px;">关闭之后直接使用余额进行抵扣，无需会员验证
				</view>
				<u-form-item v-if="form.balance==1&&form.verify==1" label="手机号验证：" prop="phone" ref="item1">
					<u-radio-group v-model="form.phone" size="22" iconSize="18" iconColor="#000" activeColor="#4275F4">
						<u-radio :customStyle="{marginRight: '20px'}" label="启用" :name="1" />
						<u-radio label="关闭" :name="0" />
					</u-radio-group>
				</u-form-item>
				<view v-if="form.balance==1&&form.verify==1" class="mb15 f16 c9" style="padding-left: 190px;">
					使用余额安全验证是否可以使用短信验证码验证
				</view>
				<u-form-item label="收款方式：" prop="way" ref="item1">
					<u-checkbox-group v-model="form.way" size="22" iconSize="18" iconColor="#000" activeColor="#4275F4">
						<u-checkbox :customStyle="{marginRight: '20px'}" label="付款码支付" labelSize="20" :name="0" />
						<u-checkbox :customStyle="{marginRight: '20px'}" label="现金支付" labelSize="20" :name="1" />
						<u-checkbox :customStyle="{marginRight: '20px'}" label="个人微信" labelSize="20" :name="2" />
						<u-checkbox :customStyle="{marginRight: '20px'}" label="个人支付宝" labelSize="20" :name="3" />
						<u-checkbox label="个人POS刷卡" labelSize="20" :name="4" />
					</u-checkbox-group>
				</u-form-item>
				<view class="mb15 f16 c9" style="padding-left: 190px;">付款码支付：扫描会员微信或支付宝付款进行收款</view>
			</u--form>
			<view class="f-1 f-y-e">
				<u-button color="#4275F4" text="保存" :customStyle="{width:'100px'}" @click="save"></u-button>
			</view>
		</view>
	</view>
</template>

<script>
	export default ({
		components: {},
		data() {
			return {
				form: {
					reduce: 0,
					integral: 0,
					balance: 1,
					verify: 1,
					phone: 1,
					way: [0, 1, 2, 3, 4]
				}
			}
		},
		methods: {
			//保存
			save() {}
		}
	})
</script>

<style lang="scss" scoped>
	.right {
		/deep/.u-form {
			.u-form-item {
				margin-bottom: 10px;
			}

			.u-form-item__body__left__content__label {
				padding-right: 15px;
				font-size: 20px;
			}

			.u-radio__text {
				font-size: 20px !important;
			}
		}

		/deep/.u-button {
			span {
				color: #000;
			}
		}
	}
</style>
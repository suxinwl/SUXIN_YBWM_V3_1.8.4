<template>
	<view class="right f-1 p15 bf f16 f-y-bt">
		<!-- <view class="mb20 f20">商品展示</view> -->
		<u--form labelPosition="left" labelAlign="right" :model="form" ref="uForm" labelWidth="120px">
			<u-form-item label="商品展示：" prop="reduce" ref="item1" class="spsz">
				<view class="">
					<view>
						<u-radio-group v-model="form.productShow" size="18" iconSize="18" iconColor="#fff"
							activeColor="#4275F4" @change="groupChange">
							<u-radio :customStyle="{marginRight: '30px'}" label="无图卡片" :name="1" />
							<u-radio :customStyle="{marginRight: '30px'}" label="大图卡片" :name="2" />
							<!-- <u-radio :customStyle="{marginRight: '30px'}" label="无图卡片+库存" :name="2" /> -->
							<!-- <u-radio label="大图卡片+库存" :name="3" /> -->
						</u-radio-group>
					</view>
					<view class="mt20">
						<image v-if="form.productShow==1" src="@/static/imgs/wutu.png" mode="widthFix" class="icon">
						</image>
						<image v-if="form.productShow==2" src="@/static/imgs/datu.png" mode="widthFix" class="icon">
						</image>
					</view>
				</view>
			</u-form-item>
			<!-- 	<u-form-item label="积分抵扣：" prop="integral" ref="item1">
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
			<view class="mb15 f16 c9" style="padding-left: 190px;">付款码支付：扫描会员微信或支付宝付款进行收款</view> -->
		</u--form>
		<view class="f-1 f-y-e">
			<u-button color="#4275F4" text="保存" :customStyle="{width:'100px'}" @click="save"></u-button>
		</view>
	</view>
</template>

<script>
	import {
		mapState,
		mapMutations,
	} from 'vuex'
	export default {
		components: {},
		data() {
			return {
				form: {
					productShow: 1,
					integral: 0,
					balance: 1,
					verify: 1,
					phone: 1,
					way: [0, 1, 2, 3, 4]
				}
			}
		},
		methods: {
			...mapMutations(["setConfig"]),
			init() {
				this.fetchData()
			},
			handTabs(e) {
				this.current = e.index
				if (e.index == 0) {
					this.$refs["basicRef"].fetchData();
				} else if (e.index == 1) {
					this.$refs["couponRef"].fetchData();
				} else if (e.index == 2) {
					this.$refs["storedRef"].fetchData();
				} else if (e.index == 3) {
					this.$refs["creditsRef"].fetchData();
				} else if (e.index == 4) {
					this.$refs["cardRef"].fetchData();
				}
			},
			async fetchData() {
				let {
					data
				} = await this.beg.request({
					url: this.api.config,
					data: {
						ident: 'cashieSetting'
					}
				})
				if (data && data.ident) {
					this.form = data
					this.setConfig({
						name: 'cashieSetting',
						data,
					})
				}
			},
			groupChange(n) {
				this.form.productShow = n
			},
			async save() {
				const {
					msg,
					code
				} = await this.beg.request({
					url: this.form.ident ? `${this.api.config}/${this.form.ident}` : this.api.config,
					method: this.form.ident ? 'PUT' : 'POST',
					data: this.form.ident ? this.form : {
						...this.form,
						ident: "cashieSetting",
						identName: "收银设置",
					}
				})
				uni.$u.toast(msg)
				this.fetchData()
			}
		}
	}
</script>

<style lang="scss" scoped>
	.right {
		.spsz {
			/deep/.u-form-item__body__left {
				align-items: flex-start;
			}
		}

		/deep/.u-form {
			.u-form-item {
				margin-bottom: 10px;
			}

			.u-form-item__body__left__content__label {
				padding-right: 15px;
				font-size: 16px;
			}

			.u-radio__text {
				font-size: 16px !important;
			}
		}

		.icon {
			width: 300px;
			height: 300px;
			margin-left: -20px;
		}
	}
</style>
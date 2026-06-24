<template>
	<view class="right f-1 f-y-bt">
		<view class="p10 bd1 tac">会员充值</view>
		<view class="p15 f-1">
			<view class="user p15">
				<vipUser @rfuser="init"></vipUser>
			</view>
			<u--form labelPosition="left" :model="form" ref="uForm">
				<u-form-item class="mb10" prop="recharge" ref="item1">
					<view class="tar f18" style="width:100px">充值方式：</view>
					<u-radio-group v-model="form.recharge" size="22" iconSize="18" iconColor="#000"
						activeColor="#4275F4">
						<u-radio :customStyle="{marginRight: '20px'}" label="充值套餐" :name="0" />
						<u-radio label="自定义金额" :name="1" v-if="valSet.topUpPrice==1" />
					</u-radio-group>
				</u-form-item>
				<u-form-item class="mb10" v-if="form.recharge==0" prop="money" ref="item1">
					<view class="f-s">
						<view class="tar f18" style="width:100px">充值金额：</view>
						<view class="dfa">
							<view :class="form.checkm==index?'ismoney':''" class="m_item f-c-c mr15"
								v-for="(item,index) in list" :key="index" @click="change(index)">
								<view class="wei6 f20 mb10">{{item.amount}}元</view>
								<view class="f18">
									到账{{ Number(item.rule && item.rule.balanceGive) + Number(item.amount) }}元</view>
								<view class="posi-a scjl" v-if="aIdx==index && item.first==0">首充奖励</view>
							</view>
						</view>
					</view>
				</u-form-item>
				<u-form-item class="mb10 mt10" v-if="form.recharge==0 && aIdx>=0" prop="discount" ref="item1">
					<view class="f-s" style="width: 100%;">
						<view class="tar f18" style="width:100px">充值优惠：</view>
						<view class="f-1" v-if="xzrule.rule">
							<view class="d_item dfa mb15 bf8" v-if="xzrule.rule.balanceSwitch==1">
								<text class="iconfont icon-Leaf" style="font-size: 22px;color:#FD8906"></text>
								<view class="f18 pl5">赠送：{{xzrule.rule.balanceGive}}元余额</view>
							</view>
							<view class="d_item dfa mb15 bf8" v-if="xzrule.rule.integralSwitch==1">
								<text class="iconfont icon-qianbi" style="font-size: 22px;color:#FD8906"></text>
								<view class="f18 pl5">赠送：{{xzrule.rule.integralGive}}积分</view>
							</view>
							<view class="d_item dfa mb15 bf8" v-if="xzrule.rule.couponSwitch==1">
								<text class="iconfont icon-quan" style="font-size: 22px;color:#FD8906"></text>
								<view class="f18 pl5">赠送：
									<text v-if="xzrule.rule.couponGive">
										<block v-for="(v,i) in xzrule.rule.couponGive" :key='i'>
											{{v.name}} <text class="ml10 mr20"
												:style="{color:'#FD8906'}">x{{v.num}}</text>
										</block>
									</text>
								</view>
							</view>
							<view class="d_item dfa mb15 bf8" v-if="xzrule.rule.levelSwitch==1">
								<text class="iconfont icon-Leaf" style="font-size: 22px;color:#FD8906"></text>
								<view class="f18 pl5">提升至会员等级：{{xzrule.rule.levelGive}}</view>
							</view>
						</view>
					</view>
				</u-form-item>
				<u-form-item class="mb10" v-if="form.recharge==1" prop="money" ref="item1">
					<view class="dfa">
						<view class="tar f20" style="width:100px">充值金额：</view>
						<view class="dfa">
							<u--input placeholder="请输入充值金额" border="surround" v-model="form.money"
								style="width:300px" type="number"></u--input>
						</view>
					</view>
				</u-form-item>
			</u--form>
			<view class="bom">
				<u-button color="#4275F4" text="确认充值" :disabled="!vipInfo || !vipInfo.id"
					:customStyle="{width:'120px',color:'#000'}" @click="saveRecharge"></u-button>
			</view>
		</view>
		<scan ref="scanRef" @savePay="savePay" />
		<u-loading-page :loading="loading"></u-loading-page>
	</view>
</template>

<script>
	import vipUser from '@/components/user/vipUser.vue';
	import scan from '@/components/pay/scan.vue';
	import {
		mapState,
		mapMutations,
	} from 'vuex'
	export default ({
		components: {
			vipUser,
			scan,
		},
		props: {
			list: {
				type: Array,
				default: [],
			},
			aIdx: {
				type: Number,
				default: 0
			},
			xzrule: {
				type: Object,
				default: {},
			},
			valSet: {
				type: Object,
				default: {},
			}
		},
		computed: {
			...mapState({
				vipInfo: state => state.vipInfo,
			}),
		},
		data(props) {
			return {
				setTime: null,
				timer: null,
				isVip: false,
				showVip: false,
				phone: '',
				date: '',
				vipForm: {},
				form: {
					recharge: 0,
					checkm: 0,
					money: '',
				},
				loading: false,
				pay: {
					id: 0,
					money: 0,
					userId: 0,
					payType: 'authCode',
					authCode: 0,
				},
			}
		},
		methods: {
			change(v) {
				this.form.checkm = v
				this.$emit('change', v)
			},
			init(){
				this.$emit('init')
			},
			saveRecharge() {
				if (this.form.recharge == 1 && !this.form.money) {
					return uni.showToast({
						title: '请输入充值金额！',
						icon: 'none',
						duration: 800
					});
				}
				if (this.form.money && this.form.money < this.valSet.minPrice) {
					return uni.showToast({
						title: `最低充值${this.valSet.minPrice}元！`,
						icon: 'none',
						duration: 800
					});
				}
				let money = this.form.recharge == 1 ? +this.form.money : this.xzrule.amount
				this.$refs['scanRef'].open(money)
			},
			async savePay(e) {
				if (e) {
					this.pay.authCode = e
					this.loading = true
				}
				this.pay.money = this.form.recharge == 1 ? +this.form.money : this.xzrule.amount
				this.pay.id = this.form.recharge == 1 ? 0 : this.list[this.aIdx].id
				this.pay.userId = this.vipInfo.id
				let {
					msg,
					data
				} = await this.beg.request({
					url: this.api.valueRecharge,
					method: 'POST',
					data: this.pay
				})
				this.$refs['scanRef'].close()
				this.loading = false
				uni.$u.toast(msg)
				// this.setVip({})
				if (data) {
					this.$emit('init')
				}
			},
		}
	})
</script>

<style lang="scss" scoped>
	.right {
		position: relative;

		/deep/.u-radio__text {
			font-size: 18px !important;
		}

		.m_item {
			position: relative;
			min-width: 130px;
			padding: 0 20px;
			height: 100px;
			border: 2px solid #ddd;
			border-radius: 3px;
		}

		.ismoney {
			color: #FD8906;
			background: rgba(#fff9eb, .4);
			border: 2px solid #FD8906;
		}

		.d_item {
			padding: 8px;
			border-radius: 3px;
			background: #eff0f4;
		}

		.bom {
			position: absolute;
			bottom: 0;
			left: 50%;
			transform: translateX(-50%);
			padding: 20px;
			width: 50%;
		}
	}

	.scjl {
		background: #FD8906;
		position: absolute;
		top: 0;
		right: 0;
		font-size: 20rpx;
		color: #fff;
		padding: 4rpx 6rpx;
	}
</style>
<template>
	<view class="rightOrder f-1 f-bt f20">
		<view class="w55 br1 p15">
			<view class="f-x-bt mb30">
				<text class="f20 wei6">选择优惠</text>
				<view><u-button text="会员登录" @click="isVip=true"></u-button></view>
			</view>
			<view class="f-x-bt mb30 w100">
				<view :class="item.isCheck?'dis_check':''" class="dis_item p-10-0 f20 bs6 w30 tac"
					v-for="(item,index) in discounts" :key="index" style="" @click="clickCheck(item,index)">
					{{item.title}}
					<view class="r_gou"></view>
					<text class="iconfont icon-duigou f12"></text>
				</view>
			</view>
			<view class="mb30 f20 wei6">选择支付方式 <text class="iconfont icon-wenhao wei5 c6 pl10"
					style="font-size: 19px;"></text></view>
			<view class="ways">
				<view class="way f-c-c mb50" v-for="(item,index) in ways" :key="index" @click="showItem(item)">
					<image :src="item.img" mode="" style="width:70px;height:70px"></image>
					<view class="f16 mt5">{{item.title}}</view>
				</view>
			</view>
		</view>
		<view class="f-1 p15 f-y-bt">
			<view class="mb20 f20 wei6">账单明细</view>
			<view class="f-x-bt mb15 pr60 c6">
				<view>菜单价格合计</view>
				<view class="f24">￥{{checkInfo.totalMoney}}</view>
			</view>
			<u-collapse class="c6" :border="false" accordion @change="isLink=!isLink" v-if="checkInfo.discountMoney">
				<u-collapse-item title="优惠合计" :border="false" :isLink="false">
					<view slot="value" class="dfa f24 c6">
						<view class="f24">-￥{{checkInfo.discountMoney}}</view>
						<view style="width: 60px">
							<u--text type="warning" :text="!isLink?'展开':'收起'" align="right"></u--text>
						</view>
					</view>
					<!-- <view class="c9 pb10 f20">
						<view class="f-x-bt mb20">
							<view>菜品手动减免</view>
							<view class="f22">-￥{{dis_money}}</view>
						</view>
						<view class="f-x-bt">
							<view>订单手动7折</view>
							<view class="f22">-￥{{reduce_count}}</view>
						</view>
					</view> -->
				</u-collapse-item>
			</u-collapse>
			<view v-if="showCoin" class="f-x-bt pb15 c6">
				<view>手动抹零</view>
				<view class="dfa f24">
					<view>￥{{handleCoin}}</view>
					<view style="width: 60px">
						<u--text type="warning" text="撤销" align="right" size="20" @click="cancleWipe"></u--text>
					</view>
				</view>
			</view>
			<view class="f-x-bt pb15 bd1 c6">
				<view>应收</view>
				<view class="dfa f24">
					<view>￥{{checkInfo.money}}</view>
					<view style="width: 60px">
						<u--text v-if="!showCoin" type="warning" text="抹零" align="right" size="20"
							@click="isCoin=!isCoin"></u--text>
					</view>
				</view>
			</view>
			<!-- <view v-if="isCoin" class="f-x-e">
				<view class="erase">
					<view v-if="isCorner" class="bd1 p-15-0" @click="wipeCorner">抹角<text
							class="pl10">(￥{{decimals}})</text>
					</view>
					<view v-if="isUnit" class="bd1 p-15-0" @click="wipeUnit">抹元<text class="pl10">(￥{{integer}})</text>
					</view>
					<view class="p-15-0">任意金额</view>
				</view>
			</view> -->
			<view v-if="pay && pay.money" class="f-x-bt pb15 c6">
				<view>{{pay.name}}</view>
				<view class="dfa f24">
					<view>￥{{pay.money}}</view>
					<view style="width: 60px">
						<u--text type="warning" text="撤销" align="right" size="20" @click="cPayType"></u--text>
					</view>
				</view>
			</view>
			<view class="f-1 r_b f-y-e">
				<u-button color="#4275F4" :customStyle="{color:'#000',height:'60px'}" @click="savePay"
					:disabled="!pay.money">
					<text class="f20 wei6">付款完成,确认结账</text></u-button>
			</view>
		</view>
		<cash ref="cashRef" :cash_money="checkInfo.totalMoney" @changeMoney="changeMoney" />
		<scan ref="scanRef" :moneyAll="checkInfo.totalMoney" @savePay="savePay" />
		<member ref="memberRef" @chooseMember="chooseMember" />
		<u-loading-page :loading="loading"></u-loading-page>
	</view>
</template>

<script>
	import member from '@/components/user/member.vue';
	import cash from '@/components/pay/cash.vue';
	import scan from '@/components/pay/scan.vue';
	import {
		mapState,
		mapMutations,
	} from 'vuex'
	export default ({
		components: {
			member,
			cash,
			scan,
		},
		props: {
			checkInfo: {
				type: Object,
				default: {}
			},
			form: {
				type: Object,
				default: {}
			}
		},
		data() {
			return {
				discounts: [{
						isCheck: false,
						title: '整单手动打折/减免'
					},
					{
						isCheck: false,
						title: '免单'
					},
					{
						isCheck: false,
						title: '不开票9.5折'
					}
				],
				ways: [{
						img: require('@/static/imgs/way4.png'),
						title: '现金-人民币',
						value: 'cash',
					},
					{
						img: require('@/static/imgs/way5.png'),
						title: '扫码支付',
						value: 'authCode',
					},
					{
						img: require('@/static/imgs/way6.png'),
						title: '会员卡',
						value: 'balance',
					},
					// {
					// 	img: require('@/static/imgs/way1.png'),
					// 	title: '挂账消费'
					// },
					// {
					// 	img: require('@/static/imgs/way3.png'),
					// 	title: '前台码',
					// 	value: 'code',
					// },
					// {
					// 	img: require('@/static/imgs/way2.png'),
					// 	title: '代金券'
					// }
				],
				pay: {
					name: '',
					money: '',
					payType: 0,
					authCode: 0,
					payUserId: 0,
					// printSwitch:2,
				},
				loading:false,
				showCoin: false, //显示抹零
			}
		},
		computed: {
			...mapState({
				vipInfo: state => state.vipInfo,
			}),
		},
		methods: {
			...mapMutations(["setVip"]),
			showItem(v) {
				this.pay.payType = v.value
				this.pay.name = v.title
				if (v.value == 'cash') {
					this.$refs['cashRef'].open()
				} else if (v.value == 'authCode') {
					this.pay.money = this.checkInfo.totalMoney
					this.$refs['scanRef'].open()
				} else if (v.value == 'balance') {
					if (this.vipInfo && this.vipInfo.id) {
						this.pay.money = this.checkInfo.totalMoney
						this.pay.payUserId = this.vipInfo.id
					} else {
						this.$refs['memberRef'].open()
					}
				}
			},
			changeMoney(e) {
				this.pay.money = e
			},
			cPayType() {
				this.pay = {
					name: '',
					money: '',
					payType: 0,
					authCode: 0,
					payUserId: 0,
				}
			},
			chooseMember(v) {
				this.setVip(v)
				this.pay.money = this.checkInfo.totalMoney
				this.pay.payUserId = v.id
			},
			async savePay(e) {
				if (e) {
					this.pay.authCode = e
					this.loading = true
				}
				this.pay.diningType = this.form.diningType
				this.pay.tableId = this.form.id
				let {
					msg
				} = await this.beg.request({
					url: this.api.inOrder,
					method: 'POST',
					data: this.pay
				})
				this.loading = false
				uni.$u.toast(msg)
				this.$emit('init')
			},
		}
	})
</script>

<style lang="scss" scoped>
	.rightOrder {
		/deep/.u-subsection--subsection {
			height: 40px !important;
			border-radius: 6px;

			.u-subsection__item__text {
				span {
					color: #000;
					font-size: 18px !important;
				}
			}
		}

		.r_cont {
			max-height: calc(100vh - 215px);
			overflow: auto;

			.r_item {
				position: relative;
				display: inline-flex;
				flex-direction: column;
				justify-content: space-between;
				margin-right: 20rpx;
				margin-bottom: 20rpx;
				width: 450rpx;
				height: 280rpx;
				border: 2rpx solid #e6e6e6;
				border-radius: 10px;

				.badge {
					position: absolute;
					top: 0px;
					right: 0px;

					/deep/.u-badge {
						line-height: 16px;
						font-size: 16px;
					}
				}
			}

			.check {
				border: 2rpx solid #FD8906;
			}
		}

		.pagona {
			height: 50px;

			/deep/.uni-pagination {
				.page--active {
					display: inline-block;
					width: 30px;
					height: 30px;
					background: #4275F4 !important;
					color: #000 !important;
				}

				.uni-pagination__total {
					font-size: 20px;
				}

				span {
					font-size: 20px;
				}
			}
		}

		/deep/.ljt-keyboard-body {
			border-radius: 6px;
			border: 1px solid #e5e5e5;

			.ljt-keyboard-number-body {
				// width: 500px !important;
				// height: 260px !important;
			}

			.ljt-number-btn-confirm-2 {
				background: #4275F4 !important;

				span {
					color: #000;
					font-size: 20px;
				}
			}
		}

		.kind {
			width: 210rpx;
			height: 90rpx;
			line-height: 80rpx;
		}

		.acKind {
			color: #000;
			background: #4275F4;
		}

		.ways {
			display: flex;
			flex-wrap: wrap;

			.way {
				width: 33.3%;
			}
		}

		.r_b {
			/deep/.u-button {
				span {
					color: #000;
				}
			}
		}

		/deep/.u-cell__body {
			padding: 0 0 15px;

			span {
				font-size: 20px;
			}
		}

		.dis_item {
			position: relative;
			height: 100rpx;
			border: 1px solid #ddd;

			.r_gou {
				display: none;
				position: absolute;
				top: 0px;
				right: 0px;
				width: 0;
				height: 0;
				border-top: 10px solid #4275F4;
				border-right: 10px solid #4275F4;
				border-left: 10px solid transparent;
				border-bottom: 10px solid transparent;
			}

			.icon-duigou {
				display: none;
				position: absolute;
				top: -2px;
				right: -2px;
				transform: scale(0.6);
			}
		}

		.dis_check {
			border: 1px solid #FD8906;
			background: #fff9dd;

			.r_gou,
			.icon-duigou {
				display: block;
			}
		}

		.erase {
			padding: 5px 30px;
			width: 190px;
			box-shadow: 0px 0px 10px 0px #e6e6e6;
		}

		/deep/.u-cell__title-text {
			span {
				color: #666;
			}
		}
	}
</style>
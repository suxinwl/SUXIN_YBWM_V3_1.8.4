<template>
	<view class="rightOrder f-1 f-bt f20 bf" :class="{'isPayType':mode!= 'fastOrder' && form.payType==1}">
		<view class="w55 br1 p-10-15">
			<view class="f-x-bt mb10">
				<text class="f20 wei6">选择优惠</text>
				<view class="flex">
					<u-button v-if="vipInfo && vipInfo.id" text="更换会员" type="primary" plain color="#4275F4"
						@click="changeVip" class="mr10"></u-button>
					<u-button v-if="vipInfo && vipInfo.id" text="退出会员" @click="outVip"></u-button>
					<u-button v-else text="会员登录" @click="vipLogin" type="primary" plain color="#4275F4"></u-button>
				</view>
			</view>
			<view class="vipInfos mb20" v-if="vipInfo && vipInfo.id">
				<view class="flex c0 f14">
					<view>{{vipInfo.nickname}}</view>
					<view class="ml10">{{vipInfo.vip && vipInfo.vip.name}}</view>
					<view class="ml10">{{vipInfo.mobile}}</view>
				</view>
				<view class="c6 f12 mt10">卡号：{{vipInfo.vipCard}}</view>
				<view class="c6 f12 mt10">
					<text class="pr10">优惠券：{{vipInfo.coupons || form.couponCount>0 && form.couponList && form.couponCount ||0}}</text>
					<text class="pr10">余额：{{vipInfo.account && vipInfo.account.balance}}</text>
					<text class="pr10">积分：{{vipInfo.account && vipInfo.account.integral}}</text>
				</view>
			</view>
			<view class="flex mb30 w100">
				<!-- <view :class="item.isCheck?'dis_check':''" class="dis_item p-10-0 f20 bs6 w30 tac mr10"
					v-for="(item,index) in discounts" :key="index" style="" @click="clickCheck(item,index)">
					{{item.title}}
					<view class="r_gou"></view>
					<text class="iconfont icon-duigou f12"></text>
				</view> -->
				<view :class="form.discounts && form.discounts.manualDiscount?'dis_check':''"
					class="dis_item p-10-0 f18 bs6 w30 tac mr10 f-c" @click="clickCheck('allDiscount')">
					整单手动打折/减免
					<!-- <view class="r_gou"></view> -->
					<!-- <text class="iconfont icon-duigou f12 cf"></text> -->
				</view>
				<view class="dis_item p-10-0 f18 bs6 w30 tac mr10 f-c" @click="clickCheck('freeOf')">免单
					<!-- <view class="r_gou"></view> -->
					<!-- <text class="iconfont icon-duigou f12 cf"></text> -->
				</view>
			</view>
			<view class="mb20 f20 wei6">选择支付</view>
			<view class="ways">
				<view class="way f-c-c mb20" v-for="(item,index) in ways" :key="index" @click="showItem(item)">
					<image :src="item.img" mode="aspectFit" class="waywh"></image>
					<view class="f16 mt5 t-o-e">{{item.title}}</view>
				</view>
			</view>
		</view>
		<view class="f-1 p15 f-y-bt">
			<view class="mb20 f20 wei6">账单明细</view>
			<view class="f-bt pb15 c6 f18">
				<view class="f-g-1 f-bt">
					<view>商品价格合计</view>
					<view class='f-y-e'>
						<view>￥{{form.goodsSellMoney}}</view>
						<!-- <view class="t-d-l c9 f14" v-if="form.goodsSellMoney>form.goodsMoney">￥{{form.goodsSellMoney}}</view> -->
					</view>
				</view>
				<view class="f-g-0 cz"></view>
			</view>
			<view class="f-bt pb15 c6 f18" v-if="form.boxMoney>0">
				<view class="f-g-1 f-bt">
					<view>包装费</view>
					<view>￥{{form.boxMoney}}</view>
				</view>
				<view class="f-g-0 cz"></view>
			</view>
			<view class="f-bt pb15 c6 f18" v-if="form.tableMoney>0">
				<view class="f-g-1 f-bt">
					<view>{{form.tableFormat || '服务费'}}</view>
					<view>￥{{form.tableMoney}}</view>
				</view>
				<view class="f-g-0 cz"></view>
			</view>
			<view class="f-bt pb15 c6 f18" v-if="form.service_money>0">
				<view class="f-g-1 f-bt">
					<view>订单服务费</view>
					<view>￥{{form.service_money}}</view>
				</view>
				<view class="f-g-0 cz"></view>
			</view>
			<!-- <u-collapse class="c6" :border="false" accordion @change="isLink=!isLink" v-if="form.discountMoney">
				<u-collapse-item title="优惠合计" :border="false" :isLink="false">
					<view slot="value" class="dfa f24 c6">
						<view class="f24">-￥{{form.discountMoney}}</view>
						<view style="width: 60px">
							<u--text type="warning" :text="!isLink?'展开':'收起'" align="right"></u--text>
						</view>
					</view>
					<view class="c9 pb10 f20">
						<view class="f-x-bt mb20">
							<view>菜品手动减免</view>
							<view class="f22">-￥{{dis_money}}</view>
						</view>
						<view class="f-x-bt">
							<view>订单手动7折</view>
							<view class="f22">-￥{{reduce_count}}</view>
						</view>
					</view>
				</u-collapse-item>
			</u-collapse> -->
			
			<!-- <view v-if="form.discounts && form.discounts.manualDiscount" class="f-bt pb15 c6 f18">
				<view class="f-g-1 f-bt">
					<view>{{form.discounts.manualDiscount.activityName}}</view>
					<view>-￥{{form.discounts.manualDiscount.money}}</view>
				</view>
				<view class="f-g-0 cz"></view>
			</view>
			<view v-if="form.discounts && form.discounts.wipeZero" class="f-bt pb15 c6 f18">
				<view class="f-g-1 f-bt">
					<view>{{form.discounts.wipeZero.activityName}}</view>
					<view>-￥{{form.discounts.wipeZero.money}}</view>
				</view>
				<view class="f-g-0 cz f-y-c">
					<u--text text="撤销" align="right" size="16" color="#4275F4"
						@click="cancleWipe('wipeZero')"></u--text>
				</view>
			</view> -->
			<block v-if="form.discountsPlus && form.discountsPlus.length">
				<view v-for="(v,i) in form.discountsPlus" :key="i" class="f-bt pb15 c6 f18">
					<view class="f-g-1 f-bt">
						<view>{{v.activityName}}</view>
						<view>-￥{{v.money}}</view>
					</view>
					<view class="f-g-0 cz">
						<u--text v-if="v.type=='wipeZero'" text="撤销" align="right" size="16" color="#4275F4"
							@click="cancleWipe('wipeZero')"></u--text>
					</view>
				</view>
			</block>
			<view class="f-bt pb15 c6 f18" v-if="vipInfo && vipInfo.id">
				<view class="f-g-1 f-bt">
					<view>可用优惠券</view>
					<view>
						<text v-if="form.couponCount>0 && form.couponList">
							<text class='' @click="sCoupon"
								v-if="form.couponId && form.discounts && form.discounts.coupon">
								{{form.couponCount}}个可用
								<!-- -￥{{form.discounts.coupon.money}} -->
							</text>
							<text class='cnum' @click="sCoupon"
								v-else>{{form.couponCount}}个可用</text>
						</text>
						<text v-else>暂无可用</text>
					</view>
				</view>
				<view class="f-g-0 cz f-y-c">
					<u--text text="选择" align="right" size="16" color="#4275F4" @click="sCoupon"></u--text>
				</view>
			</view>
			<view class="f-bt pb15 bd1 c6 f18">
				<view class="f-g-1 f-bt">
					<view>应收</view>
					<view>￥{{form.money}}</view>
				</view>
				<view class="f-g-0 cz f-y-c">
					<block v-if="!form.discounts || form.discounts && !form.discounts.wipeZero">
						<u--text v-if="!showCoin" text="抹零" align="right" size="16" color="#4275F4"
							@click="isCoin=!isCoin"></u--text>
					</block>
				</view>
			</view>
			<view v-if="isCoin" class="f-x-e">
				<view class="erase">
					<view class="bd1 p-15-0" @click="disconMoney('F')">抹分<text class="pl10"></text></view>
					<view class="bd1 p-15-0" @click="disconMoney('J')">抹角<text class="pl10"></text></view>
					<view class="bd1 p-15-0" @click="disconMoney('Y')">抹元<text class="pl10"></text></view>
					<view class="p-15-0" @click="inputMoney">任意金额</view>
				</view>
			</view>
			<view v-if="pay && pay.money" class="f-x-bt pb15 c6 pt10 f18">
				<view class="f-g-1 f-bt">
					<view>{{pay.name}}</view>
					<view>￥{{pay.money}}</view>
				</view>
				<view class="f-g-0 cz f-y-c">
					<u--text text="撤销" align="right" size="16" color="#4275F4" @click="cPayType"></u--text>
				</view>
			</view>
			<view v-if="pay && pay.amount" class="f-x-bt pb15 c6 pt10 f18">
				<view class="f-g-1 f-bt">
					<view>找零</view>
					<view>-￥{{pay.amount.toFixed(2)}}</view>
				</view>
			</view>
			<!-- <view class="f-1 r_b" style="display: flex;flex-direction: column;justify-content: flex-end;">
				<u-checkbox-group v-model="invoice" placement="row" activeColor="#4275F4" iconSize="18"
					iconColor="#000" size="22" :labelSize="18">
					<u-checkbox label="打印结账单" :labelSize="18" :name="0"></u-checkbox>
				</u-checkbox-group>
				<u-button class="mt20 mb15" text="" color="#4275F4" :customStyle="{height:'55px'}"><text
						class="wei6 f18">付款完成,确认结账</text></u-button>
			</view> -->
			<view class="f-1 r_b f-y-e">
				<u-button color="#4275F4" :customStyle="{color:'#fff',height:'60px'}" @click="savePay"
					:disabled="!pay.money">
					<text class="f20 wei6">付款完成，确认结账</text></u-button>
			</view>
		</view>
		<cash ref="cashRef" @changeMoney="changeMoney" />
		<scan ref="scanRef" @savePay="savePay" />
		<member ref="memberRef" @chooseMember="chooseMember" />
		<userNum ref="userNumRef" @changeValue="changeValue" @zc="zc"></userNum>
		<cash ref="cashMoney" tx="手动抹零金额" @cashMoney="cashMoney" />
		<goodsReduce ref="reduceRef" :v="form" :selectItem="selectItem" :t="2" @cMonry="changeMonry" />
		<goodsFreeOf ref="freeOfRef" :v="form" @save="saveFreeOf"></goodsFreeOf>
		<u-loading-page :loading="loading"></u-loading-page>
		<addUser ref="addUserRef" @fetchData="fetchData"></addUser>
		<sCoupon ref="sCouponRef" @payorder="payorder"></sCoupon>
	</view>
</template>

<script>
	import member from '@/components/user/member.vue';
	import userNum from '@/components/user/userNum.vue';
	import cash from '@/components/pay/cash.vue';
	import scan from '@/components/pay/scan.vue';
	import goodsReduce from '@/components/goods/goodsReduce.vue';
	import goodsFreeOf from './components/freeOf.vue'
	import addUser from '@/components/user/addUser.vue';
	import sCoupon from '@/components/user/sCoupon.vue';
	import {
		playAudo,
	} from "@/common/handutil.js"
	import {
		mapState,
		mapMutations,
	} from 'vuex'
	export default ({
		components: {
			member,
			userNum,
			cash,
			scan,
			goodsReduce,
			goodsFreeOf,
			addUser,
			sCoupon,
		},
		props: {
			mode: {
				type: String,
				default: 'fastOrder'
			},
			form: {
				type: Object,
				default: {}
			},
			test: Object,
			pl: {
				type: Object,
				default: {}
			}
		},
		data() {
			return {
				discounts: [{
						isCheck: false,
						title: '整单手动打折/减免',
						value: 'allDiscount',
					},
					{
						isCheck: false,
						title: '免单',
						value: 'freeOf',
					},
					// {
					// 	isCheck: false,
					// 	title: '不开票9.5折',
					// 	value:'notInvoiced',
					// }
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
					userId: 0,
					costomPayId: 0,
				},
				loading: false,
				showCoin: false, //显示抹零
				isCoin: false,
				isCorner: true,
				isUnit: true,
				selectItem: {},
			}
		},
		watch: {

		},
		computed: {
			...mapState({
				vipInfo: state => state.vipInfo,
			}),
		},
		methods: {
			...mapMutations(["setVip"]),
			showItem(v) {
				this.pay.userId = this.vipInfo && this.vipInfo.id || 0
				this.pay.payType = v.value
				this.pay.name = v.title
				this.pay.amount = 0
				this.pay.costomPayId = v.costomPayId || 0
				if (v.value == 'cash') {
					this.$refs['cashRef'].open(this.form.money)
				} else if (v.value == 'authCode') {
					this.pay.money = this.form.money
					this.$refs['scanRef'].open(this.form.money)
					playAudo('../../static/auto/fukuanma.mp3')
				} else if (v.value == 'balance') {
					if (this.vipInfo && this.vipInfo.id) {
						this.pay.money = this.form.money
						this.pay.payUserId = this.vipInfo.id
					} else {
						// this.$refs['memberRef'].open()
						this.$refs['userNumRef'].open()
					}
				}else{
					this.pay.money = this.form.money
				}
			},
			changeMoney(e) {
				this.pay.money = e.money
				this.pay.amount = e.amount
			},
			resetPay(data){
				this.pay.money = data.money;
				console.log("修改支付金额",this.pay.money)
			},
			cPayType() {
				this.pay = {
					name: '',
					money: '',
					payType: 0,
					authCode: 0,
					payUserId: 0,
					costomPayId: 0,
				}
			},
			vipLogin() {
				// this.$refs['memberRef'].open()
				this.$refs['userNumRef'].open()
			},
			changeVip() {
				this.$refs['userNumRef'].open()
				// this.$refs['memberRef'].open()
			},
			async changeValue(e) {
				if (e) {
					uni.showLoading({
						title: 'loading...'
					})
					let {
						data
					} = await this.beg.request({
						url: this.api.cMember,
						data: {
							keyword: e
						},
					})
					if (data && data.list.length) {
						if (data.list.length > 1) {
							this.$refs['memberRef'].open(data.list)
						} else {
							this.setVip(data.list[0])
							this.$emit('ck')
						}
						this.$refs['userNumRef'].close()
					} else {
						uni.$u.toast('暂未查到用户信息');
					}
					uni.hideLoading()
				} else {
					uni.$u.toast('请输入正确的手机号');
				}
			},
			outVip() {
				this.setVip(null)
				this.$emit('ck')
			},
			chooseMember(v) {
				console.log("选中的会员",v)
				this.setVip(v)
				this.pay.money = this.form.money
				this.pay.payUserId = v.id
				this.$emit('ck')
			},
			async disconMoney(type, discount) {
				let {
					data
				} = await this.beg.request({
					url: this.form.orderSn ? `${this.api.orderWipeZero}/${this.form.orderSn}` : this.api
						.inStoreWipeZero,
					method: 'POST',
					data: {
						diningType: this.form.diningType,
						storeId: this.form.storeId,
						tableId: this.form.id,
						type,
						discount,
					}
				})
				this.isCoin = false
				this.$emit('checkOut', data)
			},
			inputMoney() {
				this.isCoin = false
				this.$refs['cashMoney'].open(0, 'cashMoney')
			},
			cashMoney(e) {
				this.disconMoney('custom', e)
			},
			async cancleWipe(type) {
				let {
					data
				} = await this.beg.request({
					url: this.form.orderSn ? `${this.api.orderCancelDiscount}/${this.form.orderSn}` : this
						.api.cancelDiscount,
					method: 'POST',
					data: {
						diningType: this.form.diningType,
						storeId: this.form.storeId,
						tableId: this.form.id,
						type,
					}
				})
				this.$emit('checkOut', data)
			},
			clickCheck(v) {
				if (v == 'allDiscount') {
					if (this.form.discounts && this.form.discounts.manualDiscount) {
						uni.showModal({
							title: '提示',
							content: '请确认取消手动减免/打折吗?',
							success: async (res) => {
								this.cancleWipe('manualDiscountd')
								this.cPayType()
							}
						});
					} else {
						this.selectItem.money = this.form.money
						this.$refs['reduceRef'].open()
						this.cPayType()
					}
				} else if (v == 'freeOf') {
					this.$refs['freeOfRef'].open()
				}
			},
			async changeMonry(e) {
				let {
					data
				} = await this.beg.request({
					url: this.form.orderSn ? `${this.api.orderDiscount}/${this.form.orderSn}` : this.api
						.inStoreDiscount,
					method: 'POST',
					data: e
				})
				// uni.showToast({
				// 	title: msg,
				// 	icon: 'none'
				// })
				this.$refs['reduceRef'].close()
				this.$emit('checkOut', data)
			},
			async saveFreeOf(e) {
				let {
					data,
					msg,
					code,
				} = await this.beg.request({
					url: this.form.orderSn ? `${this.api.orderFree}/${this.form.orderSn}` : this.api
						.checkoutFree,
					method: 'POST',
					data: {
						diningType: this.form.diningType,
						storeId: this.form.storeId,
						tableId: this.form.id,
						reason: e && e.join('，'),
					}
				})
				uni.$u.toast(msg)
				this.$refs['freeOfRef'].close()
				if (code == 200) this.$emit('init')
			},
			async savePay(e) {
				if (e) {
					this.pay.authCode = e
					this.loading = true
				}
				this.pay.diningType = this.form.diningType
				this.pay.tableId = this.form.id
				if(this.form && this.form.couponId){
					this.pay.couponId = this.form.couponId
				}
				let {
					msg,
					data
				} = await this.beg.request({
					url: this.form.diningType == 4 ? `${this.api.inPay}/${this.form.orderSn}` : this.api
						.inOrder,
					method: 'POST',
					data: this.pay
				})
				this.loading = false
				uni.$u.toast(msg)
				this.setVip({})
				if (data) {
					this.$emit('init')
					playAudo('../../static/auto/zhifucg.mp3')
					this.$emit('cInit')
				}
			},
			zc() {
				this.$refs['addUserRef'].open()
				this.$refs['userNumRef'].close()
			},
			sCoupon(){
				console.log("this.form.couponList",this.form.couponList)
				this.$refs['sCouponRef'].open(this.form.couponList,this.form.couponId)
			},
			payorder(e) {
				this.$emit('cpOut',e ? e.id : 0)
				this.$refs['sCouponRef'].close()
			},
			async getWays(){
				let {
					data
				} = await this.beg.request({
					url: this.api.costomPay
				})
				let way = []
				way = data.map(v => ({
					img: v.logo,
					title: v.name,
					value: 'costomPay',
					costomPayId: v.id,
				}))
				this.ways = this.ways.concat(way)
			},
		}
	})
</script>

<style lang="scss" scoped>
	.isPayType {
		opacity: 0.2;
		pointer-events: none;
	}

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

		.ways {
			display: flex;
			flex-wrap: wrap;
			max-height: calc(100vh - 43.6197vh);
			overflow-y: auto;

			.way {
				width: 33.3%;
			}

			.waywh {
				width: 5.1244vw;
				height: 9.1145vh
			}
		}

		// .r_b {
		// 	/deep/.u-button {
		// 		span {
		// 			color: #000;
		// 		}
		// 	}
		// }

		/deep/.u-cell__body {
			padding: 0 0 15px;

			span {
				font-size: 20px;
			}
		}

		.dis_item {
			position: relative;
			height: 6.5104vh;
			width: 14.6412vw;
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
			border: 1px solid #4275F4;
			background: #4275F4;
			color: #fff;

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

	.vipInfos {
		padding: 20px;
		border-radius: 10px;
		background: linear-gradient(to right, #E4E7EA, #D2DCE4);
	}

	.cz {
		width: 3.6603vw;
	}

	@media (min-width: 1500px) and (max-width: 3280px) {
		.rightOrder {
			.ways {
				max-height: calc(100vh - 335px);
				.waywh {
					width: 70px;
					height: 70px;
				}
			}

			.dis_item {
				width: 200px;
				height: 50px;
			}

		}

		.cz {
			width: 50px;
		}
	}
	
	@media (min-width: 500px) and (max-width: 900px) {
		/deep/ .u-text__value {
		    font-size: 14px !important;
		}
	}
</style>
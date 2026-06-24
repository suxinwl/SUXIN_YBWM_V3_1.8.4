<template>
	<u-overlay :show="reduce" :opacity="0.2">
		<view class="reduce bf p15 f18 f-y-bt" @tap.stop>
			<view class="f-x-bt wei f24">
				<view v-if="t==1" class="dfa f-c f-g-1">商品打折/减免
					<!-- <text class="iconfont icon-wenhao wei5 c6 pl10" style="font-size: 19px;"></text> -->
				</view>
				<view v-else-if="t==2" class="dfa f-c f-g-1">订单打折/减免
					<text class="iconfont icon-wenhao wei5 c6 pl10" style="font-size: 19px;"></text>
				</view>
				<!-- <text class="iconfont icon-cuowu wei5 c6 pl10" style="font-size: 19px;" @click="close"></text> -->
			</view>
			<view class="overflowlnr p-5-0">{{selectItem.goods && selectItem.goods.name}}</view>
			<view class="uScroll pb10">
				<view class="mt15 mb10 bs10 srin" style="border:1px solid #4275F4">
					<u-input v-if="actReduce===0" v-model="discount" type="number" placeholder="打8折请输入80" border="none"
						disabled disabledColor="#fff" inputAlign="right">
						<view slot="prefix" class="dfa tabs f14">
							<view :class="actReduce==index?'bffd cf wei6 bs6':'c9'" class="tab_i"
								v-for="(item,index) in ['打折','减免']" :key="index" @click="tabChange(index)">{{item}}
							</view>
						</view>
						<view slot="suffix" class="">{{actReduce==0?'%':'元'}}</view>
					</u-input>
					<u-input v-else v-model="disMoney" type="text" placeholder="减8元请输入8" border="none" disabled
						disabledColor="#fff" inputAlign="right">
						<view slot="prefix" class="dfa tabs f14">
							<view :class="actReduce==index?'bffd cf wei6 bs6':'c9'" class="tab_i"
								v-for="(item,index) in ['打折','减免']" :key="index" @click="tabChange(index)">{{item}}
							</view>
						</view>
						<view slot="suffix" class="">{{actReduce==0?'%':'元'}}</view>
					</u-input>
				</view>
				<!-- <view style="height:5.2083vh;">
					<view v-if="r_dis&&actReduce===0" class="mb15 f14 c9">打折后<text
							class="cf5 pr15">￥{{afDis && afDis.toFixed(2)}}</text>包含关联做法/加料/餐盒的金额
					</view>
					<view v-if="r_dis&&actReduce===1" class="mb15 f14 c9">减免后<text
							class="cf5 pr15">￥{{afMoney && afMoney.toFixed(2)}}</text>包含关联做法/加料/餐盒的金额</view>
				</view> -->
				<!-- <view v-if="actReduce==0" class="f-x-bt mb10">
					<view class="dis w20 tac bs6" v-for="(item,index) in [60,70,80,90]" :key="index"
						@click="reduceItem(item,index)">
						{{item}}%
					</view>
				</view> -->
				<view class="key">
					<keybored v-if="actReduce===0" type="number" :max="99" v-model="discount" confirmText="确定"
						@doneClear="clearAll(1)" @input="intDis" />
					<keybored v-else type="digit" :max="money" v-model="disMoney" confirmText="确定"
						@doneClear="clearAll(2)" @input="intMoney" />
				</view>
				<view class="">
					<view class="m15 c9 f14">{{actReduce==0?'打折':'减免'}}原因<text class="cf5 pl5 wei6 f15">*</text></view>
					<creason :list="list" @getRemark="getRemark" />
				</view>
			</view>
			<view class="f-1 f-y-e">
				<u-button @click="close" class="mr20"><text class="c0">取消</text></u-button>
				<u-button color="#4275F4" @click="confirm"><text class="cf">确认</text></u-button>
			</view>
		</view>
		<u-toast ref="uToast"></u-toast>
	</u-overlay>
</template>

<script>
	import creason from '@/components/other/creason.vue';
	import keybored from '@/components/liujto-keyboard/keybored.vue';
	import {
		mapState,
	} from 'vuex'
	export default {
		props: {
			selectItem: {
				type: Object,
				default: {}
			},
			v: {
				type: Object,
				default: {}
			},
			t: {
				type: Number,
				default: 1
			},
		},
		components: {
			creason,
			keybored,
		},
		data(props) {
			return {
				reduce: false,
				show: false,
				r_dis: false,
				money: props.price,
				actReduce: 0,
				desc: '',
				discount: '',
				disMoney: '',
				afDis: '',
				afMoney: '',
				resons: [],
				list: [],
			}
		},
		computed: {
			...mapState({
				reasonConfig: state => state.config.reasonConfig,
			}),
		},
		methods: {
			open() {
				if (this.t == 2) this.list = this.reasonConfig && this.reasonConfig.orderDiscount || []
				this.list = this.reasonConfig && this.reasonConfig.goodsDiscount || []
				this.reduce = true
			},
			close() {
				this.actReduce = 0
				this.resons = []
				this.reduce = false
			},
			reduceItem(item, index) {
				this.discount = item
				this.afDis = (+this.selectItem.money) * this.discount / 100
				this.r_dis = true
			},
			tabChange(index) {
				this.actReduce = index
				this.r_dis = false
			},
			getRemark(e) {
				this.resons = e
			},
			//打折
			intDis(val) {
				console.log(val);
				if (val == 99) {
					return uni.showToast({
						title: `请输入1~99的整数`,
						icon: 'none'
					})
				}
				this.afDis = (+this.selectItem.money) * this.discount / 100
				this.r_dis = true
			},
			clearAll(type) {
				if (type === 1) {
					this.discount = 0
					this.afDis = this.money
				} else {
					this.disMoney = 0
					this.afMoney = this.money
				}
			},
			//减免
			intMoney(val) {
				let jm = (+this.selectItem.money)
				if (val > jm) {
					uni.showToast({
						title: `减免金额不能超过${jm}元`,
						icon: 'none'
					})
					this.disMoney = 0
					this.r_dis = false
				}
				this.afMoney = jm - this.disMoney
				this.r_dis = true
			},
			//确定
			confirm() {
				if (this.actReduce == 0 && !this.discount || this.actReduce == 1 && !this.disMoney) {
					return uni.showToast({
						title: '请输入优惠',
						icon: 'none'
					})
				}
				if (this.resons && this.resons.length > 0) {
					this.$emit('cMonry', {
						goods: [{
							id: this.selectItem.id,
							num: this.selectItem.num
						}],
						type: this.actReduce == 0 ? 'discount' : 'sub',
						discount: this.actReduce == 0 ? this.discount : this.disMoney,
						reason: this.resons && this.resons.join('，'),
						diningType: this.v.diningType,
						storeId: this.v.storeId,
						tableId: this.v.id,
					})
				} else {
					uni.showToast({
						title: '请选择优惠原因',
						icon: 'none'
					})
				}
			}
		}
	}
</script>

<style lang="scss" scoped>
	.reduce {
		position: absolute;
		top: 10.4166vh;
		left: 36.6032vw;
		width: 28.5505vw;
		height: calc(100vh - 19.5312vh);
		border-radius: 10px;

		.uScroll {
			height: calc(100vh - 26.0416vh);
			overflow: hidden;
			overflow-y: scroll;
		}

		.tabs {
			display: inline-flex;
			border-radius: 6px;
			background: #eeeeee;

			.tab_i {
				padding: 8px 15px;
			}
		}

		.dis {
			padding: 8px 0;
			width: 23%;
			border: 1px solid #e6e6e6;
		}

		.key {
			/deep/.ljt-keyboard-body {
				border-radius: 10px;
				border: 1px solid #e5e5e5;

				.ljt-keyboard-number-body {
					width: 26.3543vw !important;
					height: 25.8091vh !important;
				}

				.ljt-number-btn-confirm-2 {
					background: #4275F4 !important;
				}
			}
		}

		.reson_i {
			position: relative;
			display: inline-block;
			border: 1px solid #e6e6e6;
			padding: 8px 15px;

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

		.acreson_i {
			border: 1px solid #FD8906;
			background: #fff9dd;

			.r_gou,
			.icon-duigou {
				display: block;
			}
		}

		.srin {
			// height: 5.8593vh;
			padding: 0.8784vw;
		}
	}

	@media (min-width: 1500px) and (max-width: 3280px) {
		.reduce {
			position: absolute;
			top: 80px;
			left: 500px;
			width: 390px;
			height: calc(100vh - 150px);
			border-radius: 10px;

			.uScroll {
				height: calc(100vh - 200px);
			}

			.key {
				/deep/.ljt-keyboard-body {
					border-radius: 10px;
					border: 1px solid #e5e5e5;

					.ljt-keyboard-number-body {
						width: 360px !important;
						height: 275px !important;
					}

					.ljt-number-btn-confirm-2 {
						background: #4275F4 !important;
					}
				}
			}

			.srin {
				padding: 12px;
			}
		}
	}
</style>
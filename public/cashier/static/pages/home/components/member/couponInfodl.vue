<template>
	<u-overlay :show="show" :opacity="0.2" @click="close">
		<!-- <u-popup :show="allDesc" :round="10" :overlayOpacity="0.2" mode="top" @close="close"> -->
		<view class="reduce bf p15 f18 f-y-bt" @tap.stop>
			<view class="f-x-bt mb30 mt10">
				<view class="overflowlnr f-c f-g-1 wei f24">{{title}}</view>
				<!-- <text class="iconfont icon-cuowu wei5 c6 pl10" style="font-size: 19px;" @click="close"></text> -->
			</view>
			<view class="p2 main">
				<view class="flex">
					<view class="left">券名称：</view>
					<view>{{co.name}}</view>
				</view>
				<view class="flex mt15">
					<view class="left">优惠券ID：</view>
					<view>{{form.sn}}</view>
				</view>
				<view class="flex mt15">
					<view class="left">券类型：</view>
					<view>
						<text v-if="co.type == 1">代金券</text>
						<text v-if="co.type == 2">折扣券</text>
						<text v-if="co.type == 3">兑换券</text>
						<text v-if="co.type == 4">运费券</text>
					</view>
				</view>
				<view class="flex mt15">
					<view class="left">券内容：</view>
					<view>
						<view class="f-c f-y-e" v-if="co.type==1">
							<text class="">{{co.rule && co.rule.money}}</text>
							<text class="ml5">元</text>
						</view>
						<view class="f-c f-y-e" v-else-if="co.type==2">
							<text class="">{{co.rule && co.rule.discount}}</text>
							<text class="ml5">折</text>
						</view>
						<view class="f-c f-y-e" v-else-if="co.type==3">
							<text class="f30">兑换商品</text>
						</view>
						<view class="f-c f-y-e" v-else-if="co.type==4">
							<text class="f30" v-if="co.rule && co.rule.disContent==1">免配送费</text>
							<text class="f36" v-if="co.rule && co.rule.disContent==3">立减{{co.rule.money}}元</text>
						</view>
					</view>
				</view>
				<view class="flex mt15">
					<view class="left">使用门槛：</view>
					<view class="">
						<span v-if="co.startSwitch==0">无限制</span>
						<span v-else-if="co.startSwitch==1">满{{co.startMoney && parseFloat(co.startMoney)}}元可用</span>
					</view>
				</view>
				<view class="flex mt15">
					<view class="left">适用门店：</view>
					<view>
						<text v-if="co.storeType == 1">全部门店</text>
						<text v-else-if="co.storeType == 2">指定门店适用</text>
						<text v-else-if="co.storeType == 3">指定门店不适用</text>
					</view>
				</view>
				<view class="flex mt15">
					<view class="left">适用商品：</view>
					<view>
						<text v-if="co.goodsType == 1">全部商品</text>
						<text v-else-if="co.goodsType == 2">指定商品适用</text>
						<text v-else-if="co.goodsType == 3">指定商品不适用</text>
					</view>
				</view>
				<view class="flex mt15">
					<view class="left">使用场景：</view>
					<view>
						<text v-if="co.scenario && co.scenario.includes(2)">自提,</text>
						<text v-if="co.scenario && co.scenario.includes(1)">外卖</text>
					</view>
				</view>
				<view class="flex mt15">
					<view class="left">领取时间：</view>
					<view>{{form.created_at}}</view>
				</view>
				<view class="flex mt15">
					<view class="left">获得来源：</view>
					<view>{{form.channelFormat}}</view>
				</view>
				<view class="flex mt15">
					<view class="left">使用有效期：</view>
					<view v-if="co.period && co.period.type"><text
							v-if="co.period.type == 1">{{co.period.timeArr.startTime}} ~ {{co.period.timeArr.endTime}}</text>
						<text v-if="co.period.type == 2">
							获得券{{ co.period.day.type == 1 ? "当日起" : "此日起" }}开始{{ co.period.day.value }}个自然日内有效</text>
						<text v-if="co.period.type == 3">
							获得券{{ co.period.day.type == 1 ? "当日起" : "此日起" }}开始{{
							            co.period.day.value
							          }}个小时内有效
						</text>
					</view>
				</view>
				<view class="flex mt15">
					<view class="left">优惠券说明：</view>
					<view v-if="co.body">
						{{co.body}}
					</view>
				</view>
				<view class="flex mt15">
					<view class="left">券状态：</view>
					<view>
						<text v-if="form.state == 0">已过期</text>
						<text v-if="form.state == 1">待使用</text>
						<text v-if="form.state == 2">已使用</text>
						<text v-if="form.state == 3">已作废</text>
					</view>
				</view>
			</view>
			<view class="f-1 f-y-e">
				<!-- <u-button @click="close" class="mr20"><text class="c0">取消</text></u-button> -->
				<u-button color="#4275F4" @click="close"><text class="cf">确认</text></u-button>
			</view>
		</view>
	</u-overlay>
</template>

<script>
	import keybored from '@/components/liujto-keyboard/keybored.vue';
	import {
		mapState,
	} from 'vuex'
	export default {
		props: {

		},
		components: {
			keybored,
		},
		data() {
			return {
				show: false,
				title: '优惠券详情',
				form: {},
				co: {},
			}
		},
		computed: {
			...mapState({
				reasonConfig: state => state.config.reasonConfig,
			}),
		},
		methods: {
			open(t) {
				this.form = t
				this.co = t && t.coupon
				this.show = true
			},
			close() {
				this.show = false
			},
			save() {
				if (this.desc) this.resons.push(this.desc)
				if (this.type == 'remark') {
					this.$emit('itemRemark', this.resons, 1)
				} else {
					this.$emit('returnRemark', this.resons, 1)
				}
			},
		}
	}
</script>

<style lang="scss" scoped>
	/deep/.u-transition {
		background-color: rgba(0, 0, 0, 0.1) !important;
	}

	/deep/.u-modal {
		.u-modal__content {
			justify-content: flex-start;
		}

		.u-modal__button-group__wrapper__text {
			color: #000 !important;
		}
	}

	.reduce {
		position: absolute;
		// top: 7.1614vh;
		// left: 36.6032vw;
		// width: 28.5505vw;
		// height: calc(100vh - 7.1614vh);
		// border-radius: 10px;
		// box-shadow: 5px 0px 10px 0px #ccc;

		transform: translateX(-50%);
		top: 20vh;
		left: 50vw;
		width: 43.9238vw;
		height: 59.0833vh;
		border-radius: 10px;

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
					width: 360px !important;
					height: 275px !important;
				}

				.ljt-number-btn-ac {
					width: 90px !important;
				}

				.ljt-number-btn-confirm-2 {
					width: 100px !important;
					background: #4275F4 !important;

					span {
						color: #000;
					}
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
			border: 1px solid #fff;
			background: #4275F4;
			color: #fff;

			.r_gou,
			.icon-duigou {
				display: block;
			}
		}
	}

	.main {
		height: 65.1041vh;
		overflow: hidden;
		overflow-y: scroll;
		padding-bottom: 20px;

		.left {
			width: 110px;
			text-align: right;
		}
	}

	@media (min-width: 1500px) and (max-width: 3280px) {
		.reduce {
			top: 20%;
			left: 50%;
			transform: translateX(-50%);
			width: 800px;
			height: 600px;
			border-radius: 10px;
		}

		.main {
			height: 500px;
		}
	}
</style>
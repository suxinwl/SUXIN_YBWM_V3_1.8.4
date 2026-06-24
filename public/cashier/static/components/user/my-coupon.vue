<template>
	<view class="ccoupon mb20" v-if="ptype==2">
		<view class="bs20 p10 bf p-r" @click="co.isUse!=1?$emit('change',v.id):''">
			<view class="f-bt pb20 pt10">
				<view class="f-g-0 left f-c-c">
					<view class="f-c crb f-y-e l-h1" v-if="co.type==1">
						<text class="f56 wei">{{co.rule.money}}</text>
						<text class="ml5">元</text>
					</view>
					<view class="f-c crb f-y-e l-h1" v-else-if="co.type==2">
						<text class="f56 wei">{{co.rule.discount}}</text>
						<text class="ml5">折</text>
					</view>
					<view class="f-c crb f-y-e l-h1" v-else-if="co.type==3">
						<text class="f30">兑换商品</text>
					</view>
					<view class="f-c crb f-y-e l-h1" v-else-if="co.type==4">
						<text class="f30" v-if="co.rule.disContent==1">免配送费</text>
						<text class="f36" v-if="co.rule.disContent==3">立减{{co.rule.money}}元</text>
					</view>
					<view class="c9 mt10 f18">
						<span v-if="co.startSwitch==0">无限制</span>
						<span v-else-if="co.startSwitch==1">满{{co.startMoney && parseFloat(co.startMoney)}}元可用</span>
					</view>
				</view>
				<view class="f-g-1">
					<view class="wei f20 t-o-e">{{co.name}}</view>
					<view class="f18 c9 mt5" v-if="ttype==1">{{co.timeArr.startTime}} ~ {{co.timeArr.endTime}}</view>
					<view class="f18 c9 mt5" v-else-if="ttype==2">{{v.startTime}} ~ {{v.endTime}}</view>
				</view>
				<view class="f-g-0 f-c">
					<radio color="#4275F4" v-if="co.isUse!=1" :checked="v.checked" />
				</view>
			</view>
			<!-- <view class="p-a couqlx cf t-c" :style="{background:qlx.c}">{{qlx.t}}</view> -->
			<!-- <view class="p-a counum cf t-c" v-if="ttype==2 && v.num>1">x{{v.num}}</view> -->
		</view>
	</view>
	
</template>

<script>
	import {
		mapState,
		mapActions
	} from 'vuex'
	export default {
		name: 'coupon',
		components: {

		},
		props: {
			v: {
				type: Object,
				default: function() {
					return {}
				}
			},
			co: {
				type: Object,
				default: function() {
					return {}
				}
			},
			gttype: {
				type: String,
				default: ''
			},
			ptype: { 
				type: String,
				default: '1'
			},
			ttype: { 
				type: String,
				default: '1'
			},
			cname: {
				type: String,
				default: ''
			},
			u: {
				type: String,
				default: 'px'
			},
			color: {
				type: String,
				default: ''
			}
		},
		data() {
			return {
				show: false,
				active: false,
				disabled: false,
				cCode: '',
				cpName: '',
			}
		},
		// mixins: [utilMixins],
		computed: {
			qlx() {
				let t, c = ''
				switch (this.co.type) {
					case 1:
						t = '代'
						c = '#136FFE'
						break;
					case 2:
						t = '折'
						c = '#67C23A'
						break;
					case 3:
						t = '兑'
						c = '#FF4046'
						break;
					case 4:
						t = '运'
						c = '#FFAF24'
						break;
					default:
						break;
				}
				return {
					t,
					c
				}
			},
		},
		methods: {

		},
	}
</script>

<style scoped lang="scss">
	.ccoupon {

		.dot1,
		.dot2 {
			position: absolute;
			width: 30rpx;
			height: 15rpx;
			background: #f7f7f7;
		}

		.dot1 {
			left: -8rpx;
			top: 156rpx;
			border-radius: 0 0 30px 30px;
			border-top: 0;
			transform: rotateZ(-90deg);
		}

		.dot2 {
			right: -8rpx;
			top: 156rpx;
			border-radius: 30px 30px 0 0;
			transform: rotateZ(-90deg);
			border-bottom: 0;
		}

		.left {
			width: 30%;
		}

		.qsy {
			width: 130rpx;
			height: 56rpx;
		}

		.criel {
			width: 36rpx;
			height: 36rpx;
			background: #eee;
		}

		.fanz {
			transform: rotateZ(180deg);
		}

		.fanzz {
			transform: rotateZ(0deg);
		}

		.lh40 {
			line-height: 48rpx;
		}
	}

	.coubtn {
		min-width: 130rpx;
		height: 56rpx;
		color: #fff;
		background: #DDD;
		border-radius: 30rpx;
	}

	.couqlx {
		top: 0;
		left: -25px;
		width: 50px;
		height: 22px;
		font-size: 9px;
		line-height: 26px;
		background: linear-gradient(#ff3a48, #ff3a48);
		transform: rotate(-45deg);
		transform-origin: 50% 0%;
	}

	.counum {
		top: 0;
		right: -25px;
		width: 50px;
		height: 22px;
		font-size: 10px;
		line-height: 26px;
		background: linear-gradient(#ff3a48, #ff3a48);
		transform: rotate(45deg);
		transform-origin: 50% 0%;
	}

	.popCode,
	.popShare {
		padding: 100rpx;
		position: relative;
		background: #fff;

		.code {
			width: 400rpx;
			height: 400rpx;
		}

		.xzhy {
			width: 400rpx;
			height: 90rpx;
			margin-top: 60rpx;
		}
	}
</style>
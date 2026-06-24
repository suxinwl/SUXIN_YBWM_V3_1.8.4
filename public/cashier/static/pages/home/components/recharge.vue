<template>
	<view class="f-y-bt h100">
		<view class="f-1 bf f-bt">
			<!-- <view class="left br1 f-y-bt">
				<view class="f-x-bt pt15 pl15 pr15 acc">
					<u-button size="small" text="消费时间" :customStyle="{marginRight:'10px'}"></u-button>
					<view class="f-1 date">
						<view>{{date}}</view>
					</view>
				</view>
				<view class="user p15 bd1">
					<view v-if="!showVip" class="user_cont f-x-bt bs6 p10">
						<view class="f-c">
							<u-avatar src="@/static/imgs/avatar.png" size="50"></u-avatar>
							<text class="pl10 f20">散客</text>
						</view>
						<view class="">
							<u-button color="#4275F4" text="会员登录" :customStyle="{color:'#000',height:'35px'}"
								@click="isVip=true"></u-button>
						</view>
					</view>
					<view v-else class="user_cont f-x-bt  bs6 p10">
						<view v-if="!showVip" class="f-c">
							<u-avatar :src="vipForm.avatar" size="50"></u-avatar>
							<text class="pl10 f20 c0">散客</text>
						</view>
						<view v-else class="f-1 dfa pr10">
							<u-avatar :src="vipForm.avatar" size="50"></u-avatar>
							<view class="ml10 f-y-bt f12">
								<view class="dfa mb10 f16">
									<view class="nowrap" style="max-width: 80px;">{{vipForm.name}}</view>
									<view class="grade f-c-c f12">
										{{vipForm.grade==0?'普通会员':vipForm.grade==1?'大众会员':vipForm.grade==2?'标准会员':vipForm.grade==3?'白银会员':''}}
									</view>
								</view>
								<view class="mb10 f12">{{vipForm.phone}}</view>
								<view>
									<text class="pr10">余额：{{vipForm.balance}}</text>
									<text class="pr10">积分：{{vipForm.integral}}</text>
								</view>
							</view>
						</view>
						<view class="dfa">
							<u-button color="#4275F4" size="small" text="更换会员"
								:customStyle="{color:'#000',marginRight:'10px'}" @click="isVip=true"></u-button>
							<view class="sk">
								<u-button color="#4275F4" size="small" text="退出" :customStyle="{color:'#000'}"
									@click="vipForm={};showVip=false"></u-button>
							</view>
						</view>
					</view>
				</view>
				<view class="f-1 f-y-bt bd1 p15">
					<view class="f-x-bt f18 mb10">
						<view class="">结算清单（0）</view>
						<view class="f-c">
							<text class="iconfont icon-qingchu mr5" style="font-size:20px"></text>清空
						</view>
					</view>
					<view class="f-1 f-c-c list" style="overflow-y:auto">
						<image src="../../../static/imgs/car.png" mode="" style="width: 180px;height:180px"></image>
						<view class="f15" style="color:#c0c4cc">点击右侧商品，选择商品进行结账</view>
					</view>
				</view>
				<view class="p15">
					<view class="c9 f18 mb5">共0件</view>
					<view class="f-x-bt">
						<view class="f27 cf5">￥0.00</view>
						<view>
							<u-button color="#4275F4" :customStyle="{color:'#000'}">
								<view style="width: 80px;">结账</view>
							</u-button>
						</view>
					</view>
				</view>
				<view class="wrap"></view>
			</view> -->
			<rechargeRight ref="rightRef" :list="dataList" :aIdx="aIdx" :xzrule="xzrule" :valSet="valSet" @change="change" @init="init"></rechargeRight>
		</view>
	</view>
</template>

<script>
	import rechargeRight from './recharge/right.vue'
	import {
		mapState,
		mapMutations,
	} from 'vuex'
	export default ({
		components: {
			rechargeRight,
		},
		props: {
			// id: {
			// 	type: String,
			// 	default: ''
			// }
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
				
				queryForm: {
					pageNo: 1,
					pageSize: 999,
				},
				dataList:[],
				aIdx:0,
				xzrule: {},
				valSet:{},
			}
		},
		// created: function() {
		// 	this.vipData.forEach(v => {
		// 		v.phone = this.geTel(v.phone)
		// 	})
		// 	this.date = this.getTime()
		// 	this.getNowTimeFun()
		// },
		// watch: {
		// 	id: {
		// 		handler(nVal, oVal) {
		// 			if (nVal) {
		// 				this.vipForm = this.vipData.filter(v => v.id == nVal)[0]
		// 				this.showVip = true
		// 			}
		// 		},
		// 		immediate: true,
		// 		deep: true
		// 	}
		// },
		// beforeDestroy() {
		// 	clearInterval(this.timer)
		// 	this.timer = null
		// },
		computed: {
			...mapState({
				vipInfo: state => state.vipInfo,
			}),
		},
		methods: {
			init() {
				this.getSetConfig()
				this.fetchData()
			},
			async fetchData() {
				this.queryForm.userId = this.vipInfo && this.vipInfo.id || 0
				let {
					data
				} = await this.beg.request({
					url: this.api.storedValueList,
					data: this.queryForm
				})
				this.dataList = data ? data : []
				if (data.length) {
					this.aIdx = 0
					this.xzrule = data[0]
				}
			},
			async getSetConfig() {
				let {
					data
				} = await this.beg.request({
					url: this.api.config,
					data: {
						ident: 'storageVal'
					}
				})
				this.valSet = data
			},
			change(v){
				this.aIdx = v
				if (v == -1) {
					this.focus = true
				} else {
					this.focus = false
					this.xzrule = this.dataList[v]
				}
			},
			
			// //时间
			// getNowTimeFun() {
			// 	this.timer = setInterval(() => {
			// 		this.date = this.getTime()
			// 	}, 1000)
			// },
			// padaDate(value) {
			// 	return value < 10 ? '0' + value : value;
			// },
			// getTime() {
			// 	var date = new Date();
			// 	var yy = date.getFullYear();
			// 	var mm = this.padaDate(date.getMonth() + 1);
			// 	var dd = this.padaDate(date.getDate());
			// 	var h = this.padaDate(date.getHours());
			// 	var m = this.padaDate(date.getMinutes());
			// 	var s = this.padaDate(date.getSeconds());
			// 	return `${yy}-${mm}-${dd} ${h}:${m}:${s}`
			// },
			// //号码
			// geTel(tel) {
			// 	return tel.substring(0, 3) + "****" + tel.substr(tel.length - 4);
			// },
		}
	})
</script>

<style lang="scss" scoped>
	.left {
		position: relative;
		width: 800rpx;

		.user_cont {
			height: 194rpx;
			border: 2px solid #4275F4;
			background: #fff6f1;

			.grade {
				margin-left: 10px;
				background: #fff;
				color: #FD8906;
				border: 1px solid #FD8906;
				width: 55px;
			}

			/deep/.ul-button {
				.u-button__text {
					font-size: 18px !important;
				}
			}
		}

		.list {
			max-height: calc(100vh - 346px);
			overflow-y: auto;

			.isSelect {
				background: rgba(#fff0a9, .4);
			}

			/deep/.u-empty {
				height: 500px;
			}
		}

		.wrap {
			position: absolute;
			width: 800rpx;
			height: 100%;
			top: 0;
			left: 0;
			background-color: hsla(0, 0%, 100%, .6);
			z-index: 10;
			cursor: no-drop;
		}

		.acc {
			/deep/.u-button {
				width: 60px !important;
				height: 35px;
			}
		}
	}

	.right {
		position: relative;

		/deep/.u-radio__text {
			font-size: 18px !important;
		}

		.m_item {
			width: 130px;
			height: 80px;
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
</style>
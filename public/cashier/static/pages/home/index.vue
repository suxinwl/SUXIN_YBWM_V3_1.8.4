<template>
	<view class="w100v h100v f-bt f20 o-h">
		<view class="left cc">
			<view class="p15 f-c">
				<image :src="storeInfo && storeInfo.applyImage || avatar" class="avatarwh"></image>
			</view>
			<view class="tab f-c-c m1010" :class="current==item.id?'acTab':''" v-for="(item,index) in tabs" :key="index"
				@click="changeTab(item,index)" v-if="role.includes(item.role) || item.role =='gengduo'">
				<text class="iconfont navIcon" :class="item.icon"></text>
				<view class="mt5 f14">{{item.name}}</view>
			</view>
		</view>
		<view class="right f-1 f-y-bt">
			<view class="top bf mb5 f-x-bt p15">
				<pTab v-if="current>=2 && current<=5 || current==15 || current==61" :current="current" @handTabs="handTabs"></pTab>
				<view v-else class="">{{l_title}}</view>
				<view class="dfa">
					<tool @cT="changeTab"></tool>
					<!-- <text class="ml25 iconfont icon-paiduijiaohao" style="font-size:28px"></text>
					<text class="ml25 iconfont icon-lianxi2hebing_dayin" style="font-size:28px"
						@click="isType=true"></text>
					<text class="ml25 iconfont icon-xiaoxi" style="font-size:29px" @click="isNotice=true"></text>
					<text class="ml25 iconfont icon-gerenzhongxin-xuanzhong" style="font-size:24px"
						@click="isCenter=true"></text> -->
				</view>
			</view>
			<!-- <view v-else class="top bf f-x-bt p15 mb5">
				<view class="">
					<text class="p-0-10" :class="current==i?'cfd wei6':''" v-for="(v,i) in ['小票打印机','云喇叭','标签打印机']" :key="i"
						@click="current=i">{{v}}</text>
					<u-tabs :list="[{name: '小票打印机'}, {name: '云喇叭'}, {name: '标签打印机'}, ]" lineWidth="20" lineHeight="7"
						:lineColor="`url(${lineBg}) 100% 100%`"
						:activeStyle="{color: '#303133',fontWeight: 'bold',transform: 'scale(1.05)'}"
						:inactiveStyle="{color: '#606266',transform: 'scale(1)'}"
						itemStyle="padding-right: 1.4641vw;font-size:1.4641vw; height: 4.4270vh">
					</u-tabs>
				</view>
				<view class="">
					<text class="pl20 iconfont icon-paiduijiaohao" style="font-size:1.7569vw"></text>
					<text class="pl20 iconfont icon-lianxi2hebing_dayin" style="font-size:1.7569vw"
						@click="isType=true"></text>
					<text class="pl20 iconfont icon-xiaoxi" style="font-size:1.7569vw" @click="isNotice=true"></text>
					<text class="pl20 iconfont icon-gerenzhongxin-xuanzhong" style="font-size:1.6373vw"
						@click="isCenter=true"></text>
				</view>
			</view> -->
			<view class="f-1">
				<billing v-if="current==0 && role.includes('diandan')" ref="billingRef" @openOver="getOpen" />
				<desk v-if="current==1 && role.includes('zhuotai')" ref="deskRef" @openOver="getOpen" />
				<callOrder v-if="current==2 && role.includes('jiaohao')" ref="callRef" />
				<!-- <recharge v-if="current==3" ref="recharRef" /> -->
				<reconciliation  v-if="current==3 && role.includes('duizhang')" ref="recontionRef" />
				<order v-if="current==4 && role.includes('dingdan')" ref="orderRef" />
				<member v-if="current==5 && role.includes('huiyuan')" ref="memberRef" />
				<verification v-if="current==6 && role.includes('diandan')" @cT="changeTab" ref="verificationRef" />
				<goods v-if="current==7 && role.includes('goods')" ref="goodsRef" />
				<staffs v-if="current==8 && role.includes('diandan')" />
				<refund v-if="current==9 && role.includes('diandan')" />
				<shift v-if="current==10 && role.includes('jiaoban')" ref="shiftRef" />
				<information v-if="current==11 && role.includes('diandan')" />
				<setup v-if="current==12 && role.includes('diandan')" />
				<print v-if="current==13 && role.includes('yingjian')" ref="printRef" />
				<setGoods v-if="current==15 && role.includes('xitong')" ref="setGoodsRef" />
				<verificationdl v-if="current==61 && role.includes('diandan')" ref="verificationdlRef" />
			</view>
		</view>

		<typer :isType="isType" @closeType="isType=false" />
		<notice :isNotice="isNotice" @closeNotice="isNotice=false" />
		<center :isCenter="isCenter" @closeCenter="isCenter=false" />
		<openShare ref='openRef' @save="openSave" />
		<view v-if="show">
			<u-popup :show="isMore" mode="left" @close="isMore=false">
				<view class="mode f20">
					<view class="p20 f20">收银</view>
					<view class="pl20">
						<view class="f-c-c mr10 mb10 item bf5" v-for="(item,index) in moreData.list1" :key="index"
							@click="clickItem(item,index)" v-if="role.includes(item.role)">
							<text class="iconfont mb10" :class="item.icon" style="font-size: 24px;"></text>
							<text>{{item.title}}</text>
						</view>
					</view>
					<view class="p20 f20">管理</view>
					<view class="pl20">
						<view class="f-c-c mr10 mb10 item bf5" v-for="(item,index) in moreData.list2" :key="index"
							@click="clickItem(item,index)" v-if="role.includes(item.role)">
							<text class="iconfont mb10" :class="item.icon" style="font-size: 24px;"></text>
							<text>{{item.title}}</text>
						</view>
					</view>
					<!-- <view class="p20 f20">数据</view>
					<view class="pl20">
						<view class="f-c-c mr10 mb10 item bf5" v-for="(item,index) in moreData.list3" :key="index"
							@click="clickItem(item,index)">
							<text class="iconfont mb10" :class="item.icon" style="font-size: 24px;"></text>
							<text>{{item.title}}</text>
						</view>
					</view> -->
					<view class="p20 f20">设置</view>
					<view class="pl20">
						<view class="f-c-c mr10 mb10 item bf5" v-for="(item,index) in moreData.list4" :key="index"
							@click="clickItem(item,index)" v-if="role.includes(item.role)">
							<text class="iconfont mb10" :class="item.icon" style="font-size: 24px;"></text>
							<text>{{item.title}}</text>
						</view>
					</view>
				</view>
			</u-popup>

		</view>
	</view>
</template>

<script>
	import billing from './components/billing.vue';
	import desk from './components/desk.vue';
	import recharge from './components/recharge.vue';
	import order from './components/order.vue';
	import member from './components/member.vue';
	import verification from './components/verification.vue';
	import goods from './components/goods.vue';
	import reconciliation from './components/reconciliation.vue';
	import staffs from './components/staffs.vue';
	import refund from './components/refund.vue';
	import shift from './components/shift.vue';
	import information from './components/information.vue';
	import setup from './components/setup.vue';
	import print from './components/print.vue';
	import callOrder from './components/callOrder.vue';
	import setGoods from './components/setGoods.vue';
	import typer from '@/components/tool/typer.vue';
	import notice from '@/components/tool/notice.vue';
	import center from '@/components/tool/center.vue';
	import tool from '@/components/tool/tool.vue'
	import pTab from './components/tab/pTab.vue'
	import openShare from '@/components/other/openShare.vue'
	import verificationdl from './components/verificationdl.vue';
	import {
		mapState,
		mapMutations,
	} from 'vuex'
	export default {
		components: {
			billing,
			desk,
			recharge,
			order,
			member,
			verification,
			goods,
			reconciliation,
			staffs,
			refund,
			shift,
			information,
			setup,
			print,
			callOrder,
			setGoods,
			typer,
			notice,
			center,
			tool,
			pTab,
			openShare,
			verificationdl,
		},
		computed: {
			...mapState({
				storeInfo: state => state.storeInfo,
				role: state => state.user.roleData || [],
			}),
		},
		data() {
			return {
				show: false,
				isMore: false,
				isType: false, //打印机
				isNotice: false, //消息
				isCenter: false, //个人中心
				current: 0,
				id: 0,
				lineBg: "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACgAAAAOCAYAAABdC15GAAAACXBIWXMAABYlAAAWJQFJUiTwAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAFxSURBVHgBzZNRTsJAEIb/WTW+lpiY+FZPIDew3ABP4GJ8hxsI9zBpOYHeQDwBPQI+mRiRvpLojtPdYhCorQqF/6GdbGd2vvwzBXZcNAt4oj1ANeUoAT5iqkUjbEFLHNmhD1YPEvpZ3ghkGlVDCkc94/BmHMq998I5ONiY1ZBfpKAyuOtgAc5yOEDmYEWNh32BHF91sGHZHmwW4azciN9aQwnz3SJEgOmte+R2tdLprTYoa50mvuomlLpD4Y3oQZnov6D2RzCqI93bWOHaEmAGqQUyRBlZR1WfarcD/EJ2z8DtzDGvsMCwpm8XOCfDUsVOCYhiqRxI/CTQo4UOvjzO7Pow18vfywneuUHHUUxLn55lLw5JFpZ8bEUcY8oXdOLWiHLTxvoGpLqoUmy6dBT15o/ox3znpoycAmxUsiJTbs1cmxeVKp+0zmFIS7bGWiVghC7Vwse8jFKAX9eljh4ggKLLv7uaQvG9/F59Oo2SouxPu7OTCxN/s8wAAAAASUVORK5CYII=",
				l_title: '点单',
				avatar: "data:image/jpg;base64,/9j/4QAYRXhpZgAASUkqAAgAAAAAAAAAAAAAAP/sABFEdWNreQABAAQAAAA8AAD/4QMraHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wLwA8P3hwYWNrZXQgYmVnaW49Iu+7vyIgaWQ9Ilc1TTBNcENlaGlIenJlU3pOVGN6a2M5ZCI/PiA8eDp4bXBtZXRhIHhtbG5zOng9ImFkb2JlOm5zOm1ldGEvIiB4OnhtcHRrPSJBZG9iZSBYTVAgQ29yZSA1LjMtYzAxMSA2Ni4xNDU2NjEsIDIwMTIvMDIvMDYtMTQ6NTY6MjcgICAgICAgICI+IDxyZGY6UkRGIHhtbG5zOnJkZj0iaHR0cDovL3d3dy53My5vcmcvMTk5OS8wMi8yMi1yZGYtc3ludGF4LW5zIyI+IDxyZGY6RGVzY3JpcHRpb24gcmRmOmFib3V0PSIiIHhtbG5zOnhtcD0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wLyIgeG1sbnM6eG1wTU09Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9tbS8iIHhtbG5zOnN0UmVmPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvc1R5cGUvUmVzb3VyY2VSZWYjIiB4bXA6Q3JlYXRvclRvb2w9IkFkb2JlIFBob3Rvc2hvcCBDUzYgKFdpbmRvd3MpIiB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOjREMEQwRkY0RjgwNDExRUE5OTY2RDgxODY3NkJFODMxIiB4bXBNTTpEb2N1bWVudElEPSJ4bXAuZGlkOjREMEQwRkY1RjgwNDExRUE5OTY2RDgxODY3NkJFODMxIj4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6NEQwRDBGRjJGODA0MTFFQTk5NjZEODE4Njc2QkU4MzEiIHN0UmVmOmRvY3VtZW50SUQ9InhtcC5kaWQ6NEQwRDBGRjNGODA0MTFFQTk5NjZEODE4Njc2QkU4MzEiLz4gPC9yZGY6RGVzY3JpcHRpb24+IDwvcmRmOlJERj4gPC94OnhtcG1ldGE+IDw/eHBhY2tldCBlbmQ9InIiPz7/7gAOQWRvYmUAZMAAAAAB/9sAhAAGBAQEBQQGBQUGCQYFBgkLCAYG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",
				tabs: [{
						id: 0,
						name: '点单',
						icon: 'icon-caogaoxiang',
						role: 'diandan',
					}, {
						id: 1,
						name: '桌台',
						icon: 'icon-CJ',
						role: 'zhuotai',
					}, {
						id: 2,
						name: '叫号',
						icon: 'icon-paiduijiaohao',
						role: 'jiaohao',
					},
					// {
					// 	id: 3,
					// 	name: '充值',
					// 	icon: 'icon-chongzhi'
					// },
					{
						id: 4,
						name: '订单',
						icon: 'icon-dingdan',
						role: 'dingdan',
					}, {
						id: 5,
						name: '会员',
						icon: 'icon-huiyuan',
						role: 'huiyuan',
					},
					{
						id: 3,
						name: '对账',
						icon: 'icon-chongzhi',
						role: 'duizhang',
					},
					{
						id: 6,
						name: '存酒',
						icon: 'icon-cunjiu'
					},
					{
						id: -1,
						name: '更多',
						icon: 'icon-gengduo',
						role: 'gengduo',
					}
				],
				moreData: {
					list1: [{
							id: 0,
							icon: 'icon-caogaoxiang',
							title: '点单',
							role: 'diandan',
						}, {
							id: 1,
							icon: 'icon-CJ',
							title: '桌台',
							role: 'zhuotai',
						}, {
							id: 2,
							icon: 'icon-paiduijiaohao',
							title: '叫号',
							role: 'jiaohao',
						},
						{
							id: 6,
							icon: 'icon-youhuiquan',
							title: '核销',
							role: 'diandan',
						},
						// {
						// 	id: 3,
						// 	icon: 'icon-chongzhi',
						// 	title: '充值'
						// },
						// {
						// 	id: 6,
						// 	icon: 'icon-12jiaobanbiao',
						// 	title: '交班'
						// },
					],
					list2: [{
							id: 7,
							icon: 'icon-shangpinguanli',
							title: '商品管理',
							role: 'goods',
						}, {
							id: 5,
							icon: 'icon-huiyuanguanli',
							title: '会员管理',
							role: 'huiyuan',
						},
						// {
						// 	id: 8,
						// 	icon: 'icon-yuangongguanli',
						// 	title: '员工管理'
						// },
						{
							id: 4,
							icon: 'icon-dingdanguanli',
							title: '订单管理',
							role: 'dingdan',
						},
						// {
						// 	id: 9,
						// 	icon: 'icon-yuangongguanli',
						// 	title: '退款维权'
						// },
						{
							id: 10,
							icon: 'icon-12jiaobanbiao',
							title: '交班记录',
							role: 'jiaoban',
						},
					],
					list3: [{
						id: 11,
						icon: 'icon-fenxi',
						title: '营业数据',
						role: 'duizhang',
					}],
					list4: [
					// 	{
					// 	id: 12,
					// 	icon: 'icon-dianpu',
					// 	title: '收款设置'
					// },
					{
						id: 13,
						icon: 'icon-dayin',
						title: '硬件管理',
						role: 'yingjian',
					},
					// {
					// 	id: 14,
					// 	icon: 'icon-mn_kuaidiyuan',
					// 	title: '配送员'
					// }, 
					{
						id: 15,
						icon: 'icon-mn_kuaidiyuan',
						title: '系统设置',
						role: 'xitong',
					}, ],
				}
			}
		},
		onLoad(option) {
			if (option && option.current) {
				this.current = option.current
				this.l_title = option.l_title
				this.id = option.id
				this.changeInit(+option.current)
			} else {
				this.init()
			}
			this.getReasonConfig()
			this.getOpen()
			// this.getProfix()
		},
		methods: {
			...mapMutations(["setConfig","setHandOver","setUser"]),
			init() {
				this.$nextTick(() => this.$refs['billingRef'].init())
			},
			changeInit(t) {
				console.log(t)
				switch (t) {
					case 0:
						this.$nextTick(() => this.$refs['billingRef'].init())
						break;
					case 1:
						this.$nextTick(() => this.$refs['deskRef'].init())
						break;
					case 2:
						this.$nextTick(() => this.$refs['callRef'].init())
						break;
					case 3:
						this.$nextTick(() => this.$refs['recontionRef'].init())
						break;
					case 4:
						this.$nextTick(() => this.$refs['orderRef'].init())
						break;
					case 5:
						this.$nextTick(() => this.$refs['memberRef'].init())
						break;
					case 61:
						this.$nextTick(() => this.$refs['verificationdlRef'].init())
						break;
					case 7:
						this.$nextTick(() => this.$refs['goodsRef'].init())
						break;
					case 10:
						this.$nextTick(() => this.$refs['shiftRef'].init())
						break;
					case 13:
						this.$nextTick(() => this.$refs['printRef'].init())
						break;
					case 15:
						this.$nextTick(() => this.$refs['setGoodsRef'].init())
						break;
				}
			},
			handTabs(e) {
				if (this.current == 2) {
					this.$refs['callRef'].handTabs(e)
				} else if (this.current == 3) {
					this.$refs['recontionRef'].handTabs(e)
				}else if (this.current == 4) {
					this.$refs['orderRef'].handTabs(e)
				}else if (this.current == 5) {
					this.$refs['memberRef'].handTabs(e)
				}else if (this.current == 15) {
					this.$refs['setGoodsRef'].handTabs(e)
				}else if (this.current == 61) {
					this.$refs['verificationdlRef'].handTabs(e)
				}
			},

			async getReasonConfig() {
				let {
					data
				} = await this.beg.request({
					url: this.api.config,
					data: {
						ident: 'reasonConfig'
					}
				})
				this.setConfig({
					name: 'reasonConfig',
					data,
				})
			},
			changeTab(item, index) {
				if (item.id == -1) {
					// this.current = item.id
					this.show = true
					this.isMore = true
					
					this.l_title = '系统设置'
					this.current = 15
					this.changeInit(15)
				} else {
					this.l_title = item.name
					this.current = item.id
					this.show = false
				}
				if (item.id >= 0) this.changeInit(item.id)
			},
			clickItem(item, index) {
				if (item.id == 16) {
					uni.navigateTo({
						url: '/pages/handover/index'
					})
				} else {
					this.l_title = item.title
					this.current = item.id
					this.changeInit(item.id)
				}
				this.show = false
			},
			async getOpen(){
				let {
					data
				} = await this.beg.request({
					url: this.api.handStarting
				})
				if(data){
					this.setHandOver(data)
				}else{
					this.setHandOver({})
					this.$nextTick(() => this.$refs['openRef'].open())
				}
			},
			async getProfix(){
				let {
					data
				} = await this.beg.request({
					url: this.api.profix
				})
				if(data){
					this.setUser(data)
				}
			},
		}
	}
</script>

<style lang="scss" scoped>
	.top {
		height: 7.1614vh;

		/deep/.u-tabs__wrapper__nav__item__text {
			// font-size: 20px !important;
		}
	}

	// /deep/.u-button {
	// 	span {
	// 		color: #000;
	// 	}
	// }


	.left {
		// width: 80px;
		width: 5.8565vw;
		height: 100vh;
		background: #ECEBF0;
		overflow: scroll;

		.tab {
			padding: 22rpx 0;
			border-radius: 6px;
			color: #7E808C;

			.navIcon {
				font-size: 1.6105vw;
			}
		}

		.acTab {
			background: #4275F4;
			color: #fff;
		}

		.avatarwh {
			width: 3.6603vw;
			height: 3.6603vw;
			border-radius: 50%;
		}
	}

	.right {
		background: #eff0f4;
		overflow: hidden;
		overflow-y: scroll;
	}

	// /deep/.u-transition {
	// 	// left: 80px !important;
	// 	left: 5.8565vw !important;
	// }

	.mode {
		max-height: 100vh;
		// width: 350px;
		width: 25.6222vw;
		overflow-y: auto;

		.item {
			display: inline-flex;
			// width: 90px;
			width: 6.5885vw;
			height: 90px;
			border-radius: 5px;
		}
	}

	@media (min-width: 1500px) and (max-width: 3280px) {
		.top {
			height: 55px;

			/deep/.u-tabs__wrapper__nav__item__text {
				font-size: 20px !important;
			}
		}

		.left {
			width: 80px;
			height: 100vh;

			.tab {
				padding: 11px 0;

				.navIcon {
					font-size: 22px;
				}
			}

			.avatarwh {
				width: 50px;
				height: 50px;
				border-radius: 50%;
			}
		}

		.right {
			background: #eff0f4;
			overflow: hidden;
		}

		// /deep/.u-transition {
		// 	left: 80px !important;
		// }

		.mode {
			max-height: 100vh;
			width: 350px;
			overflow-y: auto;

			.item {
				display: inline-flex;
				width: 90px;
				height: 90px;
				border-radius: 5px;
			}
		}
	}
</style>
<template>
	<view class="f-y-bt h100">
		<view class="right f-1 p10">
			<view class="f-x-bt mb15">
				<view class="tabs bs6 f-bt">
					<view :class="tab==index?'istab':''" class="tab tac f16" v-for="(item,index) in tabs" :key="index"
						@click="changeTab(item,index)">{{item.name}}</view>
				</view>
				<!-- <view style="width: 250px;">
					<uni-data-select v-model="select" :localdata="selects" @change="change"></uni-data-select>
				</view> -->
			</view>
			<view class="tables">
				<view class="table mr15 mb15 bf pt15 f-y-bt"
					:class="v.state==1?'b23 cf':v.state==2?'bb3 cf':v.state==4?'bdb cf':v.state==3?'b2e cf':'bf c0'"
					v-for="(v,i) in tabelList" :key="i" @click="clickItem(v,i)">
					<view class="f-bt">
						<view class="p-0-15 f16 mb15 t-o-e">{{v.type.name}}{{v.name}}</view>
						<view class="p-0-15 sm f14 mr10" v-if="v.state!=0 && v.scan==1"
							:style="{color:v.state==2?'#FF4C54':v.state==1?'#3E9949':v.state==3?'#2979ff':v.state==4?'#DC6523':''}">扫码</view>
					</view>
					<view v-if="v.state==1" class="p-0-15 f16 mb15">待下单</view>
					<view v-else-if="v.state!=0 && v.order && v.order.money" class="p-0-15 f16 mb15">￥{{v.order.money}}</view>
					<view class="p10 f-x-bt f14 bottom" style="background: rgba(#000,.3)">
						<view class="f-y-c">
							<text class="iconfont icon-wode" style="font-size: 14px;"></text>
							{{ v.state!=0 && v.people || 0 }}/{{v.type.max}}
						</view>
						<view v-if="v.state!=0 && v.minutes" class="nowrap f-y-c">
							<text class="iconfont icon-shalou" style="font-size: 14px;"></text>
							{{v.minutes}}分钟
						</view>
					</view>
				</view>
			</view>
			<view class="p-15-13 f-x-bt bs6 bf kinds">
				<view :class="kind==index?'isKind wei6':''" class="kind f16 tac" v-for="(item,index) in nav"
					:key="index" @click="changeKind(item,index)">
					<view :class="item.color"
						style="width: 12px;height:12px;border-radius: 3px;margin-right: 5px;border:1px solid #ddd">
					</view>
					<view class="">{{item.title}}</view>({{item.num}})
				</view>
			</view>
		</view>
		<!-- <cash ref="codeRef" :t='2' tx="开台" @changeMoney="confirm" /> -->
		<share ref="shareRef" @save="confirm" />
	</view>
</template>

<script>
	import {
		mapState,
		mapMutations,
	} from 'vuex'
	// import cash from '@/components/pay/cash.vue';
	import share from '../../table/components/share.vue';
	export default ({
		components: {
			// cash,
			share,
		},
		data() {
			return {
				tab: 0,
				kind: 0,
				select: '',
				selects: [{
					value: 0,
					text: "桌台操作"
				}],
				//empty空桌台 order待下单 account待结账 accounted已预结 cleared待清台
				nav: [{
					title: '全部',
					num: 0,
					color: 'bg0',
					state: '',
				}, {
					title: '空桌台',
					num: 0,
					color: 'bf',
					state: 'free',
				}, {
					title: '待下单',
					num: 0,
					color: 'b23',
					state: 'order',
				}, {
					title: '待结账',
					num: 0,
					color: 'bb3',
					state: 'settle',
				}, {
					title: '已预结',
					num: 0,
					color: 'bdb',
					state: 'prepare',
				}, {
					title: '待清台',
					num: 0,
					color: 'b2e',
					state: 'machine',
				}],
				act: 0,
				current: 0,
				list: [],
				tabs: [],
				tabelList: [],
				dataList: [],
				areaId: '',
				state: '',
				tabelConunt: {},
				form: {},
				value: 0,
			}
		},
		computed: {
			...mapState({
				handOver: state => state.handOver,
			}),
		},
		destroyed() {
			clearInterval(this.dsq)
		},
		methods: {
			init() {
				this.fetchData()
				this.dsq = setInterval(() => {
					if(this.tabs && this.tabs.length){
						this.getTableList()
						this.getTableConunt()
					}
				}, 3000)
			},
			async fetchData() {
				const {
					data: {
						list
					}
				} = await this.beg.request({
					url: this.api.tableArea,
					data: {
						pageSize: 999
					}
				})
				list.unshift({
					id: "",
					name: "全部"
				})
				this.tabs = list ? list : []
				if (list && list.length) {
					this.areaId = list[0].id
					this.getTableList()
					this.getTableConunt()
				}
			},
			async getTableList() {
				const {
					data: {
						list
					}
				} = await this.beg.request({
					url: this.api.inTabel,
					data: {
						areaId: this.areaId,
						state: this.state,
						pageSize: 999,
					}
				})
				this.tabelList = list ? list : []
			},
			async getTableConunt() {
				const {
					data
				} = await this.beg.request({
					url: this.api.tCount,
					data: {
						areaId: this.areaId,
						state: this.state,
					}
				})
				this.tabelConunt = data
				this.nav[0].num = data.allCount
				this.nav[1].num = data.freeCount
				this.nav[2].num = data.orderCount
				this.nav[3].num = data.settleCount
				this.nav[4].num = data.prepareCount
				this.nav[5].num = data.machineCount
			},
			changeTab(v, i) {
				this.tab = i
				this.areaId = v.id
				this.getTableList()
				this.getTableConunt()
			},
			clickItem(v, i) {
				if(!this.handOver.id){
					return this.$emit('openOver')
				}
				this.form = v
				if (v.state == 0 && v.diningType == 4) {
					this.value = v.type.max
					this.$refs['shareRef'].open('open', v)
				} else if (v.state == 1 || v.state == 0 && v.diningType == 5) {
					uni.reLaunch({
						url: `/pages/table/index?id=${this.form.id}`
					})
				} else if (v.state == 2 || v.state == 3 || v.state == 4) {
					uni.navigateTo({
						url: `/pages/table/orderPay?id=${this.form.orderSn}`
					})
				}
				this.clear()
				// uni.reLaunch({
				// 	url: `/pages/table/index?id=${v.id}&name=${v.name}&num=${v.people}`
				// })
			},
			changeKind(v, i) {
				this.kind = i
				this.state = v.state
				this.getTableList()
				this.getTableConunt()
			},
			async confirm(e) {
				await this.beg.request({
					url: `${this.api.inTabel}/${this.form.id}`,
					method: 'PUT',
					data: {
						people: +e
					}
				})
				this.$refs['shareRef'].close()
				uni.navigateTo({
					url: `/pages/table/index?id=${this.form.id}`
				})
				this.fetchData()
				// if (+e) {
				// 	await this.beg.request({
				// 		url: `${this.api.inTabel}/${this.form.id}`,
				// 		method: 'PUT',
				// 		data: {
				// 			people: +e
				// 		}
				// 	})
				// 	this.$refs['shareRef'].close()
				// 	uni.navigateTo({
				// 		url: `/pages/table/index?id=${this.form.id}`
				// 	})
				// 	this.fetchData()
				// } else {
				// 	this.$refs['shareRef'].close()
				// 	uni.$u.toast('请输入正确就餐人数！');
				// }
			},
			clear(){
				clearInterval(this.dsq)
			}
		}
	})
</script>

<style lang="scss" scoped>
	.right {
		position: relative;

		.tabs {
			// width: 380px;
			height: 40px;
			line-height: 38px;
			background: #fff;
			color: #000;

			.tab {
				display: inline-block;
				width: 6.2215vw;
			}

			.istab {
				background: #4275F4;
				color: #fff;
			}
		}

		/deep/.uni-select {
			height: 40px;
			background: #fff;

			.uni-select__input-placeholder {
				font-size: 18px !important;
			}

			.uni-select__selector-item {
				font-size: 18px !important;
			}

			.uni-select__input-text {
				font-size: 18px !important;
			}
		}

		.tables {
			max-height: calc(100vh - 130px);
			padding-bottom: 70px;
			overflow-y: auto;
		}

		.table {
			display: inline-flex;
			// width: 166px;
			// height: 160px;
			width: 12.0058vw;
			height: 20.8333vh;
			border-radius: 10px;

			.bottom {
				background-color: rgba(#000, .1);
				border-bottom-left-radius: 10px;
				border-bottom-right-radius: 10px;
			}
		}

		.kinds {
			position: absolute;
			bottom: 20px;
			left: 50%;
			transform: translateX(-50%);
			box-shadow: 0px 0px 10px 5px rgba(#000, .1);

			.kind {
				display: inline-flex;
				align-items: center;
				justify-content: center;
				width: 130px;
				height: 35px;
				border-radius: 5px;
			}

			.isKind {
				background: #d5d5d9;
			}
		}

		.sm {
			background: #fff;
			padding: 2rpx 6rpx;
			border-radius: 6rpx;
			height: 24px;
			white-space: nowrap;
		}
	}

	@media (min-width: 1500px) and (max-width: 3280px) {
		.right {
			.tabs {
				.tab {
					width: 100px;
				}
			}

			.table {
				width: 164px;
				height: 160px;
			}
		}
	}
</style>
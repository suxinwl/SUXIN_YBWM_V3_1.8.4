<template>
	<view class="f-y-bt w100v h100v o-h">
		<view class="top bf f-x-bt p15">
			<view class="f24 wei">{{title}}</view>
			<tool></tool>
		</view>
		<view class="right f-1 p10 l-h1" style="background: #eff0f4;">
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
					:class="v.state==1?'b23 cd':v.state==2?'bb3 cd':v.state==4?'bdb cd':v.state==3?'b2e cd':'bf c0'"
					v-for="(v,i) in tabelList" :key="i" @click="clickItem(v,i)">
					<view class="f-bt">
						<view class="p-0-15 f18 mb15">{{v.type.name}}{{v.name}}</view>
						<view class="p-0-15 sm f14 mr10" v-if="v.scan==1"
							:style="{color:v.state==2?'#C24243':v.state==1?'#3E9949':v.state==3?'#2979ff':v.state==4?'#DC6523':''}">
							扫码</view>
					</view>
					<view v-if="v.state==1" class="p-0-15 f18 mb15">待下单</view>
					<view v-else-if="v.order && v.order.money" class="p-0-15 f18 mb15">￥{{v.order.money}}</view>
					<view class="p10 f-x-bt f14 bottom" style="background: rgba(#000,.3)">
						<view>
							<text class="iconfont icon-wode" style="font-size: 14px;"></text>
							{{ v.people || 0 }}/{{v.type.max}}
						</view>
						<view v-if="v.minutes" class="nowrap">
							<text class="iconfont icon-shalou" style="font-size: 14px;"></text>
							{{v.minutes}}分钟
						</view>
						<view class="mode f-c-c" v-if="form.id == v.id">
							<view class="cf f18">{{state=='free' ? '待转入' : '合并至'}}</view>
							<view class="r_gou"></view>
							<text class="iconfont icon-duigou cf"></text>
						</view>
					</view>
				</view>
			</view>
			<view class="p-5-10 f-x-bt bs6 bf kinds f20">
				<view class="">请选择要{{state=='free' ? '转入' : '合并'}}的桌台</view>
				<view class="dfac">
					<u-button class="mr15" text="取消" :customStyle="{width:'100px'}" @click="cancle"></u-button>
					<u-button color="#4275F4" :disabled="!form.id" :customStyle="{width:'100px'}" @click="confirm"><text
							class="cf">确定</text></u-button>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	import tool from '@/components/tool/tool.vue'
	export default {
		components: {
			tool,
		},
		data() {
			return {
				tab: 0,
				kind: 0,
				list: [],
				tabs: [],
				tabelList: [],
				dataList: [],
				areaId: '',
				title: '转台',
				state: 'free',
				form: {},
				toTable: 0,
				id: 0,
				t: 'turntable',
			}
		},
		onLoad(option) {
			if (option && option.id) {
				this.id = option.id
				this.t = option.t
				this.init()
			}
		},
		methods: {
			init() {
				if (this.t == 'turntable') {
					this.title = '转台'
					this.state = 'free'
					this.fetchData()
				} else if (this.t == 'parallel') {
					this.title = '并台'
					this.state = 'settle'
					this.fetchData()
				}
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
				if (this.tabelList && this.tabelList.length) {
					const i = this.tabelList.findIndex(v => v.id == this.id)
					if (i >= 0) {
						this.tabelList.splice(i, 1)
					}
				}
			},
			changeTab(v, i) {
				this.tab = i
				this.areaId = v.id
				this.getTableList()
			},
			clickItem(v, i) {
				this.form = v
				this.toTable = v.id
			},
			changeKind(v, i) {
				this.kind = i
				this.state = v.state
				this.getTableList()
			},
			cancle() {
				uni.navigateBack({
					delta: 1
				})
			},
			async confirm() {
				let {
					code,
					msg
				} = await this.beg.request({
					url: `${this.t=='parallel'?this.api.combineTable:this.api.changeTable}/${this.id}`,
					method: 'POST',
					data: {
						storeId: this.form.storeId,
						toTable: this.toTable
					}
				})
				uni.showToast({
					title: msg,
					icon: 'none'
				})
				if (code && code == 200) {
					this.toTable = 0
					setTimeout(() => {
						uni.reLaunch({
							url: `/pages/home/index?current=1`
						})
					}, 500)
				}
			}
		}
	}
</script>

<style lang="scss" scoped>
	.top {
		height: 110rpx;
	}

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
				font-size: 16px !important;
			}

			.uni-select__selector-item {
				font-size: 16px !important;
			}

			.uni-select__input-text {
				font-size: 16px !important;
			}
		}

		.tables {
			max-height: calc(100vh - 130px);
			overflow-y: auto;
		}

		.table {
			position: relative;
			display: inline-flex;
			width: 12.0058vw;
			height: 20.8333vh;
			border-radius: 10px;

			.bottom {
				background-color: rgba(#000, .1);
				border-bottom-left-radius: 10px;
				border-bottom-right-radius: 10px;
			}

			.mode {
				position: absolute;
				top: 0;
				left: 0;
				width: 12.0058vw;
				height: 20.8333vh;
				border-radius: 10px;
				border: 5px solid #4275F4;
				background: rgba(#000, .7);

				.r_gou {
					position: absolute;
					top: -1px;
					right: -1px;
					width: 0;
					height: 0;
					border-top: 25px solid #4275F4;
					border-right: 25px solid #4275F4;
					border-left: 25px solid transparent;
					border-bottom: 25px solid transparent;
				}

				.icon-duigou {
					position: absolute;
					top: 4px;
					right: 4px;
					font-size: 18px;
				}
			}
		}

		.kinds {
			position: absolute;
			bottom: 20px;
			left: 50%;
			transform: translateX(-50%);
			width: 50%;
			box-shadow: 0px 0px 10px 5px #eee;

			.kind {
				display: inline-flex;
				align-items: center;
				justify-content: center;
				width: 110px;
				height: 28px;
				border-radius: 5px;
			}

			.isKind {
				background: #d5d5d9;
			}
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

				.mode {
					width: 164px;
					height: 160px;
				}
			}
		}
	}
</style>
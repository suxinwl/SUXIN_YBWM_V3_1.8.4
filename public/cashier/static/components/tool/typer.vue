<template>
	<u-overlay :show="isType" @click="close">
		<view class="typer f-y-bt bf bs10 p20" @tap.stop>
			<view class="f-x-bt f18">
				<view class="">打印机状态</view>
				<view class="c6 f16" @click="printCt">打印机管理 ></view>
			</view>
			<view class="f-1 lwrap mt10" v-if="list&&list.length>0">
				<view class="list p10 mb10" :class="current == i ? 'lcur':''" v-for="(row,i) in list" :key="i"
					@click="clickItem(row,i)">
					<view class="f-bt">
						<view class="f-g-1">{{ row.config && row.config.name }}</view>
						<view class="f-g-1 f-x-e">
							<u-switch v-model="row.display" :inactiveValue="2" :activeValue="1" @change="changePri(row)"
								size="20" activeColor="#4275F4"></u-switch>
						</view>
					</view>
					<view class="c6 flex f14 mt5">
						设备类型：
						<view class="flex">
							<view v-if="row.type == 1">云小票打印机</view>
							<view v-if="row.type == 2">云标签打印机</view>
							<view v-if="row.type == 3">云语音音箱</view>
						</view>
					</view>
					<view class="c6 flex f14 mt5">
						设备SN号：
						<view class="flex">
							<view v-if="row.vendor == 'esLink'">{{ row.config && row.config.ylyNum }}</view>
							<view v-if="row.vendor == 'feie'">{{ row.config && row.config.feNum }}</view>
							<view v-if="row.vendor == 'spyun'">
								{{ row.config && row.config.spySn }}
							</view>
							<view v-if="row.vendor == 'daqu'">
								{{ row.config && row.config.daquSn }}
							</view>
							<view v-if="row.vendor == 'jiabo'">
								{{ row.config && row.config.jiabodeviceID }}
							</view>
							<view v-if="row.vendor == 'xinye'">
								{{ row.config && row.config.xinyeNo }}
							</view>
							<view v-if="row.vendor == 'yunlaba'">
								{{ row.config && row.config.sn }}
							</view>
						</view>
					</view>
					<view class="c6 flex f14 mt5">
						设备用途：
						<view v-if="row.rule && row.rule.scene.includes(1)">
							外送/店内
						</view>
					</view>
					<view class="c6 f-bt f14 mt5 f-y-c">
						<view class="flex">
							<view class="flex">
								<view v-if="row.vendor == 'esLink'">易联云</view>
								<view v-if="row.vendor == 'spyun'">商鹏云</view>
								<view v-if="row.vendor == 'daqu'">大趋云</view>
								<view v-if="row.vendor == 'jiabo'">佳博云</view>
								<view v-if="row.vendor == 'xinye' && row.type == 1">芯烨小票</view>
								<view v-if="row.vendor == 'xinye' && row.type == 2">芯烨标签</view>
								<view v-if="row.vendor == 'feie' && row.type == 1">飞鹅</view>
								<view v-if="row.vendor == 'feie' && row.type == 2">飞鹅标签</view>
								<view v-if="row.vendor == 'yunlaba' && row.type == 3">云喇叭</view>
								/
							</view>
							<view v-if="row.vendor == 'feie' && row.config && row.config.printer_size == 1">58mm
							</view>
							<view v-else-if="row.vendor == 'feie' && row.config &&row.config.printer_size == 2">
								80mm
							</view>
							<view v-else>--</view>
						</view>
						<view class="f14">
							<image v-if="row.type==1" src="@/static/imgs/rec3.jpg" mode="widthFix" class="icon">
							</image>
							<image v-if="row.type==2" src="@/static/imgs/rec1.jpg" mode="widthFix" class="icon">
							</image>
							<image v-if="row.type==3" src="@/static/imgs/rec2.jpg" mode="widthFix" class="icon">
							</image>
						</view>
					</view>
				</view>
				<view class="bf p-10-0 l_bot f-g-0">
					<uni-pagination :current="queryForm.pageNo" :total="total" @change="change" showIcon />
				</view>
			</view>
			<view class="f-1 f-c-c" v-else>
				<view class="c9 mb15">尚无打印机,请先创建</view>
				<view class=""><u-button text="去创建"
						:customStyle="{width:'200px',border:'1px solid #bcbcbc',borderRadius:'6px'}"
						@click="printCt"></u-button>
				</view>
			</view>
		</view>
	</u-overlay>
</template>

<script>
	export default {
		props: {

		},
		data() {
			return {
				isType: false,
				queryForm: {
					pageNo: 1,
					pageSize: 10,
				},
				total: 0,
				list: [],
				current: 0,
				isItem: 0,
				itemForm: {},
			}
		},
		methods: {
			open() {
				this.fetchData()
				this.isType = true
			},
			close() {
				this.isType = false
			},
			async fetchData() {
				let {
					data: {
						list,
						pageNo,
						pageSize,
						total
					},
				} = await this.beg.request({
					url: this.api.hardware,
					data: this.queryForm,
				})
				this.list = list ? list : []
				this.total = total
			},
			clickItem(item, index) {
				this.isItem = item.id
				this.current = index
			},
			change(e) {
				this.queryForm.pageNo = e.current;
				this.fetchData()
			},
			async changePri(v){
				let {
					msg
				} = await this.beg.request({
					url: this.api.changePrint,
					method: "POST",
					data:{
						id:v.id
					}
				})
				this.fetchData()
				uni.$u.toast(msg)
			},
			printCt() {
				this.$emit('cT', {
					id: 13,
					icon: 'icon-dayin',
					name: '硬件管理'
				}, 13)
				this.isType = false
			},
		}
	}
</script>

<style lang="scss" scoped>
	.lwrap {
		height: 74.5098vh;
		overflow: hidden;
		overflow-y: scroll;

		.list {
			background: #fff;
			border: 2px solid #EBEAF0;
			border-left: 6px solid #4275F4;
			border-radius: 10px;

			.icon {
				width: 30px;
				height: 30px;
				border-radius: 50%;
			}
		}

		.lcur {
			background: #E3EDFE;
			border: 2px solid #4275F4;
			// border-left-width: 6px;
			border-color: #4275F4;
		}
	}

	/deep/.u-popup__content {
		background: none;
	}

	.typer {
		position: fixed;
		right: 0;
		width: 500px;
		height: 100vh;
	}
</style>
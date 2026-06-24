<template>
	<view class="page h100 bf pr10">
		<view class="f-bt f-y-c">
			<u--form labelPosition="left" :model="queryForm" ref="uForm" labelWidth="100px" labelAlign="right"
				:labelStyle="{fontSize:'14px'}">
				<u-form-item label="订单渠道：" prop="diningType" ref="item1">
					<view style="width:193px">
						<uni-data-select v-model="queryForm.diningType" :localdata="channels" placeholder="请选择订单渠道"
							@change="handDiningType"></uni-data-select>
					</view>
				</u-form-item>
				<u-form-item label="订单来源：" prop="source" ref="item1">
					<view style="width:193px">
						<uni-data-select v-model="queryForm.source" :localdata="classfiy" placeholder="请选择订单来源"
							@change="handSource"></uni-data-select>
					</view>
				</u-form-item>
				<u-form-item label="下单时间：" prop="timeType" ref="item1">
					<view style="width:193px">
						<uni-data-select v-model="queryForm.timeType" :localdata="dates" placeholder="请选择下单时间"
							@change="handDate" :clear="false"></uni-data-select>
					</view>
				</u-form-item>
			</u--form>
			<view class="mr20">
				<view class="rf cf f-c curs" @click="rfFetchData"><text class="iconfont icon-shuaxin f22"></text></view>
			</view>
		</view>
		<view class="f-1 main p-10-0">
			<view class="f-y-bt h100">
				<view v-if="callList && callList.length">
					<view class="iList bf f-y-bt ml10 mb10" v-for="(v,i) in callList" :key="i">
						<view class="p20 top f-y-bt" @click="handDl(v)">
							<view class="f-bt f-y-c">
								<view class="flex f-y-c">
									<view class="f26 wei">{{v.pickNo}}</view>
									<view class="ml10">
										<view class="tag f-c f14" v-if="v.packaging==0">堂</view>
										<view class="tag f-c f14" v-if="v.packaging==1" style="background: #4275F4;">带
										</view>
									</view>
									<view class="ml10 f14" v-if="v.orderIndex">
										<text class="iconfont icon-huabanfuben f24" v-if="v.orderIndex.source ==11"></text>
										<text class="iconfont icon-shouyintai  f24" v-if="v.orderIndex.source ==10"></text>
										<text class="iconfont icon-weixinxiaochengxu f24" v-if="v.orderIndex.source ==1"></text>
									</view>
								</view>
								<view class="f16">共{{v.orderIndex && v.orderIndex.goodsNum}}件</view>
							</view>
							<view class="f-bt f16">
								<view>
									<view v-if="v.orderIndex && v.orderIndex.appointment ==1" class="mb5">预计取单：<text style="color: #4275F4;">{{v.orderIndex && v.orderIndex.serverTimeFormat}}</text></view>
									{{v.orderIndex && v.orderIndex.appointment ==1 ? '预约单':'即时单'}}/{{v.orderIndex && v.orderIndex.stateFormat}}
								</view>
								<view class="flex f-y-c l-h1" v-if="v.state!=6">
									<text class="iconfont icon-shalou mr5" style="font-size: 16px;"></text>
									{{v.minutes}}分
								</view>
							</view>
						</view>
						<view class="f-bt f-y-e">
							<u-button color="#1c9945" @click="startMark(v)" v-if="v.state==3"><text
									class="cf">制作完成</text></u-button>
							<u-button color="#4275F4" @click="call(v)" v-if="v.state==4"><text
									class="cf">通知取单</text></u-button>
							<u-button @click="call(v)" v-if="v.state==6"><text class="c0">再次通知</text></u-button>
							<u-button @click="qc(v)" v-if="v.state==4"><text class="c0">取餐完成</text></u-button>
						</view>
					</view>
				</view>
				<view v-if="callList.length==0" class="f-c f-g-1">
					<!-- <u-empty mode="data"></u-empty> -->
					<empty txt="暂无订单" t="jh" />
				</view>
				<view class="f-c pt15 batch pagona">
					<uni-pagination :current="queryForm.pageNo" :total="total" :pageSize="queryForm.pageSize" @change="change" title="标题文字" />
				</view>
			</view>
		</view>
		<rightdow ref="rightDowRef" @fetchData="fetchData" />
	</view>
</template>

<script>
	import rightdow from './callOrder/rightdow.vue';
	import empty from '@/components/other/empty.vue';
	export default ({
		components: {
			rightdow,
			empty,
		},
		data() {
			return {
				loading: false,
				current: 0,
				callList: [],
				queryForm: {
					pageNo: 1,
					pageSize: 20,
					state: 'all',
					diningType: '',
					source: '',
					timeType: 2,
				},
				total: 0,
				channels: [{
						value: '',
						text: '全部渠道'
					},
					{
						value: 'ziti',
						text: '自提订单'
					},
					{
						value: 'kuaican',
						text: '快餐订单'
					}
				],
				classfiy: [{
						value: '',
						text: '全部来源'
					},
					{
						value: 1,
						text: '微信小程序'
					},
					{
						value: 10,
						text: '收银台'
					},
					{
						value: 11,
						text: '门店助手'
					},
				],
				show: false,
				dates: [{
						value: 2,
						text: '今日'
					},
					{
						value: -1,
						text: '昨日'
					},
					{
						value: 7,
						text: '7日内'
					}
				],
			}
		},
		methods: {
			init() {
				this.fetchData()
			},
			handTabs(e) {
				this.queryForm.state = e.value
				this.fetchData()
			},
			async fetchData() {
				const {
					data: {
						list,
						total,
					}
				} = await this.beg.request({
					url: this.api.takeScreen,
					data: this.queryForm
				})
				this.callList = list ? list : [],
					this.total = total
			},
			handDiningType(e) {
				this.queryForm.diningType = e
				this.fetchData()
			},
			handSource(e) {
				this.queryForm.source = e
				this.fetchData()
			},
			handDate(e) {
				this.queryForm.timeType = e
				this.fetchData()
			},
			async rfFetchData(){
				uni.showLoading({
					title: 'loading...'
				})
				await this.fetchData()
				uni.hideLoading()
			},
			change(e) {
				this.queryForm.pageNo = e.current;
				this.fetchData()
			},
			async startMark(v) {
				const {
					msg
				} = await this.beg.request({
					url: `${this.api.qcMaked}/${v.id}`,
					method: "POST",
				})
				uni.$u.toast(msg)
				this.fetchData()
			},
			async call(v) {
				const {
					msg
				} = await this.beg.request({
					url: `${this.api.call}/${v.id}`,
					method: "POST",
				})
				uni.$u.toast(msg)
				this.fetchData()
			},
			async qc(v) {
				const {
					msg
				} = await this.beg.request({
					url: `${this.api.qcComplete}/${v.id}`,
					method: "POST",
				})
				uni.$u.toast(msg)
				this.fetchData()
			},
			handDl(v) {
				this.$refs['rightDowRef'].open(v)
			},
		}
	})
</script>

<style lang="scss" scoped>
	.page {
		.main {
			height: calc(100vh - 18.2291vh);
			overflow: hidden;
			overflow-y: scroll;

			.iList {
				display: inline-flex;
				width: 30.2342vw;
				height: 26.0416vh;
				box-shadow: 0px 2px 10px 4px #ddd;
				border-radius: 8px;

				.top {
					height: 20.8333vh;
				}
			}
		}

		.tag {
			width: 22px;
			height: 22px;
			color: #fff;
			border-radius: 6px;
			background: #1c9945;
		}

		/deep/.u-form {
			display: flex !important;
			flex-wrap: wrap;

			.u-input {
				background: #fff;

				.input-placeholder,
				.uni-input-input {
					font-size: 16px;
				}
			}

			.uni-select {
				height: 38px !important;
				background: #fff;

				.uni-select__input-placeholder {
					font-size: 16px !important;
					color: #ccc;
				}

				.uni-select__selector-item {
					span {
						font-size: 16px;
					}
				}
			}
		}
		
		.pagona {
			/deep/.uni-pagination {
				.page--active {
					display: inline-block;
					width: 2.1961vw;
					height: 3.9062vh;
					background: #4275F4 !important;
					color: #fff !important;
				}
		
				.is-phone-hide {
					width: 2.1961vw;
					height: 3.9062vh;
				}
		
				.uni-pagination__total {
					font-size: 1.1641vw;
					width: auto;
					display: -webkit-box;
					display: -webkit-flex;
					display: flex;
					align-items: center;
				}
		
				span {
					font-size: 1.1641vw;
				}
			}
		}

		.u-popup {
			flex: 0;
		}
		.rf{
			background: #a5a5a5;
			width: 30px;
			height: 30px;
			border-radius: 6px;
		}
	}

	@media (min-width: 1500px) and (max-width: 3280px) {
		.page {
			.main {
				height: calc(100vh - 140px);

				.iList {
					width: 413px;
					height: 200px;

					.top {
						height: 160px;
					}
				}
			}
			.pagona {
			
				/deep/.uni-pagination {
					.page--active {
						width: 30px;
						height: 30px;
					}
			
					.is-phone-hide {
						width: 30px;
						height: 30px;
					}
			
					.uni-pagination__total {
						font-size: 20px;
						width: auto;
					}
			
					span {
						font-size: 20px;
					}
				}
			}
		}
	}
</style>
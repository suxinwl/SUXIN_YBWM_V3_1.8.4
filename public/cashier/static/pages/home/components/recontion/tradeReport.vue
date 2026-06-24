<template>
	<view class="f-y-bt h100 bf p10 tradeReport" style="padding-top: 0;">
		<view class="f-bt f-y-c p10">
			<view class="search">
				<view class="tabs flex f-y-c">
					<view class="itab p-10-20 mr10 f16 c0" :class="{'ctab' : tab == i}" v-for="(v,i) in tabs" :key="i"
						@click="changeTab(v,i)">
						{{v.name}}
					</view>
					<view style="width:300px" v-if="queryForm.timeType==1">
						<uni-datetime-picker v-model="range" type="datetimerange" @change="datetimechange" />
					</view>
				</view>
			</view>
		</view>
		<view class="main f-1 f16 pb40">
			<view class="top p20">
				<view class="f-bt">
					<view class="f-g-1 p-0-20">
						<view class="f18">营业额(元)</view>
						<view class="f-bt mt20">
							<view class="wei">
								<text class="f24">￥</text>
								<text class="f45">{{newData.sellMoney || 0}}</text>
							</view>
						</view>
					</view>
					<view class="f-g-1 p-0-20">
						<view class="f18">营业收入(元)</view>
						<view class="f-bt mt20">
							<view class="wei">
								<text class="f24">￥</text>
								<text class="f45">{{newData.money || 0}}</text>
							</view>
						</view>
					</view>
				</view>
				<view class="cashes mt20">
					<view class="c_item p15 bf mr15 mb15" v-for="(item,index) in cashes" :key="index">
						<view class="mb10">{{item.title}}</view>
						<view class="wei6 f32">{{item.num}}</view>
						<view class="f14 c9 mt10" v-if="item.number>=0">{{item.nName}} ：{{item.number}}</view>
					</view>
				</view>
			</view>
			<view class="topList mt20">
				<uni-table ref="table" border stripe emptyText="暂无明细">
					<uni-tr>
						<uni-th align="center">来源</uni-th>
						<uni-th align="center">营业额</uni-th>
						<uni-th align="center">营业收入</uni-th>
						<uni-th align="center">支出金额</uni-th>
						<uni-th align="center">有效订单数</uni-th>
						<uni-th align="center">退款订单数</uni-th>
					</uni-tr>
					<uni-tr v-for="(row, i) in summary" :key="i">
						<uni-td align="center">{{ row.name }}</uni-td>
						<uni-td align="center">{{ row.sellMoney }}</uni-td>
						<uni-td align="center">{{ row.money }}</uni-td>
						<uni-td align="center">{{ row.discountMoney }}</uni-td>
						<uni-td align="center">{{ row.orderCount }}</uni-td>
						<uni-td align="center">{{ row.refundOrder }}</uni-td>
					</uni-tr>
				</uni-table>
			</view>
			<view class="mt20 tj">
				<view class="f-bt">
					<view class="tjTab flex">
						<view class="tjTabs p-10-20 f16 c0" :class="{'xztab' : tjtabs == i}" v-for="(v,i) in tjTab"
							:key="i" @click="changeCTab(v,i)">
							{{v.name}}
						</view>
					</view>
				</view>
				<view class="f-bt mt20">
					<view class="f-g-1 w50" v-if="tjv!=5">
						<view class="chartsBox1" v-if="tjv==1 && optbox1.subtitle.name>0">
							<qiun-data-charts type="ring" :opts="optbox1" :chartData="chartData1" />
						</view>
						<view class="chartsBox1" v-else-if="tjv==2">
							<qiun-data-charts type="pie" :opts="optbox2" :chartData="chartData2" />
						</view>
						<view class="chartsBox1" v-else-if="tjv==3">
							<qiun-data-charts type="pie" :opts="optbox2" :chartData="chartData3" />
						</view>
						<view class="chartsBox1" v-else-if="tjv==4">
							<qiun-data-charts type="pie" :opts="optbox2" :chartData="chartData4" />
						</view>
						<view class="chartsBox1" v-else-if="tjv==6">
							<qiun-data-charts type="pie" :opts="optbox2" :chartData="chartData6" />
						</view>
					</view>
					<view class="f-g-1 w50">
						<uni-table ref="table" border stripe emptyText="暂无明细" v-if="tjv==1">
							<uni-tr>
								<uni-th align="center">支付方式</uni-th>
								<uni-th align="center">营业收入</uni-th>
								<uni-th align="center">支付笔数</uni-th>
							</uni-tr>
							<uni-tr v-for="(row, i) in payTrend" :key="i">
								<uni-td align="center">{{ row.name }}</uni-td>
								<uni-td align="center">{{ row.money }}</uni-td>
								<uni-td align="center">{{ row.orderCount }}</uni-td>
							</uni-tr>
						</uni-table>
						<uni-table ref="table" border stripe emptyText="暂无明细" v-if="tjv==2">
							<uni-tr>
								<uni-th align="center">支出方式</uni-th>
								<uni-th align="center">支出金额</uni-th>
								<uni-th align="center">支出笔数</uni-th>
							</uni-tr>
							<uni-tr v-for="(row, i) in discountTrend" :key="i">
								<uni-td align="center">{{ row.name }}</uni-td>
								<uni-td align="center">{{ row.discountMoney }}</uni-td>
								<uni-td align="center">{{ row.orderCount }}</uni-td>
							</uni-tr>
						</uni-table>
						<uni-table ref="table" border stripe emptyText="暂无明细" v-if="tjv==3">
							<uni-tr>
								<uni-th align="center">订单类型</uni-th>
								<uni-th align="center">营业收入</uni-th>
								<uni-th align="center">有效订单</uni-th>
							</uni-tr>
							<uni-tr v-for="(row, i) in orderTrend" :key="i">
								<uni-td align="center">{{ row.name }}</uni-td>
								<uni-td align="center">{{ row.money }}</uni-td>
								<uni-td align="center">{{ row.orderCount }}</uni-td>
							</uni-tr>
						</uni-table>
						<uni-table ref="table" border stripe emptyText="暂无明细" v-if="tjv==4">
							<uni-tr>
								<uni-th align="center">类别名称</uni-th>
								<uni-th align="center">商品销量</uni-th>
								<uni-th align="center">商品销售额</uni-th>
							</uni-tr>
							<uni-tr v-for="(row, i) in goodsCat" :key="i">
								<uni-td align="center">{{ row.name }}</uni-td>
								<uni-td align="center">{{ row.sales }}</uni-td>
								<uni-td align="center">{{ row.money }}</uni-td>
							</uni-tr>
						</uni-table>
						<view class="charts-box" v-if="tjv==5">
							<qiun-data-charts type="line" :opts="opts" :chartData="chartData5" />
						</view>
						<uni-table ref="table" border stripe emptyText="暂无明细" v-if="tjv==6">
							<uni-tr>
								<uni-th align="center">订单类型</uni-th>
								<uni-th align="center">营业收入</uni-th>
								<uni-th align="center">订单数</uni-th>
							</uni-tr>
							<uni-tr v-for="(row, i) in sellOut" :key="i">
								<uni-td align="center">{{ row.name }}</uni-td>
								<uni-td align="center">{{ row.money }}</uni-td>
								<uni-td align="center">{{ row.orderCount }}</uni-td>
							</uni-tr>
						</uni-table>
					</view>
				</view>
			</view>
		</view>
		<u-calendar :show="showCalendar" color="#4275F4" mode="range" @confirm="confirm"
			@close="showCalendar=false" :minDate="calendar.minDate"></u-calendar>
	</view>
</template>

<script>
	export default ({
		components: {},
		data() {
			return {
				tab: 0,
				tabs: [{
						name: '今日',
						value: 2,
					},
					{
						name: '昨日',
						value: -1,
					},
					{
						name: '7日内',
						value: 7,
					},
					{
						name: '15日内',
						value: 15,
					},
					{
						name: '30日内',
						value: 30,
					},
					{
						name: '自定义',
						value: 1,
					}
				],
				cashes: [{
						title: '支出金额(元)',
						num: '0',
					}, {
						title: '有效订单数(笔)',
						num: '0',
					},
					// {
					// 	title: '新增会员数(人)',
					// 	num: '0',
					// },
					{
						title: '储值金额(元)',
						num: '0.00',
						nName: '储值订单数(笔)',
						number: '0',
					}, {
						title: '退款金额(元)',
						num: '0.00',
						nName: '退款订单数(笔)',
						number: '0',
					}, {
						title: '包装费(元)',
						num: 0,
					}, {
						title: '配送费(元)',
						num: '0',
					}, {
						title: '营业收入(含服务费)(元)',
						num: 0,
					}
				],
				summary: [],
				payTrend: [],
				discountTrend: [],
				sellOut: [],
				orderTrend: [],
				goodsCat: [],
				topList6: [],
				tbloading: false,
				time: [],
				range: [],
				queryForm: {
					timeType: 2,
					scene: '',
					pageNo: 1,
					pageSize: 10,
					// groupBySpec: 'spuId',
				},
				total: 0,
				showCalendar: false,
				tjtabs: 0,
				tjv: 1,
				tjTab: [{
						name: '支付渠道',
						value: 1,
					},
					{
						name: '支出统计',
						value: 2,
					},
					{
						name: '订单统计',
						value: 3,
					},
					{
						name: '商品分类',
						value: 4,
					},
					{
						name: '时段统计',
						value: 5,
					},
					{
						name: '营业外金额',
						value: 6,
					}
				],
				optbox1: {
					rotate: false,
					rotateLock: false,
					color: ["#4275F4", "#91CB74", "#FAC858", "#EE6666", "#73C0DE", "#3CA272", "#FC8452", "#9A60B4",
						"#ea7ccc"
					],
					padding: [5, 5, 5, 5],
					dataLabel: true,
					enableScroll: false,
					legend: {
						show: true,
						position: "right",
						lineHeight: 25
					},
					title: {
						name: "营业收入",
						fontSize: 15,
						color: "#666666"
					},
					subtitle: {
						name: "0",
						fontSize: 25,
						color: "#7cb5ec"
					},
					extra: {
						ring: {
							ringWidth: 60,
							activeOpacity: 0.5,
							activeRadius: 10,
							offsetAngle: 0,
							labelWidth: 15,
							border: false,
							borderWidth: 3,
							borderColor: "#FFFFFF"
						}
					}
				},
				chartData1: {
					series: [{
						data: [{name: '暂无数据',value: 0}]
					}]
				},
				optbox2: {
					color: ["#4275F4", "#91CB74", "#FAC858", "#EE6666", "#73C0DE", "#3CA272", "#FC8452", "#9A60B4",
						"#ea7ccc"
					],
					padding: [5, 5, 5, 5],
					enableScroll: false,
					extra: {
						pie: {
							activeOpacity: 0.5,
							activeRadius: 10,
							offsetAngle: 0,
							labelWidth: 15,
							border: false,
							borderWidth: 3,
							borderColor: "#FFFFFF"
						}
					}
				},
				chartData2: {
					series: [{
						data: [{name: '暂无数据',value: 0}]
					}]
				},
				chartData3: {
					series: [{
						data: [{name: '暂无数据',value: 0}]
					}]
				},
				chartData4: {
					series: [{
						data: [{name: '暂无数据',value: 0}]
					}]
				},
				chartData5: {},
				chartData6: {
					series: [{
						data: [{name: '暂无数据',value: 0}]
					}]
				},
				chartData: {},
				opts: {
					color: ["#4275F4", "#91CB74", "#FAC858", "#EE6666", "#73C0DE", "#3CA272", "#FC8452", "#9A60B4",
						"#ea7ccc"
					],
					padding: [15, 10, 0, 15],
					enableScroll: false,
					legend: {},
					xAxis: {
						rotateLabel: true
					},
					yAxis: {
						gridType: "dash",
						dashLength: 2
					},
					extra: {
						line: {
							type: "straight",
							width: 2,
							activeType: "hollow"
						},
						area: {
							type: 'curve',
							opacity: 0.2,
							addLine: true,
							width: 2,
							gradient: true
						}
					},
				},
				newData: {},
				 calendar: {
					minDate: '',
					maxDate: '',
					defaultDate: '',
					monthNum: 13,
				},
			}
		},
		methods: {
			fetchData() {
				this.fetchNewOrder()
				this.chooseTimed()
			},
			async fetchNewOrder() {
				let {
					data
				} = await this.beg.request({
					url: this.api.sNewOrder,
					data: this.queryForm
				})
				this.newData = data
				this.cashes[0].num = data.discountMoney
				this.cashes[1].num = data.orderCount
				// this.cashes[2].num = data.newMember
				this.cashes[2].num = data.storedValueMoney
				this.cashes[2].number = data.storedValueOrder
				this.cashes[3].num = data.refundMoney
				this.cashes[3].number = data.refundOrder
				this.cashes[4].num = data.boxMoney
				this.cashes[5].num = data.deliveryMoney
				this.cashes[6].num = data.money
				this.summary = data.summary
				this.payTrend = data.payTrend
				this.discountTrend = data.discountTrend
				this.sellOut = data.sellOut
				this.orderTrend = data.orderTrend
				this.goodsCat = data.goodsCat
				this.getServerData(data)
			},
			changeTab(v, i) {
				this.queryForm.pageNo = 1
				this.tab = i
				this.queryForm.timeType = v.value
				// if (v.value == 1) return this.showCalendar = true
				this.queryForm.startTime = ''
				this.queryForm.endTime = ''
				this.fetchData()
			},
			sdData() {
				setTimeout(() => {
					let res = {
						categories: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
						series: [{
								name: "订单支付金额",
								data: [11, 12, 13, 14, 15, 16, 17, 18, 19, 20]
							},

						]
					};
					this.chartData = JSON.parse(JSON.stringify(res));
				}, 500);
			},
			getServerData(d) {
				if (this.tjv == 1 && d.payTrend && d.payTrend.length) {
					let series = d.payTrend.map(v => ({
						name: v.name,
						value: +v.money,
					}))
					series.shift()
					setTimeout(() => {
						this.optbox1.subtitle.name = d.payTrend[0].money
						let res = {
							series: [{
								data: series
							}]
						}
						this.chartData1 = JSON.parse(JSON.stringify(res));
					}, 500)
				} else if (this.tjv == 2 && d.discountTrend && d.discountTrend.length) {
					let series = d.discountTrend.map(v => ({
						name: v.name,
						value: +v.money,
					}))
					series.shift()
					setTimeout(() => {
						let res = {
							series: [{
								data: series
							}]
						}
						this.chartData2 = JSON.parse(JSON.stringify(res));
					}, 500)
				} else if (this.tjv == 3 && d.orderTrend && d.orderTrend.length) {
					let series = d.orderTrend.map(v => ({
						name: v.name,
						value: +v.money,
					}))
					series.shift()
					setTimeout(() => {
						let res = {
							series: [{
								data: series
							}]
						}
						this.chartData3 = JSON.parse(JSON.stringify(res));
					}, 500)
				} else if (this.tjv == 4 && d.goodsCat && d.goodsCat.length) {
					let series = d.goodsCat.map(v => ({
						name: v.name,
						value: +v.money,
					}))
					setTimeout(() => {
						let res = {
							series: [{
								data: series
							}]
						}
						this.chartData4 = JSON.parse(JSON.stringify(res));
					}, 500)
				} else if (this.tjv == 5 && d.hourTrend && d.hourTrend.length) {
					let series = d.hourTrend.map(v => ({
						name: v.name,
						value: +v.money,
					}))
					setTimeout(() => {
						let res = {
							categories: d.hourTrend.map(v => (v.name)),
							series: [{
								name: "统计金额",
								data: series
							}]
						}
						this.chartData5 = JSON.parse(JSON.stringify(res));
					}, 500)
				} else if (this.tjv == 6 && d.sellOut && d.sellOut.length) {
					let series = d.sellOut.map(v => ({
						name: v.name,
						value: +v.money,
					}))
					series.shift()
					setTimeout(() => {
						let res = {
							series: [{
								data: series
							}]
						}
						this.chartData6 = JSON.parse(JSON.stringify(res));
					}, 500)
				}
			},
			confirm(e) {
				if (e && e.length) {
					this.time[0] = e[0]
					this.time[1] = e[e.length - 1]
				}
				this.queryForm.startTime = this.time[0]
				this.queryForm.endTime = this.time[1]
				this.fetchData()
				this.showCalendar = false
			},
			datetimechange(e) {
				this.queryForm.startTime = e && e[0] || ''
				this.queryForm.endTime = e && e[1] || ''
				this.fetchData()
			},
			changeCTab(v, i) {
				this.tjtabs = i
				this.tjv = v.value
				this.fetchNewOrder()
			},
			chooseTimed() {
			      let date = new Date();
			      let year = date.getFullYear();
			      let month = String(date.getMonth() + 1);
			      let day = String(date.getDate());
			      month = month.padStart(2, '0');
			      day = day.padStart(2, '0');
			      this.calendar.maxDate = year + '-' + month + '-' + day;
			      this.calendar.defaultDate = year + '-' + month + '-' + day;
			
			      let nowTime = date.getTime();
			      let preTime = nowTime - 60 * 24 * 60 * 60 * 1000;
			      let preDate = new Date(preTime);
			      let preYear = preDate.getFullYear();
			      let preMonth = String(preDate.getMonth() + 1);
			      let preDay = String(preDate.getDate());
			      preMonth = preMonth.padStart(2, '0');
			      preDay = preDay.padStart(2, '0');
			      this.calendar.minDate = preYear + '-' + preMonth + '-' + preDay;
			},
		}
	})
</script>

<style lang="scss" scoped>
	.tradeReport {
		.search {
			.tabs {
				.itab {
					border: 1px solid #EBEAF0;
					border-radius: 4px;
				}

				.ctab {
					background: #E3EDFE;
					border: 1px solid #4275F4;
					color: #4275F4;
				}
			}
		}

		.main {
			max-height: calc(100vh - 15.625vh);
			overflow: hidden;
			overflow-y: scroll;

			.top {
				background: #f5f5f5;
				border-radius: 6px;

				.cashes {
					display: flex;
					flex-wrap: wrap;

					.c_item {
						width: 21.3030vw;
						height: 18.2291vh;
						border-radius: 6px;
					}
				}
			}

			.topList {
				overflow: hidden;
				overflow-x: scroll;
			}

			.tj {
				.tjTab {
					border: 1px solid #4275F4;
					border-right: none;
					border-radius: 4px;

					.tjTabs {
						border-right: 1px solid #4275F4;
						color: #4275F4;
					}

					.xztab {
						background: #4275F4;
						color: #fff;
					}
				}
			}
		}

		.u-popup {
			flex: 0;
		}

		/deep/.uni-table {
			min-width: auto !important;
		}

		.chartsBox1 {
			width: 100%;
			height: 300px;
		}
	}

	@media (min-width: 1500px) and (max-width: 3280px) {
		.tradeReport {

			.main {
				max-height: calc(100vh - 120px);

				.top {
					.cashes {
						.c_item {
							width: 291px;
							height: 140px;
						}
					}
				}
			}
		}
	}
</style>
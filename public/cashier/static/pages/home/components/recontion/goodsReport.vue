<template>
	<view class="f-y-bt h100 bf p10 tradeReport" style="padding-top: 0;">
		<view class="f-bt f-y-c p10">
			<view class="search flex f-g-1 f-y-c">
				<view class="tabs flex f-y-c">
					<view class="itab p-10-20 mr10 f16 c0" :class="{'ctab' : tab == i}" v-for="(v,i) in tabs" :key="i"
						@click="changeTab(v,i)">
						{{v.name}}
					</view>
					<view style="width:260px" v-if="queryForm.timeType==1">
						<uni-datetime-picker v-model="range" type="datetimerange" @change="datetimechange" />
					</view>
				</view>
				<u--form labelPosition="left" :model="queryForm" ref="uForm" labelWidth="100px" labelAlign="right"
					:labelStyle="{fontSize:'14px'}">
					<u-form-item label="订单类型：" prop="scene" ref="item1">
						<view style="width:133px">
							<uni-data-select v-model="queryForm.scene" :localdata="channels" placeholder="请选择订单类型"
								@change="handDiningType"></uni-data-select>
						</view>
					</u-form-item>
					<!-- <u-form-item label="支付方式：" prop="payType" ref="item1">
						<view style="width:193px">
							<uni-data-select v-model="queryForm.payType" :localdata="classfiys" placeholder="请选择订单来源"
								@change="handSource"></uni-data-select>
						</view>
					</u-form-item> -->
				</u--form>
			</view>
		</view>
		<view class="main f-1 f16 pb40 f-y-bt">
			<u-tabs :list="list1" @click="handTabs" :current="current" lineColor="#4275F4"
				:activeStyle="{fontWeight: 'bold',color:'#000'}"></u-tabs>
			<view class="topList mt20 f-g-1 f-y-bt">
				<uni-table ref="table" :loading="tbloading" border stripe emptyText="暂无明细"
					v-if="queryForm.groupBySpec=='carts'">
					<uni-tr>
						<uni-th align="center">ID</uni-th>
						<uni-th align="center">商品类别</uni-th>
						<uni-th align="center">商品销量</uni-th>
						<!-- <uni-th align="center">销量占比</uni-th> -->
						<uni-th align="center">商品销售额</uni-th>
						<!-- <uni-th align="center">销售额占比</uni-th> -->
						<uni-th align="center">商品实收</uni-th>
						<!-- <uni-th align="center">实收占比</uni-th> -->
						<!-- <uni-th align="center">退款订单数</uni-th> -->
						<!-- <uni-th align="center">退款金额</uni-th> -->
					</uni-tr>
					<uni-tr v-for="(row, i) in dataList" :key="i">
						<uni-td align="center">{{ row.id}}</uni-td>
						<uni-td align="center">{{ row.name}}</uni-td>
						<uni-td align="center">{{ row.sales }}</uni-td>
						<!-- <uni-td align="center">{{ row.yxdds }}</uni-td> -->
						<uni-td align="center">{{ row.sellMoney }}</uni-td>
						<!-- <uni-td align="center">{{ row.zhdjj }}</uni-td> -->
						<uni-td align="center">{{ row.money }}</uni-td>
						<!-- <uni-td align="center">{{ row.tkdds }}</uni-td> -->
						<!-- <uni-td align="center">{{ row.tkdds }}</uni-td> -->
						<!-- <uni-td align="center">{{ row.tkdds }}</uni-td> -->
					</uni-tr>
				</uni-table>
				<uni-table ref="table" :loading="tbloading" border stripe emptyText="暂无明细" v-else>
					<uni-tr>
						<uni-th align="center">ID</uni-th>
						<uni-th align="center">商品类别</uni-th>
						<uni-th align="center">商品名称</uni-th>
						<uni-th align="center" v-if="queryForm.groupBySpec=='specMd5'">规格值</uni-th>
						<uni-th align="center">商品销量</uni-th>
						<!-- <uni-th align="center">销量占比</uni-th> -->
						<uni-th align="center">商品销售额</uni-th>
						<!-- <uni-th align="center">销售额占比</uni-th> -->
						<uni-th align="center">商品实收</uni-th>
						<!-- <uni-th align="center">实收占比</uni-th> -->
						<!-- <uni-th align="center">退款订单数</uni-th> -->
						<!-- <uni-th align="center">退款金额</uni-th> -->
					</uni-tr>
					<uni-tr v-for="(row, i) in dataList" :key="i">
						<uni-td align="center">{{ row.id }}</uni-td>
						<uni-td align="center">{{ row.goodsCat && row.goodsCat.length && row.goodsCat[0].name }}</uni-td>
						<uni-td align="center">{{ row.name }}</uni-td>
						<uni-td align="center"
							v-if="queryForm.groupBySpec=='specMd5'">{{ row.attrData && row.attrData.spec }}</uni-td>
						<uni-td align="center">{{ row.num }}</uni-td>
						<!-- <uni-td align="center">{{ row.yxdds }}</uni-td> -->
						<uni-td align="center">{{ row.sellMoney }}</uni-td>
						<!-- <uni-td align="center">{{ row.zhdjj }}</uni-td> -->
						<uni-td align="center">{{ row.money }}</uni-td>
						<!-- <uni-td align="center">{{ row.tkdds }}</uni-td> -->
						<!-- <uni-td align="center">{{ row.tkdds }}</uni-td> -->
						<!-- <uni-td align="center">{{ row.tkdds }}</uni-td> -->
					</uni-tr>
				</uni-table>
				<view class="mt10 pagona"><uni-pagination show-icon :page-size="queryForm.pageSize" :current="queryForm.pageNo"
						:total="total" @change="change" /></view>
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
				tbloading: false,
				time: [],
				range:[],
				queryForm: {
					timeType: 2,
					scene: '',
					pageNo: 1,
					pageSize: 10,
					groupBySpec: 'spuId',
				},
				total: 0,
				channels: [{
						value: '',
						text: '全部类型'
					},
					{
						value: 1,
						text: '外卖'
					},
					{
						value: 2,
						text: '自提'
					},
					{
						value: 3,
						text: '店内'
					}
				],
				classfiys: [{
						value: '',
						text: '全部方式'
					},
					{
						value: 'wexin',
						text: '微信支付'
					},
					{
						value: 'ali',
						text: '支付宝支付'
					},
					{
						value: 'balance',
						text: '余额支付'
					},
					{
						value: 'cash',
						text: '现金支付'
					},
				],
				dataList: [],
				list1: [{
						name: '商品',
						value: 'spuId',
					},
					{
						name: '商品+规格',
						value: 'specMd5',
					},
					// {
					// 	name: '商品规格',
					// 	value: 'specMd5',
					// }, 
					{
						name: '商品类别',
						value: 'carts',
					}
				],
				current: 0,
				apiGoods: 'goodsScs',
				showCalendar: false,
				calendar: {
					minDate: '',
					maxDate: '',
					defaultDate: '',
					monthNum: 13,
				},
			}
		},
		methods: {
			async fetchData() {
				this.tbloading = true
				const {
					data: {
						list,
						pageNo,
						pageSize,
						total
					},
				} = await this.beg.request({
					url: `${this.api[this.apiGoods]}`,
					data: this.queryForm
				})
				this.tbloading = false
				this.dataList = list ? list : []
				this.total = total
				this.queryForm.pageNo = pageNo
				this.queryForm.pageSize = pageSize
				this.chooseTimed()
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
			datetimechange(e) {
				this.queryForm.startTime = e && e[0] || ''
				this.queryForm.endTime = e && e[1] || ''
				this.fetchData()
			},
			handDiningType(e) {
				this.queryForm.pageNo = 1
				this.queryForm.scene = e
				this.fetchData()
			},
			handTabs(e) {
				this.queryForm.pageNo = 1
				this.queryForm.groupBySpec = e.value
				this.current = e.index
				if (e.value == 'carts') {
					this.apiGoods = 'goodsCat'
				} else {
					this.apiGoods = 'goodsScs'
				}
				this.fetchData()
			},
			change(e) {
				this.queryForm.pageNo = e.current;
				this.fetchData()
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

		/deep/ .u-form-item__body {
			padding: 0;
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


			.topList {
				// width: calc(100vw - 5.8565vw);
				overflow: hidden;
				overflow-x: scroll;
			}

		}

		.u-popup {
			flex: 0;
		}
		
		/deep/.uni-table{
		   min-width: auto !important;
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
</style>
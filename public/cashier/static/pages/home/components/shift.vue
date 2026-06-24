<template>
	<view class="page w100 h100">
		<view class="f-1 w100 p20 bf">
			<!-- <view class="tabs">
				<u-tabs :list="list1" @click="handTabs" :current="current" lineColor="#4275F4"
					:activeStyle="{fontWeight: 'bold',color:'#000'}"></u-tabs>
			</view> -->
			<!-- <view class="f-bt f-y-c p10">
				<view class="search flex f-g-1 f-y-c">
					<view class="tabs flex">
						<view class="itab p-10-20 mr10 f16 c0" :class="{'ctab' : tab == i}" v-for="(v,i) in tabs"
							:key="i" @click="changeTab(v,i)">
							{{v.name}}
						</view>
					</view>
				</view>
			</view> -->
			<view class="main f-1 f-bt f16">
				<view class="topList mt20 f-g-1 f-y-bt">
					<uni-table ref="table" :loading="tbloading" border stripe emptyText="暂无明细" v-if="current==0">
						<uni-tr>
							<!-- <uni-th align="center">班次号</uni-th> -->
							<uni-th align="center">序号</uni-th>
							<uni-th align="center">交班人</uni-th>
							<uni-th align="center">开班时间</uni-th>
							<uni-th align="center">交班时间</uni-th>
							<uni-th align="center">本班次收款</uni-th>
							<uni-th align="center">操作</uni-th>
						</uni-tr>
						<uni-tr v-for="(row, i) in dataList" :key="i">
							<!-- <uni-td align="center">{{ row.id}}</uni-td> -->
							<uni-td align="center">{{ row.id}}</uni-td>
							<uni-td align="center">{{ row.admin && row.admin.nickname }}</uni-td>
							<uni-td align="center">{{ row.created_at }}</uni-td>
							<uni-td align="center">{{ row.endTime }}</uni-td>
							<uni-td align="center">{{ row.contents && row.contents.money}}</uni-td>
							<uni-td align="center">
								<text class="cf5f mr10" style="color: #4275F4;" @click.stop="handPrint(row)">补打交班单</text>
								<text class="cf06" style="color: #4275F4;" @click.stop="handDl(row)">查看详情</text>
							</uni-td>
						</uni-tr>
					</uni-table>
					<uni-table ref="table" :loading="tbloading" border stripe emptyText="暂无明细" v-if="current==1">
						<uni-tr>
							<uni-th align="center">班次号</uni-th>
							<uni-th align="center">班次</uni-th>
							<uni-th align="center">未交班人</uni-th>
							<uni-th align="center">开班时间</uni-th>
							<uni-th align="center">本班次收款</uni-th>
							<uni-th align="center">操作</uni-th>
						</uni-tr>
						<uni-tr v-for="(row, i) in dataList" :key="i">
							<uni-td align="center">{{ row.id}}</uni-td>
							<uni-td align="center">{{ row.name}}</uni-td>
							<uni-td align="center">{{ row.sales }}</uni-td>
							<uni-td align="center">{{ row.stime }}</uni-td>
							<uni-td align="center">{{ row.money }}</uni-td>
							<uni-td align="center">
								<text class="cf5f mr10" @click.stop="handDel(row)">去交班</text>
							</uni-td>
						</uni-tr>
					</uni-table>
					<view class="mt10 pagona"><uni-pagination show-icon :page-size="queryForm.pageSize"
							:current="queryForm.pageNo" :total="total" @change="change" /></view>
				</view>
			</view>
			<u-calendar :show="showCalendar" color="#4275F4" mode="range" @confirm="confirm" @close="showCalendar=false"
				:minDate="calendar.minDate"></u-calendar>
		</view>
	</view>
</template>

<script>
	export default {
		components: {

		},
		data() {
			return {
				list1: [{
					name: '交班记录',
					value: 'all',
				}, {
					name: '未交班记录',
					value: 'making',
				}],
				current: 0,
				tbloading: false,
				time: [],
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
				dataList: [{
					id: '10470',
					name: '午班',
					sales: '超级管理员',
					sellMoney: '明交班',
					stime: '2023/10/07 14:20:58',
					etime: '2023/10/17 14:20:58',
					money: '2091.00',
					yic: '超7天未交班',
				}],
				queryForm: {
					timeType: 2,
					pageNo: 1,
					pageSize: 10,
				},
				total: 0,
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
			init() {
				this.fetchData()
			},
			handTabs(e) {
				this.current = e.index
				// this.$emit('handTabs',e)
			},
			fetchData() {
				this.fetchNewOrder()
				this.chooseTimed()
			},
			async fetchNewOrder() {
				let {
					data: {
						list,
						pageNo,
						pageSize,
						total
					},
				} = await this.beg.request({
					url: this.api.handOver,
					data: this.queryForm
				})
				this.dataList = list ? list : [],
				this.total = total
			},
			change(e) {
				this.queryForm.pageNo = e.current;
				this.fetchData()
			},
			changeTab(v, i) {
				this.queryForm.pageNo = 1
				this.tab = i
				this.queryForm.timeType = v.value
				if (v.value == 1) return this.showCalendar = true
				this.queryForm.startTime = ''
				this.queryForm.endTime = ''
				// this.fetchData()
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
			back() {
				uni.navigateBack({
					delta: 1
				})
			},
			changeTab(item, index) {
				uni.navigateTo({
					url: `/pages/home/index?current=13&l_title='硬件管理'`
				})
			},
			handDl(row) {
				uni.navigateTo({
					url: `/pages/handover/handOver?id=${row.id}&state=${row.state}`
				})
			},
			async handPrint(row){
				let {
					msg
				} = await this.beg.request({
					url: `${this.api.printOrder}/${row.id}`,
					method: "POST",
					data: {
						scene: 11
					}
				})
				this.fetchData()
				uni.$u.toast(msg)
			},
		}
	}
</script>

<style>
	.page {
		display: flex;
		flex-direction: column;
		align-items: center;
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
</style>
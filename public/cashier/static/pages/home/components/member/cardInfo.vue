<template>
	<view class="userInfo f-1 f14">
		<uni-table ref="table" border stripe emptyText="暂无明细">
			<uni-tr>
				<uni-th align="center">获取渠道</uni-th>
				<uni-th align="center">成长值变更</uni-th>
				<uni-th align="center">当前成长值</uni-th>
				<uni-th align="center">等级变更</uni-th>
				<uni-th align="center">变更时间</uni-th>
				<uni-th align="center">备注</uni-th>
			</uni-tr>
			<uni-tr v-for="(row, i) in list" :key="i">
				<uni-td>{{ row.behaviorFormat }}</uni-td>
				<uni-td>
					<view v-if="row.type == 0" style="color: #f56c6c">-{{ row.value }}</view>
					<view v-else style="color: #4275F4">+{{ row.value }}</view>
				</uni-td>
				<uni-td align="center">{{row.atLast}}</uni-td>
				<uni-td align="center">
					<view>{{row.vipChange || '--'}}</view>
				</uni-td>
				<uni-td align="center">{{ row.updated_at }}</uni-td>
				<uni-td align="left">{{ row.notes }}</uni-td>
			</uni-tr>
		</uni-table>
		<view class="mt10"><uni-pagination show-icon :page-size="queryForm.pageSize" :current="queryForm.pageNo"
				:total="total" @change="change" /></view>
	</view>
</template>

<script>
	export default {
		props: {
			form: {
				type: Object,
				default: {},
			}
		},
		components: {

		},
		data() {
			return {
				total: 0,
				queryForm: {
					pageNo: 1,
					pageSize: 10,
				},
				list: [],
			}
		},
		methods: {
			async fetchData() {
				this.queryForm.userId = this.form.id;
				const {
					data: {
						list,
						total,
						pageNo,
						pageSize
					}
				} = await this.beg.request({
					url: `${this.api.userAccountLog}/exp`,
					data: this.queryForm,
				})
				this.list = list ? list : [];
				this.total = total;
				this.queryForm.pageNo = pageNo;
				this.queryForm.pageSize = pageSize;
			},
			change(e) {
				this.queryForm.pageNo = e.current;
				this.fetchData()
			},
		}
	}
</script>

<style lang="scss" scoped>
	.userInfo {
		height: calc(100vh - 120px);
		overflow: hidden;
		overflow-y: scroll;
	}

	/deep/.uni-pagination {
		.page--active {
			background: #4275F4 !important;
			color: #fff !important;
		}
	}

	.uni-table-th {
		color: #000;
	}
</style>
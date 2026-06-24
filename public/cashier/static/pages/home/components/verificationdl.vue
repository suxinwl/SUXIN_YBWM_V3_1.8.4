<template>
	<view class="page h100 bf pr10">
		<view class="f-bt f-y-c">
			<u--form labelPosition="left" :model="queryForm" ref="uForm" labelWidth="100px" labelAlign="right"
				:labelStyle="{fontSize:'14px'}">
				<u-form-item label="抖音核销码：" prop="diningType" ref="item1">
					<view style="width:200px">
						<u--input placeholder="请输入抖音核销码" prefixIcon="search" prefixIconStyle="color: #909399"
							v-model="queryForm.keyword" @input="fetchData" clearable></u--input>
					</view>
				</u-form-item>
				<u-form-item label="兑换时间：" prop="source" ref="item1">
					<view style="width:300px">
						<uni-datetime-picker v-model="range" type="datetimerange" @change="datetimechange" />
					</view>
				</u-form-item>
			</u--form>
			<!-- <view class="mr20">
				<view class="rf cf f-c curs" @click="rfFetchData"><text class="iconfont icon-shuaxin f22"></text></view>
			</view> -->
		</view>
		<view class="userInfo f-1 f14">
			<uni-table ref="table" border stripe emptyText="暂无明细">
				<uni-tr>
					<uni-th align="center">兑换门店</uni-th>
					<uni-th align="center">抖音核销码</uni-th>
					<uni-th align="center">兑换内容</uni-th>
					<uni-th align="center">兑换时间</uni-th>
					<uni-th align="center">操作人</uni-th>
					<uni-th align="center">状态</uni-th>
					<uni-th align="center">操作</uni-th>
				</uni-tr>
				<uni-tr v-for="(row, i) in list" :key="i">
					<uni-td>
						<view class="name">{{ row.store && row.store.name }}（{{ row.poi_name }}）</view>
					</uni-td>
					<uni-td align="center">
						<view>{{ row.code}}</view>
					</uni-td>
					<uni-td align="center">{{ row.content }}</uni-td>
					<uni-td align="center">
						{{ row.created_at }}
					</uni-td>
					<uni-td align="center">
						<view>{{row.admin && row.admin.nickname }}</view>
					</uni-td>
					<uni-td align="center">
						<view class="flex f-c">
							<!-- <u-tag text="已过期" plain type="error" size="mini" v-if="row.state == 0"></u-tag> -->
							<!-- <u-tag text="待使用" plain type="warning" size="mini" v-if="row.state == 1"></u-tag> -->
							<u-tag text="已核销" plain type="success" size="mini" v-if="row.state == 1"></u-tag>
							<u-tag text="已撤销核销" plain type="error" size="mini" v-if="row.state == 2"></u-tag>
						</view>
					</uni-td>
					<uni-td align="center">
						<text class="cf5f mr10" @click.stop="handDel(row)" v-if="row.state==1">撤销</text>
					</uni-td>
				</uni-tr>
			</uni-table>
			<view class="mt10"><uni-pagination show-icon :page-size="queryForm.pageSize" :current="queryForm.pageNo"
					:total="total" @change="change" /></view>
			<u-modal :show="rescind" :showCancelButton="true" width="300px" title=" " content="您确定要撤销该订单吗？"
				confirmColor="#fff" @cancel="rescind=false" @confirm="save"></u-modal>
		</view>
	</view>
</template>

<script>
	export default ({
		components: {

		},
		data() {
			return {
				total: 0,
				queryForm: {
					pageNo: 1,
					pageSize: 10,
					order_type: 1,
				},
				list: [],
				rescind: false,
				row: {},
				range: [],
			}
		},
		methods: {
			handTabs(e) {
				if (e.index == 0) {
					this.queryForm.order_type = 1
					this.fetchData()
				} else if (e.index == 1) {
					this.queryForm.order_type = 2
					this.fetchData()
				}
			},
			init() {
				this.fetchData()
			},
			async fetchData() {
				const {
					data: {
						list,
						total,
						pageNo,
						pageSize
					}
				} = await this.beg.request({
					url: this.api.getTiktokVerifyList,
					method: 'POST',
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
			handDel(row) {
				this.rescind = true
				this.row = row
			},
			datetimechange(e) {
				this.queryForm.startTime = e && e[0] || ''
				this.queryForm.endTime = e && e[1] || ''
				this.fetchData()
			},
			async save() {
				let {
					msg
				} = await this.beg.request({
					url: this.api.revokeVerify,
					method: 'POST',
					data: {
						id: this.row.id
					},
				})
				this.fetchData()
				uni.$u.toast(msg)
				this.rescind = false
			},
		}
	})
</script>

<style lang="scss" scoped>
	.userInfo {
		height: calc(100vh - 180px);
		overflow: hidden;
		overflow-y: scroll;
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

	/deep/.uni-pagination {
		.page--active {
			background: #4275F4 !important;
			color: #fff !important;
		}
	}

	/deep/.u-modal__button-group__wrapper--confirm {
		background: #4275F4;
	}

	.uni-table-th {
		color: #000;
	}
</style>
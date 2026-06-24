<template>
	<view class="userInfo f-1 f14">
		<uni-table ref="table" border stripe emptyText="暂无明细">
			<uni-tr>
				<!-- <uni-th align="center">券ID</uni-th> -->
				<uni-th align="center">券名称</uni-th>
				<uni-th align="center">券类型</uni-th>
				<!-- <uni-th align="center">获得来源</uni-th> -->
				<uni-th align="center">获得时间</uni-th>
				<uni-th align="center">使用状态</uni-th>
				<uni-th align="center">有效期</uni-th>
				<uni-th align="center">操作</uni-th>
			</uni-tr>
			<uni-tr v-for="(row, i) in list" :key="i">
				<!-- <uni-td>{{ row.sn }}</uni-td> -->
				<uni-td>
					<view class="name">{{ row.coupon && row.coupon.name }}</view>
				</uni-td>
				<uni-td align="center">
					<view v-if="row.coupon && row.coupon.type" class="flex f-c">
						<u-tag text="代金券" plain size="mini" v-if="row.coupon.type == 1"></u-tag>
						<u-tag text="折扣券" plain type="warning" size="mini" v-if="row.coupon.type == 2"></u-tag>
						<u-tag text="兑换券" plain type="success" size="mini" v-if="row.coupon.type == 3"></u-tag>
						<u-tag text="运费券" plain type="error" size="mini" v-if="row.coupon.type == 4"></u-tag>
					</view>
				</uni-td>
				<!-- <uni-td align="center">{{ row.channelFormat }}</uni-td> -->
				<uni-td align="center">{{ row.created_at }}</uni-td>
				<uni-td align="center">
					<view class="flex f-c">
						<u-tag text="已过期" plain type="error" size="mini" v-if="row.state == 0"></u-tag>
						<u-tag text="待使用" plain type="warning" size="mini" v-if="row.state == 1"></u-tag>
						<u-tag text="已使用" plain type="success" size="mini" v-if="row.state == 2"></u-tag>
						<u-tag text="已作废" plain type="error" size="mini" v-if="row.state == 3"></u-tag>
					</view>
				</uni-td>
				<uni-td align="center">
					<view>{{ row.startTime }}</view>
					<view>{{ row.endTime }}</view>
				</uni-td>
				<uni-td align="center">
					<text class="cf5f mr10" @click.stop="handDel(row)" v-if="row.state==1">作废</text>
					<text class="cf06" style="color: #4275F4;" @click.stop="handDl(row)">查看</text>
				</uni-td>
			</uni-tr>
		</uni-table>
		<view class="mt10"><uni-pagination show-icon :page-size="queryForm.pageSize" :current="queryForm.pageNo"
				:total="total" @change="change" /></view>
		<couponInfodl ref="couponInfodlRef" />
		<u-modal :show="rescind" :showCancelButton="true" width="300px" title=" " content="你确定要作废当前优惠券吗？"
			confirmColor="#fff" @cancel="rescind=false" @confirm="save"></u-modal>
	</view>
</template>

<script>
	import couponInfodl from './couponInfodl';
	export default {
		props: {
			form: {
				type: Object,
				default: {},
			}
		},
		components: {
			couponInfodl,
		},
		data() {
			return {
				total: 0,
				queryForm: {
					pageNo: 1,
					pageSize: 10,
				},
				list: [],
				rescind: false,
				row: {},
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
					url: this.api.getCReceive,
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
				// uni.showModal({
				// 	title: '温馨提示',
				// 	content: '你确定要作废当前优惠券吗',
				// 	success: async (res) => {
				// 		if (res.confirm) {
				// 			let {
				// 				msg
				// 			} = await this.beg.request({
				// 				url: `${this.api.getCReceive}/${row.id}`,
				// 				method: "delete",
				// 			})
				// 			this.fetchData()
				// 			uni.$u.toast(msg)
				// 		}
				// 	}
				// });
			},
			async save() {
				let {
					msg
				} = await this.beg.request({
					url: `${this.api.getCReceive}/${this.row.id}`,
					method: "delete",
				})
				this.fetchData()
				uni.$u.toast(msg)
				this.rescind = false
			},
			handDl(row) {
				this.$refs['couponInfodlRef'].open(row)
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

	/deep/.u-modal__button-group__wrapper--confirm {
		background: #4275F4;
	}

	.uni-table-th {
		color: #000;
	}
</style>
<template>
	<view>
		<u-overlay :show="showExamine">
			<view class="mode f20 bf f-y-bt" style="width:1100px">
				<view class="p15 bd1 dfbc">
					<text>{{isCheck==1?'余额':isCheck==2?'积分':isCheck==3?'优惠券':'成长值'}}</text>
					<text class="iconfont icon-cuowu" @click="showExamine=false"></text>
				</view>
				<view class="p15 f-1 f-y-bt">
					<view class="f-1">
						<uni-table v-if="isCheck===1" ref="table" emptyText="暂无更多数据">
							<uni-tr class="bf5">
								<uni-th><text class="f18">账户类型</text></uni-th>
								<uni-th><text class="f18">增加/扣除</text></uni-th>
								<uni-th><text class="f18">账户余额</text></uni-th>
								<uni-th><text class="f18">时间</text></uni-th>
								<uni-th width="200"><text class="f18">备注</text></uni-th>
							</uni-tr>
							<uni-tr v-for="(item, index) in tableData" :key="index" style="height:55px">
								<uni-td><text class="f18">{{item.behaviorFormat}}</text></uni-td>
								<uni-td>
									<view class="f18">
										<view v-if="item.type == 0" style="color: #f56c6c">
											-{{ item.value }}
										</view>
										<view v-else style="color: #67c23a">+{{ item.value }}</view>
									</view>
								</uni-td>
								<uni-td><text class="f18">{{item.atLast}}</text></uni-td>
								<uni-td><text class="f18">{{item.updated_at}}</text></uni-td>
								<uni-td><text class="f18">{{item.notes}}</text></uni-td>
							</uni-tr>
						</uni-table>
						<uni-table v-if="isCheck===2" ref="table" emptyText="暂无更多数据">
							<uni-tr class="bf5">
								<uni-th><text class="f18">类型</text></uni-th>
								<uni-th><text class="f18">增加/扣除</text></uni-th>
								<uni-th><text class="f18">账户积分</text></uni-th>
								<uni-th><text class="f18">时间</text></uni-th>
								<uni-th width="200"><text class="f18">备注</text></uni-th>
							</uni-tr>
							<uni-tr v-for="(item, index) in tableData" :key="index" style="height:55px">
								<uni-td><text class="f18">{{item.behaviorFormat}}</text></uni-td>
								<uni-td>
									<view class="f18">
										<view v-if="item.type == 0" style="color: #f56c6c">
											-{{ item.value }}
										</view>
										<view v-else style="color: #67c23a">+{{ item.value }}</view>
									</view>
								</uni-td>
								<uni-td><text class="f18">{{item.atLast}}</text></uni-td>
								<uni-td><text class="f18">{{item.updated_at}}</text></uni-td>
								<uni-td><text class="f18">{{item.notes}}</text></uni-td>
							</uni-tr>
						</uni-table>
						<uni-table v-if="isCheck===3" ref="table" emptyText="暂无更多数据">
							<uni-tr class="bf5">
								<uni-th><text class="f18">券ID</text></uni-th>
								<uni-th><text class="f18">券名称</text></uni-th>
								<uni-th><text class="f18">券类型</text></uni-th>
								<uni-th><text class="f18">获得来源</text></uni-th>
								<uni-th><text class="f18">获得时间</text></uni-th>
								<uni-th><text class="f18">使用状态</text></uni-th>
								<uni-th><text class="f18">有效期</text></uni-th>
							</uni-tr>
							<uni-tr v-for="(item, index) in tableData" :key="index" style="height:55px">
								<uni-td><text class="f18">{{item.sn}}</text></uni-td>
								<uni-td><text class="f18">{{item.coupon && item.coupon.name}}</text></uni-td>
								<uni-td>
									<view class="f18" v-if="item.coupon">
										<view v-if="item.coupon.type == 1">代金券</view>
										<view v-else-if="item.coupon.type == 2">折扣券</view>
										<view v-else-if="item.coupon.type == 3">兑换券</view>
										<view v-else-if="item.coupon.type == 4">运费券</view>
									</view>
								</uni-td>
								<uni-td><text class="f18">{{item.channelFormat}}</text></uni-td>
								<uni-td><text class="f18">{{item.created_at}}</text></uni-td>
								<uni-td>
									<view class="f18">
										<view v-if="item.state == 0" style="color: #333">已过期</view>
										<view v-else-if="item.state== 1" style="color: #67c23a">待使用</view>
										<view v-else-if="item.state == 2" style="color: #999">已使用</view>
										<view v-else-if="item.state == 3" style="color: #333">已作废</view>
									</view>
								</uni-td>
								<uni-td>
									<view class="f18">
										<view>{{ item.startTime }}</view>
										<view>{{ item.endTime }}</view>
									</view>
								</uni-td>
							</uni-tr>
						</uni-table>
						<uni-table v-if="isCheck===4" ref="table" emptyText="暂无更多数据">
							<uni-tr class="bf5">
								<uni-th><text class="f18">获取渠道</text></uni-th>
								<uni-th><text class="f18">成长值变更</text></uni-th>
								<uni-th><text class="f18">当前成长值</text></uni-th>
								<uni-th><text class="f18">等级变更</text></uni-th>
								<uni-th><text class="f18">变更时间</text></uni-th>
								<uni-th><text class="f18">备注</text></uni-th>
							</uni-tr>
							<uni-tr v-for="(item, index) in tableData" :key="index" style="height:55px">
								<uni-td><text class="f18">{{item.behaviorFormat}}</text></uni-td>
								<uni-td>
									<view class="f18">
										<view v-if="item.type == 0" style="color: #f56c6c">
											-{{ item.value }}
										</view>
										<view v-else style="color: #67c23a">+{{ item.value }}</view>
									</view>
								</uni-td>
								<uni-td><text class="f18">{{item.atLast}}</text></uni-td>
								<uni-td>
									<view class="f18">
										 {{item.vipChange || '--'}}
									</view>
								</uni-td>
								<uni-td><text class="f18">{{item.updated_at}}</text></uni-td>
								<uni-td><text class="f18">{{item.notes}}</text></uni-td>
							</uni-tr>
						</uni-table>
						<uni-table v-if="isCheck===5" ref="table" emptyText="暂无更多数据">
							<uni-tr class="bf5">
								<uni-th><text class="f18">名称</text></uni-th>
								<uni-th><text class="f18">卡号</text></uni-th>
								<uni-th><text class="f18">卡类型</text></uni-th>
								<uni-th><text class="f18">总次数/已使用</text></uni-th>
								<uni-th><text class="f18">创建时间</text></uni-th>
								<uni-th><text class="f18">到期时间</text></uni-th>
								<uni-th><text class="f18">操作</text></uni-th>
							</uni-tr>
							<uni-tr v-for="(item, index) in form.cardList" :key="index" style="height:55px">
								<uni-td><text class="f18">{{item.name}}</text></uni-td>
								<uni-td><text class="f18">{{item.cardNum}}</text></uni-td>
								<uni-td><text class="f18">{{item.type}}</text></uni-td>
								<uni-td><text class="f18">{{item.total}}</text></uni-td>
								<uni-td><text class="f18">{{item.creat_at}}</text></uni-td>
								<uni-td><text class="f18">{{item.due_time}}</text></uni-td>
								<uni-td></uni-td>
							</uni-tr>
						</uni-table>
						<view class="f-c mt20">
							<uni-pagination :current="queryForm.pageNo" :total="total" :pageSize="queryForm.pageSize"
								@change="change" title="标题文字" />
						</view>
					</view>
					<!-- <u-button v-if="isCheck===3" color=" #4275F4" text="发放优惠券" @click="changeDis"></u-button> -->
				</view>
			</view>
		</u-overlay>
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
				showExamine: false,
				isCheck: 1,
				rules: {
					// value: [{
					// 	required: true,
					// 	message: '请输入修改内容',
					// 	trigger: ['change', 'blur'],
					// }],
					notes: [{
						required: true,
						message: '请输入备注',
						trigger: ['blur', 'change']
					}]
				},
				total: 0,
				queryForm: {
					pageNo: 1,
					pageSize: 10,
				},
				tableData: [],
			}
		},
		onReady() {
			this.$refs.uForm.setRules(this.rules)
		},
		methods: {
			async open(t) {
				this.isCheck = t
				await this.getTable(t)
				this.showExamine = true
			},
			async getTable(t) {
				let url = ''
				if (t == 1) {
					url = `${this.api.userAccountLog}/balance`
				} else if (t == 2) {
					url = `${this.api.userAccountLog}/integral`
				} else if (t == 3) {
					url = this.api.getCReceive
				} else if(t == 4){
					url = `${this.api.userAccountLog}/exp`
				}
				this.queryForm.userId = this.form.id;
				const {
					data: {
						list,
						pageNo,
						pageSize,
						total
					},
				} = await this.beg.request({
					url,
					data: this.queryForm
				})
				this.tableData = list;
				this.total = total;
				this.queryForm.pageNo = pageNo;
				this.queryForm.pageSize = pageSize;
			},
			change(e) {
				this.queryForm.pageNo = e.current;
				this.getTable(this.isCheck)
			},
			close() {
				this.showExamine = false
			},
			save() {
				this.$refs.uForm.validate().then(res => {
					this.$emit('save', this.bForm)
				})
			}
		}
	}
</script>

<style lang="scss" scoped>
	.mode {
		position: absolute;
		top: 50%;
		left: 50%;
		transform: translate(-50%, -50%);
		width: 750px;
		border-radius: 5px;

		/deep/.u-form-item__body__left__content__label {
			justify-content: flex-end !important;
		}

		/deep/.u-input {
			padding: 3px 9px !important;
		}

		/deep/.uni-select__input-box {
			height: 32px !important;
		}

		/deep/.uni-calendar__content {
			position: absolute;
			left: 50%;
			transform: translateX(-50%);
			width: 400px;
			border-radius: 10px;

			.uni-datetime-picker--btn {
				background: #4275F4 !important;
				color: #000 !important;
			}

			.uni-calendar-item--checked {
				background: #4275F4 !important;

				.uni-calendar-item--checked-text {
					color: #000 !important;
				}
			}
		}
	}
</style>
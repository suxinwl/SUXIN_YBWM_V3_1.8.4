<template>
	<view class="f-y-bt h100">
		<view class="main f-1 f-bt bf">
			<view class="left br1">
				<view class="p10 bd1 tac">退款维权</view>
				<view class="p-10-15 bd1">
					<u--input placeholder="搜索订单号/商品名称" prefixIcon="search"
						prefixIconStyle="font-size: 22px;color: #909399"></u--input>
				</view>
				<view v-if="list&&list.length>0" class="f-1 list">
					<view :class="isItem==item.id?'isItem':''" class="p20" v-for="(item,index) in list" :key="index"
						@click="clickItem(item,index)">
						<view class="mb20">退款编号：{{item.orderSn}}</view>
						<view class="dfa f18 mb10">
							<view style="margin-right:65px;">订单金额：<text class="cf5 f20">￥{{item.amount}}</text></view>
							<view>退款金额：<text class="cf5 f20">￥{{item.refund}}</text></view>
						</view>
						<view class="f18">退款状态：{{item.status==0?'申请维权':'维权结束'}}(退款退货)</view>
					</view>
				</view>
				<view v-else class="f-1 f-c-c" style="overflow-y:auto">
					<u-empty mode="car" icon="http://cdn.uviewui.com/uview/empty/car.png">
					</u-empty>
				</view>
			</view>
			<view class="f-1">
				<view class="p10 bd1 tac">订单详情</view>
				<view class="dfa" style="height:59px;background: #eff0f4;">
					<view :class="tab2==index?'bf':''" class="tac" v-for="(item,index) in tabs2" :key="index"
						style="width:115px;height:59px;line-height: 59px;" @click="tab2=index">{{item}}</view>
				</view>
				<view v-if="tab2==0" class="p-20-30 f18">
					<view class="p10">
						<view class="dfa mb15">
							<view class="w50 dfa">
								<view class="tar" style="width:130px">买家：</view>
								<view>{{itemForm.buyer?itemForm.buyer:'-'}}</view>
							</view>
							<view class="w50 dfa">
								<view class="tar" style="width:130px">退款编号：</view>
								<view>{{itemForm.orderSn}}</view>
							</view>
						</view>
						<view class="dfa mb15">
							<view class="w50 dfa">
								<view class="tar" style="width:130px">申请时间：</view>
								<view>{{itemForm.creat_at}}</view>
							</view>
							<view class="w50 dfa">
								<view class="tar" style="width:130px">维权类型：</view>
								<view>{{itemForm.type=='refund'?'退款':'-'}}</view>
							</view>
						</view>
						<view class="dfa mb15">
							<view class="w50 dfa">
								<view class="tar" style="width:130px">退款方式：</view>
								<view>{{itemForm.state==1?'主动退款(退款到余额)':'-'}}</view>
							</view>
							<view class="w50 dfa">
								<view class="tar" style="width:130px">退款金额：</view>
								<view>￥{{itemForm.refund}}</view>
							</view>
						</view>
						<view class="dfa mb15">
							<view class="w50 dfa">
								<view class="tar" style="width:130px">退款原因：</view>
								<view>{{itemForm.reason?itemForm.reason:'--'}}</view>
							</view>
							<view class="w50 dfa">
								<view class="tar" style="width:130px">退款说明：</view>
								<view>{{itemForm.instructions?itemForm.instructions:'--'}}</view>
							</view>
						</view>
						<view class="dfa mb15">
							<view class="w50 dfa">
								<view class="tar" style="width:130px">商家退款说明：</view>
								<view>{{itemForm.merchant?itemForm.merchant:'--'}}</view>
							</view>
							<view class="w50 dfa">
								<view class="tar" style="width:130px">退款状态：</view>
								<view>{{itemForm.status==0?'申请维权':'维权结束'}}</view>
							</view>
						</view>
					</view>
				</view>
				<view v-if="tab2==1" class="p15 f18">
					<view class="bf5 tabel_h dfbc">
						<view class="w55">商品（元）</view>
						<view class="f-1 dfbc">
							<view>价格</view>
							<view>数量</view>
							<view>小计（元）</view>
							<view>状态</view>
						</view>
					</view>
					<view class="tabel_i dfbc bd1" v-for="(item,index) in itemForm.goods" :key="index">
						<view class="w55 dfa">
							<!-- <u--image :src="item.img" width="50px" height="50px" shape="square"></u--image> -->
							<text class="pl10">{{item.name}}</text>
						</view>
						<view class="f-1 dfbc">
							<view>{{item.price}}</view>
							<view>{{item.num}}</view>
							<view>{{item.subtotal}}</view>
							<view>{{item.status==0?'申请维权':'维权结束'}}</view>
						</view>
					</view>
				</view>
				<view v-if="tab2==2" class="p-20-30 f18 tal">
					<u-steps :current="itemForm.schedule.length" direction="column">
						<view v-for="(item,index) in itemForm.schedule" :key="index" style="display:flex">
							<text class="pr10 pt10">{{item.time}}</text>
							<u-steps-item :title="item.title" :desc="item.desc"></u-steps-item>
						</view>
					</u-steps>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	export default ({
		components: {},
		data() {
			return {
				tab: 0,
				tab2: 0,
				isItem: 0,
				tabs2: ['基础信息', '商品信息', '订单日志'],
				//takeOut  fastfood  cash  value
				itemForm: {},
				list: [{
						id: 0,
						orderSn: '202308091545102789455',
						amount: '59.00',
						refund: '59.00',
						state: 1, //退款方式
						buyer: '散客',
						creat_at: '2023-08-09 14:40:06',
						type: 'refund',
						reason: '',
						instructions: '',
						merchant: '',
						status: 1, //维权结束1  申请维权0
						goods: [{
							name: '德尔玛DX700家用手持吸尘器',
							price: '142.00',
							img: '',
							num: 2,
							subtotal: '284.00',
							status: 0,
						}],
						schedule: [{
							title: '买家申请退款',
							time: '2023-08-05',
							desc: '09:10:43'
						}, ]
					},
					{
						id: 1,
						orderSn: '2023080915451027484574',
						amount: '473.44',
						refund: '473.44',
						state: 0, //退款方式  1退款退货 0原路退款
						buyer: '雨辰',
						creat_at: '2023-08-05 09:10:43',
						type: 'refund',
						reason: '未按约定时间发货',
						instructions: '',
						merchant: '',
						status: 0, //维权结束1  申请维权0
						goods: [{
							name: '九阳4L容量0涂层电饭煲智能预约',
							price: '539.00',
							img: '',
							num: 1,
							subtotal: '539.00',
							status: 0,
						}],
						schedule: [{
							title: '买家申请退款',
							time: '2023-08-05',
							desc: '09:10:43'
						}, ]
					}
				]
			}
		},
		created: function() {
			this.itemForm = this.list[0]
		},
		methods: {
			sectionChange(e) {
				this.tab = e
			},
			clickItem(item, index) {
				this.isItem = item.id
				this.itemForm = item
			}
		}
	})
</script>

<style lang="scss" scoped>
	.main {
		.left {
			display: flex;
			flex-direction: column;
			justify-content: space-between;
			width: 500px;

			/deep/.u-input {
				background: #f5f5f5;
			}

			/deep/.u-subsection {
				height: 35px;

				.u-subsection__item__text {
					span {
						color: #000;
						font-size: 16px !important;
					}
				}
			}

			.list {
				overflow-y: auto;
			}

			.isItem {
				background: #fff6cd;
			}
		}

		.tabel_h {
			padding: 0 10px 0 38px;
			height: 56px;
		}

		.tabel_i {
			padding: 10px 10px 10px 38px;
			height: 70px;
		}
	}
</style>
<template>
	<view class="f-y-bt h100">
		<view class="main f-1 f-bt bf">
			<view class="left br1">
				<view class="p10 bd1 tac">员工</view>
				<view class="p-10-15 bd1">
					<u--input placeholder="请输入员工名称/手机号" prefixIcon="search"
						prefixIconStyle="font-size: 22px;color: #909399"></u--input>
				</view>
				<view v-if="list&&list.length>0" class="f-1 list">
					<view :class="isItem==item.id?'isItem':''" class="p20 f-x-bt bd1" v-for="(item,index) in list"
						:key="index" @click="clickItem(item,index)">
						<u-avatar :src="item.avatar" size="65"></u-avatar>
						<view class="f-1 dfbc pl15">
							<view>
								<view class="f18 mb15">{{item.name}}</view>
								<view class="f20">{{item.role=='admin'?'门店管理员':'收银员'}}</view>
							</view>
							<view style="display:flex;flex-direction: column;align-items: flex-end;">
								<view class="f20 mb15 cfd">{{item.state==0?'正常':'-'}}</view>
								<view class="f20">{{item.creat_at}}</view>
							</view>
						</view>
					</view>
				</view>
				<view v-else class="f-1 f-c-c" style="overflow-y:auto">
					<u-empty mode="car" icon="http://cdn.uviewui.com/uview/empty/car.png">
					</u-empty>
				</view>
				<view class="bf p10 l_bot" style="border-top: 1px solid #ddd;">
					<u-button text="添加员工" color="#4275F4"></u-button>
				</view>
			</view>
			<view class="f-1">
				<view class="p10 bd1 tac">员工详情</view>
				<view class="p20 f20">
					<view class="mb30">基本信息</view>
					<view class="f-bt">
						<view class="f-1">
							<view class="dfa mb10">
								<view class="tar mr10" style="width: 150px;">员工名称：</view>
								<view>{{itemForm.name}}</view>
							</view>
							<view class="dfa mb10">
								<view class="tar mr10" style="width: 150px;">员工角色：</view>
								<view>{{itemForm.role=='admin'?'门店管理员':'收银员'}}</view>
							</view>
							<view class="dfa mb10">
								<view class="tar mr10" style="width: 150px;">员工状态：</view>
								<view>{{itemForm.state==0?'正常':'--'}}</view>
							</view>
							<view class="dfa mb10">
								<view class="tar mr10" style="width: 150px;">最后登录IP：</view>
								<view>{{itemForm.ip}}</view>
							</view>
							<view class="dfa mb10">
								<view class="tar mr10" style="width: 150px;">最后登录时间：</view>
								<view>{{itemForm.creat_at}}</view>
							</view>
						</view>
						<u-avatar :src="itemForm.avatar" size="150"></u-avatar>
					</view>
					<view class="butt mt10 mb10">
						<view class="mr10"><u-button color="#FD8906" text="修改" plain></u-button></view>
						<view><u-button color="#FD8906" text="删除" plain></u-button></view>
					</view>
					<view class="mb30">操作日志</view>
					<view class="empty f16 f-c-c p20 c9">没有更多数据</view>
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
				show: false,
				showDetail: false,
				isItem: 0,
				//takeOut  fastfood  cash  value
				itemForm: {},
				form: {},
				list: [{
						id: 0,
						name: 'aaaaa',
						role: 'normal',
						state: 0,
						ip: '39.129.64.135',
						creat_at: '2022-12-12 15:14:43',
					},
					{
						id: 1,
						name: 'qqqqq',
						role: 'admin',
						state: 0,
						creat_at: '2022-10-12 15:14:43',
					}
				],
			}
		},
		created: function() {
			this.itemForm = this.list[0]
		},
		methods: {
			clickItem(item, index) {
				this.isItem = item.id
				this.itemForm = item
			},
			clickTab(index) {
				console.log(index);
				this.show = true
				if (index == 0) {
					this.form = {
						...this.itemForm
					}
					this.showDetail = true
				}

			}
		}
	})
</script>

<style lang="scss" scoped>
	/deep/.u-button {
		span {
			color: #000;
		}
	}

	.main {
		.left {
			display: flex;
			flex-direction: column;
			justify-content: space-between;
			width: 500px;

			/deep/.u-input {
				background: #f5f5f5;
			}

			.list {
				max-height: calc(100vh - 215px);
				overflow-y: auto;

				.l_bot {
					width: 499px;
				}
			}

			.isItem {
				background: #fffbe7;
			}
		}

		.butt {
			display: flex;
			justify-content: flex-end;

			/deep/.u-button {
				width: 102px;
				height: 42px;

				.u-button__text {
					color: #4275F4;
				}
			}
		}
	}
</style>
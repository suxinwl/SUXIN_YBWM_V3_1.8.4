<template>
	<u-overlay :show="isNotice" @click="close">
		<view class="typer f-y-bt bf bs10 c0" @tap.stop>
			<view class="bd1 p20" style="padding-bottom: 0px;">
				<u-tabs :current="current" :list="list" lineColor="#4275F4" lineWidth="35px" :scrollable="false"
					:itemStyle="{fontSize:'18px',color:'#000'}" :activeStyle="{fontWeight:'bold'}"></u-tabs>
			</view>
			<!-- <view class="f-1 f-c-c p20 c9 f16">没有更多消息了</view> -->
			<view class="f-1">
				<view class="f-bt bd1 p20" v-for="(item,index) in notData" :key="index" style="height:150px">
					<view class="f-c-c" style="width:50px;height:50px;border-radius:25px;background:#4275F4;">
						<text class="iconfont icon-xiaoxi cf" style="font-size:50px;"></text>
					</view>
					<view class="ml15 f-1 f-y-bt">
						<view class="">
							<view class="f20 mb10">{{item.title}}</view>
							<view class="f16">桌号/牌号：{{item.orderNum}},共{{item.goodsNum}}种菜品</view>
						</view>
						<view class="f-x-bt">
							<view class="f14 c9">刚刚</view>
							<view v-if="item.type=='notice'" class="">
								<u-button color="#4275F4" text="" :customStyle="{width:'90px',height:'35px'}"
									@click="getNotice(item,index)">
									<text class="c0">我知道了</text></u-button>
							</view>
							<view v-else class="dfa">
								<u-button text="拒单" :customStyle="{width:'90px',height:'35px'}"
									@click="reject(item,index)"></u-button>
								<u-button color="#4275F4" :customStyle="{marginLeft:'10px',width:'90px',height:'35px'}"
									@click="takeOrder(item,index)">
									<text class="c0">接单</text></u-button>
							</view>
						</view>
					</view>
				</view>
			</view>
			<u-modal :show="show" :showCancelButton="true" width="250px" title=" " cancelText="取消" content='确定拒单吗？'
				@confirm="confirm" @cancel="show=false"></u-modal>
		</view>
	</u-overlay>
</template>

<script>
	export default {
		props: {

		},
		data() {
			return {
				isNotice:false,
				show: false,
				current: 0,
				acIndex: 0,
				list: [{
					name: '外卖/自提',
				}, {
					name: '堂食/外带',
				}, {
					name: '系统'
				}, {
					name: '公告'
				}],
				notData: [{
					type: 'notice',
					title: '桌10呼叫服务员',
					create_at: '刚刚',
				}, {
					type: 'order',
					title: '您有新的顾客订单，请及时处理',
					create_at: '刚刚',
					orderNum: '桌10',
					goodsNum: 9
				}, {
					type: 'order',
					title: '您有新的顾客订单，请及时处理',
					create_at: '刚刚',
					orderNum: '桌1',
					goodsNum: 6
				}]
			}
		},
		methods: {
			open() {
				this.isNotice = true
			},
			close() {
				this.isNotice = false
			},
			getNotice(item, index) {
				this.notData.splice(index, 1)
			},
			//接单
			takeOrder(item, index) {
				this.notData.splice(index, 1)
			},
			//拒单
			reject(item, index) {
				this.acIndex = index
				this.show = true
			},
			//取消
			confirm() {
				this.notData.splice(this.acIndex, 1)
				this.show = false
			}
		}
	}
</script>

<style lang="scss" scoped>
	.typer {
		position: fixed;
		right: 0;
		width: 500px;
		height: 100vh;

		/deep/.u-tabs {
			.u-tabs__wrapper__nav__item {
				padding-bottom: 15px;
			}

			.u-tabs__wrapper__nav__line {
				bottom: 0px;
			}

			.u-tabs__wrapper__nav__item__text {
				font-size: 18px;
			}
		}

		/deep/.u-modal__content {
			text-align: center;
		}
	}
</style>
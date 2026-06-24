<template>
	<view class="f-y-bt h100">
		<view class="right f-1 p20 bf">
			<view class="mb20 wei6">营业数据</view>
			<view style="width:480px;">
				<u-subsection mode="subsection" activeColor="#4275F4" :list="tabs" :current="tab"
					@change="changeTab"></u-subsection>
			</view>
			<view class="mt20 mb20 wei6">线下收银</view>
			<view class="cashes">
				<view class="c_item p15" v-for="(item,index) in cashes" :key="index">
					<view class="mb10">{{item.title}}</view>
					<view class="wei6 f24">{{item.num}}</view>
					<view class="tar cfd f18 mt15" @click="detail(item,index)">查看详情</view>
				</view>
			</view>
			<view class="mt5 mb20 wei6">线上商城</view>
			<view class="cashes">
				<view class="c_item p15" v-for="(item,index) in onlines" :key="index">
					<view class="mb10">{{item.title}}</view>
					<view class="wei6 f24">{{item.num}}</view>
					<view class="tar cfd f18 mt15" @click="detail(item,index)">查看详情</view>
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
				tabs: ['今日', '昨日', '7日内', '30日内', '自定义'],
				cashes: [{
					title: '预计收入(元)',
					num: '0.00',
				}, {
					title: '开单金额数(元)',
					num: '0.00',
				}, {
					title: '开单数量',
					num: 0,
				}, {
					title: '办卡金额数(元)',
					num: '0.00',
				}, {
					title: '办卡数',
					num: 0,
				}, {
					title: '会员充值金额(元)',
					num: '0.00',
				}, {
					title: '会员充值数量',
					num: 0,
				}, {
					title: '会员退款金额(元)',
					num: '0.00',
				}, {
					title: '会员退款数量',
					num: 0,
				}, {
					title: '门店下单会员数',
					num: 0,
				}, {
					title: '会员余额消费金额',
					num: '0.00',
				}],
				onlines: [{
					title: '商城订单(元)',
					num: '0.00',
				}, {
					title: '退款维权(元)',
					num: '0.00',
				}]
			}
		},
		created: function() {},
		methods: {
			changeTab(e) {
				this.tab = e
			},
			detail(item, index) {

			}
		}
	})
</script>

<style lang="scss" scoped>
	.right {
		height: 100vh;
		overflow-y: auto;

		/deep/.u-subsection {
			height: 40px;
			border-radius: 6px;

			span {
				font-size: 18px;
				color: #000;
			}
		}

		.cashes {
			display: flex;
			flex-wrap: wrap;

			.c_item {
				margin-right: 15px;
				margin-bottom: 15px;
				width: 238px;
				height: 128px;
				border-radius: 5px;
				border: 1px solid #ddd;
			}
		}
	}
</style>
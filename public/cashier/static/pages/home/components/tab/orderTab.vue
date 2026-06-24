<template>
	<view class="tabs">
		<u-tabs :list="list1" @click="handTabs" :current="current" lineColor="#4275F4"
			:activeStyle="{fontWeight: 'bold',color:'#000'}"></u-tabs>
	</view>
</template>

<script>
	export default ({
		components: {},
		data() {
			return {
				list1: [{
					name: '店内订单',
					value: 'inStoreRef',
				}, {
					name: '自提订单',
					value: 'selfRef',
				}, {
					name: '自营外卖',
					value: 'sideRef',
				}, {
					name: '买单订单',
					value: 'billRef',
				}, {
					name: '储值订单',
					value: 'valueRef',
				}, {
					name: '积分商城',
					value: 'integralRef',
				}],
				current: 0,
			}
		},
		methods: {
			handTabs(e) {
				this.$emit('handTabs', e)
			},
		}
	})
</script>

<style lang="scss" scoped>
	.tabs {
		.tab_i {
			display: inline-block;
			background: #f5f5f5;
			border: 1px solid #e5e5e5;
		}

		.curr {
			background: #4275F4;
			color: #000;
			border: 1px solid #4275F4;
		}

		// /deep/.u-tabs__wrapper__nav__item__text {
		// 	font-weight: bold;
		// }
	}
</style>
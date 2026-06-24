<template>
	<view class="tabs">
		<u-tabs :list="list1" @click="handTabs" :current="current" lineColor="#4275F4" :activeStyle="{fontWeight: 'bold',color:'#000'}"></u-tabs>
	</view>
</template>

<script>
	export default ({
		components: {},
		data() {
			return {
				list1: [{
					name: '会员信息',
					value: 'userInfo',
				}, {
					name: '优惠券',
					value: 'couponInfo',
				}, {
					name: '储值变更明细',
					value: 'valueInfo',
				}, {
					name: '积分明细',
					value: 'integralInfo',
				}, {
					name: '会员卡信息',
					value: 'cardInfo',
				}],
				current: 0,
			}
		},
		methods: {
			handTabs(e) {
				this.$emit('handTabs',e)
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
	}
</style>
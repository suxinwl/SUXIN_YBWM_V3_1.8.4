<template>
	<view class="page w100 h100">
		<view class="top bf mb5 f-x-bt p15 w100">
			<view class="flex f-y-c" @click="back">
				<u-icon name="arrow-leftward" color="#000" size="24"></u-icon>
				<text class="ml10 f20">交班</text>
			</view>
			<view class="dfa">
				<tool @cT="changeTab"></tool>
			</view>
		</view>
		<view class="f-1 w100 p20 bf">
			<view class="">
				<view class="f-bt p20 bbtr">
					<view class="f-g-1">
						<view class="c9 f14">开班时间</view>
						<view class="wei f34 c0 mt10">14：21：17</view>
						<view class="c0 mt10 f16">2023年10月07日</view>
					</view>
					<view class="f-g-1">
						<view class="c9 f14">交班时间</view>
						<view class="wei f34 c0 mt10">11：27：17</view>
						<view class="c0 mt10 f16">2023年10月24日</view>
					</view>
					<view class="f-g-1">
						<view class="c9 f14">交班人</view>
						<view class="wei f34 c0 mt10">超级管理员</view>
					</view>
					<view class="f-g-1">
						<view class="c9 f14">已结账单数</view>
						<view class="wei f34 c0 mt10">3单</view>
						<view class="mt10 f16 c9">未结账订单不会计入本班次</view>
					</view>
				</view>
			</view>
			<view class="mt30 w100 f-c">
				<view class="w25 mr30">
					<u-button color="#4275F4" size="large" :customStyle="{marginRight:'10px'}" @click="goRec">
						<text class="f18">开始交班</text></u-button>
				</view>
				<view class="w25">
					<u-button color="#4275F4" size="large" type="primary" :plain="true" :customStyle="{}" @click="seeRec">
						<text class="f18">查看交班记录</text></u-button>
				</view>
			</view>
		</view>
	</view>
</template>

<script>

	import tool from '@/components/tool/tool.vue'
	export default {
		components: {
			tool,
		},
		data() {
			return {
				staff: {
					name: "test",

				}
			}
		},
		methods: {
			changeTab(item, index) {
				uni.navigateTo({
					url: `/pages/home/index?current=13&l_title='硬件管理'`
				})
			},
			goRec() {
				uni.navigateTo({
					url: '/pages/handover/handOver'
				})
			},
			seeRec() {
				uni.navigateTo({
					url: '/pages/handover/handRecord'
				})
			},
			back() {
				uni.navigateTo({
					url: '/pages/home/index'
				})
			},
		}
	}
</script>

<style>
	.page {
		display: flex;
		flex-direction: column;
		align-items: center;
	}

	.top {
		height: 7.1614vh;
	}

	.bbtr {
		border: 1px solid #e0e0e0;
		border-radius: 5px;
	}
	

	@media (min-width: 1500px) and (max-width: 3280px) {
		.top {
			height: 55px;
		}
	}
</style>
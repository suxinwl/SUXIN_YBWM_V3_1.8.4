<template>
	<view class="f-1 f-c-c h100">
		<image src="@/static/imgs/data.png" mode="widthFix" class="icon" v-if="t=='dd' || t=='jh'"></image>
		<view class="f15" style="color:#c0c4cc">{{txt}}</view>
	</view>
</template>

<script>
	export default {
		props: {
			t: {
				type: String,
				default: ''
			},
			txt: {
				type: String,
				default: '暂无数据'
			}
		},
		data() {
			return {

			}
		},
		methods: {

		}
	}
</script>

<style lang="scss" scoped>
	.icon {
		width: 180px;
		height: 180px;
	}
</style>
<template>
	<view class="wh p10 bf369">
		<!-- <view class="item f-x-bt  mb10 c3" v-for="(item,index) in list" :key="index" @click="choose(item)">
			<view class="f-s ">
				<text>{{item.name}}</text>
				<u-tag v-if="item.realtimeState==1" text="营业" type="success" plain shape="circle"></u-tag>
				<u-tag v-else-if="item.realtimeState==2" text="繁忙" type="warning" plain shape="circle"></u-tag>
				<u-tag v-else-if="item.realtimeState==3" text="休息中" type="error" plain shape="circle"></u-tag>
				<u-tag v-else-if="item.realtimeState==4" text="接受预定" plain shape="circle"></u-tag>
			</view>
			<u-icon size="20" color="#999" name="arrow-right"></u-icon>
		</view> -->
		<view class="tac p-35-0 f22 mb10 c0">请选择门店</view>
		<view class="f-y-bt h100">
			<view v-if="list && list.length">
				<view class="iList bf f-y-bt ml10 mb10" v-for="(item,index) in list" :key="index">
					<view class="p20" @click="choose(item)">
						<view class="f-bt f-y-c">
							<view class="c0 wei6 f22 t-o-e">
								<view>{{item.name}}</view>
								<view class="flex mt5">
									<u-tag v-if="item.isolate==1" text="独立门店" plain shape="circle"></u-tag>
									<u-tag v-else text="品牌门店" type="success" plain shape="circle"></u-tag>
								</view>
							</view>
							<view class="c0" style="color: #4275F4;">ID：{{item.id}}</view>
						</view>
					</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	import {
		mapMutations
	} from 'vuex'
	export default {
		data() {
			return {
				subColor: uni.getStorageSync('subject_color'),
				list: []
			}
		},
		async onLoad() {
			this.fetchData()
		},
		methods: {
			...mapMutations(["setStoreId"]),
			async fetchData() {
				const {
					data: {
						list,
						total
					},
				} = await this.beg.request({
					url: this.api.storeList,
					data: {
						pageSize: 999,
						isolate: 1,
					},
				})
				this.list = list
				this.total = total;
			},
			choose(v) {
				// getApp().globalData.storeName = v.name
				this.setStoreId(v)
				uni.reLaunch({
					url: '/pages/home/index'
				})
			}
		}
	}
</script>

<style lang="scss" scoped>
	// .index {
	// 	padding: 24upx;
	// 	background-color: #f6f6f6;

	// 	.item {
	// 		width: 100%;
	// 		border-radius: 7px;
	// 		padding: 35rpx 24rpx;
	// 		background-color: #fff;

	// 		/deep/.u-tag--medium {
	// 			margin-left: 8px;
	// 			height: 18px;
	// 			line-height: 18px;
	// 			padding: 0 5px;
	// 		}

	// 		/deep/.u-tag__text--medium {
	// 			font-size: 12px;
	// 		}
	// 	}
	// }
	.bf369 {
		background: #F3F6F9;
	}
	
	.iList {
		position: relative;
		display: inline-flex;
		width: 30.2342vw;
		cursor: pointer;
		box-shadow: 0px 2px 10px 4px #ddd;
		transition: all 0.2s ease-in-out;
		border-radius: 8px;
	
		&:hover {
			transform: translateY(-10px);
			transition: all 0.2s ease-in-out;
		}
	}
	
	@media (min-width: 1500px) and (max-width: 3280px) {
		.iList {
			width: 413px;
		}
	}
</style>
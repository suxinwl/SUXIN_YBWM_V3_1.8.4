<template>
	<view class="wh p10 bf369">
		<view class="tac p-35-0 f22 mb10 c0">
			选择你要登录的品牌
		</view>
		<view class="f-y-bt h100">
			<view v-if="list && list.length">
				<view class="iList bf f-y-bt ml10 mb10" v-for="(item,index) in list" :key="index">
					<view class="p20" @click="toHome(item)">
						<view class="f-bt">
							<view>
								<u-tag :text="item.typeFormat" type="success" size="mini" v-if="item.type==0"></u-tag>
								<u-tag :text="item.typeFormat" color="#fff" bgColor="#4275F4" borderColor="#4275F4" type="success" size="mini" v-if="item.type==1"></u-tag>
							</view>
							<view class="c0" style="color: #4275F4;">门店：{{item.storeCount}}</view>
						</view>
						<view class="c0 wei6 f18 t-o-e mt20">{{item.applyName}}</view>
						<view class="f-bt f-y-c ml10 mt10">
							<u-avatar :src="item.applyImage" size="55" shape="circle"></u-avatar>
							<view class="pl10 f-g-1">
								<view class="mb5 c6 f14 t-o-e">店铺套餐：{{item.muster && item.muster.title}}</view>
								<view class="c6 f14 mt10">到期时间：{{item.endTime}}</view>
							</view>
						</view>
					</view>
				</view>
				<view class="pagona mt10 f-c-xc l-h1">
					<uni-pagination :current="queryForm.pageNo" :total="total" :pageSize="queryForm.pageSize" @change="change"
						title="标题文字" />
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
				list: [],
				total: 0,
				queryForm: {
					pageNo: 1,
					pageSize: 20,
				},
			}
		},
		async onLoad() {
			this.setStoreId('')
			this.fetchData()
		},
		onPullDownRefresh() {
			setTimeout(() => {
				uni.stopPullDownRefresh()
			}, 1000)
		},
		methods: {
			...mapMutations(["setStoreInfo", "setStoreId"]),
			async fetchData() {
				const {
					data: {
						list,
						total
					},
				} = await this.beg.request({
					url: this.api.applyList,
					data: this.queryForm,
				})
				this.list = list
				this.total = total;
			},
			toHome(v) {
				this.setStoreInfo(v)
				uni.navigateTo({
					url: '/pages/login/selectShop'
				})
			},
			change(e) {
				this.queryForm.pageNo = e.current;
				this.fetchData()
			},
		}
	}
</script>

<style lang="scss" scoped>
	.bf369 {
		background: #F3F6F9;
	}

	.iList {
		position: relative;
		display: inline-flex;
		width: 30.2342vw;
		// height: 26.0416vh;
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
			// height: 200px;
		}
	}
</style>
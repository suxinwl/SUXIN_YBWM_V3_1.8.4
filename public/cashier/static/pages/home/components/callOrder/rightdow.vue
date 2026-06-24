<template>
	<view>
		<u-popup :show="show" :round="10" mode="right" @close="close" :closeable="false">
			<view class="main f-y-bt f-1 f22">
				<view class="bcef0 p20 flex wei f24 f-bt">
					<view class="">取单号：{{form.pickNo}}</view>
					<view class="">{{form.orderIndex && form.orderIndex.stateFormat}}</view>
				</view>
				<view class="f-1 f-y-bt f-g-1">
					<view class="list p-10-15 f18">
						<view class="c6 p-0-10">
							<view class="flex">
								<view class="f-g-0">下单渠道：</view>
								<view class="f-g-1">{{form.orderIndex && form.orderIndex.sourceFormat}}</view>
							</view>
							<view class="flex mt10">
								<view class="f-g-0">取单方式：</view>
								<view class="f-g-1">
									<text v-if="form.packaging==0">店内就餐</text>
									<text v-if="form.packaging==1">打包带走</text>
									<!-- {{form.orderIndex && form.orderIndex.packagingFormat}} -->
								</view>
							</view>
							<view class="flex mt10">
								<view class="f-g-0">下单人：</view>
								<view class="f-g-1">
									{{form.orderIndex && form.orderIndex.user && form.orderIndex.user.nickname || '--'}}
								</view>
							</view>
							<view class="flex mt10">
								<view class="f-g-0">下单时间：</view>
								<view class="f-g-1">{{form.created_at}}</view>
							</view>
							<view class="flex mt10">
								<view class="f-g-0">订单号：</view>
								<view class="f-g-1">{{form.orderSn}}</view>
							</view>
						</view>
						<view class="f-bt bcef0 mt20 p10 bs5 f20">
							<view>商品信息</view>
							<view>共计{{form.orderIndex && form.orderIndex.goodsNum}}件</view>
						</view>
						<view class="mt20">
							<view class="bd2 mt10 pb10" v-for="(v,i) in form.orderIndex && form.orderIndex.goods">
								<view class="f-bt f18 p-0-10">
									<view class="flex f-y-c">
										<u-tag v-if="v.pack" class="mr5" text="包" size="small"
										bgColor="#1c9945" borderColor="#1c9945"
										style="display: inline-block;font-size: 12px;"></u-tag>
										<view class="wei overflowlnr">{{v.name}}</view>
									</view>
									<view>x{{v.num}}</view>
								</view>
								<view class="flex f-w f14 c9 p-0-10">
									<view v-if="v.attrData && v.attrData.spec">
										[{{ v.attrData.spec }}]</view>
									<view v-if="v.attrData && v.attrData.attr">
										[{{ v.attrData.attr }}]</view>
									<view v-if="v.attrData && v.attrData.matal">
										{{ v.attrData.matal }}
									</view>
								</view>
								<view class="flex f-w f14 c9 p-0-10" v-if="v.setMealData && v.setMealData.length">
									<view v-for="(cv,ci) in v.setMealData" :key="ci" class="mr10">{{cv.name}}*{{cv.num}}
										<text v-if="cv.attrData && cv.attrData.attr" class="ml10">[{{ cv.attrData.attr }}]</text>
										<text v-if="cv.attrData && cv.attrData.matal" class="ml10">[{{ cv.attrData.matal }}]</text>
									</view>
								</view>
							</view>
						</view>
					</view>
					<view class="btn p20 f-x-e">
						<view class="mr20">
							<u-button type="primary" color="#4275F4" @click="handPring(form)" plain><text
									style="color: #4275F4;">打印小票</text></u-button>
						</view>
						<view>
							<u-button color="#4275F4" @click="qc(form)" v-if="form.state==3 || form.state==4"><text
									class="cf">强制完成</text></u-button>
						</view>
					</view>
				</view>
			</view>
		</u-popup>
	</view>
</template>

<script>
	export default {
		props: {

		},
		components: {},
		data() {
			return {
				show: false,
				form: {},
			}
		},
		methods: {
			open(v) {
				this.form = v
				this.show = true
			},
			close() {
				this.show = false
			},
			async qc(v) {
				const {
					msg
				} = await this.beg.request({
					url: `${this.api.qcComplete}/${v.id}`,
					method: "POST",
				})
				uni.$u.toast(msg)
				this.close()
				this.$emit('fetchData')
			},
			async handPring(v) {
				let {
					data,
					msg
				} = await this.beg.request({
					url: `${this.api.printOrder}/${v.orderSn}`,
					method: "POST",
					data: {
						storeId: v.orderIndex.storeId,
						scene: v.orderIndex.scene,
						diningType: v.orderIndex.diningType,
					},
				})
				uni.$u.toast(msg)
				this.close()
				this.$emit('fetchData')
			},
		}
	}
</script>

<style lang="scss" scoped>
	.main {
		width: 450px;

		.list {
			max-height: 79.4270vh;
			overflow: hidden;
			overflow-y: scroll;
		}

		.btn {
			box-shadow: 0px 0px 10px 0px #ddd;
		}

		.bcef0 {
			background: #ECEBF0;
		}
	}

	@media (min-width: 1500px) and (max-width: 3280px) {
		.main {

			.list {
				max-height: 610px;
			}
		}
	}
</style>
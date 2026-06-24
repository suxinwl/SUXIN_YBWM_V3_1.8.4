<template>
	<view class="f-1 f-y-bt">
		<view class="f-g-1 p10">
			<block v-if="carList.generalGoods">
				<!-- <view class="f-x-bt f18 mb10">
					<view class="">结算清单（{{carList.goodsNum}}）</view>
					<view class="f-c" @click="clearAll">清空</view>
				</view> -->
				<view class="f-x-bt f18 wei6 mb10 l-h1 p-0-10">
					<text class="w50">商品信息</text>
					<view class="f-1 f-bt">
						<text>数量</text>
						<text>小计</text>
					</view>
				</view>
			</block>
			<view class="f-y-bt">
				<view
					v-if="carList.goodsList&&carList.goodsList.length || carList.prentGoods&&carList.prentGoods.length"
					class="f-1 list" :class="mode=='tableOrder' && carList.tableMoney >0? 'tableH' : ''">
					<uni-table v-if="batch" ref="table" type="selection" emptyText="暂无更多数据"
						@selection-change="selectionChange">
						<uni-tr>
							<uni-th class="f-1 f-x-bt">
								<view class="f-x-bt f18" style="width:303px">
									<view>全选</view>
									<view>已选<text style="padding:0 3px;color:#FD8906">0</text>件</view>
								</view>
							</uni-th>
						</uni-tr>
						<uni-tr :class="selectItem==item?'isSelect':''" class="bd2 p10 f18"
							v-for="(item,index) in carList.generalGoods" :key="index" @click="chooseGood(item,index)">
							<uni-td>
								<view class="f-x-bt">
									<!-- <u--image v-if="product_show==1||product_show==3" class="mr10" :src="item.img"
										:radius="6" width="60px" height="60px"></u--image> -->
									<view class="f-1">
										<view class="f-bt mb10">
											<view class="f-1 overflowlnr f18">
												<u-tag v-if="item.ispack" class="mr5" text="包" size="small"
													bgColor="#1c9945" borderColor="#1c9945"
													style="display: inline-block;"></u-tag>
												<view class="overflowlnr" style="max-width:320px">
													{{item.name || item.goods && item.goods.name}}
												</view>
											</view>
											<!-- <view v-if="type!='oAfter'" class="f18" style="color:#FD8906" @click.stop="delItem(item,index)">删除</view> -->
										</view>
										<view class="f-x-bt f18">
											<view class="f-1 f-bt">
												<text>x{{item.num}}</text>
												<text class="c6">￥{{item.money}}</text>
											</view>
										</view>
									</view>
								</view>
							</uni-td>
						</uni-tr>
					</uni-table>
					<block v-else>
						<view :class="actgood==item.id?'isSelect':''" class="bd2 p10 f18"
							v-for="(item,index) in carList.generalGoods" :key="index" @click="chooseGood(item,index)">
							<view class="f-x-bt">
								<!-- <u--image v-if="product_show==1||product_show==3" class="mr10" :src="item.img" :radius="6"
									width="60px" height="60px"></u--image> -->
								<view class="f-1">
									<view class="f-bt">
										<view class="f-1 overflowlnr f18">
											<view class="dfa">
												<view class="w50 flex l-h1">
													<u-tag v-if="item.pack" class="mr5" text="包" size="small"
														bgColor="#1c9945" borderColor="#1c9945"
														style="display: inline-block;font-size: 0.8784vw;"></u-tag>
													<u-tag v-if="item.state==8" class="mr5" :text="item.discountLabel"
														size="small" bgColor="#3E77B9" borderColor="#3E77B9"
														style="display: inline-block;font-size: 0.8784vw;"></u-tag>
													<view class="overflowlnr" style="max-width:320px">
														{{item.name || item.goods && item.goods.name}}
													</view>
												</view>
												<view class="f-1 f-bt">
													<text class="f18 c6">x{{item.num}}</text>
													<text class="c6">￥{{item.money}}</text>
												</view>
											</view>
											<view class="flex f-w f14 c9">
												<view v-if="item.attrData && item.attrData.spec">
													[{{ item.attrData.spec }}]</view>
												<view v-if="item.attrData && item.attrData.attr">
													[{{ item.attrData.attr }}]</view>
												<view v-if="item.attrData && item.attrData.matal">
													{{ item.attrData.matal }}
												</view>
											</view>
											<view class="flex f-w f14 c9" v-if="item.setMealData && item.setMealData.length">
												<view v-for="(cv,ci) in item.setMealData" :key="ci">{{cv.name}}*{{cv.num}}
													<text v-if="cv.attrData && cv.attrData.attr" class="ml10">[{{ cv.attrData.attr }}]</text>
													<text v-if="cv.attrData && cv.attrData.matal" class="ml10">[{{ cv.attrData.matal }}]</text>
												</view>
											</view>
										</view>
										<!-- <view class="f18" v-if="type!='oAfter'"  style="color:#FD8906" @click.stop="delItem({g: item,addwz: -item.num})">删除</view> -->
									</view>
									<view class="flex f-w f14 c9 mt5" v-if="item.notes">备注：{{item.notes}}</view>
								</view>
							</view>
							<!-- <view v-if="item.remarks.join('，')!=''" class="f15 c9 mt10">
								备注：{{item.remarks.join('，')}}</view> -->
						</view>
						<view v-if="carList.discountsGoods && carList.discountsGoods.length">
							<view class="tit f-bt p10 f-y-c">
								<view class="f-g-0 c9 f15">以下是优惠商品</view>
								<view class="line f-g-1"></view>
							</view>
							<view :class="actgood==item.id?'isSelect':''" class="bd2 p10 f18"
								v-for="(item,index) in carList.discountsGoods" :key="index"
								@click="chooseGood(item,index)">
								<view class="f-x-bt">
									<view class="f-1">
										<view class="f-bt">
											<view class="f-1 f18">
												<view class="dfa f-s">
													<view class="w50 f-col">
														<view class="flex l-h1">
															<u-tag v-if="item.pack" class="mr5" text="包" size="small"
																bgColor="#1c9945" borderColor="#1c9945"
																style="display: inline-block;font-size: 12px;"></u-tag>
															<view class="overflowlnr" style="max-width:320px">
																{{item.name || item.goods && item.goods.name}}
															</view>
														</view>
														<view v-if="item.discountType" class="flex">
															<view class="i_tag mt5 f10 cf5 f-c f-g-0">{{item.discountLabel}}</view>
														</view>
													</view>
													<view class="f-1 f-bt">
														<text class="f18 c6">x{{item.num}}</text>
														<view class="t-r">
															<view class="">￥{{item.money}}</view>
															<view class="c9 f16 t-d-l ml10">￥{{item.sellMoney}}</view>
														</view>
													</view>
												</view>

												<view class="flex f-w f14 c9">
													<view v-if="item.attrData && item.attrData.spec">
														[{{ item.attrData.spec }}]</view>
													<view v-if="item.attrData && item.attrData.attr">
														[{{ item.attrData.attr }}]</view>
													<view v-if="item.attrData && item.attrData.matal">
														{{ item.attrData.matal }}
													</view>
												</view>
												<view class="flex f-w f14 c9" v-if="item.setMealData && item.setMealData.length">
													<view v-for="(cv,ci) in item.setMealData" :key="ci">{{cv.name}}*{{cv.num}}
														<text v-if="cv.attrData && cv.attrData.attr" class="ml10">[{{ cv.attrData.attr }}]</text>
														<text v-if="cv.attrData && cv.attrData.matal" class="ml10">[{{ cv.attrData.matal }}]</text>
													</view>
												</view>
											</view>
											<!-- <view v-if="type!='oAfter'"  class="f18" style="color:#FD8906" @click.stop="delItem({g: item,addwz: -item.num})">删除</view> -->
										</view>
										<!-- <view class="f-x-bt f18">
											<view class="f-1 f-bt">
												<text class="f18 c6">x{{item.num}}</text>
												<view class="dfa">
													<view class="">￥{{item.money}}</view>
													<view class="c9 f16 t-d-l ml10">￥{{item.sellMoney}}</view>
												</view>
											</view>
										</view> -->
										<view class="flex f-w f14 c9 mt5" v-if="item.notes">备注：{{item.notes}}</view>
									</view>
								</view>
							</view>
						</view>
						<view v-if="carList.diningType==4 && carList.prentGoods && carList.prentGoods.length">
							<view class="tit f-bt p10 f-y-c">
								<view class="f-g-0 c9 f15">以下为已点商品</view>
								<view class="line f-g-1"></view>
							</view>
							<view :class="actgood==item.id?'isSelect':''" class="bd2 p10 f18"
								v-for="(item,index) in carList.prentGoods" :key="index">
								<view class="f-x-bt c9">
									<view class="f-1">
										<view class="f-bt">
											<view class="f-1 overflowlnr f18 ">
												<view class="dfa f-s">
													<view class="w50 flex">
														<view class="overflowlnr" style="max-width:320px">
															{{item.name || item.goods && item.goods.name}}
														</view>
													</view>
													<view class="f-1 f-bt">
														<text class="f18 c9">x{{item.num}}</text>
														<view class="t-r">
															<view class="">￥{{item.money}}</view>
															<view class="c9 f16 t-d-l ml10">￥{{item.sellMoney}}</view>
														</view>
													</view>
												</view>
												<view class="flex f-w f14 c9">
													<view v-if="item.attrData && item.attrData.spec">
														[{{ item.attrData.spec }}]</view>
													<view v-if="item.attrData && item.attrData.attr">
														[{{ item.attrData.attr }}]</view>
													<view v-if="item.attrData && item.attrData.matal">
														{{ item.attrData.matal }}
													</view>
												</view>
												<view class="flex f-w f14 c9" v-if="item.setMealData && item.setMealData.length">
													<view v-for="(cv,ci) in item.setMealData" :key="ci">{{cv.name}}*{{cv.num}}
														<text v-if="cv.attrData && cv.attrData.attr" class="ml10">[{{ cv.attrData.attr }}]</text>
														<text v-if="cv.attrData && cv.attrData.matal" class="ml10">[{{ cv.attrData.matal }}]</text>
													</view>
												</view>
											</view>
										</view>
										<view class="flex f-w f14 c9 mt5" v-if="item.notes">备注：{{item.notes}}</view>
									</view>
								</view>
							</view>
						</view>
					</block>
					<view v-if="checkInfo && checkInfo.notes || params.notes" class="c9 f15 mt10">
						整单备注：{{checkInfo.notes || params.notes}}</view>
				</view>
				<view v-if="carList.tableNum && carList.tableMoney>0">
					<view class="bd2 p10 f18 f-bt f-1 c9">
						<view class="f-1 overflowlnr f18">
							<view class="dfa f-s">
								<view class="w50 flex">
									<view class="overflowlnr" style="max-width:320px">
										{{carList.tableFormat || '服务费'}}
									</view>
								</view>
								<view class="f-1 f-bt">
									<text class="f18 c9">x{{carList.tableNum}}</text>
									<view class="t-r">
										￥{{carList.tableMoney}}
									</view>
								</view>
							</view>
						</view>
					</view>
				</view>
				<view v-if="carList.service_money>0">
					<view class="bd2 p10 f18 f-bt f-1 c9">
						<view class="f-1 overflowlnr f18">
							<view class="dfa f-s">
								<view class="w50 flex">
									<view class="overflowlnr" style="max-width:320px">
										{{'订单服务费'}}
									</view>
								</view>
								<view class="f-1 f-bt">
									<text class="f18 c9">x1</text>
									<view class="t-r">
										￥{{carList.service_money}}
									</view>
								</view>
							</view>
						</view>
					</view>
				</view>
				<view v-if="carList.goodsList&&carList.goodsList.length==0 && (carList.prentGoods&&carList.prentGoods.length==0 ||!carList.prentGoods)" class="f-1 f-c-c list">
					<image src="@/static/imgs/car.png" mode="" style="width: 180px;height:180px"></image>
					<view class="f15" style="color:#c0c4cc">点击右侧商品，选择商品进行结账</view>
				</view>
			</view>
		</view>
		<view class="f-x-bt p10 bd1 f-g-0 l-h1 left_db_media" style="padding-top: 0;">
			<view class="">
				<u-checkbox-group v-model="carList.pickAll" @change="allPack" v-if="mode!='tableOrder'">
					<u-checkbox :size="`${pad?'13':'20'}`" labelSize="`${pad?'14':'20'}`" iconSize="18" iconColor="#fff"
						activeColor="#4275F4" :customStyle="{fontSize:`${pad?'13px':'18px'}`}" label="整单打包" :name="1">
					</u-checkbox>
				</u-checkbox-group>
			</view>
			<view class="dfa" v-if="mode=='tableOrder' && carList.generalGoods">
				<!-- <view class="c9 f18 mr10">共{{carList.generalGoods && carList.generalGoods.length}}份</view> -->
				<view class="c9 f18 mr10">共{{ getGoodsNum(carList.generalGoods) }}份</view>
				<view class="f24 cf5 tar">
					<view class="">￥{{carList.money}}</view>
					<view v-if="carList.sellMoney>carList.money" class="c9 f14" style="text-decoration: line-through;">
						￥{{carList.sellMoney}}
					</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	export default {
		components: {

		},
		props: {
			mode: {
				type: String,
				default: 'fastOrder'
			},
			type: {
				type: String,
				default: 'oBefore'
			},
			ad: {
				type: String,
				default: '0'
			},
			carList: {
				type: Object,
				default: {}
			},
			batch: {
				type: Boolean,
				default: false
			},
			actgood: {
				type: Number,
				default: 0
			},
			checkInfo: {
				type: Object,
				default: {},
			},
			params: {
				type: Object,
				default: {},
			},
		},
		data() {
			return {
				wholePack: [],
			}
		},
		methods: {
			getGoodsNum(generalGoods){
				let num = 0;
				if(generalGoods && generalGoods.length > 0){
					generalGoods.forEach((v)=>{
						num = v.num+num
					})
				}
				return num
			},
			delItem(v) {
				this.$emit('dItem', v)
			},
			chooseGood(v, i) {
				this.$emit('chooseGood', v, i)
			},
			clearAll() {
				this.$emit('clearAll')
			},
			allPack(e) {
				this.$emit('allPack', e)
			},
		}
	}
</script>

<style lang="scss" scoped>
	.list {
		// max-height: calc(100vh - 325px);
		max-height: calc(100vh - 39.0625vh);
		overflow-y: auto;

		.isSelect {
			// background: rgba(#fff0a9, .4);
			background: #ebeef5;
			border-radius: 5px;
		}

		.i_tag {
			padding: 0 0.2196vw;
			border: 1px solid #FD8906;
			border-radius: 3px;
			background: #fff9ec;
		}

		/deep/.u-empty {
			height: 65.1041vh;
		}

		/deep/.u-tag-wrapper {
			width: 1.6837vw;

			span {
				padding-left: 3px
			}

			.u-tag__text--primary {
				width: 1.0980vw;
				height: 1.0980vw;
				line-height: 1.0980vw;
			}
		}

		/deep/.u-button {
			border-radius: 6px !important;
		}

		/deep/.checkbox {
			width: 10px !important;

			.checkbox--indeterminate,
			.is-checked {
				background: #4275F4 !important;
				border: 1px solid #4275F4;
			}

			.uni-table-checkbox:hover {
				border-color: #4275F4;
			}
		}
	}
	.tableH{
		max-height: calc(100vh - 44.4583vh);
	}

	.tit {
		.line {
			height: 1px;
			background: #e6e6e6;
			margin-left: 10px;
		}
	}

	@media (min-width: 1500px) and (max-width: 3280px) {
		.list {
			max-height: calc(100vh - 300px);
			overflow-y: auto;

			.i_tag {
				padding: 0px 3px;
				border: 1px solid #FD8906;
				border-radius: 3px;
				background: #fff9ec;
			}

			/deep/.u-empty {
				height: 500px;
			}

			/deep/.u-tag-wrapper {
				width: 23px;

				span {
					padding-left: 3px
				}

				.u-tag__text--primary {
					width: 15px;
					height: 15px;
					line-height: 15px;
				}
			}

			/deep/.u-button {
				border-radius: 6px !important;
			}

			/deep/.checkbox {
				width: 10px !important;

				.checkbox--indeterminate,
				.is-checked {
					background: #4275F4 !important;
					border: 1px solid #4275F4;
				}

				.uni-table-checkbox:hover {
					border-color: #4275F4;
				}
			}
		}
		.tableH{
			max-height: calc(100vh - 280px);
		}
	}
</style>
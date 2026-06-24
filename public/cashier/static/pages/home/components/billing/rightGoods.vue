<template>
	<view class="right f-1 f-y-bt bf rightGoods_media">
		<view class="p15 f-c">
			<!-- <view style="width:300px">
				<u-subsection :list="tabs" mode="subsection" :current="tab" activeColor="#4275F4"
					@change="sectionChange"></u-subsection>
			</view> -->
			<view class="flex f-g-1">
				<u-input placeholder="请输入商品名称/助记码" v-model="name" @input="search" border="surround"
					prefixIcon="search" prefixIconStyle="font-size: 22px;color: #909399">
						<template slot="suffix">
							<u-icon @click="clear" v-if="name" name="close-circle-fill" color="#999" size="20"></u-icon>
						</template>
					</u-input>
				<text class="pr15"></text>

			</view>
		</view>
		<view v-if="tab!=3" class="f-1 f-bt">
			<goods :list="list" :dataList="dataList" :queryForm="queryForm" :total="total" @handcar="handcar"
				@change="change" @addDish="handAddDish"></goods>
			<view class="bl1 kwarp">
				<view class="tac f18 kind bs6 mb10" :class="kind==i?'acKind':''" v-for="(v,i) in classfiy" :key="i"
					@click="changeKind(v,i)">{{v.name}}
				</view>
			</view>
		</view>
		<!-- <view v-if="tab==1" class="f-1 f-bt p-20-0">
			<view class="f-1 f-y-bt br1">
				<view class="f-1 r_cont">
					<view :class="list.some(v=>v.id==item.id)?'check':''" class="r_item p10 bs6"
						v-for="(item,index) in listData2" :key="index" @click="clickItem(item,index)">
						<view class="f20 mb5 wordall2">{{item.name}}</view>
						<view class="dfa f20">
							<u--image :src="item.img" :radius="6" shape="square" width="65px"
								height="65px"></u--image>
							<view class="ml10">
								<view class="mb5 cf5 f20">￥{{item.price.toFixed(2)}}</view>
								<view class="f18">库存：{{item.inventory}}</view>
							</view>
						</view>
					</view>
				</view>
				<view class="pagona mt10" style="height:30px">
					<uni-pagination :total="total" title="标题文字" />
				</view>
			</view>
			<view class="pl10" style="width:115px">
				<view class="tac f20 kind bs6 mb10" :class="kind==index?'acKind':''"
					v-for="(item,index) in kinds" :key="index" @click="changeKind(index)">{{item}}</view>
			</view>
		</view>
		<view v-if="tab==2" class="f-1 f-c p-15-0" style="align-items: flex-start;">
			<view class="mt20">
				<u-input class=" mb30" v-model="money" placeholder="请输入金额" type="number">
					<u--text text="元" slot="suffix" margin="0 0 0 3px" type="tips"></u--text>
				</u-input>
				<keybored type="digit" v-model="money" @doneClear="doneClear" @doneAdd="doneAdd">
				</keybored>
			</view>
		</view> -->
		<addDish ref="addDishRef" @addCar="addCar" />
	</view>
</template>

<script>
	import goods from '@/components/order/goods.vue'
	import addDish from '@/components/goods/addDish.vue';
	import {
		mapState,
		mapMutations,
	} from 'vuex'
	export default ({
		components: {
			goods,
			addDish,
		},
		props: {
			dataList: {
				type: Array,
				default: []
			},
			list: {
				type: Array,
				default: []
			},
			classfiy: {
				type: Array,
				default: [],
			},
			queryForm: {
				type: Object,
				default: {}
			},
			total: {
				type: Number,
				default: 0
			},
		},
		data() {
			return {
				tab: 0,
				tabs: ['所有商品'],
				name: '',
				kind: 0,
			}
		},
		computed: {

		},
		methods: {
			sectionChange(i) {
				this.tab = i
			},
			search() {
				this.$emit('search', this.name)
			},
			handcar(e) {
				this.$emit('handcar', e)
			},
			change(e) {
				this.$emit('change', e)
			},
			changeKind(v, i) {
				this.kind = i
				this.$emit('changeKind', v, i)
			},
			handAddDish() {
				this.$refs['addDishRef'].open()
			},
			clear(){
				this.name = ''
				this.$emit('search', this.name)
			},
			addCar(v){
				this.$emit('addCar', v)
			},
			closeAdd(){
				this.$refs['addDishRef'].close()
			},
		}
	})
</script>

<style lang="scss" scoped>
	.right {
		border-radius: 6px 0 0 0;
		/deep/.u-subsection--subsection {
			// height: 40px !important;
			height: 5.2083vh !important;
			border-radius: 6px;

			.u-subsection__item__text {
				span {
					color: #000;
					font-size: 18px !important;
				}
			}
		}

		.r_cont {
			max-height: calc(100vh - 215px);
			overflow: auto;

			.r_item {
				position: relative;
				display: inline-flex;
				flex-direction: column;
				justify-content: space-between;
				margin-right: 20rpx;
				margin-bottom: 20rpx;
				// width: 450rpx;
				// height: 280rpx;
				width: 32.9428vw;
				height: 36.4583vh;
				border: 2rpx solid #e6e6e6;
				border-radius: 10px;

				.badge {
					position: absolute;
					top: 0px;
					right: 0px;

					/deep/.u-badge {
						line-height: 16px;
						font-size: 16px;
					}
				}
			}

			.check {
				border: 2rpx solid #FD8906;
			}
		}

		.pagona {
			height: 50px;

			/deep/.uni-pagination {
				.page--active {
					display: inline-block;
					width: 30px;
					height: 30px;
					background: #4275F4 !important;
					color: #fff !important;
				}

				.uni-pagination__total {
					font-size: 20px;
				}

				span {
					font-size: 20px;
				}
			}
		}

		// /deep/.ljt-keyboard-body {
		// 	border-radius: 6px;
		// 	border: 1px solid #e5e5e5;

		// 	.ljt-keyboard-number-body {
		// 		width: 500px !important;
		// 		height: 260px !important;
		// 	}

		// 	.ljt-number-btn-confirm-2 {
		// 		background: #4275F4 !important;

		// 		span {
		// 			color: #000;
		// 			font-size: 20px;
		// 		}
		// 	}
		// }

		.kwarp {
			// width:115px;
			width:8.4187vw;
			overflow-y: auto;
			max-height: calc(100vh - 165px);
		}

		.kind {
			// width: 210rpx;
			// height: 90rpx;
			// line-height: 80rpx;
			width: 7.6866vw;
			height: 5.8593vh;
			line-height:5.8593vh;
		}

		.acKind {
			color: #fff;
			background: #4275F4;
		}

		.ways {
			display: flex;
			flex-wrap: wrap;

			.way {
				width: 33.3%;
			}
		}

		.r_b {
			/deep/.u-button {
				span {
					color: #000;
				}
			}
		}

		/deep/.u-cell__body {
			padding: 0 0 15px;

			span {
				font-size: 20px;
			}
		}

		.dis_item {
			position: relative;
			height: 100rpx;
			border: 1px solid #ddd;

			.r_gou {
				display: none;
				position: absolute;
				top: 0px;
				right: 0px;
				width: 0;
				height: 0;
				border-top: 10px solid #4275F4;
				border-right: 10px solid #4275F4;
				border-left: 10px solid transparent;
				border-bottom: 10px solid transparent;
			}

			.icon-duigou {
				display: none;
				position: absolute;
				top: -2px;
				right: -2px;
				transform: scale(0.6);
			}
		}

		.dis_check {
			border: 1px solid #FD8906;
			background: #fff9dd;

			.r_gou,
			.icon-duigou {
				display: block;
			}
		}

		.erase {
			padding: 5px 30px;
			width: 190px;
			box-shadow: 0px 0px 10px 0px #e6e6e6;
		}

		/deep/.u-cell__title-text {
			span {
				color: #666;
			}
		}
	}
	
	@media (min-width: 1500px) and (max-width: 3280px) {
		.right {
			/deep/.u-subsection--subsection {
				height: 40px !important;
				border-radius: 6px;
		
				.u-subsection__item__text {
					span {
						color: #000;
						font-size: 18px !important;
					}
				}
			}
		
			.r_cont {
				max-height: calc(100vh - 215px);
				overflow: auto;
		
				.r_item {
					position: relative;
					display: inline-flex;
					flex-direction: column;
					justify-content: space-between;
					margin-right: 10px;
					margin-bottom: 10px;
					width: 225px;
					height: 140px;
					border: 1px solid #e6e6e6;
					border-radius: 10px;
		
					.badge {
						position: absolute;
						top: 0px;
						right: 0px;
		
						/deep/.u-badge {
							line-height: 16px;
							font-size: 16px;
						}
					}
				}
		
				.check {
					border: 2rpx solid #FD8906;
				}
			}
		
			.pagona {
				height: 50px;
		
				/deep/.uni-pagination {
					.page--active {
						display: inline-block;
						width: 30px;
						height: 30px;
					}
		
					.uni-pagination__total {
						font-size: 20px;
					}
		
					span {
						font-size: 20px;
					}
				}
			}
		
		// 	/deep/.ljt-keyboard-body {
		// 		border-radius: 6px;
		// 		border: 1px solid #e5e5e5;
		
		// 		.ljt-keyboard-number-body {
		// 			width: 500px !important;
		// 			height: 260px !important;
		// 		}
		
		// 		.ljt-number-btn-confirm-2 {
		// 			background: #4275F4 !important;
		
		// 			span {
		// 				color: #000;
		// 				font-size: 20px;
		// 			}
		// 		}
		// 	}
		
			.kwarp {
				width:115px;
				overflow-y: auto;
				max-height: calc(100vh - 165px);
			}
		
			.kind {
				width: 105px;
				height: 45px;
				line-height: 40px;
			}
		
			.ways {
				display: flex;
				flex-wrap: wrap;
		
				.way {
					width: 33.3%;
				}
			}
		
			.r_b {
				/deep/.u-button {
					span {
						color: #000;
					}
				}
			}
		
			/deep/.u-cell__body {
				padding: 0 0 15px;
		
				span {
					font-size: 20px;
				}
			}
		
			.dis_item {
				position: relative;
				height: 50px;
				border: 1px solid #ddd;
		
				.r_gou {
					display: none;
					position: absolute;
					top: 0px;
					right: 0px;
					width: 0;
					height: 0;
					border-top: 10px solid #4275F4;
					border-right: 10px solid #4275F4;
					border-left: 10px solid transparent;
					border-bottom: 10px solid transparent;
				}
		
				.icon-duigou {
					display: none;
					position: absolute;
					top: -2px;
					right: -2px;
					transform: scale(0.6);
				}
			}
		
			.dis_check {
				border: 1px solid #FD8906;
				background: #fff9dd;
		
				.r_gou,
				.icon-duigou {
					display: block;
				}
			}
		
			.erase {
				padding: 5px 30px;
				width: 190px;
				box-shadow: 0px 0px 10px 0px #e6e6e6;
			}
		
			/deep/.u-cell__title-text {
				span {
					color: #666;
				}
			}
		}
	}
	
</style>
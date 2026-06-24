<template>
	<view class="right f-1 f-y-bt f18">
		<view class="p5 bd1 f14 nav">
			<view class="p-10-15 mr10 be6 bs10" :class="kind==i?'bffd wei6 cf':''" style="display: inline-block;"
				v-for="(v,i) in classfiy" :key="i" @click="changeKind(v,i)">{{v.name}}</view>
		</view>
		<view class="f-1 f-bt p-15-0" style="padding-bottom: 0;">
			<goods :list="list" :dataList="dataList" :queryForm="queryForm" :total="total" @handcar="handcar" @change="change" @addDish="handAddDish"></goods>
		</view>
		<addDish ref="addDishRef" @addCar="addCar"  />
	</view>
</template>

<script>
	import goods from '@/components/order/goods.vue'
	import addDish from '@/components/goods/addDish.vue';
	import {
		mapState,
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
				kind: 0,
			}
		},
		computed: {

		},
		methods: {
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
			handAddDish(){
				this.$refs['addDishRef'].open()
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
		
		.nav{
			display: -webkit-box; 
			overflow: hidden;
			overflow-x: auto;
			white-space: nowrap;
		}

		.kind {
			width: 210rpx;
			height: 90rpx;
			line-height: 80rpx;
		}

		.acKind {
			color: #000;
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
		::-webkit-scrollbar {
			display: block !important;
			width: 8px !important;
			height: 8px !important;
			background-color: #f5f5f5  !important;
		}

		::-webkit-scrollbar-track {
			-webkit-box-shadow: inset 0 0 6px rgb(186, 183, 183) !important;
			border-radius: 10px !important;
			background-color: #f5f5f5 !important;
		}

		::-webkit-scrollbar-thumb {
			border-radius: 10px !important;
			-webkit-box-shadow: inset 0 0 6px rgb(186, 183, 183) !important;
			background-color: rgb(190, 190, 190) !important;
		}
	}
</style>
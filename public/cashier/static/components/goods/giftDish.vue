<template>
	<u-overlay :show="giftDish" :opacity="0.2">
		<view class="reduce bf p15 f18 f-y-bt" @tap.stop>
			<view class="f-x-bt mb10">
				<view class="f-c f-g-1 wei f24">赠菜</view>
				<!-- <text class="iconfont icon-cuowu wei5 c6 pl10" style="font-size: 19px;" @click="close"></text> -->
			</view>
			<view class="overflowlnr p-5-0">{{selectItem.name || selectItem.goods && selectItem.goods.name}}</view>
			<view class="uScroll pb10">
				<view class="f-x-bt pb15 bd1 mb10 c9">
					<text>赠送数量</text>
					<u-number-box v-model="count" :min="1" :max="num" button-size="36"></u-number-box>
				</view>
				<view class="f-x-e">
					<view class="f14 c9">最多赠送<text class="cf5">{{selectItem.num}}</text>份</view>
				</view>
				<!-- <view class="key">
				<keybored type="number" confirmText="确定" v-model="iCount" @input="intGift" @doneClear="count=0">
				</keybored>
			</view> -->
				<view class="">
					<view class="c9 f14 mb10">赠菜原因<text class="cf5 wei6 f15 pl5">*</text></view>
					<creason :list="reasonConfig && reasonConfig.give" @getRemark="getRemark" />
				</view>
			</view>
			<view class="f-1 f-y-e">
				<u-button @click="close" class="mr20"><text class="c0">取消</text></u-button>
				<u-button color="#4275F4" @click="confirm"><text class="cf">确认</text></u-button>
			</view>
		</view>
		<u-toast ref="uToast"></u-toast>
	</u-overlay>
</template>

<script>
	import creason from '@/components/other/creason.vue';
	import keybored from '@/components/liujto-keyboard/keybored.vue';
	import {
		mapState,
	} from 'vuex'
	export default {
		props: {
			selectItem: {
				type: Object,
				default: {}
			},
			v: {
				type: Object,
				default: {}
			},
		},
		components: {
			creason,
			keybored,
		},
		data(props) {
			return {
				giftDish: false,
				num: 1,
				count: 1,
				iCount: '',
				resons: [],
				list: ['友情赠送', '赠送活动', '客投诉质量问题改赠菜'],
			}
		},
		computed: {
			...mapState({
				reasonConfig: state => state.config.reasonConfig,
			}),
		},
		methods: {
			open() {
				this.num = this.selectItem.num
				this.count = this.selectItem.num
				this.giftDish = true
			},
			close() {
				this.reason = ''
				this.giftDish = false
			},
			intGift(val) {
				this.iCount = val
				if (this.iCount > this.selectItem.num) {
					this.iCount = 0
					return uni.showToast({
						title: `赠菜数量大于已选商品数量`,
						icon: 'none'
					})
				} else {
					this.count = this.iCount
				}
			},
			getRemark(e) {
				// this.resons = e
				if (e) this.resons.push(e)
			},
			//确定
			confirm() {
				if (!this.count) {
					return uni.showToast({
						title: `请输入赠菜数量`,
						icon: 'none'
					})
				}
				if (this.resons && this.resons.length > 0) {
					this.$emit('cGift', {
						goods: [{
							id: this.selectItem.id,
							num: this.count
						}],
						type: 'give',
						reason: this.resons && this.resons.join('，'),
						diningType: this.v.diningType,
						storeId: this.v.storeId,
						tableId: this.v.id,
					})
				} else {
					uni.showToast({
						title: `请填写赠菜原因`,
						icon: 'none'
					})
				}
			}
		}
	}
</script>

<style lang="scss" scoped>
	.reduce {
		position: absolute;
		top: 10.4166vh;
		left: 36.6032vw;
		width: 28.5505vw;
		height: calc(100vh - 19.5312vh);
		border-radius: 10px;
		.uScroll{
			height: calc(100vh - 26.0416vh);
			overflow: hidden;
			overflow-y:scroll;
		}

		.tabs {
			display: inline-flex;
			border-radius: 6px;
			background: #eeeeee;

			.tab_i {
				padding: 8px 15px;
			}
		}

		.dis {
			padding: 8px 0;
			width: 23%;
			border: 1px solid #e6e6e6;
		}

		.key {
			/deep/.ljt-keyboard-body {
				border-radius: 10px;
				border: 1px solid #e5e5e5;

				.ljt-keyboard-number-body {
					width: 26.3543vw !important;
					height: 25.8091vh !important;
				}

				.ljt-number-btn-confirm-2 {
					background: #4275F4 !important;

					span {
						// color: #000;
					}
				}
			}
		}

		.reson_i {
			position: relative;
			display: inline-block;
			border: 1px solid #e6e6e6;
			padding: 8px 15px;

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

		.acreson_i {
			border: 1px solid #FD8906;
			background: #fff9dd;

			.r_gou,
			.icon-duigou {
				display: block;
			}
		}
	}

	@media (min-width: 1500px) and (max-width: 3280px) {
		.reduce {
			position: absolute;
			top: 80px;
			left: 500px;
			width: 390px;
			height: calc(100vh - 150px);
			border-radius: 10px;
			.uScroll{
				height: calc(100vh - 200px);
			}

			.key {
				/deep/.ljt-keyboard-body {
					border-radius: 10px;
					border: 1px solid #e5e5e5;

					.ljt-keyboard-number-body {
						width: 360px !important;
						height: 275px !important;
					}

					.ljt-number-btn-confirm-2 {
						background: #4275F4 !important;

						span {
							color: #000;
						}
					}
				}
			}
		}
	}
</style>
<template>
	<u-overlay :show="remark" :opacity="0.2" @click="close">
		<view class="reduce bf p15 f18 f-y-bt" @tap.stop>
			<view class=" f-x-bt mb30">
				<view class="overflowlnr">单品备注</view>
				<text class="iconfont icon-cuowu wei5 c6 pl10" style="font-size: 19px;" @click="close"></text>
			</view>
			<view>
				<view class="reson_i f16 mr10 mb10 bs6 " :class="resons.includes(item)?'acreson_i':''"
					v-for="(item,index) in list" :key="index" @click="chooseRes(item)">
					{{item}}
					<view class="r_gou"></view>
					<text class="iconfont icon-duigou f12"></text>
				</view>
				<view v-show="show" class="reson_i f16 mr10 mb10 bs6 " :class="resons.includes(i_remark)?'acreson_i':''"
					@click="addesc">
					{{i_remark}}
					<view class="r_gou"></view>
					<text class="iconfont icon-duigou f12"></text>
				</view>
				<view class="dfa">
					<u-input v-model="desc" placeholder="请输入自定义备注" :class="resons.includes(desc)?'acreson_i':''"
						style="background: #fcfcfc;"></u-input>
					<u-button color="#4275F4" :customStyle="{width:'80px',marginLeft:'10px',fontSize:'16px'}"
						@click="confirm"><text class="c0">确认</text></u-button>
				</view>
			</view>
			<view class="f-1 f-y-e">
				<u-button color="#4275F4" @click="close"><text class="c0">确认</text></u-button>
			</view>
		</view>
	</u-overlay>
</template>

<script>
	import keybored from '@/components/liujto-keyboard/keybored.vue';
	export default {
		props: {
			// reson: {
			// 	type: Array,
			// 	default: () => []
			// }
		},
		components: {
			keybored,
		},
		data(props) {
			return {
				remark:false,
				show: false,
				i_remark: '',
				desc: '',
				resons: props.reson || [],
				list: ['少辣', '少糖', '少盐', '少油', '不加葱', '清真', '不加香菜', '加辣椒', '和干锅放一起', '补单不做', '猪鞭公鸡蛋汤']
			}
		},
		watch: {
			reson(val) {
				this.resons = val
			}
		},
		methods: {
			open() {
				this.remark = true
			},
			close() {
				this.remark = false
			},
			chooseRes(item, type) {
				if (!this.resons.includes(item)) {
					this.resons.push(item)
				} else {
					this.resons = this.resons.filter(v => {
						return v !== item
					});
				}
				this.$emit('itemRemark', this.resons)
			},
			addesc() {
				if (!this.resons.includes(this.desc)) {
					this.resons.push(this.desc)
				} else {
					this.resons = this.resons.filter(v => {
						return v !== this.desc
					});
				}
				this.$emit('itemRemark', this.resons)
			},
			confirm() {
				if (this.desc) {
					this.i_remark = this.desc
					this.show = true
				} else {
					this.show = false
				}
			}
		}
	}
</script>

<style lang="scss" scoped>
	/deep/.u-transition {
		background-color: rgba(0, 0, 0, 0.1) !important;
	}

	/deep/.u-modal {
		.u-modal__content {
			justify-content: flex-start;
		}

		.u-modal__button-group__wrapper__text {
			color: #000 !important;
		}
	}

	.reduce {
		position: absolute;
		top: 55px;
		left: 500px;
		width: 390px;
		height: calc(100vh - 55px);
		border-radius: 10px;
		box-shadow: 5px 0px 10px 0px #ccc;

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
					width: 360px !important;
					height: 275px !important;
				}

				.ljt-number-btn-ac {
					width: 90px !important;
				}

				.ljt-number-btn-confirm-2 {
					width: 100px !important;
					background: #4275F4 !important;

					span {
						color: #000;
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
</style>
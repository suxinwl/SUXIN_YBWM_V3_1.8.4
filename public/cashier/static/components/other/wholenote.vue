<template>
	<u-overlay :show="allDesc" :opacity="0.2" @click="close">
		<!-- <u-popup :show="allDesc" :round="10" :overlayOpacity="0.2" mode="top" @close="close"> -->
		<view class="reduce bf p15 f18 f-y-bt" @tap.stop>
			<view class="f-x-bt mb30 mt10">
				<view class="overflowlnr f-c f-g-1 wei f24">{{title}}</view>
				<!-- <text class="iconfont icon-cuowu wei5 c6 pl10" style="font-size: 19px;" @click="close"></text> -->
			</view>
			<view>
				<view class="reson_i f16 mr10 mb10 bs6 " :class="resons.includes(item)?'acreson_i':''"
					v-for="(item,index) in list" :key="index" @click="chooseRes(item)">
					{{item}}
					<!-- <view class="r_gou"></view> -->
					<!-- <text class="iconfont icon-duigou f12"></text> -->
				</view>
				<view v-show="show" class="reson_i f16 mr10 mb10 bs6 " :class="resons.includes(remark)?'acreson_i':''"
					@click="addesc">
					{{remark}}
					<view class="r_gou"></view>
					<text class="iconfont icon-duigou f12"></text>
				</view>
				<view class="dfa">
					<u--textarea v-model="desc" placeholder="请输入自定义备注" :class="resons.includes(desc)?'acreson_i':''"
						style="background: #fcfcfc;"></u--textarea>
					<!-- <u-button color="#4275F4" :customStyle="{width:'80px',marginLeft:'10px',fontSize:'16px'}"
						@click="confirm"><text class="c0">确认</text></u-button> -->
				</view>
			</view>
			<view class="f-1 f-y-e">
				<u-button @click="close" class="mr20"><text class="c0">取消</text></u-button>
				<u-button color="#4275F4" @click="save"><text class="cf">确认</text></u-button>
			</view>
		</view>
	</u-overlay>
</template>

<script>
	import keybored from '@/components/liujto-keyboard/keybored.vue';
	import {
		mapState,
	} from 'vuex'
	export default {
		props: {

		},
		components: {
			keybored,
		},
		data() {
			return {
				allDesc: false,
				show: false,
				remark: '',
				desc: '',
				resons: [],
				list: [],
				type: 'allDesc',
				title: '整单备注',
			}
		},
		computed: {
			...mapState({
				reasonConfig: state => state.config.reasonConfig,
			}),
		},
		methods: {
			open(t) {
				this.type = t
				if (t && t == 'remark') {
					this.title = '单品备注'
					this.list =  this.reasonConfig &&  this.reasonConfig.goodsNotes || []
				}else{
					this.title = '整单备注'
					this.list =  this.reasonConfig &&  this.reasonConfig.orderNotes || []
				}
				this.resons = []
				this.allDesc = true
			},
			close() {
				this.desc = ''
				this.allDesc = false
			},
			// close() {
			// 	this.$emit('closeDesc', false)
			// },
			chooseRes(item, type) {
				if (!this.resons.includes(item)) {
					this.resons.push(item)
				} else {
					this.resons = this.resons.filter(v => {
						return v !== item
					});
				}
				// this.close()
			},
			addesc() {
				if (!this.resons.includes(this.desc)) {
					this.resons.push(this.desc)
				} else {
					this.resons = this.resons.filter(v => {
						return v !== this.desc
					});
				}
				if (this.type == 'remark') {
					this.$emit('itemRemark', this.resons, 1)
				} else {
					this.$emit('returnRemark', this.resons, 1)
				}
				this.close()
			},
			confirm() {
				if (this.desc) {
					if (this.type == 'remark') {
						this.$emit('itemRemark', this.desc, 2)
					} else {
						this.$emit('returnRemark', this.desc, 2)
					}
					this.close()
				} else {
					this.show = false
				}
			},
			save() {
				if(this.desc) this.resons.push(this.desc)
				if (this.type == 'remark') {
					this.$emit('itemRemark', this.resons, 1)
				} else {
					this.$emit('returnRemark', this.resons, 1)
				}
			},
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
		transform: translateX(-50%);
		top: 20vh;
		left: 50vw;
		width: 43.9238vw;
		height: 55.0833vh;
		border-radius: 10px;

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
			border: 1px solid #fff;
			background: #4275F4;
			color: #fff;

			.r_gou,
			.icon-duigou {
				display: block;
			}
		}
	}
	@media (min-width: 1500px) and (max-width: 3280px) {
		.reduce {
			top: 80px;
			left: 50%;
			transform: translateX(-50%);
			width: 800px;
			height: calc(100vh - 150px);
			border-radius: 10px;
		}
	}
</style>
<template>
	<u-overlay :show="allDesc" :opacity="0.2" @click="close">
		<view class="reduce bf p15 f18 f-y-bt" @tap.stop>
			<view class="f-x-bt mb30">
				<view class="overflowlnr">{{title}}</view>
				<text class="iconfont icon-cuowu wei5 c6 pl10" style="font-size: 19px;" @click="close"></text>
			</view>
			<view>
				<view class="dfa">
					<u--textarea v-model="desc" placeholder="请输入商家备注" :class="resons.includes(desc)?'acreson_i':''"
						style="background: #fcfcfc;"></u--textarea>
				</view>
			</view>
			<view class="f-1 f-y-e">
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
				title: '商家备注',
			}
		},
		computed: {
			...mapState({
				reasonConfig: state => state.config.reasonConfig,
			}),
		},
		methods: {
			open(t) {
				this.resons = []
				if(t && t.storeNotes) this.desc = t.storeNotes
				this.allDesc = true
			},
			close() {
				this.desc = ''
				this.allDesc = false
			},
			save() {
				if (this.desc) {
					this.$emit('itemRemark', this.desc)
				} else {
					uni.$u.toast('请输入商家备注')
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
		top: 7.1614vh;
		left: 36.6032vw;
		width: 28.5505vw;
		height: calc(100vh - 7.1614vh);
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

	@media (min-width: 1500px) and (max-width: 3280px) {
		.reduce {
			position: absolute;
			top: 55px;
			left: 500px;
			width: 390px;
			height: calc(100vh - 55px);
			border-radius: 10px;
		}
	}
</style>
<template>
	<u-overlay :show="show" :opacity="0.2">
		<view class="reduce bf p15 f18 f-y-bt" @tap.stop>
			<view class="f-x-bt wei f24">
				<view class="dfa f-c f-g-1">调整会员等级</view>
			</view>
			<view class="overflowlnr p-5-0 f18 mt20">当前等级：<text
					style="color: #4275F4;">{{ form.vip && form.vip.name || "--" }}</text>
			</view>
			<view class="p-5-0 f18 mt20 flex f-y-c">修改等级：
				<view class="f-g-1">
					<uni-data-select v-model="bForm.vipId" :localdata="channels" placeholder="请选择会员等级"
						@change="handDiningType"></uni-data-select>
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
			form: {
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
				show: false,
				r_dis: false,
				actReduce: 0,
				desc: '',
				discount: '',
				disMoney: '',
				afDis: '',
				afMoney: '',
				resons: [],
				channels: [],
				bForm:{
					vipId:'',
				},
			}
		},
		computed: {
			...mapState({
				reasonConfig: state => state.config.reasonConfig,
			}),
		},
		methods: {
			open(t) {
				this.getVipList()
				this.show = true
			},
			close() {
				this.show = false
			},
			async getVipList(){
				let {
					data: {
						list,
					},
				} = await this.beg.request({
					url: this.api.vipList
				})
				this.channels = list ? list : []
				this.channels.forEach((v) => {
					v.value = v.id
					v.text = `${v.name}(VIP${v.level})`
				})
			},
			getRemark(e) {
				this.bForm.notes = e.join('，')
			},
			handDiningType(e) {
				this.bForm.vipId = e
			},
			confirm() {
				this.$emit('save', this.bForm)
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

		.uScroll {
			height: calc(100vh - 26.0416vh);
			overflow: hidden;
			overflow-y: scroll;
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

		.srin {
			// height: 5.8593vh;
			padding: 0.8784vw;
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

			.uScroll {
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
					}
				}
			}

			.srin {
				padding: 12px;
			}
		}
	}
</style>
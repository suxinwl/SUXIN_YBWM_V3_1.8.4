<template>
	<u-overlay :show="show" :opacity="0.2">
		<view class="reduce bf p15 f18 f-y-bt" @tap.stop>
			<view class="f-x-bt wei f24">
				<view class="dfa f-c f-g-1">{{type=='balance'?'调整余额':'调整积分'}}</view>
			</view>
			<view class="overflowlnr p-5-0 f16">当前{{type=='balance'?'余额':'积分'}}：<text
					style="color: #4275F4;">{{type=='balance'? form.account && form.account.balance : form.account && form.account.integral || 0}}</text>
			</view>
			<view class="uScroll pb10">
				<view class="mt15 mb10 bs10 srin" style="border:1px solid #4275F4">
					<u-input v-model="bForm.value" type="number" placeholder="请输入数值" border="none" disabled
						disabledColor="#fff" inputAlign="right">
						<view slot="prefix" class="dfa tabs f14">
							<view :class="actReduce==index?'bffd cf wei6 bs6':'c9'" class="tab_i"
								v-for="(item,index) in lists" :key="index" @click="tabChange(item,index)">{{item.name}}
							</view>
						</view>
						<view slot="suffix" class="">{{type=='balance'?'元':'分'}}</view>
					</u-input>
				</view>
				<view class="key">
					<keybored type="digit" :max="99999" v-model="bForm.value" confirmText="确定" @doneClear="clearAll()"
						@input="intMoney" />
				</view>
				<view class="">
					<view class="m15 c9 f14">调整原因<text class="cf5 pl5 wei6 f15">*</text></view>
					<creason :list="list" @getRemark="getRemark" />
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
				list: [],

				lists: [{
						name: '增加',
						value: 1,
					},
					{
						name: '扣减',
						value: 2,
					}
				],
				bForm: {
					value: 0,
					type: 1,
					notes: "",
				},
				type: 'balance',
			}
		},
		computed: {
			...mapState({
				reasonConfig: state => state.config.reasonConfig,
			}),
		},
		methods: {
			open(t) {
				this.bForm.value = 0
				this.type = t
				this.list = t == 'balance' ? this.reasonConfig && this.reasonConfig.balance : this.reasonConfig && this
					.reasonConfig.integral || []
				this.show = true
			},
			close() {
				this.show = false
			},
			tabChange(v, i) {
				this.bForm.type = v.value
				this.actReduce = i
			},
			getRemark(e) {
				this.bForm.notes = e.join('，')
			},
			clearAll() {
				this.bForm.value = 0
			},
			intMoney(val) {
				this.bForm.value = val
			},
			confirm() {
				if (this.type == "balance") {
					this.$emit('handeditBe', this.bForm)
				} else {
					this.$emit('handeditIl', this.bForm)
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
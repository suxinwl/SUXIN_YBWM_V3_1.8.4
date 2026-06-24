<template>
	<u-overlay :show="show" :opacity="0.2" @click="close">
		<view class="reduce bf f18 f-y-bt" @tap.stop>
			<view class="dfbc p20">
				<view class="f-c f-g-1 wei f24">请确认是否撤销此订单</view>
				<!-- <text class="iconfont icon-cuowu" @click="close"></text> -->
			</view>
			<view class="p-0-20">
				<u-alert title="撤单后本订单无法修改或恢复" type="warning" :show-icon="true"></u-alert>
			</view>
			<view class="p-0-20">
				<view class="m15 c9 f14">撤销原因<text class="cf5 pl5 wei6 f15">*</text></view>
				<creason :list="reasonConfig && reasonConfig.withdraw" @getRemark="getRemark" />
			</view>
			<view class="p20 f-e butt">
				<view class="mr15">
					<u-button @click="close"><text class="f18 wei6 p10">取消</text></u-button>
				</view>
				<view>
					<u-button @click="saveOrder" color="#4275F4"><text class="f18 wei6 p10">确认</text></u-button>
				</view>
			</view>
		</view>
		<u-toast ref="uToast"></u-toast>
	</u-overlay>
</template>

<script>
	import creason from '@/components/other/creason.vue';
	import {
		mapState,
	} from 'vuex'
	export default {
		props: {

		},
		components: {
			creason,
		},
		data() {
			return {
				show: false,
				current: 0,
				currItem: {},
				list: [],
			}
		},
		computed: {
			...mapState({
				reasonConfig: state => state.config.reasonConfig,
			}),
		},
		methods: {
			open() {
				this.show = true
			},
			close() {
				this.show = false
			},
			getRemark(e) {
				// if(this.resons) this.resons.push(e)
				this.resons = e
			},
			async saveOrder() {
				if (this.resons && this.resons.length > 0) {
					this.$emit('save', this.resons)
				} else {
					uni.showToast({
						title: '请输入原因',
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
		transform: translateX(-50%);
		top: 25vh;
		left: 50vw;
		width: 36.6032vw;
		// height: 39.0625vh;
		border-radius: 10px;

		.reson_i {
			position: relative;
			display: inline-flex;
			flex-direction: column;
			justify-content: space-between;
			border: 1px solid #e6e6e6;
			width: 160px;
			height: 120px;

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
		
		/deep/ .u-alert__content__title{
		    font-weight: normal;
		}
	}

	@media (min-width: 1500px) and (max-width: 3280px) {
		.reduce {
			position: absolute;
			top: 30%;
			left: 50%;
			transform: translateX(-50%);
			width: 500px;
			// height: 300px;
			border-radius: 10px;
		}
	}
</style>
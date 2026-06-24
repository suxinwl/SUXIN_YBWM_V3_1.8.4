<template>
	<view>
		<u-popup :show="service" :round="10" :overlayOpacity="0.2" mode="top" @close="close">
			<view class="reduce bf p15 f18 f-y-bt">
				<view class="f-x-bt">
					<view class="overflowlnr">服务费</view>
					<text class="iconfont icon-cuowu wei5 c6 pl10" style="font-size: 19px;" @click="close"></text>
				</view>
				<view class="f-1 f-c-c c9">
					暂无服务费
				</view>
			</view>
		</u-popup>
	</view>
</template>

<script>
	export default {
		props: {
			service: {
				type: Boolean,
				default: false
			},
		},
		data() {
			return {}
		},
		methods: {
			close() {
				this.$emit('closeService', false)
			},
		}
	}
</script>

<style lang="scss" scoped>
	.reduce {
		position: fixed;
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
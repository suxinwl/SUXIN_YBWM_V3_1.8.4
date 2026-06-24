<template>
	<u-overlay :show="show" :opacity="0.2" @click="close">
		<view class="reduce bf p15 f18 f-y-bt" @tap.stop>
			<view class="f-x-bt mb30 mt10">
				<view class="overflowlnr f-c f-g-1 wei f24">新增设备</view>
			</view>
			<view class="p2 main f-c">
				<view class="list flex f-w">
					<view class="item f-c-xc mr20 mb10 p-20-10" v-for="(v,i) in list" :key="i" @click="sItem(v)">
						<view class="f-c">
							<image v-if="v.value==1" src="@/static/imgs/rec3.jpg" mode="widthFix" class="icon"></image>
							<image v-if="v.value==2" src="@/static/imgs/rec1.jpg" mode="widthFix" class="icon"></image>
							<image v-if="v.value==3" src="@/static/imgs/rec2.jpg" mode="widthFix" class="icon"></image>
						</view>
						<view class="f-c mt20 wei">{{v.name}}</view>
						<view class="f-c f14 c9 mt10">{{v.desc}}</view>
					</view>
				</view>
			</view>
			<view class="f-1 f-y-e">
				<u-button @click="close" class="mr20"><text class="c0">取消</text></u-button>
				<!-- <u-button color="#4275F4" @click="save"><text class="cf">确认</text></u-button> -->
			</view>
		</view>
	</u-overlay>
</template>

<script>
	import {
		mapState,
	} from 'vuex'
	export default {
		props: {

		},
		components: {

		},
		data() {
			return {
				show: false,
				list: [{
					value: 1,
					name: '云小票打印机',
					desc: '用于门店订单小票打印',
				}, {
					value: 2,
					name: '云标签打印机',
					desc: '用于门店打印杯贴/标签',
				}, {
					value: 3,
					name: '云语音盒',
					desc: '用于门店收款/提醒语音播报',
				}],
			}
		},
		computed: {
			
		},
		methods: {
			open(t) {
				this.show = true
			},
			close() {
				this.show = false
			},
			sItem(v) {
				this.$emit('addDevice', v)
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
		width: 58.5651vw;
		height: 52.0833vh;
		border-radius: 10px;
	}

	.main {
		height: 65.1041vh;
		overflow: hidden;
		overflow-y: scroll;
		padding-bottom: 20px;

		.list {
			.item {
				width: 16.1054vw;
				border: 1px solid #ddd;
				border-radius: 8px;

				.icon {
					width: 60px;
					height: 60px;
				}
			}
		}
	}

	@media (min-width: 1500px) and (max-width: 3280px) {
		.reduce {
			top: 20%;
			left: 50%;
			transform: translateX(-50%);
			width: 900px;
			height: 500px;
			border-radius: 10px;
		}

		.main {
			height: 500px;

			.list {
				.item {
					width: 220px;
				}
			}
		}
	}
</style>
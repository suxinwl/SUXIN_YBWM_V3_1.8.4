<template>
	<u-overlay :show="show" :opacity="0.2" @click="close">
		<view class="reduce bf p15 f18 f-y-bt" @tap.stop>
			<view class="f-x-bt mb30 mt10">
				<view class="overflowlnr f-c f-g-1 wei f24">{{title}}</view>
			</view>
			<view class="p2 main">
				<u-steps current="100" direction="column" activeColor='#4275F4' v-if="form.deliveryOrder && form.deliveryOrder.log">
					<u-steps-item :title="v.text" :desc="v.time" v-for="(v,i) in form.deliveryOrder.log" :key="i"></u-steps-item>
				</u-steps>
			</view>
			<view class="f-1 f-y-e">
				<!-- <u-button @click="close" class="mr20"><text class="c0">取消</text></u-button> -->
				<u-button color="#4275F4" @click="close"><text class="cf">确认</text></u-button>
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
				show: false,
				title: '配送详情',
				form: {},
				co: {},
			}
		},
		computed: {
			...mapState({
				reasonConfig: state => state.config.reasonConfig,
			}),
		},
		methods: {
			open(t) {
				this.form = t
				this.show = true
			},
			close() {
				this.show = false
			},
			save() {
				if (this.desc) this.resons.push(this.desc)
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
		height: 59.0833vh;
		border-radius: 10px;

	}

	.main {
		height: 65.1041vh;
		overflow: hidden;
		overflow-y: scroll;
		padding-bottom: 20px;

		.left {
			width: 110px;
			text-align: right;
		}
	}
	
	/deep/.u-text__value--content{
	    color: #000;
	}

	@media (min-width: 1500px) and (max-width: 3280px) {
		.reduce {
			top: 20%;
			left: 50%;
			transform: translateX(-50%);
			width: 800px;
			height: 600px;
			border-radius: 10px;
		}

		.main {
			height: 500px;
		}
	}
</style>
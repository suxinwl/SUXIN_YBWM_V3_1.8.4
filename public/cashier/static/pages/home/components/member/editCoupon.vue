<template>
	<view>
		<u-overlay :show="showDiscount">
			<view class="mode f20 bf f-y-bt" style="width:900px;height:600px">
				<view class="p15 bd1 dfbc">
					<text>送优惠券</text><text class="iconfont icon-cuowu" @click="showDiscount=false"></text>
				</view>
				<view class="p15 f-1 f-y-bt">
					<view class="f-1">
						<uni-table ref="table" emptyText="暂无更多数据">
							<uni-tr class="bf5">
								<uni-th><text class="f18">优惠券名称</text></uni-th>
								<uni-th><text class="f18">券类型</text></uni-th>
								<uni-th><text class="f18">券内容</text></uni-th>
								<uni-th><text class="f18">金额</text></uni-th>
								<uni-th><text class="f18">有效期</text></uni-th>
								<uni-th width="200" align="center"><text class="f18">发放数量</text></uni-th>
							</uni-tr>
							<uni-tr v-for="(item, index) in discounts" :key="index" style="height:55px">
								<uni-td><text class="f18">{{item.name}}</text></uni-td>
								<uni-td><text class="f18">{{item.money}}</text></uni-td>
								<uni-td><text class="f18">{{item.validity}}</text></uni-td>
								<uni-td>
									<view class="dfa">
										<view class="f-c-c bs5 tac wei6" @click="item.num>0?item.num--:0"
											style="width: 25px;height:25px;background: #f1f1f1;">
											<text class="iconfont icon-icon_cut c6"></text>
										</view>
										<view class="ml10 mr10" style="width: 100px;">
											<u--input type="number" inputAlign="center" border="surround"
												v-model="item.num"></u--input>
										</view>
										<view class="f-c-c bs5 tac bf5 wei6" @click="item.num++"
											style="width: 25px;height:25px;background: #4275F4;font-size: 14px;">
											<text class="iconfont icon-jia cf"></text>
										</view>
									</view>
								</uni-td>
							</uni-tr>
						</uni-table>
					</view>
					<u-button color=" #4275F4" text="发放优惠券" @click="changeDis"></u-button>
				</view>
			</view>
		</u-overlay>
	</view>
</template>

<script>
	export default {
		props: {
			form: {
				type: Object,
				default: {},
			}
		},
		components: {

		},
		data() {
			return {
				showDiscount: false,
				type: 1,
				rules: {
					// value: [{
					// 	required: true,
					// 	message: '请输入修改内容',
					// 	trigger: ['change', 'blur'],
					// }],
					notes: [{
						required: true,
						message: '请输入备注',
						trigger: ['blur', 'change']
					}]
				},
				bForm: {
					value: 1,
					type: 1,
					notes: "",
				},
				discounts:[],
			}
		},
		onReady() {
			this.$refs.uForm.setRules(this.rules)
		},
		methods: {
			open() {
				this.bForm.value = 1
				this.bForm.notes = ''
				this.showDiscount = true
			},
			close() {
				this.showDiscount = false
			},
			save(){
				this.$refs.uForm.validate().then(res => {
					this.$emit('save',this.bForm)
				})
			}
		}
	}
</script>

<style lang="scss" scoped>
	.mode {
		position: absolute;
		top: 50%;
		left: 50%;
		transform: translate(-50%, -50%);
		width: 750px;
		border-radius: 5px;

		/deep/.u-form-item__body__left__content__label {
			justify-content: flex-end !important;
		}

		/deep/.u-input {
			padding: 3px 9px !important;
		}

		/deep/.uni-select__input-box {
			height: 32px !important;
		}

		/deep/.uni-calendar__content {
			position: absolute;
			left: 50%;
			transform: translateX(-50%);
			width: 400px;
			border-radius: 10px;

			.uni-datetime-picker--btn {
				background: #4275F4 !important;
				color: #000 !important;
			}

			.uni-calendar-item--checked {
				background: #4275F4 !important;

				.uni-calendar-item--checked-text {
					color: #000 !important;
				}
			}
		}
	}
</style>
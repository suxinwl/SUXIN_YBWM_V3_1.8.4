<template>
	<view>
		<u-overlay :show="showIntegral">
			<view class="mode f20 bf" style="width:610px">
				<view class="p15 bd1 dfbc">
					<text>调整积分</text><text class="iconfont icon-cuowu" @click="showIntegral=false"></text>
				</view>
				<view class="p15 f-y-bt">
					<view class="f-1 f-c-ac">
						<u--form class="mb10" ref="uForm" :model="bForm" :rules="rules" :labelWidth="120"
							:labelStyle="{fontSize:'18px'}">
							<u-form-item label="当前积分：" prop="integral" ref="item1">
								<text class="f18">{{(form.account && form.account.integral) || 0}}</text>
							</u-form-item>
							<u-form-item label="修改类型：" prop="integral" ref="item1">
								<u-radio-group v-model="bForm.type" size="22" iconSize="18" iconColor="#000"
									activeColor="#4275F4">
									<u-radio :customStyle="{marginRight: '20px'}" label="增加" :name="1" />
									<u-radio label="扣减" :name="2" />
								</u-radio-group>
							</u-form-item>
							<u-form-item label="修改内容：" prop="value" ref="item1">
								<view class="flex f-y-c">
									<view class="flex mr20 f15">{{ bForm.type == 1 ? "积分补发" : "积分扣减" }}</view>
									<u-number-box buttonSize="38" v-model="bForm.value" :min="1" :max="99999"></u-number-box>
								</view>
							</u-form-item>
							<u-form-item :labelWith="0" prop="notes" ref="item1">
								<view class="f-s-bt f18">
									<view class="tar pr5" style="width:120px">备注：</view>
									<view class="" style="width:230px">
										<u--textarea v-model="bForm.notes" height="100"
											placeholder="请输入备注"></u--textarea>
									</view>
								</view>
							</u-form-item>
						</u--form>
					</view>
					<u-button color="#4275F4" text="确定" @click="save"></u-button>
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
				showIntegral: false,
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
			}
		},
		onReady() {
			this.$refs.uForm.setRules(this.rules)
		},
		methods: {
			open() {
				this.bForm.value = 1
				this.bForm.notes = ''
				this.showIntegral = true
			},
			close() {
				this.showIntegral = false
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
<template>
	<view>
		<u-overlay :show="showDetail" @click="showDetail=false">
			<view class="mode f20 bf" @tap.stop>
				<view class="p15 bd1 dfbc">
					<text>会员详情</text><text class="iconfont icon-cuowu" @click="showDetail=false"></text>
				</view>
				<view class="p10 f-c-c">
					<u--form class="mb10" :model="bForm" ref="uForm" :labelWidth="140" :labelStyle="{fontSize:'18px'}">
						<u-form-item label="昵称：" prop="name" ref="item1">
							<view class="" style="width:230px">
								<text class="f18"> {{form.nickname}}</text>
								<!-- <u--input v-model="bForm.name" border="surround" :disabled="true"></u--input> -->
							</view>
						</u-form-item>
						<u-form-item label="手机号：" prop="phone" ref="item1">
							<view class="" style="width:230px">
								<text class="f18"> {{form.mobile}}</text>
								<!-- <u--input v-model="bForm.phone" border="surround" :disabled="true"></u--input> -->
							</view>
						</u-form-item>
						<u-form-item label="会员等级：" prop="grade" ref="item1">
							<text class="f18"> {{ form.vip && form.vip.name || "--" }}</text>
							<!-- <view class="" style="width:230px">
								<uni-data-select v-model="bForm.grade" placeholder="请选择会员等级"
									:localdata="grades"></uni-data-select>
							</view> -->
						</u-form-item>
						<u-form-item label="性别：" prop="sex" ref="item1">
							<text class="f18"> {{form.sexFormat}}</text>
							<!-- <view class="" style="width:230px">
								<u-radio-group v-model="bForm.sex" placement="row" size="20" activeColor="#4275F4"
									iconColor="#000" iconSize="18">
									<u-radio labelSize="18" :customStyle="{marginRight: '15px'}"
										v-for="(item, index) in sexList" :key="index" :label="item.text"
										:name="item.value">
									</u-radio>
								</u-radio-group>
							</view> -->
						</u-form-item>
						<u-form-item label=" 生日：" prop="birthday" ref="item1">
							<text class="f18"> {{ form.birthday }}</text>
							<!-- <view class="" style="width:230px">
								<uni-datetime-picker type="date" :clear-icon="false" v-model="bForm.birthday"
									@maskClick="maskClick" />
							</view> -->
						</u-form-item>
						<u-form-item label="注册时间：" prop="creat_at" ref="item1">
							<text class="f18"> {{ form.created_at }}</text></u-form-item>
						<u-form-item label="最后访问时间：" prop="creat_at" ref="item1">
							<text class="f18"> {{ form.updated_at }}</text></u-form-item>
					</u--form>
					<u-button color="#4275F4" text="确定" @click="showDetail=false"></u-button>
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
				showDetail: false,
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
				this.showDetail = true
			},
			close() {
				this.showDetail = false
			},
			save() {
				this.$refs.uForm.validate().then(res => {
					this.$emit('save', this.bForm)
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
<template>
	<view class="">
		<u-overlay :show="showAdd">
			<view class="mode f18 bf" style="width:610px;height:400px">
				<view class="p15 bd1 dfbc">
					<text>添加会员</text><text class="iconfont icon-cuowu" @click="showAdd=false"></text>
				</view>
				<view class="p15 f-y-bt" style="height:343px">
					<view class="f-1 f-c-ac">
						<u--form class="mb10" ref="addRef" :model="addForm" :labelWidth="100" :rules="addRules"
							:labelStyle="{fontSize:'18px'}">
							<u-form-item labelWidth="0" prop="mobile" ref="item1">
								<view class="dfbc f18">
									<view class="tar pr5" style="width:100px"><text class="pr5 cf5">*</text>手机号：</view>
									<view class="" style="width:230px">
										<u--input v-model="addForm.mobile" border="surround"  type="number"></u--input>
									</view>
								</view>
							</u-form-item>
							<u-form-item label="真实姓名：" prop="name" ref="item1">
								<view class="" style="width:230px">
									<u--input v-model="addForm.realname" border="surround"></u--input>
								</view>
							</u-form-item>
							<u-form-item label="性别：" prop="sex" ref="item1">
								<view class="" style="width:230px">
									<u-radio-group v-model="addForm.sex" placement="row" size="20" activeColor="#4275F4"
										iconColor="#000" iconSize="18">
										<u-radio :customStyle="{marginRight: '15px'}" v-for="(item, index) in sexList"
											:key="index" :label="item.text" :name="item.value" labelSize="18">
										</u-radio>
									</u-radio-group>
								</view>
							</u-form-item>
							<u-form-item label="生日：" prop="birthday" ref="item1">
								<view class="f18 c6" style="width:230px" @click="show = true">
									{{addForm.birthday || '请选择生日'}}
									<!-- <u--input v-model="addForm.birthday" border="surround"></u--input> -->
									<!-- <uni-datetime-picker type="date" :clear-icon="false" v-model="addForm.birthday"
										@maskClick="maskClick" /> -->
								</view>
							</u-form-item>
							<!-- <u-calendar :show="show" :mode="mode" @confirm="confirm"></u-calendar> -->
							<!-- <u-datetime-picker :show="show" v-model="value1" mode="date" @confirm="maskClick" @close="show = false"></u-datetime-picker> -->
						</u--form>
					</view>
					<u-button color="#4275F4" text="确定" @click="sureAdd"></u-button>
				</view>
			</view>
			<u-datetime-picker :show="show" v-model="value1" mode="date" @confirm="maskClick"
				@cancel="show = false"></u-datetime-picker>
		</u-overlay>
	</view>
</template>

<script>
	import {
		sj,
	} from "@/common/handutil.js"
	export default {
		props: {
			isVip: {
				type: Boolean,
				default: false
			},
		},
		data() {
			return {
				showAdd: false,
				sexList: [{
					value: 0,
					text: "未知"
				}, {
					value: 1,
					text: "男"
				}, {
					value: 2,
					text: "女"
				}],
				addRules: {
					mobile: [{
						required: true,
						message: '手机号不能为空',
						trigger: ['blur', 'change'],
					}, {
						validator: (rule, value, callback) => {
							if (value) {
								return this.$u.test.mobile(value);
							} else {
								return true
							}
						},
						message: '手机号码不正确',
						trigger: ['blur'],
					}],
				},
				addForm: {
					mobile: "",
					realname: "",
					sex: 1,
					birthday: "",
				},
				show: false,
				value1: Number(new Date()),
			}
		},
		methods: {
			open() {
				this.showAdd = true
			},
			maskClick(e) {
				let date = e.value
				this.addForm.birthday = this.timestampToTime(date)
				this.show = false
			},
			timestampToTime(timestamp) {
				var date = new Date(timestamp);
				var Y = date.getFullYear() + "-";
				var M =
					(date.getMonth() + 1 < 10 ?
						"0" + (date.getMonth() + 1) :
						date.getMonth() + 1) + "-";
				var D = (date.getDate() < 10 ? "0" + date.getDate() : date.getDate()) + " ";
				var h = date.getHours() + ":";
				var m = date.getMinutes() + ":";
				var s = date.getSeconds();
				return Y + M + D;
			},
			sureAdd() {
				this.$refs.addRef.validate().then(async res => {
					let that = this
					this.addForm.nickname = `用户_${sj()}`;
					let {
						msg
					} = await this.beg.request({
						url: this.api.cMember,
						method: 'POST',
						data: this.addForm
					})
					that.$emit('fetchData')
					uni.$u.toast(msg)
					this.showState = false
					this.close()
				})
			},
			close() {
				this.showAdd = false
				this.addForm = {}

			},
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
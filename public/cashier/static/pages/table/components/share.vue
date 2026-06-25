<template>
	<view>
		<u-popup :show="share" :round="10" :closeable="false" :overlayOpacity="0.2" mode="center" @close="close">
			<view class="share bf f18 f-x-bt bs10">
				<!-- <view class="w50 p20 h100" style="padding-top: 0px;">
					<view class="f-x-bt mb15">
						<view>
							<view class="f22 wei6 c3 p10">{{tit}}</view>
							<view class="p-0-10 c3 f12 wei5">当前桌号：{{form.type && form.type.name}}{{form.name}}</view>
						</view>
						<text class="iconfont icon-cuowu wei5 c6 pl10" style="font-size: 22px;" @click="close"></text>
					</view>
					<u--form labelPosition="left" :model="form" :rules="rules" ref="uForm"
						:labelStyle="{fontSize:'18px'}" labelWidth="80px">
						<u-form-item :required="true" label="人数" prop="people" borderBottom ref="item1">
							<u-input v-model="form.people" :fontSize="18" inputAlign="right" placeholder="请输入"
								border="none">
								<view slot="prefix" class=""></view>
							</u-input>
						</u-form-item>
							<u-form-item label="会员" prop="vip" borderBottom ref="item1">
							<u-input v-model="form.vip" :fontSize="18" inputAlign="right" placeholder="卡号/手机号后四位"
								border="none">
								<view slot="prefix" class=""></view>
								<view slot="suffix" class="f18 cf5f pl15" @click="search">搜索</view>
							</u-input>
						</u-form-item>
						<u-form-item label="提成人" prop="commenter" borderBottom ref="item1" @click="current=2">
							<view class="f-1 f18 cc tar">请选择</view>
						</u-form-item>
						<u-form-item label="桌台备注" prop="desc" borderBottom ref="item1" @click="current=3">
							<view class="f-1 f18 tar" :class="form.desc?'':'cc'">{{form.desc?form.desc:'请选择'}}</view>
						</u-form-item>
					</u--form>
					<u-button class="mt30" size="large" color="#4275F4" @click="founding"><text
							class="cf">保存</text></u-button>
				</view> -->
				<view class="f-1 h100">
					<!-- <view class="keys">
						<view class="key"
							:class="[1,2,4,5,7,8].includes(v)?'r_key b_key':[3,6,9,].includes(v)?'b_key':['清空',0].includes(v)?'r_key':''"
							v-for="(v,i) in [1,2,3,4,5,6,7,8,9,'清空',0,'回退']" :key="i" @click="clickKey(v,i)">
							{{v}}
						</view>
					</view> -->
					<view class="f-x-bt f-y-c mt20">
						<view class="f-g-1 ml20">
							<view class="f-g-1 wei f24 flex f-y-c c0">
								{{tit}}
								<view class="p-0-10 c9 f16">({{form.type && form.type.name}}{{form.name}})</view>
							</view>
						</view>
						<view class="f-g-0 f-c p10 mr10">
							<text class="iconfont icon-cuowu wei5 c6" style="font-size: 20px;" @click="close"></text>
						</view>
					</view>
					<u--form labelPosition="left" :model="form" :rules="rules" ref="uForm"
						:labelStyle="{fontSize:'18px'}" labelWidth="80px">
						<u-form-item :required="true" label="" prop="people" borderBottom ref="item1">
							<u-input v-model="form.people" :fontSize="30" color="#4275F4" inputAlign="right"
								placeholder="请输入人数" border="none" :customStyle="{marginRight:'20px',fontWeight:'bold'}" placeholderStyle="fontSize:20px;fontWeight:normal">
								<view slot="prefix" class=""></view>
							</u-input>
						</u-form-item>
					</u--form>
					<keybored type="number" v-model="form.people" :confirmText="t == 'open' ? '开台' :'确认'"
						:isClose="false" @doneClear="form.people=''" @doneAdd="founding">
					</keybored>
					<!-- <view v-if="current==1" class=""></view>
					<view v-if="current==2" class="p20">
						<view class="mb20 f18">请选择提成人</view>
						<view class="f16 c9">当前桌台没有提成方案，无需设置提成人</view>
					</view>
					<view v-if="current==3" class="p20">
						<view class="mb20 f16 c9">请选择桌台备注</view>
						<view class="reson_i f16 mr10 mb10 bs6 " :class="form.resons.includes(item)?'acreson_i':''"
							v-for="(item,index) in ['某公司员工','某领导']" :key="index" @click="chooseRes(item)">{{item}}
							<view class="r_gou"></view>
							<text class="iconfont icon-duigou f12"></text>
						</view>
						<view class="f-x-bt">
							<u-input v-model="remark" placeholder="请输入自定义备注" style="background: #fcfcfc;"></u-input>
							<u-button color="#4275F4" :customStyle="{width:'100px'}" @click="addRemark"><text
									class="c0">确定</text></u-button>
						</view>
					</view> -->
				</view>

			</view>
		</u-popup>
		<u-toast ref="uToast"></u-toast>
		<u-modal :show="show" :showCancelButton="showCancelButton" title=" " width="300px" confirmColor="#000"
			cancelText="重新输入" :confirmText="confirmText" :content='content' @confirm="confirm"
			@cancel="show=false"></u-modal>
	</view>
</template>

<script>
	import keybored from '@/components/liujto-keyboard/keybored.vue';
	export default {
		props: {
			// table: {
			// 	type: Object,
			// 	default: {}
			// }
		},
		components: {
			keybored,
		},
		data() {
			return {
				share: false,
				show: false,
				showCancelButton: true,
				current: 0,
				remark: '',
				confirmText: '新增会员',
				content: '该手机号/卡号不存在 您可以新增会员或返回重新输入卡号/手机号',
				form: {
					people: 0,
					vip: '',
					commenter: '',
					desc: '',
					resons: []
				},
				rules: {
					people: {
						type: 'number',
						required: true,
						message: '请填写人数',
						trigger: ['blur', 'change']
					}
				},
				tit: '',
				t: 'open',
			}
		},
		methods: {
			open(t, v) {
				this.form = v
				if (t == 'open') {
					this.form.people = v.type.max
					this.tit = '请输入就餐人数'
					this.t = t
				} else if (t == 'edit') {
					this.form.people = v.people
					this.tit = '修改桌台信息'
					this.t = t
				}
				this.share = true
			},
			close() {
				this.share = false
			},
			//搜索
			search() {
				if (!this.form.vip) {
					this.$refs.uToast.show({
						message: '请输入卡号/手机号后四位',
						position: 'center',
					})
				} else {
					this.show = true
					// this.current = 1
				}
			},
			//确定
			confirm() {
				this.confirmText = '我知道了'
				this.content = '当前服务已到期，如需使用请联系18038018206'
			},
			//开台
			founding() {
				this.$emit('save', this.form.people)
			},
			//备注
			chooseRes(item) {
				if (!this.form.resons.includes(item)) {
					this.form.resons.push(item)
				} else {
					this.form.resons = this.form.resons.filter(v => {
						return v !== item
					});
				}
				this.form.desc = this.form.resons.join(',')
			},
			addRemark() {
				if (!this.remark.length) {
					this.form.desc += this.remark
				} else {
					this.form.desc += ''
				}
			}
		}
	}
</script>

<style lang="scss" scoped>
	.share {
		position: fixed;
		top: 50%;
		// left: 50%;
		transform: translate(-50%, -50%);
		width: 400px;
		// height: 410px;

		/deep/.u-form-item__body {
			padding: 15px 0;
		}

		/deep/.ljt-keyboard-body {
			border-left: 2px solid #e5e5e5;

			.ljt-keyboard-number-body {
				width: 400px !important;
				height: 300px !important;
			}

			.ljt-number-btn-confirm-2 {
				background: #4275F4 !important;

				// span {
				// 	color: #000;
				// }
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

	/deep/.u-toast {
		position: absolute;
		z-index: 99999999;
	}

	/deep/.u-modal {
		border: 1px solid #e6e6e6 !important;
		box-shadow: 0 0 10px 0 rgba(#000, .5);

		.u-modal__button-group__wrapper--confirm {
			background: #4275F4;
		}
	}
</style>
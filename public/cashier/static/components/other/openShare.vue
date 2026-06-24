<template>
	<view>
		<u-popup :show="share" :round="10" :closeable="false" :overlayOpacity="0.2" mode="center" @close="close">
			<view class="share bf f18 f-x-bt bs10">
				<view class="f-1 h100">
					<view class="f-x-bt f-y-c mt20">
						<view class="f-g-1 ml20">
							<view class="f-g-1 wei f24 flex f-y-c c0">
								开班
								<view class="p-0-10 c9 f16">收银员：{{user && user.nickname}}</view>
							</view>
						</view>
						<view class="f-g-0 f-c p10 mr10">
							<text class="iconfont icon-cuowu wei5 c6" style="font-size: 20px;" @click="close"></text>
						</view>
					</view>
					<u--form labelPosition="left" :model="form" :rules="rules" ref="uForm"
						:labelStyle="{fontSize:'18px'}" labelWidth="80px">
						<!-- 	<u-form-item :required="true" label="" prop="people" borderBottom ref="item1">
							<u-input v-model="form.people" :fontSize="30" color="#4275F4" inputAlign="right"
								placeholder="请输入备用金" border="none" :customStyle="{marginRight:'20px',fontWeight:'bold'}" placeholderStyle="fontSize:20px;fontWeight:normal">
								<view slot="prefix" class=""></view>
							</u-input>
						</u-form-item> -->
					</u--form>
					<!-- <keybored type="number" v-model="form.people" confirmText="开班" :isClose="false"
						@doneClear="form.people=''" @doneAdd="founding">
					</keybored> -->
					<view class="p30">
						<view class="mb30 f20">当前时间：{{getNowDate()}}</view>
						<u-button color="#4275F4" size="large" :customStyle="{}" @click="founding">
							<text class="f18">开班</text></u-button>
					</view>
				</view>
			</view>
		</u-popup>
	</view>
</template>

<script>
	import {
		mapState,
		mapMutations,
	} from 'vuex'
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
		computed: {
			...mapState({
				user: state => state.user,
			}),

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
					people: '',
					vip: '',
					commenter: '',
					desc: '',
					resons: []
				},
				rules: {
					people: {
						type: 'number',
						required: true,
						message: '请输入备用金',
						trigger: ['blur', 'change']
					}
				},
				tit: '',
				t: 'open',
			}
		},
		methods: {
			...mapMutations(["setHandOver"]),
			open(t, v) {
				// this.form = v
				this.share = true
			},
			close() {
				this.share = false
			},
			async founding() {
				// this.$emit('save', this.form.people)
				let {
					data,
					code,
					msg,
				} = await this.beg.request({
					url: this.api.handOver,
					method: 'POST'
				})
				uni.$u.toast(msg)
				if (data) {
					this.setHandOver(data)
				}
				if (code && code == 200) {
					this.close()
				}
			},
			getNowDate() {
				var date = new Date();
				var sign2 = ":";
				var year = date.getFullYear()
				var month = date.getMonth() + 1;
				var day = date.getDate();
				var hour = date.getHours();
				var minutes = date.getMinutes();
				var seconds = date.getSeconds()
				var weekArr = ['星期一', '星期二', '星期三', '星期四', '星期五', '星期六', '星期天'];
				var week = weekArr[date.getDay()];
				if (month >= 1 && month <= 9) {
					month = "0" + month;
				}
				if (day >= 0 && day <= 9) {
					day = "0" + day;
				}
				if (hour >= 0 && hour <= 9) {
					hour = "0" + hour;
				}
				if (minutes >= 0 && minutes <= 9) {
					minutes = "0" + minutes;
				}
				if (seconds >= 0 && seconds <= 9) {
					seconds = "0" + seconds;
				}
				return year + "-" + month + "-" + day + " " + hour + sign2 + minutes + sign2 + seconds;
			},

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
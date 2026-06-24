<template>
	<view :ref="mRef" class="flex ljt-keyboard-body">
		<slot></slot>
		<view class="flex ljt-keyboard-number-body"
			:style="{width:windowWidth+'px',backgroundColor:bodyBackground,padding:padding,height:keyboardHeight+'rpx'}">
			<view class="" style="flex:3;">
				<view class="flex ljt-number-row" :style="{height:height+'rpx'}"
					:class="[(rowIndex==0||theme=='button')?'':'ljt-top-border']"
					v-for="(row,rowIndex) in keyboardCell">
					<view :style="{padding:padding,flex:cell.flex?cell.flex:1}" class="ljt-number-cell"
						:class="[(cellIndex==0||theme=='button')?'':'ljt-left-border',themeCellClass]"
						v-for="(cell,cellIndex) in row">
						<view class="ljt-number-btn" @click="onCellClick($event,cell.value)"
							:class="[cell.id||cell.id=='0'?btnActiveClass:'',themeClass]"
							:style="{backgroundColor:mBtnStyle.backgroundColor}">
							<template v-if="cell.value=='_close'">
								<text :class="[closeIcon.class]" class="fs-28"
									:style="{color:mBtnStyle.color}">{{closeIcon.icon}}</text>
							</template>
							<template v-else-if="cell.value=='_back'">
								<text :class="[backIcon.class]" class="fs-28"
									:style="{color:mBtnStyle.color}">{{backIcon.icon}}</text>
							</template>
							<template v-else>
								<text class="ljt-number-text"
									:style="{color:mBtnStyle.color,fontWeight:mBtnStyle.fontWeight}">{{cell.text}}</text>
							</template>
						</view>
					</view>
				</view>
			</view>
			<!-- 清空按钮 -->
			<view class="flex ljt-keyboard-right" v-if="isConfirmShow" :class="[theme!='button'?'ljt-left-border':'']"
				style="flex: 1;">
				<view :style="{padding:padding}" class="ljt-number-cell" :class="[themeCellClass]" style="flex: 1;">
					<view class="ljt-number-btn" @click="$emit('clearAll')" :class="[btnActiveClass,themeClass]"
						:style="{backgroundColor:mBtnStyle.backgroundColor}">
						<!-- <view class="ljt-number-btn" @click="onCellClick($event,'_back')"
							:class="[btnActiveClass,themeClass]" :style="{backgroundColor:mBtnStyle.backgroundColor}"> -->
						<text :class="[backIcon.class]" class="fs-28"
							:style="{color:mBtnStyle.color}">{{backIcon.icon}}</text>
					</view>

				</view>
				<view :style="{padding:padding}" class="ljt-number-cell border-top-1" :class="[themeCellClass]"
					style="flex: 1;" v-if="isCut">
					<view class="ljt-number-btn" @click="onCellClick($event,'-')"
						:class="[mValue.indexOf('-')<0?btnActiveClass:'',themeClass]"
						:style="{backgroundColor:mBtnStyle.backgroundColor}">
						<text class="fs-28" :style="{color:mBtnStyle.color}">-</text>
					</view>

				</view>
				<view :style="{padding:padding,flex:(isCut?2:3)}" class="ljt-number-cell" :class="[themeCellClass]">
					<view class="ljt-number-btn" @click="onCellClick($event,'_done')"
						:class="[btnConfirmActiveClass,themeClass]" style="flex: 1;"
						:style="{backgroundColor:mConfirmStyle.backgroundColor}">
						<text class="ljt-number-text"
							:style="{color:mConfirmStyle.color,fontWeight:mConfirmStyle.fontWeight}">{{confirmText || '完成'}}</text>
					</view>

				</view>
			</view>
		</view>
	</view>
</template>

<script>
	export default {
		props: {
			value: {
				type: [Number, String],
				default: ''
			},
			/**
			 * 主题模式
			 */
			theme: {
				type: String,
				default: 'block', //block button
			},
			/**
			 * 模式 number 整数,digit 小数,idcard 身份证,password 密码，money金额
			 */
			type: {
				type: String,
				default: 'number',
			},
			/**
			 * 提交按钮文案
			 */
			confirmText: {
				type: String,
				default: '搜索'
			},
			/**
			 * 背景色
			 */
			backgroundColor: {
				type: String,
				default: '#ffffff'
			},
			/**
			 * 保留小数位数 0为整数模式
			 */
			point: {
				type: [Number, String],
				default: 2
			},
			/**
			 * 按钮样式
			 */
			btnStyle: {
				type: Object,
				default: function() {
					return {
						backgroundColor: '#ffffff', //按钮背景色
						color: 'rgba(0,0,0,.85)', //按钮文字颜色
						fontWeight: 400,
					}
				}
			},
			/**
			 * 提交按钮样式
			 */
			confirmStyle: {
				type: Object,
				default: function() {
					return {
						backgroundColor: '#FD7231', //按钮背景颜色
						color: '#ffffff', //按钮文字颜色
					}
				}
			},
			/**
			 * 最大值
			 */
			max: {
				type: [Number, String],
				default: 9999999999
			},
			/**
			 * 最小值，如果设置了，则默认值如果小于该值，则会变为该值
			 */
			min: {
				type: [Number, String],
				default: 0
			},
			/**
			 * 是否可关闭
			 */
			isClose: {
				type: Boolean,
				default: true
			},
			/**
			 * 是否带负号
			 */
			isCut: {
				type: Boolean,
				default: false
			},
			/**
			 * 回退按钮图标
			 */
			backIcon: {
				type: Object,
				default: function() {
					return {
						class: '',
						icon: '清空'
					}
				}
			},
			/**
			 * 关闭按钮图标
			 */
			closeIcon: {
				type: Object,
				default: function() {
					return {
						class: '',
						icon: '回退'
					}
				}
			}
		},
		data() {
			let _ref = this.id || 'ljtKeyboardNumber'
			return {
				sysInfo: null,
				windowWidth: 375, //屏幕宽度
				cellWidth: 186, //单元格宽度
				keyboardCell: [],
				themeClass: '', //主题外层类名
				themeCellClass: '', //主题外层类名
				bodyBackground: '#ffffff', //背景色,
				height: 110, //按钮高度
				padding: '0rpx',
				mValue: '',
				mRef: _ref,
				mMax: 0,
				mMin: 0,
				btnActiveClass: 'ljt-number-btn-ac',
				btnConfirmActiveClass: 'ljt-number-btn-confirm',
				mConfirmStyle: {},
				mBtnStyle: {}
			}
		},
		watch: {
			value(_val) {
				this.mValue = _val
			},
			// H5 下禁止底部滚动
			showPopup(show) {
				// #ifdef H5
				// fix by mehaotian 处理 h5 滚动穿透的问题
				document.getElementsByTagName('body')[0].style.overflow = show ? 'hidden' : 'visible'
				// #endif
			},
			max(_val) {
				this.mMax = Number(_val)
			},
			min(_val) {
				this.mMin = Number(_val)
			},
			type(_val) {
				this.type = _val
				this.initKeyboardNumber()
			}
		},
		computed: {
			isConfirmShow() {
				return this.type != 'password' && this.type != 'money'
			}
		},
		created() {
			this.mValue = this.value
			this.sysInfo = uni.getSystemInfoSync()
			this.windowWidth = this.sysInfo.windowWidth
			this.cellWidth = (this.sysInfo.windowWidth / 4) - 4
			this.mMax = Number(this.max)
			this.mMin = Number(this.min)

			this.mBtnStyle = {
				backgroundColor: '#ffffff', //按钮背景色
				color: 'rgba(0,0,0,.85)', //按钮文字颜色
				customClass: '', //额外类名
				fontWeight: 400,
				...this.btnStyle,
			}
			this.mConfirmStyle = {
				backgroundColor: '#FD7231', //按钮背景颜色
				color: '#ffffff', //按钮文字颜色
				customClass: '', //额外类名
				...this.confirmStyle,
			}

			//激活颜色 适配背景色的点击效果
			this.btnActiveClass = this.mBtnStyle.backgroundColor != '#ffffff' ? 'ljt-number-btn-ac-2' : this.btnActiveClass
			this.btnConfirmActiveClass = this.mConfirmStyle.backgroundColor != '#ffffff' ? 'ljt-number-btn-confirm-2' :
				this.btnConfirmActiveClass

			if (this.theme == 'button') {
				this.bodyBackground = this.backgroundColor === '#ffffff' ? '#f6f6f6' : this.backgroundColor
				this.themeClass = 'ljt-button-theme'
				this.themeCellClass = 'ljt-button-theme-cell'
				this.height = 100
				this.padding = '10rpx'
			}
			this.keyboardHeight = this.height * 4

			this.initKeyboardNumber()
		},
		methods: {
			//初始化键盘数字
			initKeyboardNumber() {
				let _list = [
					[{
						id: 1,
						text: 1,
						value: 1
					}, {
						id: 2,
						text: 2,
						value: 2
					}, {
						id: 3,
						text: 3,
						value: 3
					}],
					[{
						id: 4,
						text: 4,
						value: 4
					}, {
						id: 5,
						text: 5,
						value: 5
					}, {
						id: 6,
						text: 6,
						value: 6
					}],
					[{
						id: 7,
						text: 7,
						value: 7
					}, {
						id: 8,
						text: 8,
						value: 8
					}, {
						id: 9,
						text: 9,
						value: 9
					}],
					[{
						id: 0,
						text: 0,
						value: 0
					}]
				]
				this.keyboardCell = this[[this.type] + 'Keyboard'](_list)
			},
			//数字键盘
			numberKeyboard(_list) {
				//整数输入
				if (this.isClose) {
					_list[3][0]['flex'] = 2
					_list[3].push({
						id: '_close',
						text: '',
						value: '_close',
						flex: 1
					})
				} else {
					_list[3][0]['flex'] = 1
				}
				return _list
			},
			//身份证键盘
			idcardKeyboard(_list) {
				//身份证
				_list[3].unshift({
					id: 'X',
					text: 'X',
					value: 'X'
				})
				if (this.isClose) {
					_list[3][0]['flex'] = 1
					_list[3][1]['flex'] = 1
					_list[3].push({
						id: '_close',
						text: '',
						value: '_close',
						flex: 1
					})
				} else {
					_list[3][0]['flex'] = 1
					_list[3][1]['flex'] = 2
				}
				return _list
			},
			//小数点键盘
			digitKeyboard(_list) {
				_list[3].unshift({
					id: '.',
					text: '.',
					value: '.'
				}) //加入小数点
				if (this.isClose) {
					_list[3][0]['flex'] = 1
					_list[3][1]['flex'] = 1
					_list[3].push({
						id: '_close',
						text: '',
						value: '_close',
						flex: 1
					})
				} else {
					_list[3][0]['flex'] = 1
					_list[3][1]['flex'] = 2
				}
				return _list;
			},
			//密码键盘
			passwordKeyboard(_list) {
				if (this.isClose) {
					_list[3].unshift({
						id: '_close',
						text: '',
						value: '_close'
					}) //加入关闭
				} else {
					//调整0的按钮为flex2
					_list[3][0]['flex'] = 2
				}

				//最后加入回退
				_list[3].push({
					id: '_back',
					text: '',
					value: '_back',
					flex: 1
				})
				return _list
			},
			//金额键盘
			moneyKeyboard(_list) {
				_list[3].unshift({
					id: '.',
					text: '.',
					value: '.'
				}) //加入小数点

				//最后加入回退
				_list[3].push({
					id: '_back',
					text: '',
					value: '_back',
					flex: 1
				})
				return _list
			},
			onCellClick(e, _val) {
				e.stopPropagation()


				_val = _val + ''
				let _text = this.mValue + '' //转为字符串
				// if (_val == '_back') {
				// 	//关闭
				// 	this.$emit('onClose', _text)
				// 	return
				// }
				if (_val == '_done') {
					//完成
					this.$emit('onDone', _text)
					return
				}
				if (_val == '_close') {
					//回退
					if (_text.length > 0) {
						_text = _text.substring(0, _text.length - 1)
					}
				} else if (_val == '.') {
					//小数点 如果是第一位或者是有小数点了，则不允许输入
					if (_text.length <= 0 || _text.indexOf('.') >= 0) {
						return
					}
					_text += '.'
				} else if (_val == '-') {
					if (_text.indexOf('-') >= 0) {
						return
					}
					_text += '-'
				} else if (_val == 'X') {
					//身份证
					if (_text.indexOf('X') >= 0) {
						return
					}
					_text += 'X'
				} else {

					if (this.type == 'idcard') {
						if (_text.length == 18) {
							return
						}
						_text += _val
					} else if (this.type == 'password') {
						_text += _val
					} else {
						if (this.mMax && Number(this.mValue) >= this.mMax) {
							return
						}
						//判断小数点后的长度
						let _point = Number(this.point) || 0
						if (_point) {
							let _arr = _text.split('.')
							if (_arr.length == 2) {
								if (_arr[1].length >= (_point)) {
									return
								}
							}
						}
						//判断数字
						if (this.mMax && Number(this.mValue + _val) > this.mMax) {
							_text = this.mMax + ''
						} else {
							_text += _val
						}
						//00不能开头
						if (_text.indexOf('00') == 0) {
							_text = '0'
						}
						if (_text.indexOf('0') == 0 && _text.indexOf('0.') < 0) {
							_text = Number(_text) + ''
						}
					}
				}
				_text = _text + ''

				this.$emit('input', _text)

			}
		}
	}
</script>

<style lang="scss" scoped>
	view {
		display: flex;
		flex-direction: column;
	}

	.flex {
		display: flex;
		overflow: hidden;
		box-sizing: border-box;
	}

	.ljt-keyboard-body {
		/* #ifndef APP-NVUE */
		z-index: 999;
		width: 100%;
		/* #endif */
		// position: fixed;
		left: 0;
		right: 0;
		bottom: 0;
	}

	.ljt-keyboard-number-body {
		flex-direction: row;
	}

	.ljt-keyboard-right {}

	.ljt-number-row {
		flex: 1;
		flex-direction: row;
	}

	.ljt-number-cell {
		flex: 1;
	}

	.ljt-number-btn {
		flex: 1;
		align-items: center;
		justify-content: center;
	}

	.ljt-number-text {
		font-size: 36rpx;
		color: rgba(0, 0, 0, .85);
	}

	.ljt-number-btn-ac:active {
		background-color: #e5e5e5 !important;
	}

	.ljt-number-btn-ac-2:active {
		opacity: .7 !important;
	}

	.ljt-number-btn-confirm:active {
		background-color: #f9f9f9 !important;
	}

	.ljt-number-btn-confirm-2:active {
		opacity: .7 !important;
	}

	.ljt-button-theme {
		border-width: 0rpx !important;
		border-radius: 10rpx;
	}

	.ljt-left-border {
		border-color: #f5f5f5;
		border-left-width: 1px;
		border-style: solid;
		border-right: 0;
		border-top: 0;
		border-bottom: 0;
	}

	.ljt-right-border {
		border-color: #f5f5f5;
		border-right-width: 1px;
		border-style: solid;
		border-left: 0;
		border-top: 0;
		border-bottom: 0;
	}

	.ljt-bottom-border {
		border-color: #f5f5f5;
		border-bottom-width: 1px;
		border-style: solid;
		border-top: 0;
		border-left: 0;
		border-right: 0;
	}

	.ljt-top-border {
		border-color: #f5f5f5;
		border-top-width: 1px;
		border-style: solid;
		border-bottom: 0;
		border-left: 0;
		border-right: 0;
	}
</style>

<template>
	<view>
		<u-popup :show="addDish" :round="10" mode="center" @close="close" closeable>
			<view class="addDish">
				<view class="wei6 p15 f18 bd1">添加临时商品</view>
				<view class="p15">
					<view class="c9 f16">将临时商品添加至购物车，不会影响总商品库</view>
					<u--form class="p15" labelPosition="left" :model="form" ref="uForm" labelWidth="90px">
						<view class="f-g-1 mb10" @click="pShow=false,nShow=false">
							<u-form-item class="" :required="true" label="商品名称" prop="name" borderBottom ref="item1">
								<u--input v-model="form.name" inputAlign="right" border="none"
									placeholder="请输入"></u--input>
							</u-form-item>
							<!-- <u-form-item :required="true" label="商品分类" prop="classify" borderBottom ref="item1">
								<u--input v-model="form.classify" inputAlign="right" border="none"
									placeholder="请输入"></u--input>
							</u-form-item> -->
						</view>
						<view class="f-x-bt">
							<u-form-item class="mr30 p-r" :required="true" label="商品价格" prop="name" borderBottom
								ref="item1">
								<u--input v-model="form.price" inputAlign="right" border="none" placeholder="请输入"
									@focus="pShow=true,nShow=false"></u--input>
								<view class="srk bf">
									<keybored v-if="pShow" type="digit" v-model="form.price" confirmText="确认"
										@doneClear="doneClear" @doneAdd="doneAdd" @input="cInput">
									</keybored>
								</view>
							</u-form-item>
							<u-form-item class="p-r" :required="true" label="购买数量" prop="classify" borderBottom
								ref="item1">
								<u--input v-model="form.num" inputAlign="right" border="none" placeholder="请输入"
									@focus="nShow=true,pShow=false"></u--input>
								<view class="srk bf">
									<keybored v-if="nShow" type="digit" v-model="form.num" confirmText="确认"
										@doneClear="doneClear2" @doneAdd="doneAdd" @input="cInput">
									</keybored>
								</view>
							</u-form-item>
						</view>
						<!-- <view class="f-x-bt">
							<u-form-item class="mr30" :required="true" label="出品档口" prop="name" borderBottom
								ref="item1">
								<u--input v-model="form.name" inputAlign="right" border="none"
									placeholder="请输入"></u--input>
							</u-form-item>
							<u-form-item :required="true" label="单位" prop="classify" borderBottom ref="item1">
								<u--input v-model="form.classify" inputAlign="right" border="none"
									placeholder="请输入"></u--input>
							</u-form-item>
						</view> -->
					</u--form>
				</view>
				<view class="bf p15 f18 f-y-bt" @click="pShow=false,nShow=false">
					<view>
						<view class="reson_i f16 mr10 mb10 bs6 " :class="resons.includes(item)?'acreson_i':''"
							v-for="(item,index) in list" :key="index" @click="chooseRes(item)">
							{{item}}
						</view>
						<view v-show="show" class="reson_i f16 mr10 mb10 bs6 "
							:class="resons.includes(remark)?'acreson_i':''" @click="addesc">
							{{remark}}
							<view class="r_gou"></view>
							<text class="iconfont icon-duigou f12"></text>
						</view>
						<view class="dfa">
							<u--textarea v-model="desc" placeholder="请输入自定义备注"
								:class="resons.includes(desc)?'acreson_i':''"
								style="background: #fcfcfc;"></u--textarea>
						</view>
					</view>
					<view class="f-1 f-y-e mt20">
						<u-button @click="close" class="mr20"><text class="c0">取消</text></u-button>
						<u-button color="#4275F4" @click="save"><text class="cf">确认</text></u-button>
					</view>
				</view>
			</view>
		</u-popup>
	</view>
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
				addDish: false,
				resons: [],
				list: [],
				type: 'allDesc',
				show: false,
				remark: '',
				desc: '',
				form: {},
				rules: {
					name: {
						type: 'string',
						required: true,
						message: '请填写菜品名称',
						trigger: ['blur', 'change']
					}
				},
				value: '',
				forms: {},
				pShow: false,
				nShow: false,
			}
		},
		computed: {
			...mapState({
				reasonConfig: state => state.config.reasonConfig,
			}),
		},
		methods: {
			open(v) {
				this.list = this.reasonConfig && this.reasonConfig.goodsNotes || []
				this.resons = []
				this.addDish = true
			},
			close() {
				this.form = {}
				this.desc = ''
				this.pShow = false
				this.nShow = false
				this.addDish = false
			},
			chooseRes(item, type) {
				if (!this.resons.includes(item)) {
					this.resons.push(item)
				} else {
					this.resons = this.resons.filter(v => {
						return v !== item
					});
				}
				// this.close()
			},
			addesc() {
				if (!this.resons.includes(this.desc)) {
					this.resons.push(this.desc)
				} else {
					this.resons = this.resons.filter(v => {
						return v !== this.desc
					});
				}
				if (this.type == 'remark') {
					this.$emit('itemRemark', this.resons, 1)
				} else {
					this.$emit('returnRemark', this.resons, 1)
				}
				this.close()
			},
			doneClear() {
				this.form.price = ''
			},
			doneClear2() {
				this.form.num = ''
			},
			cInput(e) {
				// this.value = e
			},
			confirm() {
				if (this.desc) {
					if (this.type == 'remark') {
						this.$emit('itemRemark', this.desc, 2)
					} else {
						this.$emit('returnRemark', this.desc, 2)
					}
					this.close()
				} else {
					this.show = false
				}
			},
			save() {
				if (!this.form.name) {
					return uni.$u.toast('请输入商品名称')
				} else if (!this.form.price) {
					return uni.$u.toast('请输入商品价格')
				} else if (!this.form.num) {
					return uni.$u.toast('请输入商品数量')
				}
				this.pShow = false
				this.nShow = false
				if (this.desc) this.resons.push(this.desc)
				this.form.notes = this.resons.join('，')
				this.$emit('addCar', this.form)
			},
			doneAdd() {
				this.pShow = false
				this.nShow = false
			}
		}
	}
</script>

<style lang="scss" scoped>
	.addDish {
		// width: 550px;
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
		border: 1px solid #fff;
		background: #4275F4;
		color: #fff;

		.r_gou,
		.icon-duigou {
			display: block;
		}
	}

	.srk {
		position: absolute;
		top: 44px;
		left: 0;
		z-index: 99;

		/deep/.ljt-keyboard-body {
			border-radius: 6px;
			border: 1px solid #e5e5e5;

			.ljt-keyboard-number-body {
				width: 300px !important;
				height: 200px !important;
			}

			.ljt-number-btn-confirm-2 {
				background: #4275F4 !important;

				span {
					color: #fff;
					font-size: 20px;
				}
			}
		}
	}
</style>
<template>
	<u-overlay :show="show" :opacity="0.2" @click="close">
		<view class="reduce bf f18 f-y-bt" @tap.stop>
			<scroll-view scroll-y class="content bf">
				<!-- <view class="imgh o-h">
					<image :src="ginfo.logo" mode="aspectFit" class="swiper" style="width: 100%;"></image>
				</view> -->
				<view class="wrapper">
					<view class="f-bt f-y-c">
						<view class="title">{{ ginfo.name }}</view>
						<view><text class="iconfont icon-cuowu wei5 c6 pl10" style="font-size: 17px;" @click="close"></text></view>
					</view>
					<!-- <view class="mb-20 desc" v-if="ginfo.desc && ginfo.desc!='null'">{{ ginfo.desc }}</view> -->
					<view v-if="ginfo.specSwitch==1 && ginfo.specData.length">
						<view v-for="(pv,pi) in ginfo.specData" :key='pi' class="" @click="comPrice">
							<view class="c0 f26" style="padding: 10px 0;">{{pv.name}}</view>
							<view class="f-raw">
								<view v-if="cv.name" @click="clickSpec(pi,ci,cv)" v-for="(cv,ci) in pv.value" :key='ci'
									class="specv t-o-e f22 c0" :class="{'opc':!cv.b}"
									:style="{background:cv.b && cv.a?subColor:'',color:cv.b && cv.a?'#fff':''}">
									{{cv.name}}
									￥{{cv.price}}
								</view>
							</view>
						</view>
					</view>
					<view v-if="ginfo.attrSwitch==1 && ginfo.attrData.length">
						<view v-for="(pv,pi) in ginfo.attrData" :key='pi' class="">
							<view class="c0 f26" style="padding: 10px 0;">{{pv.name}}</view>
							<view class="f-raw">
								<view v-if="cv.name" @click="clickAttribute(pi,ci)" v-for="(cv,ci) in pv.value"
									:key='ci' class="specv t-o-e f22 c0"
									:style="{background:cv.a?subColor:'',color:cv.a?'#fff':''}">{{cv.name}}</view>
							</view>
						</view>
					</view>
					<block v-if="ginfo.materialSwitch==1 && ginfo.materialData.length">
						<view class="materials" style="width: 100%;" v-for="(v, i) in ginfo.materialData" :key="i">
							<view class="c0 f26" style="padding: 10px 0;">{{ v.name }}
								<text class="f20 c9 ml10" v-if="v.required==1">(必选)</text>
							</view>
							<view class="values">
								<view class="t-o-e f22 specv f-bt p-r numSpec c0" :class="{'opc':!cv.b}"
									:style="{background:cv.a?subColor:'',color:cv.a?'#fff':''}"
									@tap="clickMaterial(i, ci ,cv)" v-for="(cv, ci) in v.materialList" :key="ci">
									<view class="t-o-e f-g-1" v-if="v.required==1 && v.maxNum<=1">
										{{cv.name}}
										<text v-if="cv.price" class="ml10"
											:style="{color:cv.a?'#fff':''}">￥{{parseFloat(cv.price)}}</text>
									</view>
									<block v-else>
										<block v-if="!cv.b">
											<view class="specsV f-g-1 f-bt ysq">
												<view class="f-g-1 t-o-e f-s">{{ cv.name }}</view>
												<view class="f-g-0">已售罄</view>
											</view>
										</block>
										<block v-else>
											<view class='f34 c3 f-g-0 mr5 f-c' @tap.stop="minusMaterial(i, ci)"><u-icon
													name="minus" size="18" :color="cv.a?'#fff':'#000'"></u-icon></view>
											<view class="specsV f-g-1 f-bt m02">
												<view class="f-g-1 t-o-e f-s c0" :style="{color:cv.a?'#fff':''}">
													{{ cv.name }}
												</view>
												<view class="f-g-0 c0 wei" :style="{color:cv.a?'#fff':''}">
													￥{{ cv.price && parseFloat(cv.price) }}</view>
											</view>
											<view class='f34 c3 f-g-0 ml5 f-c' @tap.stop="addMaterial(i, ci)"><u-icon
													name="plus" size="18" :color="cv.a?'#fff':'#000'"></u-icon></view>
											<view class="p-a num bsf cf f20 f-c" v-show="cv.a"
												:style="{background:cv.a?subColor:''}">{{cv.num}}</view>

										</block>
									</block>
								</view>
							</view>
						</view>
					</block>
					<block v-if="ginfo.type==2 && ginfo.setmealData">
						<block v-if="ginfo.setmealData.fix.length">
							<view v-for="(v,i) in ginfo.setmealData.fix" :key="i">
								<view class="wei mt20">{{v.name}}</view>
								<view class="tcgoods mt10 flex">
									<view class="gswh bs10 mr20 f-g-0 posi-r" :class="{'gswhb':cv.a}"
										:style="{borderColor:cv.a?subColor:'#f5f5f5'}" v-for="(cv,ci) in v.goods" :key="ci">
										<view class="f-g-1 f-c-c">
											<image :src="cv.logo" mode="aspectFit" class="whs"></image>
										</view>
										<view class="t-o-e t-c">{{cv.name}}</view>
										<view class="mt10 t-o-e f-c neutra-font"><text class="wei">{{cv.num || 1}}份</text></view>
										<view class="f-g-0 p10 f-c">
											<view class="materials-box" v-if="cv.isSpec">
												<button type="primary" size="mini" :style="{backgroundColor:subColor,color:fontColor}" class="materials-btn"
													@tap.stop="tchanddl(i,v,ci,cv)">选规格</button>
												<view class="number-badge" v-show="cv.num">
													<view class="number neutra-font" :style="{backgroundColor:subColor,color:fontColor}">{{ cv.num }}</view>
												</view>
											</view>
										</view>
									</view>
								</view>
							</view>
						</block>
						<block v-if="ginfo.setmealData.match.length">
							<view v-for="(v,i) in ginfo.setmealData.match" :key="i" class="mb20">
								<view class="wei mt20">{{v.name}}{{v.goods && v.goods.length}}选{{v.select}}</view>
								<view class="tcgoods mt10 flex">
									<view class="gswh bs10 mr20 f-g-0 posi-r" :class="{'gswhb':cv.a}"
										:style="{borderColor:cv.a?subColor:'#f5f5f5'}" v-for="(cv,ci) in v.goods" :key="ci"
										@click="stcGoods(i,v,ci,cv)">
										<view class="f-g-1 f-c-c">
											<image :src="cv.logo" mode="aspectFit" class="whs"></image>
										</view>
										<view class="t-o-e t-c">{{cv.name}}</view>
										<view class="mt10 t-o-e f-c neutra-font">￥<text class="wei">{{cv.addPrice}}</text></view>
										<view class="f-g-0 f-c mt5">
											<actions v-if="cv.price" :materials-btn="cv.isSpec" @materials="tchanddl(i,v,ci,cv)"
												:number="cv.num" @add="tchandcar($event,i,ci,cv)"
												@minus="tchandcar($event,i,ci,cv)" :product="cv" />
										</view>
									</view>
								</view>
							</view>
						</block>
					</block>
				</view>
			</scroll-view>
			<view class="bottom"
				v-if="ginfo.isShow!=1 && (!ginfo.specSwitch && ginfo.singleSpec && ginfo.singleSpec.surplusInventory!=0 || ginfo.specSwitch && !ginfo.singleSpec)">
				<view class="flex aict f-bt">
					<view class="jljg f-g-1">
						<view class="price">
							￥{{ jsPrice>=0 && jsPrice || (ginfo.specSwitch==1 ? ginfo.mixPrice : ginfo.singleSpec.price)}}
							<!-- <text class="f26 t-d-l c0 nowei ml10"
								v-if="ginfo.specSwitch==0 && ginfo.singleSpec.linePrice>0 && ginfo.singleSpec.price && (Number(ginfo.singleSpec.linePrice)>Number(ginfo.singleSpec.price))">￥{{parseFloat(ginfo.singleSpec.linePrice)}}</text> -->
							<text class="f26 t-d-l c9 nowei ml10" v-if="ginfo.specSwitch==1 && jslinePrice>0 && jsPrice && (Number(jslinePrice)>Number(jsPrice))">￥{{jslinePrice}}</text>
						</view>
						<view class="f22 c0 flex f-w">
							<view v-if="xzSpecInfo && xzSpecInfo.ggdata">[{{xzSpecInfo.ggdata}}]</view>
							<view v-if="xzSxInfo.arr && xzSxInfo.arr.length">[{{xzSxInfo.arr.map(v => v.name).join()}}]
							</view>
							<view class="materials c0" v-show="getProductSelectedMaterials">
								{{ getProductSelectedMaterials }}
							</view>
						</view>
					</view>
					<view class="f-g-0">
						<view class="num-box f-bt f-y-c">
							<view class="minus" @click.stop="minus"><u-icon name="minus" size="20"></u-icon></view>
							<input class="f-g-1 number t-c h-100 f28" type="number" v-model="cnum" @input="cNum" />
							<view class="plus" @click.stop="add"><u-icon name="plus" size="20"></u-icon></view>
						</view>
					</view>
				</view>
				<view class="f22 c9 f-w" v-if="ginfo.type==2">
					<view v-if="xzTcInfo && xzTcInfo.tcdata && xzTcInfo.tcdata.length">{{xzTcInfo.tcdata}}</view>
				</view>
				<view class="f-bt mt10">
					<button type="primary" class="add-cart-btn f-c f-g-1 bs60"
						:style="{backgroundColor:subColor,color:'#fff'}" @tap="addToCart">加入购物车</button>
				</view>
			</view>
		</view>
		<product-modal ref="productModal" :product="tcproduct" :visible="productModalVisible"
			@cancel="closeProductDetailModal" @add-to-cart="handleAddToCartInModal"></product-modal>
	</u-overlay>
</template>

<script>
	import {
		mapState
	} from 'vuex'
	import Actions from "./actions.vue"
	import ProductModal from './productModals.vue'
	export default {
		name: 'ProductModal',
		components: {
			Actions,
			ProductModal,
		},
		props: {
			visible: {
				type: Boolean,
				default: false
			},
			product: {
				type: Object,
				default: () => {}
			},
			storeId: {
				type: String,
				default: ''
			},
			carList: {
				type: Object,
				default: () => {}
			}
		},
		data() {
			return {
				ginfo: {},
				cnum: 1,
				jsPrice: '',
				jslinePrice: '',
				jsDiscountLabel: '',
				show: false,
				subColor: '#4275F4',
				fontColor:'#fff',
				productModalVisible:false,
				tcproduct:{},
			}
		},
		watch: {
			product(val) {
				if (val.specSwitch == 1 && val.specData) {
					val.specData.forEach(v => {
						v.value = v.value.map((v, i) => {
							let kc = val.skus.find(f => f.specName[0].id == v.id).surplusInventory
							return {
								name: v.name,
								id: v.id,
								price: val.skus.find(f => f.specName[0].id == v.id).price,
								a: kc && v.checkId ? 1 : 0,
								b: val.skus.find(f => f.specName[0].id == v.id).surplusInventory,
							}
						})
					})
				}
				if (val.attrSwitch == 1 && val.attrData) {
					val.attrData.forEach(v => {
						v.value.forEach((v, i) => {
							v.a = v.checkId ? 1 : 0
						})
					})
				}
				if (val.materialSwitch == 1 && val.materialData) {
					this.havebxjl = val.materialData.findIndex(v => v.required == 1) > -1
					val.materialData.forEach(v => {
						v.materialList.forEach((v, i) => {
							v.a = v.inventory && v.checkId ? 1 : 0
							v.b = v.inventory
							v.num = v.checkId ? 1 : 0
						})
					})
				}
				setTimeout(() => {
					this.comPrice()
				}, 500)
				this.ginfo = JSON.parse(JSON.stringify(val))
			}
		},
		computed: {
			getProductSelectedMaterials() {
				if (this.ginfo.materialSwitch && this.ginfo.materialData) {
					let materialData = []
					this.ginfo.materialData.forEach(({
						materialList
					}) => {
						materialList.forEach(value => {
							if (value.a) {
								materialData.push(`${value.name}${value.num>1?'*'+value.num:''}`)
							}
						})
					})
					return materialData.length ? materialData.join(',') : ''
				}
				return ''
			},
			xzJlInfo() {
				let obj = {
					money: 0,
					arr: []
				}
				if (this.ginfo.materialSwitch == 1 && this.ginfo.materialData.length) {
					let money = 0,
						arr = []
					this.ginfo.materialData.forEach(({
						materialList
					}) => {
						materialList.forEach(cv => {
							if (cv.a) {
								arr.push(cv)
							}
						})
					})
					obj.arr = arr
				}
				return obj
			},
			xzSxInfo() {
				let obj = {
					arr: []
				}
				if (this.ginfo.attrSwitch == 1 && this.ginfo.attrData.length) {
					let r = this.ginfo.attrData,
						c = [];
					for (let n in r) {
						for (let d in r[n].value) {
							if (r[n].value[d].a) {
								c.push(r[n].value[d])
								// break
							}
						}
					}
					obj.arr = c
				}
				return obj
			},
			xzSpecInfo() {
				let obj = {
					ggdata: [],
					specInfo: {},
				}
				if (this.ginfo.specSwitch == 1 && this.ginfo.specData.length) {
					let r = this.ginfo.specData,
						i = [],
						c = [];
					for (let n in r) {
						for (let d in r[n].value) {
							if (r[n].value[d].a) {
								c.push(r[n].value[d].name)
								i.push(r[n].value[d].id)
								break
							}
						}
					}
					obj.ggdata = c.toString()
					obj.specInfo = this.ginfo.skus.find(v => v.specName[0].id == i)
					return obj
				} else {
					return {}
				}

			},
			xzTcInfo() {
				let obj = {
					money: 0,
					arr: [],
					tcdata: [],
					fc : [],
					xz : []
				}
				if (this.ginfo.type == 2 && this.ginfo.setmealData && this.ginfo.setmealData.fix.length) {
					this.ginfo.setmealData.fix.forEach(({
						goods
					}) => {
						goods.forEach(cv => {
							cv.type = 1
							obj.arr.push(cv)
							obj.fc.push(`${cv.name}*${cv.num || 1} ${cv.g && cv.g.attribute || ''} ${cv.g && cv.g.jldata || ''}`)
						})
					})
				}
				if (this.ginfo.type == 2 && this.ginfo.setmealData && this.ginfo.setmealData.match.length) {
					let money = 0,
						arr = []
					this.ginfo.setmealData.match.forEach(({
						goods
					}) => {
						goods.forEach(cv => {
							if (cv.a) {
								cv.type = 2
								obj.arr.push(cv)
								obj.fc.push(`${cv.name}*${cv.num || 1} ${cv.g && cv.g.attribute || ''} ${cv.g && cv.g.jldata || ''}`)
								obj.xz.push(cv)
							}
						})
					})
					// obj.money = +obj.money.toFixed(2)
					// obj.arr = arr
					obj.tcdata = obj.fc.join('+')
				}
				return obj
			}
		},
		methods: {
			async open() {
				this.cnum = 1
				this.jsPrice = ''
				this.jslinePrice = ''
				this.show = true
			},
			close() {
				this.show = false
				this.jsPrice = ''
				this.jslinePrice = ''
			},
			change({
				show
			}) {
				this.$emit('change', show)
			},
			clickSpec(pi, ci, cv) {
				if (!cv.b) return
				let r = this.ginfo.specData
				for (let n in r[pi].value) {
					if (n == ci) {
						this.$set(r[pi].value[n], 'a', 1)
					} else {
						this.$set(r[pi].value[n], 'a', 0)
					}
				}
			},
			clickAttribute(pi, ci) {
				let r = this.ginfo.attrData
				for (let n in r[pi].value) {
					if (r[pi].state && r[pi].state == 1) {
						if (n == ci) this.$set(r[pi].value[n], 'a', r[pi].value[n].a == 1 ? 0 : 1)
					} else {
						if (n == ci) {
							this.$set(r[pi].value[n], 'a', 1)
						} else {
							this.$set(r[pi].value[n], 'a', 0)
						}
					}
				}
			},
			clickMaterial(pi, ci, cv) {
				if (!cv.b) return
				let r = this.ginfo.materialData
				for (let n in r[pi].materialList) {
					if (r[pi].required == 1 && r[pi].maxNum <= 1) {
						if (n == ci) {
							this.$set(r[pi].materialList[n], 'a', 1)
							this.$set(r[pi].materialList[n], 'num', 1)
						} else {
							this.$set(r[pi].materialList[n], 'a', 0)
							this.$set(r[pi].materialList[n], 'num', 1)
						}
					} else {
						if (n == ci && !r[pi].materialList[n].num) {
							this.$set(r[pi].materialList[n], 'a', r[pi].materialList[n].a == 1 ? 0 : 1)
							this.$set(r[pi].materialList[n], 'num', r[pi].materialList[n].num == 1 ? 0 : 1)
						}
					}
				}
				this.comPrice()
			},
			addMaterial(pi, ci) {
				let r = this.ginfo.materialData
				for (let n in r[pi].materialList) {
					if (n == ci) {
						if(!r[pi].materialList[ci].a) {this.$set(r[pi].materialList[n], 'a', 1)}
						this.$set(r[pi].materialList[n], 'num', r[pi].materialList[n].num + 1)
					}
				}
				this.comPrice()
			},
			minusMaterial(pi, ci) {
				let r = this.ginfo.materialData
				for (let n in r[pi].materialList) {
					if (n == ci) {
						if (!r[pi].materialList[n].a) return
						if (r[pi].materialList[n].num > 1) {
							this.$set(r[pi].materialList[n], 'num', r[pi].materialList[n].num - 1)
						} else if (r[pi].materialList[n].num = 1) {
							this.$set(r[pi].materialList[n], 'num', 0)
							this.$set(r[pi].materialList[n], 'a', 0)
						} else {
							uni.$u.toast('不能再少了')
						}
					}
				}
				this.comPrice()
			},
			cNum() {
				if (this.cnum <= 0) {
					this.cnum = 1
				}
				if (this.cnum >= 999999) {
					this.cnum = 999999
				}
			},
			add() {
				this.cnum = parseInt(this.cnum) + 1
				this.comPrice()
			},
			minus() {
				if (this.cnum == 1) {
					return
				}
				this.cnum = parseInt(this.cnum) - 1
				this.comPrice()
			},
			async addToCart() {
				if(this.ginfo.type==2 && this.ginfo.setmealData &&  this.ginfo.setmealData.match){
					const gdsum = this.ginfo.setmealData.match.reduce((total, obj) => total + obj.select, 0)
					const xzsum = this.xzTcInfo.xz.reduce((total, obj) => total + obj.num, 0)
					if(xzsum<gdsum){
						return uni.$u.toast('请选择可选搭配')
					}
				}
				let specInfo = Object.assign({}, this.xzSpecInfo),
					jlInfo = {
						jlmoney: this.xzJlInfo.money,
						material: this.xzJlInfo.arr.map(v => ({
							id: v.id,
							name: v.name,
							num: v.num || 1
						})),
						jldata: '',
						jlids: '',
					},
					sxInfo = {
						attribute: '',
					},
					tcInfo = {
						setMealData: this.xzTcInfo.arr.map(v => ({
							id: v.id,
							name: v.name,
							num: v.num || 1,
							price: v.addPrice,
							logo: v.logo,
							specMd5: v.specMd5,
							attrData: v.g ? (v.g.specMd5 ? v.g.attrData : (v.g.specSwitch || v.g.attrSwitch || v.g
								.materialSwitch) ? {
								spec: v.g.specSwitch && v.g.ggdata ? v.g.ggdata : '',
								attr: v.g.attribute,
								matal: v.g.jldata,
								material: v.g.material,
							} : {}) : {},
						})),
						tcdata: '',
					}
				// console.log(1, this.xzJlInfo, this.xzSxInfo, this.xzSpecInfo)
				sxInfo.attribute = this.xzSxInfo.arr.map(v => v.name).toString()
				jlInfo.jldata = this.xzJlInfo.arr.map(v => `${v.name}${v.num>1?'*'+v.num:''}`).toString()
				jlInfo.jlids = this.xzJlInfo.arr.map(v => v.id).toString()
				tcInfo.tcdata = this.xzTcInfo.arr.map(v => `${v.name}${v.num>1?'*'+v.num:''}`).toString()
				if (this.havebxjl) {
					let arr = this.ginfo.materialData.filter(v => v.required == 1),
						num = 0,
						carr = jlInfo.material.map(v => v.id)
					for (let i = 0; i < arr.length; i++) {
						if (arr[i].materialList.find(item => carr.includes(item.id))) {
							num += 1
						}
					}
					if (num < arr.length) {
						return uni.$u.toast('请选择必选加料')
					}
				}
				let goods = Object.assign({
					ggnum: this.ginfo.ggnum
				}, this.ginfo, specInfo, sxInfo, jlInfo ,tcInfo)
				this.$emit('add-to-cart', {
					g: goods,
					addwz: this.cnum > 1 ? this.cnum : 1,
				})
				this.cnum = 1
				this.show = false
			},
			async tchanddl(pi,pv,ci,cv) {
				console.log(cv)
				this.sPi = pi
				this.sCi = ci,
				this.sPv = pv
				let r = this.ginfo.setmealData && this.ginfo.setmealData.match && pv.type == 1 ? this.ginfo.setmealData.fix : this.ginfo.setmealData.match
				for (let n in r[pi].goods) {
					const sum = r[pi].goods.reduce((total, obj) => total + obj.num, 1)
					if (n == ci) {
						if(sum>r[pi].select && pv.type == 2){
							return uni.showToast({
								title: '不能再加了！',
								icon: 'none',
								duration: 1500
							})
						}
					}
				}
				let res = await this.beg.request({
					'url': `${this.api.storeGoods}/${cv.spuId}`,
					data: {
						storeId: this.storeId,
						diningType: this.diningType
					}
				})
				this.tcproduct = res.data
				this.productModalVisible = true
				this.$refs['productModal'].open(res.data, this.storeId, this.diningType)
			},
			handleAddToCartInModal(product) {
				let pi = this.sPi,
				ci = this.sCi,
				pv = this.sPv,
				r = this.ginfo.setmealData && this.ginfo.setmealData.match && pv.type == 1 ? this.ginfo.setmealData.fix : this.ginfo.setmealData.match
				for (let n in r[pi].goods) {
					if (n == ci) {
						this.$set(r[pi].goods[n], 'g', product.g)
						if(pv.type == 2){
							this.$set(r[pi].goods[n], 'num',  1)
							this.$set(r[pi].goods[n], 'a', 1)
						}
					}
				}
				this.comPrice()
				this.closeProductDetailModal()
			},
			closeProductDetailModal() {
				this.productModalVisible = false
				this.$refs['productModal'].close()
				setTimeout(() => {
					this.tcproduct = {}
				}, 100)
			},
			tchandcar(v, pi, ci, cv) {
				let r = this.ginfo.setmealData && this.ginfo.setmealData.match
				for (let n in r[pi].goods) {
					const sum = r[pi].goods.reduce((total, obj) => total + obj.num, v.addwz)
					if (n == ci) {
						if(sum>r[pi].select && v.addwz>=1){
							return uni.showToast({
								title: '不能再加了！',
								icon: 'none',
								duration: 1500
							})
						}
						this.$set(r[pi].goods[n], 'num', r[pi].goods[n].num = parseInt(r[pi].goods[n].num) + (v.addwz))
						this.$set(r[pi].goods[n], 'a', sum < 1 ? 0 : 1)
					}
				}
				this.comPrice()
			},
			stcGoods(pi, pv, ci, cv) {
				// this.tccurr = ci
				// if (cv.a) return
				let r = this.ginfo.setmealData && this.ginfo.setmealData.match
				for (let n in r[pi].goods) {
					const sum = r[pi].goods.reduce((total, obj) => total + obj.num, 1)
					if (n == ci) {
						if(sum>r[pi].select){
							if(!r[pi].goods[n].num){
								return uni.showToast({
									title: '不能再加了！',
									icon: 'none',
									duration: 1500
								})
							}else{
								this.$set(r[pi].goods[n], 'a', 0)
								this.$set(r[pi].goods[n], 'num', 0)
								 return
							}
						}
						if(cv.isSpec){
							this.tchanddl(pi, pv, ci, cv)
						}else{
							this.$set(r[pi].goods[n], 'num',  r[pi].goods[n].num == 1 ? 0 : 1)
							this.$set(r[pi].goods[n], 'a', r[pi].goods[n].a == 1 ? 0 : 1)
						}
					}
				}
				this.comPrice()
			},
			async comPrice() {
				let res = await this.beg.request({
					url: this.api.sPrice,
					method: "POST",
					data: {
						// storeId: this.sjxx.id,
						specMd5: this.ginfo.specSwitch ? this.xzSpecInfo.specInfo && this.xzSpecInfo.specInfo
							.specMd5 : this.ginfo.singleSpec && this.ginfo.singleSpec.specMd5,
						attrData: this.ginfo.specSwitch || this.ginfo.attrSwitch || this.ginfo.materialSwitch ?
						{
							spec: this.ginfo.specSwitch && this.xzSpecInfo.ggdata ? this.xzSpecInfo
								.ggdata : '',
							attr: this.xzSxInfo.arr.map(v => v.name).toString(),
							matal: this.xzJlInfo.arr.map(v => `${v.name}${v.num>1?'*'+v.num:''}`)
							.toString(),
							material: this.xzJlInfo.arr.map(v => ({
								id: v.id,
								name: v.name,
								num: v.num || 1
							})),
						} : {},
						setMealData:this.ginfo.type ==2 && this.xzTcInfo && this.xzTcInfo.arr.map(v => ({
							id: v.id,
							name: v.name,
							num: v.num || 1,
							price: v.addPrice,
							logo: v.logo,
							specMd5: v.specMd5,
							attrData: v.g ? (v.g.specMd5 ? v.g.attrData : (v.g.specSwitch || v.g.attrSwitch || v.g
								.materialSwitch) ? {
								spec: v.g.specSwitch && v.g.ggdata ? v.g.ggdata : '',
								attr: v.g.attribute,
								matal: v.g.jldata,
								material: v.g.material,
							} : {}) : {},
						})),
						spuId: this.ginfo.id,
						num: this.cnum,
					}
				})
				this.jsPrice = res ? res.data  && res.data.money : ''
				this.jslinePrice =  res ? res.data  && res.data.sellMoney : ''
				this.jsDiscountLabel = res ? res.data && res.data.discountLabel : ''
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
		top: 7.1614vh;
		left: 36.6032vw;
		width: 28.5505vw;
		// height: calc(100vh - 8.4635vh);
		border-radius: 10px;
		box-shadow: 5px 0px 10px 0px #ccc;
	}
	
	.tcgoods {
		display: -webkit-box;
		overflow: hidden;
		overflow-x: auto;
		white-space: nowrap;
	
		.gswh {
			background: #f5f5f5;
			width: 160rpx;
			border: 2rpx solid #f5f5f5;
	
			.whs {
				width: 90rpx;
				height: 120rpx;
			}
	
			.tclable {
				top: 5rpx;
				left: 5rpx;
				font-size: 20rpx;
				padding: 2rpx 6rpx;
				color: '#FF3131';
				border: 2rpx solid #FF3131;
				border-radius: 6rpx;
			}
		}
	
		.gswhb {
			background: #F6FAFF;
			border: 2rpx solid #f5f5f5;
		}
	}
	
	.materials-box {
		position: relative;
		display: flex;
	
		.materials-btn {
			border-radius: 15px !important;
			font-size: 10px;
			padding:0 10px;
		}
	
		.number-badge {
			z-index: 4;
			position: absolute;
			right: -16rpx;
			top: -14rpx;
			background-color: #fff;
			border-radius: 100%;
			width: 1.1rem;
			height: 1.1rem;
			display: flex;
			align-items: center;
			justify-content: center;
	
			.number {
				font-size: 20rpx;
				flex-shrink: 0;
				color: #fff;
				width: 0.9rem;
				height: 0.9rem;
				line-height: 0.9rem;
				text-align: center;
				border-radius: 100%;
			}
		}
	}

	@media (min-width: 1500px) and (max-width: 3280px) {
		.reduce {
			position: absolute;
			top: 55px;
			left: 500px;
			width: 390px;
			// height: calc(100vh - 65px);
			border-radius: 10px;
		}
		::-webkit-scrollbar {
			display: block !important;
			width: 8px !important;
			height: 8px !important;
			background-color: #f5f5f5  !important;
		}
			
		::-webkit-scrollbar-track {
			-webkit-box-shadow: inset 0 0 6px rgb(186, 183, 183) !important;
			border-radius: 10px !important;
			background-color: #f5f5f5 !important;
		}
			
		::-webkit-scrollbar-thumb {
			border-radius: 10px !important;
			-webkit-box-shadow: inset 0 0 6px rgb(186, 183, 183) !important;
			background-color: rgb(190, 190, 190) !important;
		}
	}


	.header {
		padding: 20rpx 30rpx;
		position: absolute;
		top: 0;
		left: 0;
		right: 0;
		display: flex;
		justify-content: flex-end;
		z-index: 11;

		image {
			width: 60rpx;
			height: 60rpx;

			&:nth-child(1) {
				margin-right: 30rpx;
			}
		}
	}

	.content {
		display: flex;
		flex-direction: column;
		font-size: 24rpx;
		color: #999;
		min-height: 1vh;
		max-height: calc(100vh - 260px);
		overflow: hidden;
		overflow-y: scroll;
		border-radius: 10px;

		.imgh {
			height: 400rpx;
		}

		.wrapper {
			width: 100%;
			height: 100%;
			overflow: hidden;
			padding: 30rpx 30rpx 20rpx;
		}

		.title {
			font-size: 40rpx;
			color: #343434;
			font-weight: bold;
			margin-bottom: 10rpx;
		}

		.labels {
			display: flex;
			font-size: 20rpx;
			margin-bottom: 10rpx;
			overflow: hidden;
			flex-wrap: wrap;

			.label {
				max-width: 40%;
				padding: 6rpx 10rpx;
				margin-right: 10rpx;
				overflow: hidden;
				text-overflow: ellipsis;
				white-space: nowrap;
			}
		}

		.materials {
			width: 80%;
			margin-bottom: 20rpx;

			.values {
				display: flex;
				flex-wrap: wrap;
				// overflow: hidden;
			}
		}
	}

	.bottom {
		padding: 20rpx 40rpx;
		display: flex;
		flex-direction: column;
		justify-content: space-between;
		border-top: 1px solid #c8c7cc;
		background-color: #fff;
		position: relative;
		border-radius: 0 0 10px 10px;
		z-index: 11;

		.jljg {
			flex: 1;
			display: flex;
			flex-direction: column;
			overflow: hidden;
			margin-right: 10rpx;

			.price {
				color: #333;
				font-size: 36rpx;
				font-weight: bold;
			}

			.materials {
				font-size: 24rpx;
				color: #999;
				display: -webkit-box;
				-webkit-box-orient: vertical;
				-webkit-line-clamp: 2;
				overflow: hidden;
			}
		}


		.buy-now-btn {
			margin-top: 20rpx;
			font-size: 36rpx;
		}

		.add-cart-btn {
			margin-top: 20rpx;
			font-size: 36rpx;
		}
	}

	.specv {
		min-width: 115rpx;
		padding: 0 20rpx;
		height: 80rpx;
		border-radius: 10rpx;
		text-align: center;
		line-height: 80rpx;
		background: #F5F5F7;
		margin: 0 20rpx 20rpx 0;

		.specsV {
			// width: 180rpx
			width: 6.5885vw;
		}

		.num {
			width: 36rpx;
			height: 36rpx;
			top: -13rpx;
			right: -11rpx;
			background: #f5f5f5;
			border: 2rpx solid #fff;
		}
	}

	.numSpec {
		width: 300px;
		height: 80rpx;
		line-height: 80rpx;
		margin-right: 20rpx;
		overflow: visible;
	}

	.num-box {
		width: 300rpx;
		height: 32px;
		border-radius: 60rpx;
		border: 2rpx solid #ccc;

		.icon-jianhao,
		.icon-jiahao1 {
			width: 60rpx;
		}

		.number {
			border: 2rpx solid #ccc;
			border-top: none;
			border-bottom: none;
		}
	}

	.opc {
		background: #f8f8f8;
		color: #ccc;
	}

	.ysq {
		color: #ccc;
		width: 282rpx !important;
	}

	.desc {
		word-break: break-all;
		display: -webkit-box;
		-webkit-box-orient: vertical;
		-webkit-line-clamp: 2;
		overflow: hidden;
	}

	.f26 {
		font-size: 36rpx;
	}

	.f28 {
		font-size: 34rpx;
	}

	.f20 {
		font-size: 20rpx;
	}

	.f22 {
		font-size: 28rpx;
	}

	.p-a {
		position: absolute;
	}

	.p-r {
		position: relative;
	}

	.t-c {
		text-align: center;
	}
	.h-100{
		height: 100%;
	}

	.minus,
	.plus {
		width: 60rpx;
		height: 60rpx;
		border-width: 1px;
		border-color: #E6E6E6;
		@include flex;
		justify-content: center;
		align-items: center;
		padding: 20rpx;
	}

	.minusy,
	.plusy {
		width: 60rpx;
		height: 60rpx;
		margin-top: 6rpx;
		border-radius: 50%;
		border-color: #333;
		@include flex;
		justify-content: center;
		align-items: center;
		padding: 20rpx;
	}

</style>
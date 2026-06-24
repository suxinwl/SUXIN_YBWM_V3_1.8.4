<template>
	<view class="f-y-bt w100v o-h">
		<tTop :form="form" @getTableInfo="getTableInfo" @search="search"></tTop>
		<view class="f-1 bf f-bt">
			<view class="left br1 f-y-bt f18">
				<view class="user p15 bd1">
					<vipUser mode="tableOrder" @rfuser='checkOut'></vipUser>
				</view>
				<leftGoods mode='tableOrder' :ad="addGoods" :carList="carList" :batch="batch" :actgood="actgood"
					:checkInfo="checkInfo" :params="params" @hItem="handItem" @dItem="handDel" @chooseGood="chooseGood"
					@clearAll="clearAll"></leftGoods>
				<view class="f-c l-h1 p10">
					<u-button
						:customStyle="{color:'#000',width:`${pc?'175px':'12.8111vw'}`,height:`${pc?'50px':'7.1614vh'}`,marginRight:`${pc?'15px':'1.0980vw'}`}"
						@click="settleAcc" :disabled="!list.length">
						<text class="f20 wei6">下单并结账</text>
					</u-button>
					<u-button color="#4275F4" @click="takeOrder"
						:customStyle="{color:'#fff',width:`${pc?'175px':'12.8111vw'}`,height:`${pc?'50px':'7.1614vh'}`}"
						:disabled="!list.length">
						<text class="f20 wei6">下单</text>
					</u-button>
				</view>
			</view>
			<leftCz mode="tableOrder" :selectItem="selectItem" :carList="carList" :list="list" @hItem="handItem"
				@handItemDel="handItemDel" @cDis="cancelDis" @handRemark="handRemark" @handBatch="handBatch"
				@handAllDesc="handAllDesc" @handRescind="handRescind" @gDis="handDis" @gGift="handGift"
				@turntable="turntable" @handPack="handPack"></leftCz>
			<rightGoods ref="rightGoodRef" :queryForm="queryForm" :total="total" :list="list" :dataList="dataList" :classfiy="classfiy"
				@handcar="handcar" @change="change" @changeKind="changeKind" @addCar="addCar">
			</rightGoods>
		</view>
		<wholenote ref="wholenoteRef" @returnRemark="returnRemark" @itemRemark="itemRemark" />
		<goodsReduce ref="reduceRef" :v="form" :selectItem="selectItem" @cMonry="changeMonry" />
		<giftDish ref="giftRef" :v="form" :selectItem="selectItem" @cGift="changeNumber" />
		<u-modal :show="rescind" :showCancelButton="true" width="300px" title=" " content="请确认是否撤销此桌台吗？"
			confirmColor="#fff" @cancel="rescind=false" @confirm="pullpack"></u-modal>
		<u-modal :show="showDel" title="确定清空购物车吗？" width="300px" :showCancelButton="true" confirmColor="#fff"
			cancelText="取消" @cancel="showDel=false" @close="showDel=false" @confirm="delCar" ref="uModal"></u-modal>
		<!-- <wholenote :allDesc="allDesc" @closeDesc="allDesc=false" @returnRemark="returnRemark" /> -->
		<u-modal :show="recharge" width="300px" title=" " content="请先开通后再使用" confirmColor="#fff"
			@confirm="recharge=false"></u-modal>
		<serviceCharge :service="service" @closeService="service=false" />
		<!-- <share :share="share" @closeShare="share=false" /> -->
		<!-- <addDish :addDish="addDish" @closeAdd="addDish=false" /> -->
	</view>
</template>

<script>
	import {
		mapState,
		mapMutations,
	} from 'vuex'
	import serviceCharge from './components/serviceCharge.vue';
	import goodsReduce from '@/components/goods/goodsReduce.vue';
	import giftDish from '@/components/goods/giftDish.vue';
	import wholenote from '@/components/other/wholenote.vue';
	import tTop from './components/tTop.vue'
	import vipUser from '@/components/user/vipUser.vue';
	import leftGoods from '@/components/order/leftGoods.vue'
	import leftCz from '@/components/order/leftcz.vue';
	import rightGoods from './components/rightGoods.vue';
	import {
		throttle
	} from '@/common/handutil.js'
	export default {
		components: {
			serviceCharge,
			goodsReduce,
			giftDish,
			wholenote,
			tTop,
			vipUser,
			leftGoods,
			leftCz,
			rightGoods,
		},
		data() {
			return {
				batch: false, //批量操作
				recharge: false, //团购券
				service: false, //服务费
				share: false, //拼桌
				rescind: false, //撤台
				kind: 0,
				actgood: 0,
				id: 0,
				selectItem: {},
				queryForm: {
					diningType: 6,
					pageNo: 1,
					pageSize: 20,
					categoryId: null,
					state: null,
					keyword: '',
				},
				classfiy: [],
				form: {},
				dataList: [],
				loading: '',
				total: 0,
				carList: {},
				list: [],
				params: {
					notes: '',
					packaging: 0,
					userId: 0,
				},
				checkInfo: {},
				cOderList: [],
				showDel: false,
				addGoods: '',
			}
		},
		computed: {
			...mapState({
				vipInfo: state => state.vipInfo,
			}),
		},
		async onLoad(option) {
			if (option) {
				this.id = option.id
				await this.getTableInfo()
				if (option.addGoods) {
					this.addGoods = option.addGoods
				}
				this.init()
			}
		},
		methods: {
			...mapMutations(["setVip"]),
			...mapMutations(["setConfig"]),
			init() {
				this.getCategory()
				// this.getCar()
				this.checkOut()
				this.setVip({})
				this.cashieSetting()
				this.getReasonConfig()
			},
			async getTableInfo() {
				let {
					data
				} = await this.beg.request({
					url: `${this.api.inTabel}/${this.id}`,
				})
				this.form = data ? data : {},
				this.checkOut()
			},
			async getCategory() {
				this.loading = true
				let {
					data: {
						list,
						total
					},
				} = await this.beg.request({
					url: this.api.inGoodsCategory,
					data: {
						pageNo: 1,
						pageSize: 999,
						state: this.queryForm.state
					},
				})
				this.classfiy = list ? list : []
				this.classfiy.unshift({
					name: '全部',
					id: '',
				})
				await this.fetchData()
				this.loading = false
			},
			async fetchData() {
				let {
					data: {
						list,
						pageNo,
						pageSize,
						total
					},
				} = await this.beg.request({
					url: this.api.inStoreGoodsList,
					data: this.queryForm,
				})
				this.total = total
				this.dataList = list ? list : []
			},
			async cashieSetting() {
				let {
					data
				} = await this.beg.request({
					url: this.api.config,
					data: {
						ident: 'cashieSetting'
					}
				})
				if (data && data.ident) {
					this.setConfig({
						name: 'cashieSetting',
						data,
					})
				}
			},
			async getReasonConfig() {
				let {
					data
				} = await this.beg.request({
					url: this.api.config,
					data: {
						ident: 'reasonConfig'
					}
				})
				this.setConfig({
					name: 'reasonConfig',
					data,
				})
			},
			// async getCar() {
			// 	let {
			// 		data
			// 	} = await this.beg.request({
			// 		url: this.api.cart,
			// 		data: {
			// 			diningType: this.form.diningType,
			// 			storeId: this.form.storeId,
			// 			tableId: this.form.id,
			// 		}
			// 	})
			// 	this.carList.generalGoods = data.goodsList ? data.goodsList : []
			// 	if (data.goodsList && data.goodsList.length || this.addGoods==1) {
			// 		this.checkOut()
			// 	}
			// },
			search(n) {
				this.queryForm.pageNo = 1
				this.queryForm.keyword = n
				this.fetchData()
			},
			changeKind(v, i) {
				this.queryForm.pageNo = 1
				this.queryForm.categoryId = v.id
				this.fetchData()
			},
			change(e) {
				this.queryForm.pageNo = e.current;
				this.fetchData()
			},
			async handcar(p) {
				try {
					let {
						data,
						code,
					} = await this.beg.request({
						url: this.api.cart,
						method: 'POST',
						data: {
							spuId: p.g.spuId,
							specMd5: p.g.specSwitch ? p.g.specInfo && p.g.specInfo.specMd5 : p.g.specMd5 ||
								p.g
								.singleSpec.specMd5,
							attrData: p.g.specMd5 ? p.g.attrData || {} : (p.g.specSwitch || p.g.attrSwitch || p.g
								.materialSwitch) ? {
								spec: p.g.specSwitch && p.g.ggdata ? p.g.ggdata : '',
								attr: p.g.attribute,
								matal: p.g.jldata,
								material: p.g.material,
							} : {},
							setMealData:p.g.type==2 && p.g.setMealData ? p.g.setMealData : [],
							num: p.addwz,
							storeId: this.form.storeId,
							tableId: this.form.id,
							diningType: this.form.diningType,
							userId: this.vipInfo && this.vipInfo .id || this.params.userId,
							isTemp: p.g.isTemp || 0,
							tempIndex: p.g.tempIndex || 0,
						}
					})
					// if (data && data.cart) {
					// 	this.checkOut()
					// }
					// this.selectItem.num = p.addwz>0 ? this.selectItem.num + 1 : this.selectItem.num - 1
					if(code && code==200){
						this.carList = data ? data : {}
						let sList = data.goodsList && data.goodsList.length && data.goodsList
						if (sList && sList.length) {
							if (this.addGoods == 1) {
								this.list = [...data.prentGoods, ...data.goodsList]
							} else {
								this.selectItem = this.selectItem.id && this.selectItem.num >= 1 ? sList.find(v => v.id ==
									this.selectItem.id) : sList[0]
								this.actgood = this.selectItem.id && this.selectItem.num >= 1 ? this.selectItem.id : sList[
									0].id
								this.list = data.goodsList
							}
						} else {
							this.actgood = 0
							this.selectItem = {}
							this.list = []
						}
					}
				} catch (e) {
					console.log(e)
				}
			},
			async settleAcc() {
				await this.checkOut()
				let {
					data
				} = await this.beg.request({
					url: this.api.inOrder,
					method: 'POST',
					data: {
						diningType: this.form.diningType,
						tableId: this.form.id,
					}
				})
				this.orderInfo = data
				if (data && (data.prentOrderSn || data.orderSn)) {
					uni.redirectTo({
						url: `/pages/table/orderPay?id=${data.prentOrderSn || data.orderSn}`
					})
				}
			},
			takeOrder: throttle(async function(e) {
				await this.checkOut()
				let {
					data
				} = await this.beg.request({
					url: this.api.inOrder,
					method: 'POST',
					data: {
						diningType: this.form.diningType,
						tableId: this.form.id,
					}
				})
				this.orderInfo = data
				if (data && (data.prentOrderSn || data.orderSn)) {
					uni.reLaunch({
						url: `/pages/home/index?current=1`
					})
				}
			}, 500),
			async checkOut() {
				if (this.vipInfo && this.vipInfo.id){
					 this.params.userId = this.vipInfo.id
				}else{
					this.params.userId = 0
				}
				let {
					data
				} = await this.beg.request({
					url: this.api.checkout,
					data: {
						diningType: this.form.diningType,
						storeId: this.form.storeId,
						tableId: this.form.id,
						packaging: this.params.packaging,
						userId: this.params.userId,
						notes: this.params.notes,
						check: 'false',
					}
				})
				this.carList = data ? data : {}
				let sList = data.goodsList && data.goodsList.length && data.goodsList
				if (sList && sList.length) {
					if (this.addGoods == 1) {
						this.list = [...data.prentGoods, ...data.goodsList]
					} else {
						this.selectItem = this.selectItem.id && this.selectItem.num >= 1 ? sList.find(v => v.id == this
							.selectItem.id) : sList[0]
						this.actgood = this.selectItem.id && this.selectItem.num >= 1 ? this.selectItem.id : sList[0]
							.id
						this.list = data.goodsList
					}
				} else {
					this.actgood = 0
					this.selectItem = {}
					this.list = []
				}
				if(data.prentOrder && data.prentOrder.notes){
					this.params.notes = data.prentOrder.notes
				} 
			},
			clearAll() {
				if (this.carList.goodsList.length == 0) {
					uni.showToast({
						title: '购物车无数据！',
						icon: 'none',
						duration: 800
					});
				} else {
					this.showDel = true
				}
			},
			async delCar() {
				let {
					msg
				} = await this.beg.request({
					url: this.api.clearCart,
					method: 'DELETE',
					data: {
						tableId: this.form.id,
						storeId: this.form.storeId,
						diningType: this.form.diningType,
					}
				})
				uni.showToast({
					title: msg,
					icon: 'none'
				});
				this.checkOut()
				this.showDel = false
			},
			chooseGood(item, index) {
				console.log(11, item)
				this.actgood = item.id
				this.selectItem = item
			},
			async handItem(p) {
				if (p.g.num < 1) {
					this.selectItem = {}
				}
				if (p.g.discountType && p.g.discountType<=3) {
					let {
						data,
						code
					} = await this.beg.request({
						url: this.api.give,
						method: 'POST',
						data: {
							goods: [{
								id: p.g.id,
								num: p.addwz
							}],
							type: 'give',
							storeId: this.form.storeId,
							tableId: this.form.id,
							diningType: this.form.diningType,
						}
					})
					if (code && code == 200) {
						this.selectItem.num = p.addwz > 0 ? this.selectItem.num + 1 : this.selectItem.num - 1
						this.checkOut()
					}
				} else {
					// this.selectItem = {}
					this.handcar(p)
				}
			},
			handDel(p) {
				this.selectItem = {}
				this.handcar(p)
			},
			async handItemDel(p) {
				let {
					msg,
					code,
					data
				} = await this.beg.request({
					url: `${this.api.cart}/${p.g.id}`,
					method: 'DELETE'
				})
				uni.$u.toast(msg)
				if (code && code == 200) {
					this.selectItem = {}
					this.checkOut()
				}
			},
			async cancelDis(p) {
				let {
					data
				} = await this.beg.request({
					url: this.api.give,
					method: 'POST',
					data: {
						goods: [{
							id: p.g.id,
							num: p.addwz
						}],
						type: 'back',
						storeId: this.form.storeId,
						tableId: this.form.id,
						diningType: this.form.diningType,
					}
				})
				this.checkOut()
			},
			handRemark(t) {
				this.$refs['wholenoteRef'].open(t)
			},
			handAllDesc(t) {
				this.$refs['wholenoteRef'].open(t)
			},
			returnRemark(e, t) {
				if (t == 1) {
					this.params.notes = e.join('，')
					this.allRemark()
				} else {
					this.params.notes = e
				}
				this.$refs['wholenoteRef'].close()
			},
			async allRemark(e) {
				let {
					data
				} = await this.beg.request({
					url: this.api.goodsAllRemark,
					method: 'POST',
					data: {
						notes: this.params.notes,
						tableId: this.form.id,
						storeId: this.form.storeId,
						diningType: this.form.diningType,
					},
				})
				this.checkOut()
			},
			async itemRemark(e, t) {
				let ids = [],
					notes = t == 1 ? e.join('，') : e
				ids.push(this.selectItem && this.selectItem.id)
				let {
					data
				} = await this.beg.request({
					url: this.api.goodsNotes,
					method: 'POST',
					data: {
						ids,
						notes,
					}
				})
				this.checkOut()
				this.$refs['wholenoteRef'].close()
			},
			handRescind() {
				this.rescind = true
			},
			handDis() {
				this.$refs['reduceRef'].open()
			},
			handGift() {
				this.$refs['giftRef'].open()
			},
			async changeMonry(e) {
				let {
					data,
					msg,
					code
				} = await this.beg.request({
					url: this.api.give,
					method: 'POST',
					data: e
				})
				uni.showToast({
					title: msg,
					icon: 'none'
				})
				if (code && code == 200 && this.selectItem.num == e.goods[0].num) {
					this.selectItem = {}
					this.checkOut()
					this.$refs['reduceRef'].close()
				} else {
					this.$refs['reduceRef'].close()
					this.checkOut()
				}
			},
			async changeNumber(e) {
				let {
					data,
					msg,
					code
				} = await this.beg.request({
					url: this.api.give,
					method: 'POST',
					data: e
				})
				uni.showToast({
					title: msg,
					icon: 'none'
				})
				if (code && code == 200 && this.selectItem.num == e.goods[0].num) {
					this.selectItem = {}
					this.checkOut()
					this.$refs['giftRef'].close()
				} else {
					this.$refs['giftRef'].close()
					this.checkOut()
				}
			},
			async pullpack() {
				let {
					msg,
					code
				} = await this.beg.request({
					url: `${this.api.backTb}/${this.form.id}`,
					method: 'POST'
				})
				uni.showToast({
					title: msg,
					icon: 'none',
					duration: 2000
				})
				if (code && code == 200) {
					this.rescind = false
					setTimeout(() => {
						uni.reLaunch({
							url: '/pages/home/index?current=1'
						})
					}, 800)
				}
			},
			turntable() {
				uni.navigateTo({
					url: `/pages/table/table?id=${this.id}&t=turntable`
				})
			},
			async handPack() {
				let ids = []
				ids.push(this.selectItem && this.selectItem.id)
				let {
					data
				} = await this.beg.request({
					url: this.api.goodsPack,
					method: 'POST',
					data: {
						ids,
						type: this.selectItem && this.selectItem.pack ? 'back' : '',
					}
				})
				this.checkOut()
			},
			combine() {
				uni.navigateTo({
					url: `/pages/table/table?id=${this.id}&t=parallel`
				})
			},
			async addCar(v){
				let {
					data
				} = await this.beg.request({
					url: this.api.ctemp,
					method: 'POST',
					data: {
						tableId: this.form.id,
						storeId: this.form.storeId,
						diningType: this.form.diningType,
						isTemp: 1,
						tempIndex: 0,
						num: v.num,
						name: v.name,
						price: v.price,
						notes: v.notes,
					},
				})
				this.$refs['rightGoodRef'].closeAdd()
				this.checkOut()
			}
		}
	}
</script>

<style lang="scss" scoped>
	/deep/.u-modal__button-group__wrapper--confirm {
		background: #4275F4;
	}

	/deep/.u-modal__content__text {
		font-size: 16px !important;
		color: #000 !important;
	}

	.left {
		width: 29.2825vw;
	}

	@media (min-width: 1500px) and (max-width: 3280px) {
		.left {
			width: 400px;
		}
	}
</style>
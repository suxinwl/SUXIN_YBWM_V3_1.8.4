<template>
	<view class="f-y-bt h100 billing_media">
		<view class="f-1 f-bt">
			<view class="left br1 f-y-bt f20 bf left_media">
				<view class="user p15 bd1">
					<vipUser @clearAll="clearAll" mode="fastOrder" @rfuser="checkOut"></vipUser>
				</view>
				<leftGoods :carList="carList" :batch="batch" :actgood="actgood" :checkInfo="checkInfo" :params="params"
					@hItem="handItem" @dItem="handDel" @chooseGood="chooseGood" @clearAll="clearAll" @allPack="allPack">
				</leftGoods>
				<view v-if="!isOrder" class="p15 f-c isOrder_media">
					<!-- <u-button class="mr5" type="error" :customStyle="{width:`${pc?'100px':'7.3206vw'}`,height:`${pc?'60px':'7.8125vh'}`}"
						:disabled="list.length==0" @click="payType(1)">
						<view class="f-c-c l-h1">
							<view class="iconfont icon-licai mb5"></view>
							<view class="f14">现金支付</view>
						</view>
					</u-button> -->
					<u-button color="#3c9cff" class="mr5" type="primary" :customStyle="{width:`${pc?'100px':'7.3206vw'}`,height:`${pc?'60px':'7.8125vh'}`}"
						:disabled="list.length==0" @click="payType(2)">
						<view class="f-c-c l-h1">
							<view class="iconfont icon-saoma mb5"></view>
							<view class="f14">扫码支付</view>
						</view>
					</u-button>
					<!-- <u-button class="mr5" :customStyle="{color:'#000',width:`${pc?'100px':'7.3206vw'}`,height:`${pc?'60px':'7.8125vh'}`}"
						:disabled="list.length==0" @click="takeOrder">
						<view class="f-c-c l-h1">

							<view class="f14">更多支付</view>
						</view>
					</u-button> -->
					<u-button color="#4275F4" @click="takeOrder" :disabled="list.length==0"
						:customStyle="{color:'#fff',width:`${pc?'250px':'18.3016vw'}`,height:`${pc?'60px':'7.8125vh'}`}">
							<view class="f18 f-bt f-1 f-y-c l-h1">
								<view class="">结帐{{carList.goodsNum && carList.goodsNum}}件</view>
								<view class="tar f22 f-c-xc" v-if="carList.money">
									<view class="l-h1">￥{{carList.money}}</view>
									<view v-if="carList.sellMoney>carList.money" class="cd f14 mt5" style="text-decoration: line-through;">
										￥{{carList.sellMoney}}
									</view>
								</view>
							</view>
						</u-button>
				</view>
				<view v-else class="p15 f-c">
					<u-button color="#4275F4" :customStyle="{color:'#000',height:`${pc?'60px':'7.8125vh'}`}" @click="rOrder">
						<text class="f20 wei6">返回点单</text></u-button>
				</view>
			</view>
			<leftCz v-if="!isOrder" :selectItem="selectItem" :carList="carList" :list="list" @hItem="handItem" @handItemDel="handItemDel" @cDis="cancelDis" @handRemark="handRemark"
				@handBatch="handBatch" @handAllDesc="handAllDesc" @handRescind="handRescind" @gDis="handDis"
				@gGift="handGift" @handPack="handPack" @handDeposit="handDeposit" @handUpOrder="handUpOrder" @handEditNum="handEditNum"></leftCz>
			<rightOrder v-if="isOrder" ref="rightOrderRef" mode="fastOrder" :pl="params" :form="payInfo" @init="init" @checkOut="rfCheckOut" @ck="checkOut" @cpOut="cpOut" @cInit="cInit"></rightOrder>
			<rightGoods v-else ref="rightGoodRef" :queryForm="queryForm" :total="total" :list="list" :dataList="dataList"
				:classfiy="classfiy" @search="search" @handcar="handcar" @change="change" @changeKind="changeKind" @addCar="addCar">
			</rightGoods>
		</view>
		<goodsReduce ref="reduceRef" :v="form" :selectItem="selectItem" @cMonry="changeMonry" />
		<giftDish ref="giftRef" :v="form" :selectItem="selectItem" @cGift="changeNumber" />
		<takeOrder ref="takeRef" :list="depositList" @checkOut="checkOut" />
		<wholenote ref="wholenoteRef" @returnRemark="returnRemark" @itemRemark="itemRemark" />
		<u-toast ref="uToast"></u-toast>
		<u-modal :show="showDel" title="确定清空购物车吗？" width="300px" :showCancelButton="true" confirmColor="#fff"
			cancelText="取消" @cancel="showDel=false" @close="showDel=false" @confirm="delCar" ref="uModal"></u-modal>
		<cash ref="cashRef" @changeMoney="changeMoney" />
		<scan ref="scanRef" @savePay="savePay" />
		<goodsNum ref="goodsNumRef" @changeValue="changeValue"></goodsNum>
	</view>
</template>

<script>
	import goodsReduce from '@/components/goods/goodsReduce.vue';
	import giftDish from '@/components/goods/giftDish.vue';
	import wholenote from '@/components/other/wholenote.vue';
	import takeOrder from './billing/takeOrder.vue';
	import vipUser from '@/components/user/vipUser.vue';
	import leftGoods from '@/components/order/leftGoods.vue'
	import leftCz from '@/components/order/leftcz.vue';
	import rightOrder from '@/components/pay/payInfo.vue';
	import rightGoods from './billing/rightGoods.vue';
	import cash from '@/components/pay/cash.vue';
	import scan from '@/components/pay/scan.vue';
	import goodsNum from '@/components/goods/goodsNum.vue';
	import {
		mapState,
		mapMutations,
	} from 'vuex'
	import {
		playAudo,
	} from "@/common/handutil.js"
	export default ({
		components: {
			goodsReduce,
			giftDish,
			wholenote,
			takeOrder,
			vipUser,
			leftGoods,
			leftCz,
			rightOrder,
			rightGoods,
			cash,
			scan,
			goodsNum,
		},
		data() {
			return {
				isOrder: false,
				batch: false, //批量操作
				actgood: 0,
				depositList: [], 
				selectItem: {},
				queryForm: {
					diningType: 6,
					pageNo: 1,
					pageSize: 30,
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
					// packaging: 0,
					userId: 0,
					couponId: 0,
				},
				checkInfo: {},
				cOderList: [],
				payInfo: {},
				showDel: false,
				pay: {
					name: '',
					money: '',
					payType: 0,
					authCode: 0,
					payUserId: 0,
				},
			}
		},
		computed: {
			...mapState({
				storeId: state => state.storeId,
				vipInfo: state => state.vipInfo,
				handOver: state => state.handOver,
			}),
		},
		// watch: {
		// 	selectItem(val) {
		// 		this.selectItem = val
		// 	}
		// },
		methods: {
			...mapMutations(["setVip"]),
			...mapMutations(["setConfig"]),
			init() {
				this.form.diningType = 6
				this.form.storeId = this.storeId
				this.form.id = 0
				this.cashieSetting()
				this.getCategory()
				// this.getCar()
				this.checkOut()
				this.isOrder = false
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
				// if (list && list.length) {
				// 	this.queryForm.catId = list[0].id
				// }
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
			// 	if (data.goodsList && data.goodsList.length) {
			// 		this.checkOut()
			// 	}else{
			// 		this.actgood = 0
			// 		this.selectItem = {}
			// 		this.list = []
			// 		this.carList = {}
			// 	}
			// 	// this.carList = data ? data : {},
			// 	// this.list = data ? data.goodsList : []
			// },
			search(n) {
				this.queryForm.keyword = n
				this.fetchData()
			},
			//切换种类
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
								p.g.singleSpec.specMd5,
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
					if(code && code==200){
						// this.selectItem.num = p.addwz>0 ? this.selectItem.num + 1 : this.selectItem.num = p.addwz
						this.carList = data ? data : {}
						let sList = data.goodsList && data.goodsList.length && data.goodsList
						
						if (sList && sList.length) {
							this.selectItem = this.selectItem.id && this.selectItem.num >= 1 ? sList.find(v => v.id ==
								this.selectItem.id) : sList[0]
							this.actgood = this.selectItem.id && this.selectItem.num >= 1 ? this.selectItem.id : sList[
								0].id
							// this.selectItem = sList[0]
							// this.actgood = sList[0].id
							this.list = data.goodsList
						}else{
							this.actgood = 0
							this.selectItem = {}
							this.list = []
						}
					}
				} catch (e) {
					console.log(e)
				}
			},
			//下单
			takeOrder() {
				if(!this.handOver.id){
					return this.$emit('openOver')
				}
				if (this.list && this.list.length > 0) {
					this.isOrder = true
					this.checkOut()
					this.$nextTick(() => this.$refs['rightOrderRef'].getWays())
				} else {
					uni.showToast({
						title: `请先选择商品`,
						icon: 'none'
					})
				}
			},
			async rOrder(){
				let {
					data
				} = await this.beg.request({
					url: this.api.cancelDiscount,
					method: 'POST',
					data: {
						diningType: this.form.diningType,
						storeId: this.form.storeId,
						tableId: this.form.id,
						type:'all',
					}
				})
				this.rfCheckOut(data)
				this.isOrder = false
			},
			rfCheckOut(data){
				this.payInfo = {
					...this.form,
					...data
				}
				this.carList = data ? data : {}
				let sList = data.goodsList && data.goodsList.length && data.goodsList
				if (sList && sList.length) {
					this.selectItem = this.selectItem.id && this.selectItem.num >= 1 ? sList.find(v => v.id == this
						.selectItem.id) : sList[0]
					this.actgood = this.selectItem.id && this.selectItem.num >= 1 ? this.selectItem.id : sList[0].id
					this.list = data.goodsList
				} else {
					this.actgood = 0
					this.selectItem = {}
					this.list = []
				}
			},
			async checkOut(p) {
				let {
					data
				} = await this.beg.request({
					url: this.api.checkout,
					data: {
						diningType: this.form.diningType,
						storeId: this.form.storeId,
						tableId: this.form.id,
						// packaging: this.params.packaging,
						userId: this.vipInfo && this.vipInfo .id || this.params.userId,
						// couponId: this.params.couponId,
						notes: this.params.notes,
						check: 'false',
					}
				})
				// this.checkInfo = data ? data : {},
				this.payInfo = {
					...this.form,
					...data
				}
				// this.selectItem.num = p.addwz>0 ? this.selectItem.num + 1 : this.selectItem.num = p.addwz
				this.carList = data ? data : {}
				this.carList.pickAll = data && data.packaging ? [1] : []
				let sList = data.goodsList && data.goodsList.length && data.goodsList
				if (sList && sList.length) {
					this.selectItem = this.selectItem.id && this.selectItem.num >= 1 ? sList.find(v => v.id == this
						.selectItem.id) : sList[0]
					this.actgood = this.selectItem.id && this.selectItem.num >= 1 ? this.selectItem.id : sList[0].id
					this.list = data.goodsList
				} else {
					this.actgood = 0
					this.selectItem = {}
					this.list = []
				}
				console.log("获取到优惠相关信息",this.payInfo)
			},
			async cpOut(e) {
				this.params.couponId = e
				let {
					data
				} = await this.beg.request({
					url: this.api.cCoupon,
					method: 'POST',
					data: {
						diningType: this.form.diningType,
						storeId: this.form.storeId,
						tableId: this.form.id,
						couponId: e || this.params.couponId,
						userId: this.vipInfo && this.vipInfo .id || this.params.userId,
						notes: this.params.notes,
					}
				})
				this.payInfo = {
					...this.form,
					...data
				}
				this.carList = data ? data : {}
				this.carList.pickAll = data && data.packaging ? [1] : []
				let sList = data.goodsList && data.goodsList.length && data.goodsList
				if (sList && sList.length) {
					this.selectItem = this.selectItem.id && this.selectItem.num >= 1 ? sList.find(v => v.id == this
						.selectItem.id) : sList[0]
					this.actgood = this.selectItem.id && this.selectItem.num >= 1 ? this.selectItem.id : sList[0].id
					this.list = data.goodsList
				} else {
					this.actgood = 0
					this.selectItem = {}
					this.list = []
				}
				this.$nextTick(() => this.$refs['rightOrderRef'].resetPay(this.payInfo))
			},
			handBatch(e) {
				this.batch = e
			},
			//整单打包
			async allPack(e) {
				let {
					data
				} = await this.beg.request({
					url: this.api.goodsPackAll,
					method: 'POST',
					data: {
						type: !e.includes(1) ? 'back' : '',
						tableId: this.form.id,
						storeId: this.form.storeId,
						diningType: this.form.diningType,
					},
				})
				this.checkOut()
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
			//选择商品
			chooseGood(item, index) {
				console.log(11, item)
				this.actgood = item.id
				this.selectItem = item
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
			async handItem(p) {
				if (p.g.num < 1) {
					this.selectItem = {}
				}
				if (p.g.discountType && p.g.discountType<=3) {
					let {
						data,
						code,
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
					if(code && code==200){
						this.selectItem.num = p.addwz>0 ? this.selectItem.num + 1 : this.selectItem.num - 1
						this.checkOut()
					}
				} else {
					this.handcar(p)
				}
			},
			handDel(p) {
				this.selectItem = {}
				this.handcar(p)
			},
			async handItemDel(p){
				let {
					msg,
					code,
					data
				} = await this.beg.request({
					url: `${this.api.cart}/${p.g.id}`,
					method: 'DELETE'
				})
				uni.$u.toast(msg)
				if(code && code==200){
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
				this.checkOut(p)
			},
			handRemark(t) {
				this.$refs['wholenoteRef'].open(t)
			},
			handAllDesc(t) {
				this.$refs['wholenoteRef'].open(t)
			},
			//整单备注
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
					code,
				} = await this.beg.request({
					url: this.api.give,
					method: 'POST',
					data: e
				})
				uni.showToast({
					title: msg,
					icon: 'none'
				})
				if(code && code==200 && this.selectItem.num == e.goods[0].num){
					this.selectItem = {}
					this.checkOut()
					this.$refs['reduceRef'].close()
				}else{
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
				if(code && code==200 && this.selectItem.num == e.goods[0].num){
					this.selectItem = {}
					this.checkOut()
					this.$refs['giftRef'].close()
				}else{
					this.$refs['giftRef'].close()
					this.checkOut()
				}
			},
			payType(t) {
				if(!this.handOver.id){
					return this.$emit('openOver')
				}
				if (t == 1) {
					this.pay.payType = 'cash'
					this.pay.name = '现金-人民币'
					this.$refs['cashRef'].open(this.carList.money)
				} else if (t == 2) {
					this.pay.payType = 'authCode'
					this.pay.name = '扫码支付'
					this.$refs['scanRef'].open(this.carList.money)
					playAudo('../../static/auto/fukuanma.mp3')
				}
			},
			changeMoney(e) {
				this.pay.money = e
				this.savePay()
			},
			async savePay(e) {
				if (e) {
					this.pay.authCode = e
					this.loading = true
				}
				this.pay.diningType = this.form.diningType
				this.pay.tableId = this.form.id
				let {
					msg,
					data
				} = await this.beg.request({
					url: this.api.inOrder,
					method: 'POST',
					data: this.pay
				})
				this.loading = false
				uni.$u.toast(msg)
				this.setVip({})
				this.$refs['scanRef'].close()
				if (data) {
					this.init()
					this.cInit()
				}
			},
			cInit(){
				this.params.notes = ''
			},
			async handDeposit() {
				let {
					data,
					msg,
				} = await this.beg.request({
					url: this.api.goodsFreeze,
					method: 'POST'
				})
				uni.$u.toast(msg)
				this.checkOut()
			},
			handUpOrder(){
				this.$refs['takeRef'].open()
			},
			handEditNum(){
				if(this.selectItem && this.selectItem.num) {
					this.$refs['goodsNumRef'].open(this.selectItem)
				}
			},
			async changeValue(e){
				let num = Number(e) - Number(this.selectItem.num)
				await this.handItem({g: this.selectItem,addwz: num})
				this.$refs['goodsNumRef'].close()
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
	})
</script>

<style lang="scss" scoped>
	.left {
		width: 30vw;
		border-radius: 0 6px 0 0;
		/deep/.u-button {
			span {
				color: #fff;
			}
		}
	}
	.u-popup{
		flex: 0;
	}
	.icon-licai,.icon-saoma{
		font-size: 1.6105vw !important;
	}
	@media (min-width: 1500px) and (max-width: 3280px) {
		.left {
			width: 400px;
		}
		.icon-licai,.icon-saoma{
			font-size: 22px !important;
		}
	}
	@media (min-width: 500px) and (max-width: 900px) {
		.isOrder_media { 
			/deep/.u-button--normal{
			   padding: 0 !important;
			}
		}
	}
	
	
</style>
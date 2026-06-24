<template>
	<view class="f-y-bt w100v o-h" v-if="!loading">
		<!-- <button @click="clickTest">kkk</button> -->
		<tTop type="oAfter" :form="form" @fetchData="fetchData"></tTop>
		<view class="f-1 bf f-bt">
			<view class="left br1 f-y-bt f18">
				<view class="user p15 bd1">
					<vipUser mode="tableOrder"></vipUser>
				</view>
				<leftGoods mode='tableOrder' type="oAfter" :carList="form" :batch="batch" :actgood="actgood"
					:checkInfo="form" :params="params" @hItem="handItem" @chooseGood="chooseGood"
					@clearAll="clearAll"></leftGoods>
				<view class="f-c l-h1 p10">
					<u-button color="#4275F4" :disabled="form.payType==1"
						:customStyle="{color:'#fff',width:`${pc?'150px':'10.9809vw'}`,height:`${pc?'55px':'7.1614vh'}`,marginRight:`${pc?'15px':'1.0980vw'}`}"
						@click="addMenu">
						<text class="f20 wei6">加菜</text></u-button>
					<u-button
						:customStyle="{color:'#000',width:`${pc?'175px':'12.8111vw'}`,height:`${pc?'55px':'7.1614vh'}`}"
						@click="dyyjd">
						<text class="f20 wei6">打印预结单</text></u-button>
				</view>
			</view>
			<leftCz mode='tableOrder' type="oAfter" :carList="form" :selectItem="selectItem" :list="list"
				@hItem="handItem" @cDis="cancelDis" @handRemark="handRemark" @handBatch="handBatch"
				@handAllDesc="handAllDesc" @handRescind="handRescind" @gDis="handDis" @gGift="handGift"
				@gRefund="handRefund" @turntable="turntable" @combine="combine" @backTb="handBackTb"
				@clearTb="handClearTb">
			</leftCz>
			<payInfoA ref="rightOrderRef" :pl="params" :form="payInfo" @init="init" @checkOut="fetchData" @ck="checkOut" @cpOut="cpOut"></payInfoA>
		</view>
		<goodsReduce ref="reduceRef" :v="form" :selectItem="selectItem" @cMonry="changeGive" />
		<giftDish ref="giftRef" :v="form" :selectItem="selectItem" @cGift="changeGive" />
		<refundDish ref="refundRef" :v="form" :selectItem="selectItem" @cRefund="changeGive" />
		<backTable ref="backTableRef" :v="form" @save="pullpack"></backTable>
		<u-modal :show="rescind" :showCancelButton="true" width="300px" title=" " content="请确认要整单撤销吗？"
			confirmColor="#000" @cancel="rescind=false" @confirm="pullpack"></u-modal>
		<u-modal :show="clearTbShow" :showCancelButton="true" width="300px" title=" " content="请确认要清台吗？"
			confirmColor="#fff" @cancel="clearTbShow=false" @confirm="pullClear"></u-modal>
	</view>
</template>

<script>
	import {
		mapState,
		mapMutations,
	} from 'vuex'
	import tTop from './components/tTop.vue'
	import vipUser from '@/components/user/vipUser.vue';
	import leftGoods from '@/components/order/leftGoods.vue'
	import leftCz from '@/components/order/leftcz.vue';
	import payInfoA from '@/components/pay/payInfo.vue';
	import goodsReduce from '@/components/goods/goodsReduce.vue';
	import giftDish from '@/components/goods/giftDish.vue';
	import refundDish from '@/components/goods/refundDish.vue';
	import backTable from '@/components/goods/backTable.vue';
	export default {
		components: {
			tTop,
			vipUser,
			leftGoods,
			leftCz,
			payInfoA,
			goodsReduce,
			giftDish,
			refundDish,
			backTable,
		},
		data() {
			return {
				id: '',
				classfiy: [],
				form: {},
				dataList: [],
				loading: true,
				total: 0,
				carList: {},
				list: [],
				params: {
					notes: '',
					packaging: 0,
					userId: 0,
				},
				cOderList: [],
				batch: false,
				actgood: 0,
				checkInfo: {},
				selectItem: {},
				rescind: false,
				clearTbShow: false,
				payInfo: null,
			}
		},
		async onLoad(option) {
			if (option && option.id) {
				this.id = option.id
				await this.fetchData()
				this.setVip({})
			}
			this.getReasonConfig()
			this.$nextTick(() => this.$refs['rightOrderRef'].getWays())
		},
		computed: {
			...mapState({
				storeId: state => state.storeId,
				vipInfo: state => state.vipInfo,
			}),
		},
		methods: {
			...mapMutations(["setVip"]),
			...mapMutations(["setConfig"]),
			clickTest(){
				console.log("this.form",this.payInfo)
				// this.payInfo.money = 12;
			},
			async fetchData() {
				this.loading = true;
				let {
					data
				} = await this.beg.request({
					url: `${this.api.inStoreOrder}/${this.id}`,
					data: {
						storeId: this.storeId
					},
				})
				this.form = data ? data : {}
				this.form.goodsList = data.subGoods ? data.subGoods : []
				this.payInfo = {
					...this.form,
					...data
				}
				let sList = data.subGoods && data.subGoods.length && data.subGoods
				if (sList && sList.length) {
					this.selectItem = this.selectItem.spuId && this.selectItem.num >= 1 ? sList.find(v => v.spuId ==
						this.selectItem.spuId) : sList[0]
					this.actgood = this.selectItem.spuId && this.selectItem.num >= 1 ? this.selectItem.id : sList[0].id
					this.list = sList
				} else {
					this.actgood = 0
					this.selectItem = {}
					this.list = []
				}
				this.loading = false;
				console.log("初始化了我",this.form,this.payInfo)
			},
			// 选择用户
			async checkOut(p) {
				let {
					data
				} = await this.beg.request({
					url: this.api.checkout,
					data: {
						diningType: this.form.diningType,
						storeId: this.form.storeId,
						tableId: this.form.id,
						userId: this.vipInfo && this.vipInfo.id || this.params.userId,
						notes: this.params.notes,
						check: 'false',
						orderSn: this.id,
					}
				})
				this.carList = data ? data : {}
				this.carList.pickAll = data && data.packaging ? [1] : []
				let sList = data.goodsList && data.goodsList.length && data.goodsList
				let couponList = data.couponList.true;
				if(couponList){
					this.payInfo.couponCount = couponList.length || 0;
					this.payInfo.couponList = data.couponList;
					this.$nextTick(() => this.$refs['rightOrderRef'].getWays())
				}
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
				console.log("选中用户以后",this.form)
			},
			// 优惠券金额处理
			async cpOut(e){
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
				// this.payInfo = {
				// 	...this.form,
				// 	...data
				// }    
				// this.carList = data ? data : {}
				// this.carList.pickAll = data && data.packaging ? [1] : []
				// let sList = data.goodsList && data.goodsList.length && data.goodsList
				// if (sList && sList.length) {
				// 	this.selectItem = this.selectItem.id && this.selectItem.num >= 1 ? sList.find(v => v.id == this
				// 		.selectItem.id) : sList[0]
				// 	this.actgood = this.selectItem.id && this.selectItem.num >= 1 ? this.selectItem.id : sList[0].id
				// 	this.list = data.goodsList
				// } else {
				// 	this.actgood = 0
				// 	this.selectItem = {}
				// 	this.list = []
				// }
				let CouponPrice = 0;
				let discountsPlus = data.discountsPlus;
				if(discountsPlus && discountsPlus.length > 0){
					discountsPlus.forEach((v=>{
						CouponPrice = v.money;
					}))
				}
				this.payInfo.money = Number(this.form.money)-Number(CouponPrice)
				this.payInfo.discountsPlus = data.discountsPlus;
				this.$nextTick(() => this.$refs['rightOrderRef'].resetPay(this.payInfo))
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
			addMenu() {
				uni.redirectTo({
					url: `/pages/table/index?id=${this.form.tableId}&addGoods=1`
				})
			},
			async dyyjd() {
				let {
					msg
				} = await this.beg.request({
					url: `${this.api.printOrder}/${this.form.id}`,
					method: "POST",
					data: {
						scene: 6,
						orderSn: this.form.orderSn,
						tableId: this.form.tableId
					}
				})
				this.fetchData()
				uni.$u.toast(msg)
			},
			init() {
				setTimeout(() => {
					uni.reLaunch({
						url: `/pages/home/index?current=1`
					})
				}, 800)
			},
			chooseGood(item, index) {
				this.actgood = item.id
				this.selectItem = item
			},
			handDis() {
				this.$refs['reduceRef'].open()
			},
			handGift() {
				this.$refs['giftRef'].open()
			},
			handRefund() {
				this.$refs['refundRef'].open()
			},
			async changeGive(e) {
				let {
					data,
					msg
				} = await this.beg.request({
					url: `${this.api.giveOrder}/${this.form.orderSn}`,
					method: 'POST',
					data: e
				})
				uni.showToast({
					title: msg,
					icon: 'none'
				})
				if (e.type == 'discount' || e.type == 'sub') {
					this.$refs['reduceRef'].close()
				} else if (e.type == 'give') {
					this.$refs['giftRef'].close()
				} else if (e.type == 'backFood') {
					this.$refs['refundRef'].close()
				}
				this.fetchData()
			},
			async cancelDis(p) {
				let {
					data
				} = await this.beg.request({
					url: `${this.api.giveOrder}/${this.form.orderSn}`,
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
				this.fetchData()
			},
			turntable() {
				uni.navigateTo({
					url: `/pages/table/table?id=${this.form.tableId}&t=turntable`
				})
			},
			combine() {
				uni.navigateTo({
					url: `/pages/table/table?id=${this.form.tableId}&t=parallel`
				})
			},
			handBackTb() {
				this.$refs['backTableRef'].open()
				// this.rescind = true
			},
			async pullpack(e) {
				let {
					msg,
					code
				} = await this.beg.request({
					url: `${this.api.backTb}/${this.form.tableId}`,
					data: {
						notes: e
					},
					method: 'POST'
				})
				uni.showToast({
					title: msg,
					icon: 'none',
					duration: 2000
				})
				if (code && code == 200) {
					this.$refs['backTableRef'].close()
					setTimeout(() => {
						uni.reLaunch({
							url: '/pages/home/index?current=1'
						})
					}, 800)
				}
			},
			handClearTb() {
				this.clearTbShow = true
			},
			async pullClear() {
				let {
					msg,
					code
				} = await this.beg.request({
					url: `${this.api.inStoreComplete}/${this.form.id}`,
					method: "POST",
					data: {
						scene: this.form.scene
					}
				})
				uni.$u.toast(msg)
				if (code && code == 200) {
					this.clearTbShow = false
					setTimeout(() => {
						uni.reLaunch({
							url: '/pages/home/index?current=1'
						})
					}, 800)
				}
			},
		}
	}
</script>

<style lang="scss" scoped>
	.left {
		width: 29.2825vw;
	}

	/deep/.u-modal__button-group__wrapper--confirm {
		background: #4275F4;
	}

	/deep/.u-modal__content__text {
		font-size: 16px !important;
		color: #000 !important;
	}

	@media (min-width: 1500px) and (max-width: 3280px) {
		.left {
			width: 400px;
		}
	}
</style>
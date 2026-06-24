<template>
	<view class="f-y-bt h100  bf p10">
		<view class="f-bt f-y-c search">
			<u--form labelPosition="left" :model="queryForm" ref="uForm" labelWidth="100px" labelAlign="right"
				:labelStyle="{fontSize:'16px'}">
				<u-form-item label="商品名称：" prop="name" ref="item1">
					<u-input placeholder="请输入商品名称" v-model="queryForm.name" @input="fetchData" border="surround"
						prefixIcon="search" prefixIconStyle="font-size: 22px;color: #909399">
						<template slot="suffix">
							<u-icon @click="clear" v-if="queryForm.name" name="close-circle-fill" color="#999" size="20"></u-icon>
						</template>
					</u-input>
				</u-form-item>
				<u-form-item label="商品渠道：" prop="channelIds" ref="item1">
					<view class="sw">
						<uni-data-select v-model="queryForm.channelIds" :localdata="channels" placeholder="请选择"
							@change="handDiningType"></uni-data-select>
					</view>
				</u-form-item>
				<u-form-item label="商品分类：" prop="catId" ref="item1">
					<view class="sw">
						<uni-data-select v-model="queryForm.catId" :localdata="classfiy" placeholder="请选择"
							@change="handSource"></uni-data-select>
					</view>
				</u-form-item>
				<!-- <u-form-item label="商品状态：" prop="name" ref="item1">
						<view style="width:193px">
							<uni-data-select v-model="form.state" :localdata="status"
								placeholder="请输入商品状态"></uni-data-select>
						</view>
					</u-form-item> -->
				<!-- <u-form-item label=" " ref="item1">
					<u-button color="#4275F4" :customStyle="{color:'#000',padding:'5px 20px',height:'42px'}"
						@click="fetchData">
						<text class="f18">筛选</text></u-button>
					<text class="p-0-10"></text>
					<u-button plain :customStyle="{color:'#000',padding:'5px 20px',height:'42px'}" @click="reset"><text
							class="f18">重置</text></u-button>
				</u-form-item> -->
			</u--form>
		</view>
		<view class="main f-1 f16 pb40 f-y-bt">
			<view class="bd1">
				<u-tabs :current="current" :list="tabList" lineWidth="35px" lineColor="#4275F4"
					itemStyle="height: 55px;font-size:18px;color:#000" activeStyle="font-weight:bold"
					@change="changeTabs"></u-tabs>
			</view>
			<view class="topList mt20 f-g-1 f-y-bt">
				<uni-table class="select f18" ref="table" :loading="loading" type="selection" emptyText="暂无更多数据">
					<uni-tr class="bf5 c6">
						<uni-th><text class="c6">ID</text></uni-th>
						<uni-th><text class="c6">商品名称</text></uni-th>
						<uni-th><text class="c6">商品渠道</text></uni-th>
						<uni-th><text class="c6">商品分类</text></uni-th>
						<uni-th><text class="c6">门店售价(元)</text></uni-th>
						<uni-th><text class="c6">销量</text></uni-th>
						<uni-th><text class="c6">剩余库存</text></uni-th>
						<uni-th><text class="c6">状态</text></uni-th>
						<uni-th align="left"><text class="c6">操作</text></uni-th>
					</uni-tr>
					<uni-tr v-for="(row, index) in dataList" :key="index">
						<uni-td>{{row.goods.id}}</uni-td>
						<uni-td>
							<view class="dfa">
								<u--image :src="row.goods.logo" width="50px" height="50px"></u--image>
								<view class="ml10">{{ row.goods.name }}</view>
							</view>
						</uni-td>
						<uni-td>
							<view class="dfa">
								<u-tag class="mr10" v-if="row.goods.channelIds  && row.goods.channelIds.includes(1)"
									text="外卖" type="primary" plain plainFill></u-tag>
								<u-tag v-if="row.goods.channelIds && row.goods.channelIds.includes(2)" text="店内"
									type="success" plain plainFill></u-tag>
							</view>
						</uni-td>
						<uni-td>
							<view v-if="row.goods && row.goods.category">
								<text v-for="(v,i) in row.goods.category">
									{{v.name}}
								</text>
							</view>
						</uni-td>
						<uni-td>
							<view v-if="row.goods.channelIds && row.goods.channelIds.length>=2 && row.selfPriceSwitch">
								<view v-if="row.goods.channelIds.includes(1)">外卖售价：
									<text v-if="row.goods.specSwitch == 0">
										{{ row.goods.singleSpec.price }}
									</text>
									<text v-else>
										{{ row.goods.mixPrice }}~{{ row.goods.maxPrice }}
									</text>
								</view>
								<view v-if="row.goods.channelIds.includes(2)">店内售价：
									<text v-if="row.goods.specSwitch == 0">
										{{ row.goods.singleSpec.inStorePrice }}
									</text>
									<text v-else>
										{{ row.goods.minInStorePrice }}~{{ row.goods.maxInStorePrice }}
									</text>
								</view>
							</view>
							<view v-else>
								<view>
									<text v-if="row.goods.specSwitch == 0">
										{{ row.goods.singleSpec.price }}
									</text>
									<text v-else>
										{{ row.goods.mixPrice }}~{{ row.goods.maxPrice }}
									</text>
								</view>
							</view>
						</uni-td>
						<uni-td>
							<view>
								<text
									v-if="row.goods.specSwitch == 0">{{ row.goods.singleSpec && row.goods.singleSpec.sales}}</text>
								<text v-else>{{ row.goods.skus[0].sales }}</text>
							</view>
						</uni-td>
						<uni-td>
							<view>
								<view v-if="row.goods.specSwitch == 0" class="flex">
									<view>
										<u--text type="primary" link size="16"
											@click="handEditStorck(row.goods, typeId, { storeId: storeId})"
											:text="row.goods.singleSpec && row.goods.singleSpec.surplusInventory">
										</u--text>
									</view>
									<u--text type="primary" size="16"
										v-if="row.goods.singleSpec && row.goods.singleSpec.surplusInventory<=0" class="kuc"
										text="(已售罄)"></u--text>
									<u--text type="primary" size="16"
										v-else-if="row.goods.singleSpec && row.goods.singleSpec.surplusInventory<=20"
										class="kuc" text="(库存不足)"></u--text>
								</view>
								<u--text size="16" type="primary" v-else
									@click="handEditStorck(row.goods, typeId, { storeId: storeId })" text="详情"></u--text>
							</view>
						</uni-td>
						<uni-td>
							<block v-if="role.includes('shangxiajia')">
								<u--text v-if="!row.deleted_at" type="success" text="上架" size="16"
									@click="show=true,showState=true,rows=row"></u--text>
								<u--text v-else type="error" text="下架" size="16"
									@click="show=true,showStates=true,rows=row"></u--text>
							</block>
							<block v-else>
								<u--text v-if="!row.deleted_at" type="success" text="上架" size="16"></u--text>
								<u--text v-else type="error" text="下架" size="16"></u--text>
							</block>
						</uni-td>
						<uni-td>
							<text class="cfd8 pr15" @click="show=true,showSet=true,rows=row">置满</text>
							<text class="cfd8 pr15" @click="show=true,showSell=true,rows=row">沽清</text>
							<text class="cfd8 pr15" @click="handEditStorck(row.goods, typeId, { storeId: storeId })" v-if="role.includes('kucun')">库存管理</text>
							<text class="cfd8" v-if="!row.deleted_at && role.includes('shangxiajia')" @click="show=true,showState=true,rows=row">下架</text>
							<text class="cfd8" v-if="row.deleted_at && role.includes('shangxiajia')" @click="show=true,showStates=true,rows=row">上架</text>
						</uni-td>
					</uni-tr>
				</uni-table>
			</view>
			<view class="f-c mt10 pagona">
				<!-- <view class="dfa">
					<u-button text="批量上架"></u-button>
					<text class="p-0-10"></text>
					<u-button text="批量下架"></u-button>
				</view> -->
				<uni-pagination show-icon :page-size="queryForm.pageSize" :current="queryForm.pageNo" :total="total"
					@change="change" title="标题文字" />
			</view>
		</view>
		<u-toast ref="uToast"></u-toast>
		<view v-if="show" class="">
			<u-modal :show="showState" :showCancelButton="true" title="温馨提示" cancelText="取消" width="300px"
				content="你确定要下架该商品吗？" @cancel="showState=false" @confirm="clickXiaJia" confirmColor="#fff"></u-modal>
			<u-modal :show="showStates" :showCancelButton="true" title="温馨提示" cancelText="取消" width="300px"
				content="你确定要上架该商品吗？" @cancel="showStates=false" @confirm="clickShangJia" confirmColor="#fff"></u-modal>
			<u-modal :show="showSet" :showCancelButton="true" title="温馨提示" cancelText="取消" width="300px"
				content="你确定要置满该商品的库存吗？" @cancel="showSet=false" @confirm="clickOutofStock(1)" confirmColor="#fff"></u-modal>
			<u-modal :show="showSell" :showCancelButton="true" title="温馨提示" cancelText="取消" width="300px"
				content="你确定要沽清该商品的库存吗？" @cancel="showSell=false" @confirm="clickOutofStock(2)" confirmColor="#fff"></u-modal>
		</view>
		<editStorck ref="editStorckRef" @fetch-data="fetchData" />
	</view>
</template>

<script>
	import editStorck from "./goods/editStorck";
	import {
		mapState,
	} from 'vuex'
	export default ({
		components: {
			editStorck,
		},
		data() {
			return {
				total: 0,
				current: 0,
				status: [{
						value: 0,
						text: '上架'
					},
					{
						value: 1,
						text: '下架'
					}
				],
				channels: [{
						value: 1,
						text: '外卖'
					},
					{
						value: 2,
						text: '店内'
					},
					{
						value: 3,
						text: '外卖+店内商品'
					},
				],
				tabList: [{
						name: '全部商品',
						value: ''
					},
					{
						name: '库存不足',
						value: 'inventoryOff'
					},
					{
						name: '下架商品',
						value: 'offShelf'
					},
				],
				typeId: 1,
				queryForm: {
					pageNo: 1,
					pageSize: 10,
					catId: null,
					state: null,
					channelIds: '',
					name: '',
				},
				classfiy: [],
				form: {},
				dataList: [],
				loading: false,
				rows: {},
				show: false,
				showState: false,
				showStates: false,
				showSet: false,
				showSell: false,
			}
		},
		computed: {
			...mapState({
				storeId: state => state.storeId,
				role: state => state.user.roleData || [],
			}),
		},
		methods: {
			init() {
				this.fetchData()
				this.getCategory()
			},
			async getCategory() {
				let {
					data: {
						list,
						total
					},
				} = await this.beg.request({
					url: `${this.api.goodsCategory}/${this.typeId}`,
					data: {
						pageNo: 1,
						pageSize: 999,
						state: this.queryForm.state
					},
				})
				this.classfiy = list
				this.classfiy.forEach((v) => {
					v.value = v.id
					v.text = v.name
				})
			},
			async fetchData() {
				this.loading = true
				let {
					data: {
						list,
						pageNo,
						pageSize,
						total
					},
				} = await this.beg.request({
					url: `${this.api.storeGoodsList}/${this.typeId}`,
					data: this.queryForm,
				})
				this.total = total
				this.dataList = list
				this.loading = false
			},
			handDiningType(e) {
				this.queryForm.channelIds = e
				this.fetchData()
			},
			handSource(e) {
				this.queryForm.catId = e
				this.fetchData()
			},
			change(e) {
				this.queryForm.pageNo = e.current;
				this.fetchData()
			},
			//切换
			changeTabs(e) {
				this.queryForm.channelIds = ''
				this.queryForm.catId = ''
				this.queryForm.state = e.value;
				this.queryForm.pageNo = 1;
				this.fetchData()
			},
			//重置
			reset() {
				this.queryForm = {
					pageNo: 1,
					pageSize: 10,
					catId: null,
					state: null,
					channelIds: '',
					name: '',
				}
			},
			handEditStorck(v, tid, store) {
				this.$refs['editStorckRef'].open(v, tid, store)
			},
			async clickXiaJia() {
				let goodsIds = [this.rows.goods.id];
				let {
					msg
				} = await this.beg.request({
					url: `${this.api.storeGoodsList}/${this.typeId}`,
					method: 'DELETE',
					data: {
						goodsIds,
						storeId: this.storeId
					}
				})
				this.showState = false
				uni.$u.toast(msg)
				this.fetchData();
			},
			async clickShangJia() {
				let goodsIds = [this.rows.goods.id];
				let {
					msg
				} = await this.beg.request({
					url: `${this.api.goodsRestore}/${this.typeId}`,
					method: 'POST',
					data: {
						goodsIds,
						storeId: this.storeId
					}
				})
				this.showStates = false
				uni.$u.toast(msg)
				this.fetchData();
			},
			async clickOutofStock(t) {
				let goodsIds = [this.rows.spuId];
				let {
					msg
				} = await this.beg.request({
					url: `${t==1?this.api.fillUp:this.api.outofStock}/${this.typeId}`,
					method: 'POST',
					data: {
						goodsIds,
						storeId: this.storeId
					}
				})
				t == 1 ? this.showSet = false : this.showSell = false
				uni.$u.toast(msg)
				this.fetchData();
			},
			clear(){
				this.queryForm.name = ''
				this.fetchData();
			},
		}
	})
</script>

<style lang="scss" scoped>
	.search {
		.sw {
			width: 13.1771vw;
		}

		.iw {
			width: 14.6412vw;
		}

		/deep/.u-form {
			display: flex !important;
			flex-wrap: wrap;

			.u-input {
				background: #fff;

				.input-placeholder,
				.uni-input-input {
					font-size: 16px;
				}
			}

			.uni-select {
				height: 38px !important;
				background: #fff;

				.uni-select__input-placeholder {
					font-size: 16px !important;
					color: #ccc;
				}

				.uni-select__selector-item {
					span {
						font-size: 16px;
					}
				}
			}
		}
	}
	
	.main {
		max-height: calc(100vh - 15.625vh);
		overflow: hidden;
		overflow-y: scroll;
	
	
		.topList {
			overflow: hidden;
			overflow-x: scroll;
		}
	
	}

	.pagona {

		/deep/.uni-pagination {
			.page--active {
				display: inline-block;
				width: 2.1961vw;
				height: 3.9062vh;
				background: #4275F4 !important;
				color: #fff !important;
			}

			.is-phone-hide {
				width: 2.1961vw;
				height: 3.9062vh;
			}

			.uni-pagination__total {
				font-size: 1.1641vw;
				width: auto;
				display: -webkit-box;
				display: -webkit-flex;
				display: flex;
				align-items: center;
			}

			span {
				font-size: 1.1641vw;
			}
		}
	}
	
	/deep/.uni-table{
	   min-width: auto !important;
	}
	

	.kuc {
		color: #f00;
	}

	@media (min-width: 1500px) and (max-width: 3280px) {
		.search {
			.sw {
				width: 180px;
			}

			.iw {
				width: 200px;
			}
		}

		.pagona {

			/deep/.uni-pagination {
				.page--active {
					width: 30px;
					height: 30px;
				}

				.is-phone-hide {
					width: 30px;
					height: 30px;
				}

				.uni-pagination__total {
					font-size: 20px;
					width: auto;
				}

				span {
					font-size: 20px;
				}
			}
		}
	}
</style>
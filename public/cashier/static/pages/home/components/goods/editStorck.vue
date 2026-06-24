<template>
	<view class="">
		<u-popup :show="model" :round="10" mode="center" @close="model=false">
			<view class="invent" :customStyle="{borderRadius:'10px'}">
				<view class="dfbc p15 bd1">
					<view class="wei6">修改库存</view>
					<text class="iconfont icon-cuowu" @click="model=false"></text>
				</view>
				<view class="p-20-15 f-y-bt f16">
					<view class="main">
						<view class="dfa mb15">
							<u--image :src="form.logo" width="68px" height="68px"></u--image>
							<view class="ml10 f16">
								<view class="mb20">{{form.name}}</view>
								<view>ID:{{form.id}}</view>
							</view>
						</view>
						<view v-if="form.specSwitch == 0">
							<uni-table ref="table" emptyText="暂无更多数据">
								<uni-tr class="bf5 c6">
									<uni-th><text class="p-5-0 f16 c6">剩余库存</text></uni-th>
									<uni-th><text class="p-5-0 f16 c6">默认库存</text></uni-th>
								</uni-tr>
								<uni-tr>
									<uni-td>
										<u-number-box class="mr20" v-model="form.singleSpec.surplusInventory" :min="0"
											button-size="38">
											<view slot="input" style="width: 100px;text-align: center;" class="input">
												<u--input type="number" border="surround" inputAlign="center"
													v-model="form.singleSpec.surplusInventory"></u--input>
											</view>
										</u-number-box>
									</uni-td>
									<uni-td>
										<view class="dfa m10 f16">
											<u-number-box class="mr20" v-model="form.singleSpec.inventory" :min="0"
												button-size="38">
												<view slot="input" style="width: 100px;text-align: center;"
													class="input">
													<u--input type="number" border="surround" inputAlign="center"
														v-model="form.singleSpec.inventory"></u--input>
												</view>
											</u-number-box>
											<u-checkbox-group v-model="form.singleSpec.dayFilling" size="22"
												placement="column">
												<u-checkbox :customStyle="{marginRight: '10px'}" labelSize="16"
													activeColor="#4275F4" iconSize="16" iconColor="#fff" label="次日置满"
													:name="1">
												</u-checkbox>
											</u-checkbox-group>
											<text class="f14 c9">(勾选后，次日0点自动补足到默认库存)</text>
										</view>
									</uni-td>
								</uni-tr>
							</uni-table>
						</view>
						<view v-if="form.specSwitch == 1">
							<uni-table ref="table" emptyText="暂无更多数据">
								<uni-tr class="bf5 c6">
									<uni-th><text class="p-5-0 f16 c6">规格值</text></uni-th>
									<uni-th><text class="p-5-0 f16 c6">剩余库存</text></uni-th>
									<uni-th><text class="p-5-0 f16 c6">默认库存</text></uni-th>
								</uni-tr>
								<uni-tr v-for="(v,i) in form.skus" :key="i">
									<uni-td>
										<span v-if="form.skus[i].specName">
											{{ form.skus[i].specName[0].name }}
										</span>
									</uni-td>
									<uni-td>
										<u-number-box class="mr20" v-model="form.skus[i].surplusInventory" :min="0"
											button-size="38">
											<view slot="input" style="width: 100px;text-align: center;" class="input">
												<u--input type="number" border="surround" inputAlign="center"
													v-model="form.skus[i].surplusInventory"></u--input>
											</view>
										</u-number-box>
									</uni-td>
									<uni-td>
										<view class="dfa m10 f16">
											<u-number-box class="mr20" v-model="form.skus[i].inventory" :min="0"
												button-size="38">
												<view slot="input" style="width: 100px;text-align: center;"
													class="input">
													<u--input type="number" border="surround" inputAlign="center"
														v-model="form.skus[i].inventory"></u--input>
												</view>
											</u-number-box>
											<u-checkbox-group v-model="form.skus[i].dayFilling" size="22"
												placement="column">
												<u-checkbox :customStyle="{marginRight: '10px'}" labelSize="16"
													activeColor="#4275F4" iconSize="16" iconColor="#fff" label="次日置满"
													:name="1">
												</u-checkbox>
											</u-checkbox-group>
											<text class="f14 c9">(勾选后，次日0点自动补足到默认库存)</text>
										</view>
									</uni-td>
								</uni-tr>
							</uni-table>
						</view>
					</view>
					<view class="mt30 tar">
						<u-button class="mr15" @click="close"
							:customStyle="{display:'inline-block',width:'110px',height:'45px',lineHeight:'45px',borderRadius:'5px'}">
							<text class="f16">取消</text></u-button>
						<u-button color="#4275F4" @click="save"
							:customStyle="{display:'inline-block',width:'110px',height:'45px',lineHeight:'45px',borderRadius:'5px'}">
							<text class="f16">确定</text></u-button>
					</view>
				</view>
			</view>
		</u-popup>
	</view>
</template>

<script>
	export default ({
		components: {},
		data() {
			return {
				loading: false,
				typeId: 1,
				model: false,
				labelList: [],
				singleSpec: [{}],
				form: {},
				loading: true,
				typeId: "",
				headerObj: {},
			}
		},
		methods: {
			open(goods, typeId, headerObj) {
				this.loading = true;
				this.typeId = typeId;
				this.headerObj = headerObj;
				if (goods.id) {
					this.form = JSON.parse(JSON.stringify(goods));
					if (this.form.specSwitch == 1) {
						for (let i = 0; i < goods.skus.length; i++) {
							this.form.skus[i].dayFilling = goods.skus[i].dayFilling == 1 ? [1] : []
						}
					} else {
						this.form.singleSpec.dayFilling = goods.singleSpec.dayFilling == 1 ? [1] : []
					}
				}
				this.model = true;
				this.loading = false;
			},
			close() {
				this.model = false;
			},
			async save() {
				let changes = [];
				if (this.form.specSwitch == 1) {
					changes = this.form.skus.map((item) => {
						return {
							specMd5: item.specMd5,
							inventory: item.inventory,
							dayFilling: item.dayFilling.length ? 1 : 0,
							surplusInventory: item.surplusInventory,
						};
					});
				} else if (this.form.specSwitch == 0) {
					changes.push({
						specMd5: this.form.singleSpec.specMd5,
						inventory: this.form.singleSpec.inventory,
						dayFilling: this.form.singleSpec.dayFilling.length ? 1 : 0,
						surplusInventory: this.form.singleSpec.surplusInventory,
					});
				}
				let {
					msg
				} = await this.beg.request({
					url: `${this.api.storeGoodsList}/${this.typeId}`,
					method: 'PUT',
					data: {
						changes,
						storeId: this.headerObj.storeId
					}
				})
				uni.$u.toast(msg)
				this.$emit("fetch-data");
				this.close();
			},
		}
	})
</script>

<style lang="scss" scoped>
	.invent {
		width: 65.8857vw;
		.main{
			height: 62.5vh;
			overflow-y: scroll;
		}
	}

	@media (min-width: 1500px) and (max-width: 3280px) {
		width: 900px;
		.main{
			height: 480px;
		}
	}
</style>
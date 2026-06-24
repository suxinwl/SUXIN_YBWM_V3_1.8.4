<template>
	<u-overlay :show="getOrder" :opacity="0.2" @click="close">
		<view class="reduce bf f18 f-y-bt" @tap.stop>
			<view class="dfbc p20">
				<view class="wei6 f24 f-c f-g-1">取单</view>
				<text class="iconfont icon-cuowu" @click="close"></text>
			</view>
			<view class="p-0-20 f-1 f-bt main">
				<view class="left">
					<view class="reson_i p15 f18 mb15 bs6 " :class="current==index?'acreson_i':''"
						v-for="(item,index) in list" :key="index" @click="changeCurr(item,index)">
						<!-- <view class="r_gou"></view> -->
						<!-- <text class="iconfont icon-duigou f12"></text> -->
						<view class="f28 wei6">{{index+1}}</view>
						<view class="f20">￥{{item.checkout.money}}</view>
						<view class="f14">{{item.created_at}}</view>
					</view>
				</view>
				<view class="f-1 pb10 right"
					v-if="list && list.length">
					<view class="w100 dfbc p-15-0 bd2" v-for="(item,index) in list[current].goods" :key="index">
						<view class="w55 c3">
							<view>{{item.goods && item.goods.name}}</view>
							<view class="flex f-w f14 c9">
								<view v-if="item.attrData && item.attrData.spec">
									[{{ item.attrData.spec }}]</view>
								<view v-if="item.attrData && item.attrData.attr">
									[{{ item.attrData.attr }}]</view>
								<view v-if="item.attrData && item.attrData.matal">
									{{ item.attrData.matal }}
								</view>
							</view>
						</view>
						<view class="dfbc f-1">
							<view class="c9">x{{item.num}}</view>
							<view>￥{{item.money}}</view>
						</view>
					</view>
				</view>
			</view>
			<view class="p20 f-e butt bf">
				<view class="mr15">
					<u-button :customStyle="{width:'135px',height:'50px',borderRadius:'6px'}" @click="delUpOrder">
						<text class="f20 wei6 ">删除此单</text></u-button>
				</view>
				<view>
					<u-button :customStyle="{width:'135px',height:'50px',borderRadius:'6px'}" color="#4275F4"
						@click="saveOrder">
						<text class="f20 wei6 ">确认此单</text></u-button>
				</view>
			</view>
		</view>
		<u-toast ref="uToast"></u-toast>
	</u-overlay>
</template>

<script>
	export default {
		props: {

		},
		data() {
			return {
				getOrder: false,
				current: 0,
				currItem: {},
				list: [],
			}
		},
		methods: {
			async open(t) {
				await this.getUpOrder()
				this.getOrder = true
			},
			close() {
				this.getOrder = false
			},
			async getUpOrder() {
				let {
					data: {
						list
					}
				} = await this.beg.request({
					url: this.api.goodsFreeze,
				})
				this.list = list ? list : []
				if (list && list.length) {
					this.currItem = list[0]
					this.current = 0
				} else {
					this.currItem = {}
					this.close()
				}
			},
			changeCurr(v, i) {
				this.current = i
				this.currItem = v
			},
			async delUpOrder() {
				let {
					data,
					msg,
				} = await this.beg.request({
					url: `${this.api.goodsFreeze}/${this.currItem.id}`,
					method: 'DELETE'
				})
				uni.$u.toast(msg)
				this.getUpOrder()
				this.$emit('checkOut')
			},
			async saveOrder() {
				let {
					data,
					msg,
				} = await this.beg.request({
					url: `${this.api.goodsUnFreeze}/${this.currItem.id}`,
					method: 'POST'
				})
				uni.$u.toast(msg)
				this.$emit('checkOut')
				this.close()
			},
		}
	}
</script>

<style lang="scss" scoped>
	.reduce {
		position: absolute;
		transform: translateX(-50%);
		top: 10vh;
		left: 50vw;
		width: 58.5651vw;
		height: 78.125vh;
		overflow: hidden;
		// overflow-y: scroll;
		border-radius: 10px;

		.reson_i {
			position: relative;
			display: inline-flex;
			flex-direction: column;
			justify-content: space-between;
			border: 1px solid #e6e6e6;
			width: 160px;
			height: 120px;

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

		.butt {
			box-shadow: 0px 2px 20px 6px #ddd;
		}
		
		.main{
			height: calc(100vh - 300px);
			overflow-y: scroll;
			.left{
				width: 200px;
			}
		}
	}
	@media (min-width: 1500px) and (max-width: 3280px) {
		.reduce {
			position: absolute;
			top: 80px;
			left: 50%;
			transform: translateX(-50%);
			width: 800px;
			height: calc(100vh - 150px);
			border-radius: 10px;
			.reson_i {
				width: 180px;
				height: 120px;
			}
		}
	}
</style>
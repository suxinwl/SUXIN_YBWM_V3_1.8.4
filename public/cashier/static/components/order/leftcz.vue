<template>
	<view class="l_but br1 p-15-10">
		<view class="numberBox">
			<u-number-box v-model="selectItem.num" :disabled="list.length<1" v-if="type!='oAfter'">
				<view slot="minus" class="minus" @click.stop="handcar({g: selectItem,addwz: -1,})">
					<u-icon name="minus" size="20"></u-icon>
				</view>
				<text slot="input" class="input" @click="editNum(selectItem)">{{selectItem.num?selectItem.num:0}}</text>
				<!-- view slot="input" class="input">
					<u--input
						border="none"
						v-model="selectItem.num || 0"
						@input="handChange({g: selectItem,addwz:selectItem.num,})"></u--input>
				</view> -->
				<view slot="plus" class="plus" @click.stop="handcar({g: selectItem,addwz: 1,})">
					<u-icon name="plus" size="20"></u-icon>
				</view>
			</u-number-box>
		</view>

		<u-button v-if="type=='oAfter'&& selectItem.state!=8 && carList.payType!=1" class="mb10 l-h1" @click="handRefund"
			:disabled="list.length<1" :customStyle="{borderRadius:'6px',border:'1px solid #e6e6e6',color:'#000'}">
			<text class="f16">退菜</text></u-button>
		<u-button v-if="type!='oAfter'" class="mb10 l-h1" :disabled="list.length<1" @click="handRemark"
			:customStyle="{borderRadius:'6px',border:'1px solid #e6e6e6',color:'#000'}">
			<text class="f16">单品</br>备注</text></u-button>
		<u-button v-if="type!='oAfter'" class="mb10 l-h1" :disabled="list.length<1"
			:customStyle="{borderRadius:'6px',border:'1px solid #e6e6e6',color:'#000'}" @click="handPack">
			<text class="f16" v-if="selectItem.pack">取消</br>打包</text>
			<text class="f16" v-else>打包</text>
		</u-button>
		<block v-if="mode=='fastOrder' || mode=='tableOrder' && carList.payType!=1">
			<u-button v-if="selectItem.discountType==2 || selectItem.discountType==3" class="mb10 gift l-h1"
				@click="cancelDis({g: selectItem,addwz: selectItem.num})" :disabled="list.length<1"
				:customStyle="{borderRadius:'6px',border:'1px solid #e6e6e6',color:'#000'}">
				<text class="f16" v-if="selectItem.discountType==2">取消</br>折扣</text>
				<text class="f16" v-if="selectItem.discountType==3">取消</br>减免</text>
			</u-button>
			<u-button v-if="!selectItem.discountType" class="mb10 gift l-h1" @click="handDis" :disabled="list.length<1"
				:customStyle="{borderRadius:'6px',border:'1px solid #e6e6e6',color:'#000'}">
				<text class="f16">商品打折/减免</text></u-button>
		<u-button v-if="selectItem.discountType==1" class="mb10 l-h1"
			@click="cancelDis({g: selectItem,addwz: selectItem.num})" :disabled="list.length<1"
			:customStyle="{borderRadius:'6px',border:'1px solid #e6e6e6',color:'#000'}">
			<text class="f16">取消<br>赠菜</text></u-button>
		<u-button v-else class="mb10 l-h1" @click="handGift" :disabled="list.length<1"
			:customStyle="{borderRadius:'6px',border:'1px solid #e6e6e6',color:'#000'}">
			<text class="f16">赠菜</text></u-button>
		</block>
		<u-button v-if="type!='oAfter'" class="mb10 l-h1" @click="handDelItem({g: selectItem,addwz: -selectItem.num})"
			:disabled="list.length<1" :customStyle="{borderRadius:'6px',border:'1px solid #e6e6e6',color:'#000'}">
			<text class="f16">删除</text></u-button>
		<u-button v-if="type=='oAfter' && selectItem.state==8" class="mb10 l-h1"
			@click="cancelDis({g: selectItem,addwz: selectItem.num})" :disabled="list.length<1"
			:customStyle="{borderRadius:'6px',border:'1px solid #e6e6e6',color:'#000'}">
			<text class="f16">取消<br>退菜</text></u-button>
		<block v-if="type!='oAfter'">
			<block v-if="mode=='fastOrder'">
				<u-button class="mb10 l-h1" @click="handDeposit" :disabled="list.length<1"
					:customStyle="{borderRadius:'6px',border:'1px solid #e6e6e6',color:'#000'}">
					<text class="f16">挂单</text></u-button>
				<u-button class="mb10 p-r badge l-h1" @click="handUpOrder" :disabled="carList.freezeCount<=0"
					:customStyle="{borderRadius:'6px',border:'1px solid #e6e6e6',color:'#000'}">
					<text class="f16">取单</text>
					<u-badge type="error" color="#fff" bgColor="#4275F4" :value="carList.freezeCount" :absolute="true"
						:offset="[0,0]"></u-badge>
				</u-button>
			</block>
			<u-button class="mb10 l-h1" @click="handAllDesc"
				:customStyle="{borderRadius:'6px',border:'1px solid #e6e6e6',color:'#000'}">
				<text class="f16">整单<br>备注</text></u-button>
			<!-- <u-button class="mb10" :disabled="list.length<1" @click="handBatch"
				:customStyle="{borderRadius:'6px',border:'1px solid #e6e6e6',color:'#000'}">
				<text class="f16">{{!batch?'批量操作':'取消批量'}}</text></u-button> -->
		</block>
		<block v-if="mode=='tableOrder'">
			<!-- <u-button class="mb10 l-h1" @click="service=true"
				:customStyle="{borderRadius:'6px',border:'1px solid #e6e6e6',color:'#000'}">
				<text class="f16">服务费</text></u-button> -->
			<!-- <u-button class="mb10 l-h1" @click="share=true"
				:customStyle="{borderRadius:'6px',border:'1px solid #e6e6e6',color:'#000'}">
				<text class="f16">拼桌</text></u-button> -->
			<block v-if="carList.payType!=1">
				<u-button class="mb10 l-h1" @click="handTurntable"
					:customStyle="{borderRadius:'6px',border:'1px solid #e6e6e6',color:'#000'}">
					<text class="f16">转台</text></u-button>
				<u-button v-if="type=='oAfter'" class="mb10 l-h1" @click="handCombine"
					:customStyle="{borderRadius:'6px',border:'1px solid #e6e6e6',color:'#000'}">
					<text class="f16">并台</text></u-button>
			</block>
			<u-button v-if="type=='oAfter'" class="mb10 l-h1" @click="handBackTb"
				:customStyle="{borderRadius:'6px',border:'1px solid #e6e6e6',color:'#000'}">
				<text class="f16">撤单</text></u-button>
			<u-button v-if="type=='oAfter' && carList.payType==1" class="mb10 l-h1" @click="handClearTb"
				:customStyle="{borderRadius:'6px',border:'1px solid #e6e6e6',color:'#000'}">
				<text class="f16">清台</text></u-button>
			<u-button v-if="type!='oAfter'" @click="handRescind" class="l-h1"
				:customStyle="{borderRadius:'6px',border:'1px solid #e6e6e6',color:'#000'}">
				<text class="f16">撤台</text></u-button>
		</block>
	</view>
</template>

<script>
	import {
		mapState,
		mapMutations,
	} from 'vuex'
	export default ({
		components: {
			
		},
		props: {
			mode: {
				type: String,
				default: 'fastOrder'
			},
			type: {
				type: String,
				default: 'oBefore'
			},
			selectItem: {
				type: Object,
				default: {}
			},
			list: {
				type: Array,
				default: []
			},
			carList: {
				type: Object,
				default: {}
			},
		},
		data() {
			return {
				batch: false,
			}
		},
		// watch:{
		// 	selectItem: {
		// 		handler(val) {
		// 			this.$emit('rf',val)
		// 		},
		// 		immediate: true
		// 	}
		// },
		computed: {

		},
		methods: {
			handcar(g) {
				if (this.selectItem && this.selectItem.id) {
					this.$emit('hItem', g)
				}
			},
			// handChange(g){
			// 	if (this.selectItem && this.selectItem.id) {
			// 		this.$emit('cNumItem', g)
			// 	}
			// },
			handRemark() {
				this.$emit('handRemark', 'remark')
			},
			handPack() {
				this.$emit('handPack')
			},
			handAllDesc() {
				this.$emit('handAllDesc', 'allRemark')
			},
			handBatch() {
				this.batch = !this.batch
				this.$emit('handBatch', this.batch)
			},
			handDis() {
				this.$emit('gDis')
			},
			handGift() {
				this.$emit('gGift')
			},
			handRefund() {
				this.$emit('gRefund')
			},
			cancelDis(g) {
				this.$emit('cDis', g)
			},
			handRescind() {
				this.$emit('handRescind')
			},
			handBackTb() {
				this.$emit('backTb')
			},
			handClearTb(){
				this.$emit('clearTb')
			},
			handTurntable() {
				this.$emit('turntable')
			},
			handCombine() {
				this.$emit('combine')
			},
			handDelItem(v) {
				// this.$emit('hItem', v)
				this.$emit('handItemDel', v)
			},
			handDeposit() {
				this.$emit('handDeposit')
			},
			handUpOrder() {
				this.$emit('handUpOrder')
			},
			editNum(g){
				this.$emit('handEditNum',g)
			},
		}
	})
</script>

<style lang="scss" scoped>
	.l_but {
		// width: 200rpx;
		width: 6.9444vw;
		height: calc(100vh - 7.8125vh);
		overflow-y: auto;
		background: #eff0f4;
		padding-left: 0;
		padding-right: 0;
		// padding-top: 0;
		
		.numberBox{
			width: 5.8565vw;
			margin: 0 auto;
		}

		/deep/.u-button {
			padding: 10px;
			// width: 80px;
			// height: 75px !important;
			width: 5.8565vw;
			height: 9.7656vh !important;

			.u-button__text {
				font-size: 18px !important;
			}
		}

		/deep/ .gift {
			.u-button__text {
				font-size: 14px !important;
			}
		}

		.isBut {
			background: #4275F4;
		}

		/deep/.u-number-box {
			display: flex;
			flex-direction: column;
			margin-bottom: 10px;
			// width: 80px;
			width: 5.8565vw;
			border-radius: 6px;
			border: 1px solid #e6e6e6;
			background: #fff;

			.minus {
				// width: 80px;
				// height: 60px;
				width: 5.8565vw;
				height: 7.8125vh;
				@include flex;
				justify-content: center;
				align-items: center;

				.u-icon__icon {
					// font-size: 24px;
					font-size: 1.7569vw;
				}
			}

			.input {
				// width: 80px;
				// height: 60px;
				width: 5.8565vw;
				height: 7.8125vh;
				border-top: 1px solid #e6e6e6;
				border-bottom: 1px solid #e6e6e6;
				text-align: center;
				line-height: 7.8125vh;
				font-size: 24px;
				font-weight: 600;
			}

			.plus {
				// width: 80px;
				// height: 60px;
				// font-size: 24px;
				width: 5.8565vw;
				height: 7.8125vh;
				font-size: 1.7569vw;
				color: #000;
				/* #ifndef APP-NVUE */
				display: flex;
				/* #endif */
				justify-content: center;
				align-items: center;

				.u-icon__icon {
					// font-size: 24px;
					font-size: 1.7569vw;
				}
			}
		}
		// /deep/.u-input{
		// 	width: 100%;
		// 	height: 100%;
		// }
		// /deep/.u-input__content__field-wrapper__field{
		// 	text-align: center !important;
		// 	font-size: 24px !important;
		// }

		.remark {
			padding: 5px;
			width: 55px;
			height: 55px;
			border: 1px solid #e6e6e6;
		}

		.pack {
			width: 55px;
			height: 55px;
			border: 1px solid #e6e6e6;
		}

		.discount {
			padding: 5px;
			width: 55px;
			border: 1px solid #e6e6e6;
		}
	}

	.badge {
		/deep/.u-badge {
			line-height: 16px;
			font-size: 16px;
		}

		/deep/.u-badge span {
			color: #fff !important;
		}
	}

	@media (min-width: 1500px) and (max-width: 3280px) {
		.l_but {
			width: 100px;
			height: calc(100vh - 60px);
			overflow-y: auto;
			
			.numberBox{
				width: 80px;
			}

			/deep/.u-button {
				padding: 10px;
				width: 80px;
				height: 75px !important;

				.u-button__text {
					font-size: 18px !important;
				}
			}

			/deep/ .gift {
				.u-button__text {
					font-size: 14px !important;
				}
			}

			.isBut {
				background: #4275F4;
			}

			/deep/.u-number-box {
				display: flex;
				flex-direction: column;
				margin-bottom: 10px;
				width: 80px;
				border-radius: 6px;
				border: 1px solid #e6e6e6;
				background: #fff;

				.minus {
					width: 80px;
					height: 60px;
					@include flex;
					justify-content: center;
					align-items: center;

					.u-icon__icon {
						font-size: 24px;
					}
				}

				.input {
					width: 80px;
					height: 60px;
					border-top: 1px solid #e6e6e6;
					border-bottom: 1px solid #e6e6e6;
					text-align: center;
					line-height: 60px;
					font-size: 24px;
					font-weight: 600;
				}

				.plus {
					width: 80px;
					height: 60px;
					font-size: 24px;
					color: #000;
					/* #ifndef APP-NVUE */
					display: flex;
					/* #endif */
					justify-content: center;
					align-items: center;

					.u-icon__icon {
						font-size: 24px;
					}
				}
			}
		}
	}
</style>
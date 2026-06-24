<template>
	<view class="">
		<u-popup :show="isCash" :round="10" :closeable="true" mode="center" @close="close">
			<view class="cash">
				<view class="tac wei6 f24 mt20">{{tx}}</view>
				<view class="p20 mt20">
					<block v-if="t ==1">
						<u-input class="f20" v-model="cash_money" :placeholder="tx" type="digit" style="height: 70px;" @input="cInput(cash_money)">
							<u--text text="元" slot="suffix" margin="0 0 0 3px" type="tips"></u--text>
						</u-input>
						<view class="flex f18 mt10 c9">
							<view v-if="carMoney" class="mr20">应收：{{carMoney}}</view>
							<view>找零：<text v-if="xjshoukuan>=0">￥{{ xjshoukuan.toFixed(2) }}</text></view>
						</view>
						<view v-if="carMoney > 0" class="flex zl mt20">
							<view v-for="(v,i) in zllist" :key="i" class="item p10 mr20 c6 f18" @click="cInput(v)">￥{{v}}</view>
						</view>
					</block>
					<block v-else-if="t ==2">
						<u-input class="f20" v-model="cash_money" :placeholder="tx" type="number" style="height: 70px;"></u-input>
					</block>
				</view>
				<keybored type="digit" v-model="cash_money" confirmText="确认" @doneClear="doneClear" @doneAdd="doneAdd"
					@input="cInput">
				</keybored>
			</view>
		</u-popup>
	</view>
</template>

<script>
	import keybored from '@/components/liujto-keyboard/keybored.vue';
	export default {
		props: {
			tx: {
				type: String,
				default: '现金支付'
			},
			t: {
				type: Number,
				default: 1
			},
		},
		components: {
			keybored,
		},
		data() {
			return {
				isCash: false,
				cash_money: '',
				type:'',
				xjshoukuan:0,
				carMoney:0,
				zllist:[],
			}
		},
		methods: {
			open(m,t) {
				if(t) this.type = t
				if(m){
					this.cash_money = JSON.stringify(JSON.parse(m))
					this.carMoney = JSON.stringify(JSON.parse(m))
					this.jszl(this.carMoney)
				}
				this.isCash = true
			},
			jszl(m){
				let q1,q2,q3,q4
					q1 = Math.ceil(m)
					q2 = Math.ceil(q1 / 50) * 50
					q3 = Math.ceil(q2 / 100) * 100
					q4 = Math.ceil(q3 / 100) * 100 + 100
				this.zllist = [q1,q2,q3,q4]
				console.log(q1,q2,q3,q4)
			},
			close() {
				this.isCash = false
				this.xjshoukuan = 0
				this.carMoney = 0
			},
			doneClear() {
				this.cash_money = ''
			},
			cInput(e) {
				this.cash_money = e
				 if (+e > +this.carMoney) {
				    this.xjshoukuan = +e - +this.carMoney;
				}else {
					this.xjshoukuan = 0;
				}
			},
			doneAdd() {
				if(this.type == 'cashMoney') {
					this.$emit('cashMoney', this.cash_money)
				}else if(this.type == 'inputCode'){
					this.$emit('changeMoney', this.cash_money)
				}else{
					if(+this.cash_money < +this.carMoney){
						return uni.$u.toast('请输入合理的应收金额')
					}
					this.$emit('changeMoney', {money:this.cash_money,amount:this.xjshoukuan})
				}
				this.close()
			}
		}
	}
</script>

<style lang="scss" scoped>
	.cash {
		width: 450px;
		// height: 416px;

		/deep/.u-input {
			padding: 9px !important;
		}

		/deep/.ljt-keyboard-body {
			border: 1px solid #e5e5e5;

			.ljt-keyboard-number-body {
				width: 450px !important;
				height: 275px !important;
			}

			.ljt-number-btn-ac {
				// width: 100px !important;
			}

			.ljt-number-btn-confirm-2 {
				// width: 100px !important;
				background: #4275F4 !important;
			}
		}
		
		/deep/.u-input__content__field-wrapper__field{
		    font-size: 22px !important;
			font-weight: bold;
		}
		
		.zl{
			.item{
				border: 1px solid #dadbde;
				border-radius: 4px
			}
		}
	}
	@media (min-width: 500px) and (max-width: 900px) {
		.cash { 
			/deep/.ljt-keyboard-body {
				.ljt-keyboard-number-body {
					height: 150px !important;
				}
			}
		}
	}
</style>
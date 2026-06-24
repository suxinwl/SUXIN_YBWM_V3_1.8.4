<template>
	<view class="f-y-bt h100">
		<view class="main f-1 f-c-c bf">
			<view class="f20 mb20">查询核销码核销</view>
			<view class="dfa mb20">
				<view class="selecthx">
					<uni-data-select v-model="order_type" :localdata="classfiy" :clear="false"
						placeholder="请选择订单来源"></uni-data-select>
				</view>
				<view style="width:550px" class="srinput">
					<u--input placeholder="请输入核销码或扫描核销码" fontSize="18px" border="surround" v-model="code"
						style="height:55px" type="text"></u--input>
				</view>
				<u-button color="#4275F4" style="width: 100px;height:55px" @click="search"><text
						class="f20 wei6 cf">查询</text></u-button>
			</view>
			<view class="f16 c9">使用扫码枪扫码时需注意光标需要停留在输入框中</view>
			<view class="f18 f-c f-y-c l-h1 mt5" style="color: #4275F4;" @click="handScan">
				<view class="mr5">点击使用摄像头扫码</view>
				<u-icon name="scan" color="#4275F4" size="20"></u-icon>
			</view>
			<view class="f20 mb50 mt20" style="color:#FD8906" @click="hxdl">核销记录</view>
		</view>
		<codedl ref="codedlRef" @changeValue="changeValue" @cb="hxcg"></codedl>
		<selfdl ref="selfdlRef" @changeValue="changeValue" @cb="hxcg"></selfdl>
	</view>
</template>

<script>
	import codedl from './verification/codedl.vue';
	import selfdl from './verification/selfdl.vue';
	export default ({
		components: {
			codedl,
			selfdl
		},
		data() {
			return {
				order_type: 1,
				classfiy: [{
						value: 1,
						text: '自提核销'
					},
					{
						value: 2,
						text: '抖音核销'	
					},
					{
						value: 3,
						text: '快手核销'
					},
				],
				code: '',
			}
		},
		methods: {
			async search() {
				if (this.code) {
					let {
						msg,
						data,
						code,
					} = await this.beg.request({
						url: this.api.prepare,
						method: 'POST',
						data: {
							code: this.code,
							order_type: this.order_type,
						}
					})
					if (code && code == 200) {
						
						if(this.order_type==1){
							this.$refs['selfdlRef'].open(this.code, this.order_type, data)
						}else{
							if (data && data.data) {
								this.$refs['codedlRef'].open(this.code, this.order_type, data)
							} else {
								uni.$u.toast(data && data.extra && data.extra.description)
							}
						}
						
					} else {
						uni.$u.toast(msg || status || data)
					}
				} else {
					uni.$u.toast('请输入核销码')
				}
			},
			hxdl() {
				this.$emit('cT', {
					id: 61,
					icon: 'icon-dayin',
					name: '核销记录'
				}, 61)
			},
			hxcg() {
				this.code = ''
				this.$refs['codedlRef'].close()
			},
			handScan() {
				var that = this
				uni.scanCode({
					onlyFromCamera: true,
					success: function(res) {
						uni.showLoading({
							title: 'loading...'
						})
						if (res.result) {
							that.code = res.result
							that.search()
						} else {
							uni.hideLoading()
						}
					},
					complete: function(res) {
						uni.hideLoading()
					}
				})
			},
		}
	})
</script>

<style lang="scss" scoped>
	// /deep/.u-button {
	// 	span {
	// 		color: #000;
	// 	}
	// }

	// .main {
	// 	.left {
	// 		display: flex;
	// 		flex-direction: column;
	// 		justify-content: space-between;
	// 		width: 500px;

	// 		/deep/.u-input {
	// 			background: #f5f5f5;
	// 		}

	// 		.list {
	// 			max-height: calc(100vh - 215px);
	// 			overflow-y: auto;
	// 		}

	// 		.isItem {
	// 			background: #fffbe7;
	// 		}
	// 	}
	// }

	.main {
		.selecthx {
			height: 100%;

			/deep/ .uni-stat__select {
				height: 100%;
			}

			/deep/ .uni-select {
				height: 100%;
			}

			/deep/ .uni-stat__actived {
				height: 100%;
			}

			/deep/ .uni-select__input-box {
				height: 100%;
			}
		}

		.srinput {}
	}
</style>
<template>
	<view class="actions">
		<template v-if="!materialsBtn">
			<view class="jh f-c jhb" v-show="number" @tap.stop="minus">
				<text class='iconfont icon-jianhao f13 nowei'></text>
			</view>
			<view v-if="number && number>0" class="number">{{ number }}</view>
			<view class="jh f-c" :style="{background:subColor}" @tap.stop="add">
				<text class='iconfont icon-jiahao1-copy f13 nowei'></text>
			</view>
		</template>
		<template v-else>
			<view class="materials-box">
				<button type="primary" size="mini" :style="{backgroundColor:subColor,color:'#fff'}" class="materials-btn"
					@tap.stop="$emit('materials')">选规格</button>
				<view class="number-badge"  v-show="number">
					<view class="number" :style="{backgroundColor:subColor}">{{ number }}</view>
				</view>
			</view>
		</template>
	</view>
</template>

<script>
	export default {
		name: 'Actions',
		props: {
			number: {
				type: Number,
				default: 0
			},
			materialsBtn: {
				type: Boolean,
				default: false
			},
			product: {
				type: Object,
				default: () => {}
			}
		},
		data(){
			return{
				subColor: '#4275F4',
				fontColor:'#fff',
			}
		},
		methods: {
			add() {
				this.$emit('add', {
					g: this.product,
					addwz: 1,
				})
			},
			minus() {
				this.$emit('minus', {
					g: this.product,
					addwz: -1,
				})
			}
		}
	}
</script>

<style lang="scss" scoped>
	.actions {
		// margin-right: 12rpx;
		display: flex;
		align-items: center;

		.add-btn,
		.minus-btn {
			width: 44rpx;
			height: 44rpx;
		}

		.number {
			width: 40rpx;
			height: 40rpx;
			margin: 0 5px;
			display: flex;
			justify-content: center;
			align-items: center;
			font-size: 32rpx;
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
				background-color: #ffffff;
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
	}
	
	.f40{
		font-size: 40rpx;
	}
	.f42{
		font-size: 42rpx;
	}
	
	.jh{
		width: 40rpx;
		height: 40rpx;
		border-radius: 50%;
	}
	.jhb{
		border: 2rpx solid #ddd;
	}
</style>

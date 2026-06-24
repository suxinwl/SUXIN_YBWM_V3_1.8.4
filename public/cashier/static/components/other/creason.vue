<template>
	<view>
		<view class="reson_i f16 mr10 mb10 bs6 " :class="resons.includes(item)?'acreson_i':''"
			v-for="(item,index) in list" :key="index" @click="chooseRes(item)">
			{{item}}
			<!-- <view class="r_gou"></view>
			<text class="iconfont icon-duigou f12"></text> -->
		</view>
		<!-- <view v-show="show" class="reson_i f16 mr10 mb10 bs6 " :class="resons.includes(remark)?'acreson_i':''"
			@click="addesc">
			{{remark}}
			<view class="r_gou"></view>
			<text class="iconfont icon-duigou f12"></text>
		</view> -->
		<view class="dfa">
			<u-textarea v-model="desc" placeholder="请输入自定义备注" :class="resons.includes(desc)?'acreson_i':''"
				style="background: #fcfcfc;" @input="confirm"></u-textarea >
			<!-- <u-button color="#4275F4" :customStyle="{width:'80px',marginLeft:'10px',fontSize:'16px'}"
				@click="confirm"><text class="c0">确认</text></u-button> -->
		</view>
	</view>
</template>

<script>
	export default {
		props: {
			list: {
				type: Array,
				default: () => []
			}
		},
		data() {
			return {
				show: false,
				remark: '',
				desc: '',
				resons: []
			}
		},
		methods: {
			chooseRes(item, type) {
				if (!this.resons.includes(item)) {
					this.resons.push(item)
				} else {
					this.resons = this.resons.filter(v => {
						return v !== item
					});
				}
				this.$emit('getRemark', this.resons)
			},
			addesc() {
				if (!this.resons.includes(this.desc)) {
					this.resons.push(this.desc)
				} else {
					this.resons = this.resons.filter(v => {
						return v !== this.desc
					});
				}
				this.$emit('getRemark', this.resons)
			},
			confirm() {
				if (this.desc) {
					this.remark = this.desc
					// this.show = true
					let arr = []
					arr.push(this.desc)
					this.$emit('getRemark', arr)
				} else {
					this.show = false
				}
			}
		}
	}
</script>

<style lang="scss" scoped>
	.reson_i {
		position: relative;
		display: inline-block;
		border: 1px solid #e6e6e6;
		padding: 8px 15px;

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
</style>
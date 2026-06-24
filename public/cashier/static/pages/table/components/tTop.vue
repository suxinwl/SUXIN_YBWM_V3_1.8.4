<template>
	<view class="top bf f-x-bt p15 cf f18">
		<view class="dfa top_l">
			<text class="iconfont icon-fanhui cf" style="font-size: 26px;" @click="back"></text>
			<view class="p-0-20 wei f24">桌台
				<text
					v-if="type=='oAfter'">{{form.table && form.table.type.name}}{{form.table && form.table.name}}</text>
				<text>{{form.type && form.type.name}}{{form.name}}</text>
			</view>
			<view class="num f16 dfa">人数：{{form.people}}人
				<view @click="edit">
					<text class="f15 pl10">修改</text><text class="iconfont icon-youbian f14"></text>
				</view>
			</view>
		</view>
		<view class="f-1 p-5-0 search" style="padding-right: 100px;">
			<u-input placeholder="菜名/首字母/助记码" placeholderStyle="color:#fff" border="none" v-model="value" v-if="type=='oBefore'" @input="search">
				<template slot="suffix">
					<u-icon @click="clear" v-if="value" name="close-circle-fill" color="#fff" size="20"></u-icon>
				</template>
			</u-input>
		</view>
		<tool></tool>
		<share ref="shareRef" @save="editPeople" />
	</view>
</template>

<script>
	import share from './share.vue';
	import tool from '@/components/tool/tool.vue'
	export default {
		components: {
			share,
			tool,
		},
		props: {
			form: {
				type: Object,
				default: {}
			},
			type: {
				type: String,
				default: 'oBefore'
			}
		},
		data() {
			return {
				value: '',
				share: false,
			}
		},
		methods: {
			edit() {
				this.$refs['shareRef'].open('edit', this.type=="oAfter" ? this.form.table : this.form)
			},
			async editPeople(e) {
				if (+e >=0 ) {
					if (this.type == 'oAfter') {
						await this.beg.request({
							url: `${this.api.cPeople}/${this.form.table.id}`,
							method: 'POST',
							data: {
								people: +e
							}
						})
						this.$refs['shareRef'].close()
						this.$emit('fetchData')
					} else {
						await this.beg.request({
							url: `${this.api.inTabel}/${this.form.id}`,
							method: 'PUT',
							data: {
								people: +e
							}
						})
						this.$refs['shareRef'].close()
						this.$emit('getTableInfo')
					}
				} else {
					this.$refs['shareRef'].close()
					uni.$u.toast('请输入就餐人数！');
				}
			},
			back() {
				uni.reLaunch({
					url: '/pages/home/index?current=1'
				})
			},
			search() {
				this.$emit('search', this.value)
			},
			clear(){
				this.value = ''
				this.$emit('search', this.value)
			},
		}
	}
</script>

<style lang="scss" scoped>
	.top {
		height: 7.8125vh;
		background: #4275F4;

		.top_l {
			width: 500px;
		}

		.num {
			padding: 5px 8px;
			border-radius: 5px;
			background: rgba(109,151,255, .9);	
			color: #f9f9f9;
		}

		.search /deep/.u-input {
			padding: 3px 9px !important;
			background: rgba(109,151,255, .9) !important;

			.uni-input-input {
				color: #fff;
			}
		}
	}
	@media (min-width: 1500px) and (max-width: 3280px) {
		.top {
			height: 55px;
		}
	}
</style>
<template>
	<view>
		<u-popup :show="isVip" :round="10" :closeable="true" mode="center" @close="close">
			<view class="vip">
				<view class="p15 bd1 wei">选择优惠券</view>
				<view class="p10 v_cont">
					<my-coupon @change='radioChange' @btntap='btntap(v.id)' color='#4275F4' cname='mb30' v-for="(v,i) in tList" :key='i' :co='v.coupon' :v="v" ptype='2'></my-coupon>
				</view>
			</view>
		</u-popup>
		
	</view>
</template>

<script>
	import myCoupon from '@/components/user/my-coupon.vue'
	export default {
		components: {
			myCoupon,
		},
		props: {
			
		},
		data() {
			return {
				isVip:false,
				params: {
					page: 1,
					size: 10,
				},
				tList:[],
				fList:[],
			}
		},
		methods: {
			close() {
				this.isVip = false
			},
			open(v,couponId) {
				let arr = v
				if (couponId) {
					for (let i in arr.true) {
						if (couponId == arr.true[i].id) {
							arr.true[i].checked = !arr.true[i].checked
						} else {
							arr.true[i].checked = false
						}
					}
				}
				this.tList = arr.true
				this.fList = arr.false
				this.isVip = true
			},
			radioChange(e) {
				let arr = this.tList
				for (let i in arr) {
					if (e == arr[i].id) {
						arr[i].checked = !arr[i].checked
					} else {
						arr[i].checked = e == arr[i].id
					}
				}
				let i = arr.find(v => v.checked == true)
				this.$emit('payorder', i)
				this.close()
			},
			async btntap(e) {},
			
			chooseMember(item, index) {
				this.v_index = index
				this.$emit('chooseMember', item)
				this.phone = ''
				this.vipData = []
				this.close()
			},
			async search() {
				if (this.phone) {
					uni.showLoading({
						title: 'loading...'
					})
					let {
						data
					} = await this.beg.request({
						url: this.api.cMember,
						data: {
							keyword: this.phone
						},
					})
					if (data && data.list.length) {
						this.vipData = data.list
					} else {
						uni.$u.toast('暂未查到用户信息');
					}
					uni.hideLoading()
				} else {
					uni.$u.toast('请输入正确的手机号');
				}
			},
			addVip(){
				this.isVip = false
				this.$refs['addUserRef'].open()
			},
			fetchData(){},
		}
	}
</script>

<style lang="scss" scoped>
	.vip {
		width: 36.6032vw;
		height: 71.6145vh;
		overflow: hidden;
		background: #f5f5f5;

		.v_cont {
			overflow-y: scroll;
			height: 53.3854vh;
		}
	}
	@media (min-width: 1500px) and (max-width: 3280px) {
		.vip {
			width: 500px;
			height: 550px;
			.v_cont {
				height: 410px;
			}
		}
	}
</style>
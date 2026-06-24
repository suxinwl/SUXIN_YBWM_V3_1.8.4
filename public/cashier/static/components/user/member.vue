<template>
	<view>
		<u-popup :show="isVip" :round="10" :closeable="true" mode="center" @close="close">
			<view class="vip f-y-bt">
				<view class="p15 bd1 wei">会员选择</view>
				<view class="f-1 p-10-15 f-y-bt">
					<!-- <view class="f-bt mb15">
						<view class="mr15 f-1 dfa">
							<u--input placeholder="可查询会员账号/手机号/昵称" border="surround" v-model="phone" type="number"></u--input>
							<u-button color="#4275F4" text="查询" style="width:150px;color:#000"
								@click="search"></u-button>
						</view>
						<u-button plain color="#FD8906" text="新增会员" style="width:150px" @click="addVip"></u-button>
					</view> -->
					<view class="v_cont">
						<view :class="v_index===index?'is_vitem':''" class="p10 bf5 bs6 mb10 mr15 flex" style="margin-right: 0"
							v-for="(item,index) in vipData" :key="index" @click="chooseMember(item,index)">
							<u-avatar :src="item.avatar" size="45"></u-avatar>
							<view class="f-1 ml10">
								<view class="f18 wei6 mb10">{{item.nickname}}
									<text class="f14 c9 pl10 wei5">(ID:{{item.id}})</text>
								</view>
								<view class="f14 c6">手机号：{{item.mobile}}</view>
								<!-- <view class="f14 c6">余额：{{item.account && item.account.balance}}</view> -->
							</view>
						</view>
					</view>
				</view>
			</view>
		</u-popup>
		<addUser ref="addUserRef" @fetchData="fetchData"></addUser>
	</view>
</template>

<script>
	import addUser from '@/components/user/addUser.vue';
	export default {
		components: {
			addUser,
		},
		props: {
			
		},
		data() {
			return {
				isVip:false,
				v_index: '',
				phone: '',
				vipData:[],
			}
		},
		methods: {
			close() {
				this.isVip = false
			},
			open(v) {
				if(v) this.vipData = v
				this.isVip = true
			},
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
		width: 32.9428vw;
		height: 71.6145vh;
		overflow: hidden;

		.v_cont {
			overflow-y: scroll;
			height: 53.3854vh;

			.v_item {
				// display: inline-flex;
				// justify-content: space-between;
				// align-items: flex-start;
				// width: 20.1317vw;
			}

			.is_vitem {
				// background: #4275F4;
			}
		}
	}
	@media (min-width: 1500px) and (max-width: 3280px) {
		.vip {
			width: 450px;
			height: 550px;
			.v_cont {
				height: 410px;
			
				.v_item {
					width: 275px;
				}
			}
		}
	}
</style>
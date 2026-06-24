<template>
	<view class="user">
		<!-- <view v-if="vipInfo && vipInfo.id" class="user_cont f-x-bt  bs6 p10">
			<view class="f-1 dfa pr10">
				<u-avatar :src="vipInfo.avatar" size="50"></u-avatar>
				<view class="ml10 f-y-bt f12">
					<view class="dfa mb10 f16">
						<view class="nowrap" style="max-width: 80px;">{{vipInfo.nickname}}</view>
						<view class="grade f-c-c f12">{{vipInfo.vip && vipInfo.vip.name}}</view>
					</view>
					<view class="mb10 f12">{{vipInfo.mobile}}</view>
					<view>
						<text class="pr10">余额：{{vipInfo.account && vipInfo.account.balance}}</text>
						<text class="pr10">积分：{{vipInfo.account && vipInfo.account.integral}}</text>
					</view>
				</view>
			</view>
			<view class="dfa">
				<u-button color="#4275F4" size="small" text="更换会员"
					:customStyle="{color:'#000',marginRight:'10px'}" @click="changeVip"></u-button>
				<view class="sk">
					<u-button color="#4275F4" size="small" text="退出" :customStyle="{color:'#000'}"
						@click="outVip"></u-button>
				</view>
			</view>
		</view> -->
		<!-- <view v-else class="user_cont f-x-bt bs6 p10">
			<view class="f-c">
				<u-avatar src="@/static/imgs/avatar.png" size="50"></u-avatar>
				<text class="pl10 f20">散客</text>
			</view>
			<view class="">
				<u-button color="#4275F4" text="会员登录" :customStyle="{color:'#000',height:'35px'}"
					@click="vipLogin"></u-button>
			</view>
		</view> -->
		<view v-if="vipInfo && vipInfo.id" class="flex f-x-bt">
			<view class="f18">
				<text class="iconfont icon-huangguan" style="color:#b5873a;"></text>
				<text class="pl5">{{vipInfo.nickname}}</text>
				<text class="pl5">{{vipInfo.vip && vipInfo.vip.name}}</text>
			</view>
			<view class="flex f18">
				<view @click="outVip" class="mr10" style="color: #4275F4;">退出</view>
				<view @click="clearAll" v-if="mode=='fastOrder'" class="f18">清空</view>
			</view>
		</view>
		<view v-else class="user f-bt f-y-c l-h1">
			<view @click="vipLogin">
				<text class="iconfont icon-huangguan" style="color:#b5873a;"></text>
				<text class="pl5">会员登录</text>
			</view>
			<view>
				<view @click="clearAll" v-if="mode=='fastOrder'" class="f18">清空</view>
			</view>
		</view>
		<member ref="memberRef" @chooseMember="chooseMember" />
		<userNum ref="userNumRef" @changeValue="changeValue" @zc="zc"></userNum>
		<addUser ref="addUserRef" @fetchData="fetchData"></addUser>
	</view>
</template>

<script>
	import member from '@/components/user/member.vue';
	import userNum from '@/components/user/userNum.vue';
	import addUser from '@/components/user/addUser.vue';
	import {
		mapState,
		mapMutations,
	} from 'vuex'
	export default ({
		components: {
			member,
			userNum,
			addUser,
		},
		props: {
			mode: {
				type: String,
				default: 'fastOrder'
			},
		},
		data() {
			return {
				showVip: false,
				vipData: [],
				vipForm: {},
			}
		},
		computed: {
			...mapState({
				vipInfo: state => state.vipInfo,
			}),
		},
		methods: {
			...mapMutations(["setVip"]),
			chooseMember(v) {
				this.setVip(v)
				this.$emit('rfuser')
			},
			vipLogin() {
				// this.$refs['memberRef'].open()
				this.$refs['userNumRef'].open()
			},
			async changeValue(e){
				if (e) {
					uni.showLoading({
						title: 'loading...'
					})
					let {
						data
					} = await this.beg.request({
						url: this.api.cMember,
						data: {
							keyword: e
						},
					})
					if (data && data.list.length) {
						if(data.list.length>1){
							this.$refs['memberRef'].open(data.list)
						}else{
							this.chooseMember(data.list[0])
						}
						this.$refs['userNumRef'].close()
						this.$emit('rfuser')
					} else {
						uni.$u.toast('暂未查到用户信息');
					}
					uni.hideLoading()
				} else {
					uni.$u.toast('请输入正确的手机号');
				}
			},
			changeVip() {
				this.$refs['memberRef'].open()
			},
			zc(){
				this.$refs['addUserRef'].open()
				this.$refs['userNumRef'].close()
			},
			clearAll() {
				this.$emit('clearAll')
			},
			outVip() {
				this.setVip(null)
				this.$emit('rfuser')
			},
		}
	})
</script>

<style lang="scss" scoped>
	.user_cont {
		height: 194rpx;
		border: 2px solid #4275F4;
		background: #fff6f1;

		.grade {
			margin-left: 10px;
			background: #fff;
			color: #FD8906;
			border: 1px solid #FD8906;
			width: 55px;
		}

		/deep/.ul-button {
			.u-button__text {
				font-size: 18px !important;
			}
		}
	}

	.icon-huangguan {
		font-size: 1.7569vw !important;
	}

	@media (min-width: 1500px) and (max-width: 3280px) {
		.icon-huangguan {
			font-size: 24px !important;
		}
	}
</style>
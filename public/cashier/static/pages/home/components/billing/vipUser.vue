<template>
	<view class="user bd1">
		<view v-if="vipInfo && vipInfo.id" class="user_cont f-x-bt  bs6 p10">
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
		</view>
		<view v-else class="user_cont f-x-bt bs6 p10">
			<view class="f-c">
				<u-avatar src="@/static/imgs/avatar.png" size="50"></u-avatar>
				<text class="pl10 f20">散客</text>
			</view>
			<view class="">
				<u-button color="#4275F4" text="会员登录" :customStyle="{color:'#000',height:'35px'}"
					@click="vipLogin"></u-button>
			</view>
		</view>
		<member ref="memberRef"  @chooseMember="chooseMember"  />
	</view>
</template>

<script>
	import member from '@/components/user/member.vue';
	import {
		mapState,
		mapMutations,
	} from 'vuex'
	export default ({
		components: {
			member,
		},
		data() {
			return {
				showVip: false,
				vipData:[],
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
			chooseMember(v){
				this.setVip(v)
			},
			vipLogin(){
				this.$refs['memberRef'].open()
			},
			changeVip(){
				this.$refs['memberRef'].open()
			},
			outVip(){
				this.setVip(null)
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
</style>
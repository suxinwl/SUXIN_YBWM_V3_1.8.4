<template>
	<view class="h100">
		<view class="main f-1 bf h100 pt20">
			<!-- <view class="tabs p20">
				<u-tabs :list="list1" @click="handTabs" :current="current" lineColor="#4275F4"></u-tabs>
			</view> -->
			<view v-if="vipInfo && vipInfo.id" class="p20" style="padding-top: 0;">
				<userInfo ref="basicRef" :form="form" v-show="current==0" @cMember="cMember" @fetchData="fetchData">
				</userInfo>
				<couponInfo ref="couponRef" :form="form" v-show="current==1"></couponInfo>
				<valueInfo ref="storedRef" :form="form" v-show="current==2"></valueInfo>
				<integralInfo ref="creditsRef" :form="form" v-show="current==3"></integralInfo>
				<cardInfo ref="cardRef" :form="form" v-show="current==4"></cardInfo>
			</view>
			<view class="f-c" v-else>
				<noUser ref="noUserRef" @addMember="addMember" @changeValue="changeValue" @fetchData="fetchData" />
			</view>
		</view>
		<addUser ref="addUserRef" @fetchData="fetchData"></addUser>
		<member ref="memberRef" @chooseMember="chooseMember" />
	</view>
</template>

<script>
	import {
		mapState,
		mapMutations,
	} from 'vuex'
	import noUser from '../components/member/noUser.vue'
	import addUser from '@/components/user/addUser.vue';
	import member from '@/components/user/member.vue';
	import userInfo from '../components/member/userInfo.vue';
	import couponInfo from '../components/member/couponInfo.vue';
	import valueInfo from '../components/member/valueInfo.vue';
	import integralInfo from '../components/member/integralInfo.vue';
	import cardInfo from '../components/member/cardInfo.vue';
	export default {
		components: {
			noUser,
			userInfo,
			addUser,
			member,
			couponInfo,
			valueInfo,
			integralInfo,
			cardInfo,
		},
		data() {
			return {
				list1: [{
					name: '会员信息',
					value: 'userInfo',
				}, {
					name: '优惠券信息',
					value: 'couponInfo',
				}, {
					name: '储值信息',
					value: 'valueInfo',
				}, {
					name: '积分信息',
					value: 'integralInfo',
				}, {
					name: '会员卡信息',
					value: 'cardInfo',
				}],
				current: 0,
				form: {},
			}
		},
		computed: {
			...mapState({
				vipInfo: state => state.vipUserInfo,
				role: state => state.user.roleData || [],
			}),
		},
		methods: {
			...mapMutations(["setUserVip"]),
			...mapMutations(["setConfig"]),
			init() {
				this.fetchData()
				this.getReasonConfig()
				if(!this.vipInfo && !this.vipInfo.id){
					this.$refs['noUserRef'].open()
				}
			},
			async getReasonConfig() {
				let {
					data
				} = await this.beg.request({
					url: this.api.config,
					data: {
						ident: 'reasonConfig'
					}
				})
				this.setConfig({
					name: 'reasonConfig',
					data,
				})
			},
			handTab(v, i) {
				this.curr = i
			},
			addMember() {
				this.$refs['addUserRef'].open('addMember')
			},
			async fetchData() {
				if (this.vipInfo && this.vipInfo.id) {
					let {
						data
					} = await this.beg.request({
						url: `${this.api.cMember}/${this.vipInfo.id}`,
					})
					this.form = data ? data : {}
					if (this.current == 0) {
						this.$refs["basicRef"].fetchData();
					} else if (this.current == 1) {
						this.$refs["couponRef"].fetchData();
					} else if (this.current == 2) {
						this.$refs["storedRef"].fetchData();
					} else if (this.current == 3) {
						this.$refs["creditsRef"].fetchData();
					} else if (this.current == 4) {
						this.$refs["cardRef"].fetchData();
					}
				}
			},
			async changeValue(e) {
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
						if (data.list.length > 1) {
							this.$refs['memberRef'].open(data.list)
						} else {
							this.chooseMember(data.list[0])
							this.current = 0
							this.fetchData()
						}
					} else {
						uni.$u.toast('暂未查到用户信息');
					}
					uni.hideLoading()
				} else {
					uni.$u.toast('请输入正确的手机号');
				}
			},
			chooseMember(v) {
				this.setUserVip(v)
				this.current = 0
				this.fetchData()
			},
			cMember() {
				this.setUserVip({})
			},
			handTabs(e) {
				if (this.vipInfo && this.vipInfo.id) {
					this.current = e.index
					if (e.index == 0) {
						this.$refs["basicRef"].fetchData();
					} else if (e.index == 1) {
						this.$refs["couponRef"].fetchData();
					} else if (e.index == 2) {
						this.$refs["storedRef"].fetchData();
					} else if (e.index == 3) {
						this.$refs["creditsRef"].fetchData();
					} else if (e.index == 4) {
						this.$refs["cardRef"].fetchData();
					}
				}
			},
		}
	}
</script>

<style lang="scss" scoped>
	.tabs {
		.tab_i {
			display: inline-block;
			background: #f5f5f5;
			border: 1px solid #e5e5e5;
		}

		.curr {
			background: #4275F4;
			color: #000;
			border: 1px solid #4275F4;
		}
	}
</style>
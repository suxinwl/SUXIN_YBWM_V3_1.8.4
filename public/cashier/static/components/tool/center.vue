<template>
	<u-overlay :show="isCenter" @click="close">
		<view class="typer f-y-bt bf bs10 f18" @tap.stop>
			<view class="f-x-bt p20 bd1">
				<view class="f22">个人中心</view>
				<view class="dfa">
					 <!-- #ifdef H5 -->
					<u-button text="退出全屏" v-if="isqp" type="primary" :plain="true" color="#4275F4"
						customStyle="width:80px;margin-right:10px;border-radius:6px" @click="exitFullScreen"></u-button>
					<u-button text="全屏" v-else type="primary" :plain="true" color="#4275F4"
						customStyle="width:80px;margin-right:10px;border-radius:6px" @click="openFullScreen"></u-button>
					<!-- #endif -->
					<u-button text="退出登录" type="primary" :plain="true" color="#4275F4"
						customStyle="width:80px;border-radius:6px" @click="show=true"></u-button>
				</view>
			</view>
			<view class="f-1 p20 f16">
				<view class="mb10 l-h1 f-y-c" @click="toShop">
					<view class="iconfont icon-dianpufill c9" style="font-size: 22px;"></view>
					<view class="f24 c0 pl10 wei">{{store && store.name}}</view>
					<u-icon size="18" color="#000" name="arrow-right"></u-icon>
				</view>
				<!-- <view class="f-sh mb10">
					<text class="iconfont icon-dianpufill c9" style="font-size: 22px;color:#4275F4"></text>
					<text class="f14 c0 pl10 pr15">美团</text>
					<text class="iconfont icon-shenghuodazhongdianping" style="font-size: 20px;color: #ff6525;"></text>
					<text class="f14 c0 pl10">大众点评</text>
				</view> -->
				<view class="dfbc mb15">
					<view class="">
						<view class="f24 wei6 mb10">{{user && user.nickname}}</view>
						<view class="f14 c9">{{user && user.mobile}}</view>
					</view>
					<u-avatar :src="storeInfo && storeInfo.applyImage" :size="70"></u-avatar>
				</view>
				<view class="dfac mb20" v-if="handOver && handOver.id">
					<!-- <u-button color="#4275F4" size="large" :customStyle="{marginRight:'10px'}">
						<text class="f18">开钱箱</text></u-button> -->
					<u-button color="#4275F4" size="large" :customStyle="{}" @click="goRec">
						<text class="f18">去交班</text></u-button>
				</view>
				<view class="dfac mb20" v-else>
					<u-button color="#4275F4" size="large" :customStyle="{}" @click="founding">
						<text class="f18">开班</text></u-button>
				</view>
				<u-cell-group :border="false">
					<!-- <u-cell class="mb15" size="large" isLink :border="false">
						<view slot="title" class="u-slot-title">
							<text class="iconfont icon-cloud-upload-full c9" style="font-size: 24px;"></text>
							<text class="u-cell-text f20 c6 pl15">数据更新</text>
						</view>
					</u-cell> -->
					<u-cell class="mb15" size="large" value="版本:1.000" isLink :border="false">
						<view slot="title" class="u-slot-title">
							<text class="iconfont icon-xiajia c9" style="font-size: 24px;"></text>
							<text class="u-cell-text f20 c6 pl15">软件更新</text>
						</view>
					</u-cell>
					<!-- <u-cell class="mb15" size="large" value="IP地址:192.168.2.116" :border="false">
						<view slot="title" class="u-slot-title">
							<text class="iconfont icon-dakaixinxi c9" style="font-size: 24px;"></text>
							<text class="u-cell-text f20 c6 pl15">收银机信息</text>
						</view>
					</u-cell> -->
					<!-- <u-cell class="mb15" size="large" value="美团管家&点餐助手&平板点餐" isLink :border="false">
						<view slot="title" class="u-slot-title">
							<text class="iconfont icon-data-download-full c9" style="font-size: 24px;"></text>
							<text class="u-cell-text f20 c6 pl15">APP下载</text>
						</view>
					</u-cell> -->
					<u-cell class="mb15" size="large" isLink :border="false">
						<view slot="title" class="u-slot-title">
							<text class="iconfont icon-kefu c9" style="font-size: 24px;"></text>
							<text class="u-cell-text f20 c6 pl15">在线客服</text>
						</view>
					</u-cell>
				</u-cell-group>
				<view class="f-c mt15 c9 f16">7*24小时服务，点击在线客服开启咨询</view>
				<view class="f-c mt15 c9 f16" @click="showClear = true">清除缓存</view>
			</view>
			<u-modal :show="show" :showCancelButton="true" :buttonReverse="false" confirmColor="#000" confirmText="退出"
				cancelText="取消" width="300px" title="" content='确定退出登录吗?' @cancel="show=false"
				@confirm="cancel"></u-modal>
			<u-modal :show="showRec" :showCancelButton="true" :buttonReverse="false" confirmColor="#000"
				confirmText="确认交班" cancelText="取消" width="300px" title="" content='确定交班并退出登录吗?'
				@cancel="showRec = false" @confirm="cancel"></u-modal>
			<u-modal :show="showClear" :showCancelButton="true" :buttonReverse="false" confirmColor="#000"
				confirmText="清除缓存" cancelText="取消" width="300px" title="" content='确定清除缓存并退出登录吗?'
				@cancel="showClear = false" @confirm="conClear"></u-modal>
		</view>
	</u-overlay>
</template>

<script>
	import {
		mapState,
		mapMutations,
	} from 'vuex'
	export default {
		props: {

		},
		computed: {
			...mapState({
				storeInfo: state => state.storeInfo,
				store: state => state.store,
				handOver: state => state.handOver,
				user: state => state.user,
			}),
		},
		data() {
			return {
				isCenter: false,
				show: false,
				showRec: false,
				showClear: false,
				isqp:false,
			}
		},
		methods: {
			...mapMutations(["setHandOver","setUserVip","setVip"]),
			...mapMutations(["setStoreId"]),
			...mapMutations(["setSiteroot"]),
			open() {
				this.isCenter = true
			},
			close() {
				this.isCenter = false
			},
			goRec() {
				uni.navigateTo({
					url: `/pages/handover/handOver?id=${this.handOver.id}&state=${this.handOver.state}`
				})
				// this.showRec = true
			},
			toShop() {
				this.setStoreId('')
				uni.reLaunch({
					url: '/pages/login/selectShop'
				})
			},
			async founding() {
				let {
					data,
					code,
					msg,
				} = await this.beg.request({
					url: this.api.handOver,
					method: 'POST'
				})
				uni.$u.toast(msg)
				if (data) {
					this.setHandOver(data)
				}
				if (code && code == 200) {
					this.close()
				}
			},
			cancel() {
				this.show = false
				this.showRec = false
				uni.removeStorageSync('token')
				uni.removeStorageSync('storeId')
				this.setHandOver('')
				this.setUserVip({})
				this.setVip({})
				uni.removeStorageSync('handOver')
				uni.reLaunch({
					url: `/pages/login/index`
				})
			},
			openFullScreen() {
				var doc = document.documentElement;
				if (doc.requestFullscreen) {
					doc.requestFullscreen();
				} else if (doc.msRequestFullscreen) {
					doc.msRequestFullscreen(); 
				} else if (doc.webkitRequestFullScreen) {
					doc.webkitRequestFullScreen(); 
				} else if (doc.mozRequestFullScreen) {
					doc.mozRequestFullScreen(); 
				}
				this.isqp = true
			},
			exitFullScreen() {
			    var doc = document;
			    if(doc.exitFullscreen) {
			        doc.exitFullscreen();
			    } else if(doc.msExitFullscreen) {
			        doc.msExitFullscreen(); 
			    } else if(doc.webkitExitFullscreen) {
			        doc.webkitExitFullscreen(); 
			    } else if(doc.mozCancelFullScreen) {
			        doc.mozCancelFullScreen(); 
			    }
				this.isqp = false
			},
			conClear(){
				this.showClear = false
				this.isCenter = false
				uni.removeStorageSync('token')
				uni.removeStorageSync('storeId')
				uni.removeStorageSync('siteroot')
				uni.removeStorageSync('store')
				uni.removeStorageSync('user_info')
				this.setSiteroot('')
				uni.reLaunch({
					url: `/pages/login/index`
				})
			},
		}
	}
</script>

<style lang="scss" scoped>
	.typer {
		position: fixed;
		right: 0;
		width: 500px;
		height: 100vh;
		overflow: scroll;

		/deep/.u-cell__body--large {
			padding-left: 0;
			padding-right: 0;
		}

		/deep/.u-cell__value--large {
			color: #999;
			font-size: 16px;
		}
	}

	/deep/.u-modal {
		.u-modal__content__text {
			padding: 10px 0;
			font-size: 18px;
			color: #000;
		}

		.u-modal__button-group__wrapper {
			span {
				font-size: 18px;
			}
		}

		.u-modal__button-group__wrapper--confirm {
			background: #4275F4;

			span {
				color: #fff;
			}
		}
	}
</style>
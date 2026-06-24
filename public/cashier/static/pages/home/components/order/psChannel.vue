<template>
	<u-popup :show="fqps" :safeAreaInsetBottom="false" round="10" @close="fqps=false" @open="fqps=true" mode="center">
		<view class="psw">
			<view class="flex p-10-0">
				<view class="f-g-0">配送地址</view>
				<view class="ml10 mr15" v-if="rows.address">
					<view class="wei">
						{{ rows.address.address }} {{ rows.address.description }}
					</view>
					<view class="mt5 f12">
						{{ rows.address.contact }}（{{ rows.address.call }}）{{
				              rows.address.mobile
				            }}
					</view>
				</view>
			</view>
			<view class="flex p-10-0">
				<view class="f-g-0">配送方式</view>
				<view class="ml10 mr15 f-g-1">
					<u-radio-group v-model="psform.deliveryType" @change="changeType" placement="column"
						iconColor="#fff" labelSize='14'>
						<view class="mb5" v-if="rows.deliveryStoreRule && rows.deliveryStoreRule.deliveryType==1">
							<u-radio activeColor="#4275F4" label="平台配送" :name="1"></u-radio>
						</view>
						<view class="mb5"><u-radio activeColor="#4275F4" label="门店自配送" :name="2"></u-radio></view>
					</u-radio-group>
				</view>
			</view>
			<view class="flex p-10-0">
				<view class="f-g-0">配送渠道</view>
				<view class="ml10 mr15 f-g-1">
					<u-radio-group v-model="psform.channel" placement="column" labelSize='14' iconColor="#fff">
						<view class="mb5" v-if="psform.deliveryType == 2">
							<u-radio activeColor="#4275F4" label="门店自行配送" :name="0"></u-radio>
						</view>
						<view class="mb5"
							v-if="fhChannel.includes(1) && (rows.deliveryStoreRule && rows.deliveryStoreRule.deliveryType==2 || psform.deliveryType==1)">
							<u-radio activeColor="#4275F4" :label="mytName" :name="1"></u-radio>
						</view>
						<view class="mb5"
							v-if="fhChannel.includes(2) && (rows.deliveryStoreRule && rows.deliveryStoreRule.deliveryType==2 || psform.deliveryType==1)">
							<u-radio activeColor="#4275F4" :label="mKName" :name="2"></u-radio>
						</view>
						<view class="mb5" v-if="psform.deliveryType == 2 && fhChannel.includes(3)"><u-radio
								activeColor="#4275F4" :label="wsbName" :name="3"></u-radio>
						</view>
					</u-radio-group>
				</view>
			</view>
			<view class="mt20">
				<u-button type="primary" color="#4275F4" customStyle="color:'#fff'" @click="save"><text
						class="cf">确定</text></u-button>
			</view>
		</view>
	</u-popup>
</template>

<script>
	import keybored from '@/components/liujto-keyboard/keybored.vue';
	import {
		mapState,
	} from 'vuex'
	export default {
		props: {

		},
		components: {
			keybored,
		},
		data() {
			return {
				fqps: false,
				title: '发起配送',
				psform: {
					deliveryType: 1,
					channel: 1,
				},
				storeInfo: {},
				rows: {},
				mytName: "麦芽田聚合配送",
				mKName: "码科配送",
				wsbName: "外送帮",
				fhChannel: [],
			}
		},
		computed: {
			...mapState({
				reasonConfig: state => state.config.reasonConfig,
			}),
		},
		methods: {
			open(row) {
				let {
					deliverySetting
				} = uni.getStorageSync('setInfo')
				this.mytName = deliverySetting.appId || "麦芽田聚合配送";
				this.mKName = deliverySetting.appId2 || "码科配送";
				this.wsbName = deliverySetting.wsbName || "外送帮";
				let info = uni.getStorageSync('storeInfo')
				if (info) {
					this.storeInfo = info;
				}
				if (row.deliveryStoreRule.channel.includes(1)) {
					this.psform.channel = 1;
				} else if (row.deliveryStoreRule.channel.includes(2)) {
					this.psform.channel = 2;
				} else if (row.deliveryStoreRule.channel.includes(3)) {
					this.psform.channel = 3;
				} else {
					this.psform.channel = null;
				}
				this.psform.deliveryType = row.deliveryType
				this.fhChannel = row.deliveryStoreRule && row.deliveryStoreRule.channel
				this.rows = row;
				this.fqps = true
			},
			close() {
				this.desc = ''
				this.fqps = false
			},
			async save() {
				if (!this.psform.channel && String(this.psform.channel) !== '0') {
					return uni.$u.toast('请选择配送渠道')
				}
				let {
					msg
				} = await this.beg.request({
					url: `${this.api.delivery}/${this.rows.id}`,
					method: "POST",
					data: this.psform,
				})
				this.$emit('psChannel')
				uni.$u.toast(msg)
				this.fqps = false
			},
		}
	}
</script>

<style lang="scss" scoped>
	/deep/.u-transition {
		background-color: rgba(0, 0, 0, 0.1) !important;
	}

	/deep/.u-modal {
		.u-modal__content {
			justify-content: flex-start;
		}

		.u-modal__button-group__wrapper__text {
			color: #000 !important;
		}
	}

	.psw {
		padding: 30rpx;
		font-size: 28rpx;
		width: 650rpx;
	}
</style>
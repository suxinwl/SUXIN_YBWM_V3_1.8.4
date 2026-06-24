<template>
	<view class="">
		<u-popup :show="shows" :round="10" mode="center">
			<view class="cash">
				<view class="f-c f-y-c pt20">
					<view class="tac wei6 f24">会员注册</view>
				</view>
				<view class="p20">
					<u--form class="mb10" ref="addRef" :model="addForm" :labelWidth="100" :rules="addRules"
						:labelStyle="{fontSize:`${pc?'18px':'1.3177vw'}`,textAlign:'right'}">
						<u-form-item label="" prop="name" ref="item1">
							<view class="f-bt f-y-c">
								<view class="" style="width:230px">
									<u--input v-model="addForm.realname" border="surround" placeholder="请输入姓名(必填)"
										:customStyle="{height: '42px'}"></u--input>
								</view>
								<view class="flex">
									<view v-for="(v, i) in sexList" :key="i" class="sexv f-c f20 ml10"
										:class="svcurr==v.value ? 'sv' :''" @click="handVal(v)">{{v.text}}</view>
									<!-- <u-radio-group v-model="addForm.sex" placement="row" size="20" activeColor="#4275F4"
										iconColor="#fff" iconSize="18">
										<u-radio :customStyle="{marginRight: '15px'}" v-for="(item, index) in sexList"
											:key="index" :label="item.text" :name="item.value" labelSize="18">
										</u-radio>
									</u-radio-group> -->
								</view>
							</view>
						</u-form-item>
						<u-form-item label="" prop="mobile" ref="item1">
							<view class="" style="width:400px">
								<u--input v-model="addForm.mobile" border="surround" type="number"
									placeholder="请输入手机号(必填)" :customStyle="{height: '42px'}"></u--input>
							</view>
						</u-form-item>
						<!-- <u-form-item label="" prop="sex" ref="item1">
							<view class="" style="width:230px">
								<u-radio-group v-model="addForm.sex" placement="row" size="20" activeColor="#4275F4"
									iconColor="#fff" iconSize="18">
									<u-radio :customStyle="{marginRight: '15px'}" v-for="(item, index) in sexList"
										:key="index" :label="item.text" :name="item.value" labelSize="18">
									</u-radio>
								</u-radio-group>
							</view>
						</u-form-item> -->
						<u-form-item label="" prop="mobile" ref="item1">
							<view class="" style="width:400px">
								<uni-data-select v-model="addForm.vipId" :localdata="channels" placeholder="请选择会员等级"
									@change="handDiningType"></uni-data-select>
							</view>
						</u-form-item>
						<u-form-item label="" prop="birthday" ref="item1">
							<view class="f-1" style="width:400px" @click="show = true">
								<u--input v-model="addForm.birthday" border="surround" disabled="disabled"
									placeholder="请选择生日" :customStyle="{height: '42px'}"></u--input>
							</view>
							<!-- <view class="f18 c6" style="width:230px" @click="show = true">
								{{addForm.birthday || '请选择生日'}}
							</view> -->
						</u-form-item>
					</u--form>
					<view class="f-1 f-y-e mt30">
						<u-button @click="close" class="mr20 qx">取消</u-button>
						<u-button color="#4275F4" @click="sureAdd"><text class="cf">确认注册</text></u-button>
					</view>
				</view>
			</view>
			<u-datetime-picker :show="show" v-model="value1" mode="date" @confirm="maskClick" @cancel="show = false"
				confirmColor="#4275F4" :minDate="23592663"></u-datetime-picker>
		</u-popup>
	</view>
</template>

<script>
	import {
		sj,
	} from "@/common/handutil.js"
	import {
		mapState,
		mapMutations,
	} from 'vuex'
	export default {
		props: {
			isVip: {
				type: Boolean,
				default: false
			},
		},
		data() {
			return {
				shows: false,
				sexList: [{
					value: 1,
					text: "男"
				}, {
					value: 2,
					text: "女"
				}],
				svcurr: 1,
				addRules: {
					mobile: [{
						required: true,
						message: '手机号不能为空',
						trigger: ['blur', 'change'],
					}, {
						
						message: '手机号码不正确',
						trigger: ['blur'],
					}],
				},
				addForm: {
					mobile: "",
					realname: "",
					sex: 1,
					birthday: '',
					vipId:'',
				},
				show: false,
				value1: Number(new Date()),
				t:'',
				channels: [],
			}
		},
		methods: {
			...mapMutations(["setUserVip"]),
			open(t) {
				if(t) this.t = t
				this.getVipList()
				this.shows = true
			},
			maskClick(e) {
				let date = e.value
				this.addForm.birthday = this.timestampToTime(date)
				this.show = false
			},
			handVal(v) {
				this.svcurr = v.value
				this.addForm.sex = v.value
			},
			timestampToTime(timestamp) {
				var date = new Date(timestamp);
				var Y = date.getFullYear() + "-";
				var M =
					(date.getMonth() + 1 < 10 ?
						"0" + (date.getMonth() + 1) :
						date.getMonth() + 1) + "-";
				var D = (date.getDate() < 10 ? "0" + date.getDate() : date.getDate()) + " ";
				var h = date.getHours() + ":";
				var m = date.getMinutes() + ":";
				var s = date.getSeconds();
				return Y + M + D;
			},
			sureAdd() {
				this.$refs.addRef.validate().then(async res => {
					let that = this
					this.addForm.nickname = `用户_${sj()}`;
					let {
						msg,
						data,
					} = await this.beg.request({
						url: this.api.cMember,
						method: 'POST',
						data: this.addForm
					})
					if(data && this.t=='addMember'){
						this.setUserVip(data)
					}
					that.$emit('fetchData')
					uni.$u.toast(msg)
					this.showState = false
					this.close()
				})
			},
			close() {
				this.shows = false
				this.addForm = {}

			},
			async getVipList(){
				let {
					data: {
						list,
					},
				} = await this.beg.request({
					url: this.api.vipList
				})
				this.channels = list ? list : []
				this.channels.forEach((v) => {
					v.value = v.id
					v.text = `${v.name}(VIP${v.level})`
				})
			},
			handDiningType(e) {
				this.addForm.vipId = e
			},
		}
	}
</script>

<style lang="scss" scoped>
	.cash {
		width: 450px;

		.sexv {
			width: 75px;
			height: 42px;
			background: #EDEDED;
			color: #000;
			border-radius: 5px;
		}

		.sv {
			background: #4275F4;
			color: #fff;
		}
	}
</style>
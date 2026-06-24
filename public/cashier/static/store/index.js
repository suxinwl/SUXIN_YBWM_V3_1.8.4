import Vue from 'vue'
import Vuex from 'vuex'
import api from '@/api';
import beg from '@/common/request';
Vue.use(Vuex)


const store = new Vuex.Store({
	state: {
		user: uni.getStorageSync('user_info'),
		token: '',
		isLogin: false,
		storeInfo: uni.getStorageSync('storeInfo'),
		storeId: uni.getStorageSync('storeId'),
		store: uni.getStorageSync('store'),
		vipInfo: uni.getStorageSync('vipInfo'),
		vipUserInfo: uni.getStorageSync('vipUserInfo'),
		siteroot: uni.getStorageSync('siteroot'),
		config: {
			reasonConfig: {},
			cashieSetting: {},
		},
		handOver:uni.getStorageSync('handOver'),
	},
	mutations: {
		setUser(state, data) {
			uni.setStorageSync('userId', data.id)
			uni.setStorageSync('user_info', data)
			state.user = data
			if (data.mobile) state.isLogin = true
		},
		setToken(state, data) {
			uni.setStorageSync('token', data)
			state.token = data
		},
		setStoreInfo(state, data) {
			uni.setStorageSync('storeInfo', data)
			uni.setStorageSync('uniacid', data.id)
			state.storeInfo = data
		},
		setStoreId(state, data) {
			uni.setStorageSync('store', data)
			uni.setStorageSync('storeId', data.id)
			state.store = data
			state.storeId = data.id
		},
		setVip(state, data) {
			uni.setStorageSync('vipInfo', data)
			state.vipInfo = data
		},
		setUserVip(state, data) {
			uni.setStorageSync('vipUserInfo', data)
			state.vipUserInfo = data
		},
		setSiteroot(state, data) {
			uni.setStorageSync('siteroot', data)
			state.siteroot = data
		},
		setConfig(state, data) {
			state.config[data.name] = data.data
		},
		setHandOver(state, data) {
			uni.setStorageSync('handOver', data)
			state.handOver = data
		},
	},
	actions: {
		async getLogin({
			commit,
			state
		}, params = {}) {
			return await new Promise(async (resolve, reject) => {
				let res = await beg.request({
					'url': params.type == 1 ? api.mobelLogin : api.login,
					method: 'POST',
					data: params
				})
				if (res.code == 200) {
					if (res?.data?.token) {
						commit('setToken', res.data.token)
						if (res.data && res.data.user_info) {
							commit('setUser', res.data.user_info)
							resolve()
							if (res.data.user_info.uniacid == 0) {
								uni.reLaunch({
									url: '/pages/login/selectStore'
								})
							} else {
								uni.setStorageSync('uniacid', res.data.user_info.uniacid)
								if (res.data.user_info && res.data.user_info.apply) {
									commit('setStoreInfo', res.data.user_info.apply)
								}
								uni.reLaunch({
									url: '/pages/login/selectShop'
								})
							}
						}
					}
				} else {
					reject()
					uni.showToast({
						title: res.msg,
						icon: "none"
					});
				}
			})
		},
	},
})

export default store
import App from './App'
import store from './store'
import request from '@/common/request';
import api from '@/api';
// import dLoading from '@/uni_modules/d-loading/components/d-loading/d-loading.vue'

// #ifndef VUE3
import Vue from 'vue'
Vue.config.productionTip = false
App.mpType = 'app'
const app = new Vue({
	store,
	...App
})
import uView from '@/uni_modules/uview-ui'
Vue.use(uView)
Vue.prototype.beg = request
Vue.prototype.api = api
// Vue.component('dLoading',dLoading)

app.$mount()
// #endif

// #ifdef VUE3
import {
	createSSRApp
} from 'vue'
export function createApp() {
	const app = createSSRApp(App)
	return {
		app
	}
}
// #endif
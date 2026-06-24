import core from './core';
import set from './seeting.js';
import store from './store.js';
import order from './order.js';
import goods from './goods.js';
let t = '/channel/',
	api = {
		...core,
		...set,
		...store,
		...order,
		...goods,
	};
export default api
export default class Socket {
	constructor(config) {
		this.$config = config
		this.sTask = null
		this.init()
	}
	init() {
		let o = {
			url: `${this.$config.url}?Authorization=${`Bearer ${uni.getStorageSync('token')}`}&uniacid=${uni.getStorageSync('uniacid')}&storeId=${uni.getStorageSync('storeId')}`,
			header: {
				Authorization: `Bearer ${uni.getStorageSync('token')}`,
				uniacid:  uni.getStorageSync('uniacid'),
				storeId: uni.getStorageSync('storeId'),
				appType: 'cashier',
			},
			success: (res) => {
				console.log('创建socket成功', res)
			},
			fail: (err) => {
				console.log('创建socket失败：', err)
			},
		}
		this.sTask = uni.connectSocket(o)
		this._onSocketOpened()
	}

	_reconnect() {
		this.init()
		this.onMessage(this.$cb)
	}

	onMessage(cb) {
		this.$cb = cb
		this.sTask.onMessage(res => {
			if (res.data === 'success') {
			} else {
				const ms = JSON.parse(res.data)
				if (ms.msgType !== 'login') {
					cb(ms)
				}
			}
		})
	}

	_reset() {
		clearTimeout(this._timeOutHeartBeat)
		return this
	}

	_start() {
		this._timeOutHeartBeat = setInterval(() => {
			this.sTask.send({
				data: 'heartbeat',
				success: res => {
					// console.log('心跳检测')
				},
				fail: err => {
					// console.log(err)
					this._reconnect()
				}
			})
		}, 10000)
	}

	_onSocketOpened() {
		this.sTask.onOpen(res => {
			// console.log('连接成功：', res)
			//发送登录信息
			// this.sendMsg('', 'login')
			//心跳检测
			this._reset()._start()
		})
		this.sTask.onClose(res => {
			console.log('连接失败', res)
			const code = res.code
			if (code === 1006)
				console.log('服务未开启')
		})
		this.sTask.onError(res => {
			console.log(res)
		})
	}

	sendMsg(content, type) {
		let message = this.user
		// message.msgType = type
		// message.content = content
		message = JSON.stringify(message)
		console.log('msg:', message)
		this.sTask.send({
			data: message,
			success: res => {
				// console.log('发送成功：',res)
			},
			fail: err => {
				console.log('发送失败：', err)
			}
		})
	}

	close() {
		this._reset()
		uni.closeSocket({
			success: res => {
				console.log(res)
			}
		})
	}
}

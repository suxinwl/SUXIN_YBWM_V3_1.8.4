export function mergeData(obj, bigObj) {
	let initData = JSON.parse(JSON.stringify(obj));
	let list = Object.assign(obj, bigObj);
	let keys = Object.keys(list);
	for (let key of keys) {
		if (!bigObj[key]) {
			bigObj[key] = initData[key];
		}
	}
	return bigObj;
}

export function fuzhi(n) {
	uni.setClipboardData({
		data: n,
		success() {
			uni.showToast({
				title: '澶嶅埗鎴愬姛',
				duration: 2000
			});
		}
	});
}

export function callTel(e) {
	uni.makePhoneCall({
		phoneNumber: e
	});
}

export function sj() {
	let r = Math.floor(Math.random() * 1000000 + 0);
	return r
}

export function deepCopy(e) {
	return JSON.parse(JSON.stringify(e))
}


export function getRoute() {
	let pages = getCurrentPages(),
		currentPage = pages[pages.length - 1],
		url = currentPage.route || currentPage.__route__
	return url
}

export function getPage(i = 2) {
	let pages = getCurrentPages()
	return pages[pages.length - i]
}

export function changeDate(dateA) {
	var dateee = new Date(dateA).toJSON();
	var date = new Date(+new Date(dateee) + 8 * 3600 * 1000)
		.toISOString()
		.replace(/T/g, " ")
		.replace(/\.[\d]{3}Z/, "");
	return date;
}

export async function sB64(f, bs) {
	uni.getFileSystemManager().writeFile({
		filePath: f,
		data: bs,
		encoding: 'base64',
		success: res => {
			uni.saveImageToPhotosAlbum({
				filePath: f,
				success: (ress) => {
					// console.log('success',ress)
				},
				fail: (errr) => {
					console.log('errr', errr)
				}
			})
		},
		fail: err => {
			console.log(err)
		}
	})
}

export function throttle(fn, interval) {
	let enterTime = 0; //触发的时间
	let gapTime = interval || 300; //间隔时间
	return function() {
		let context = this,
			backTime = new Date(); //第一次函数return即触发的时间
		// console.log(this)
		if (backTime - enterTime > gapTime) {
			fn.call(context, arguments);
			enterTime = backTime; //赋值给第一次触发的时间，这样就保存了第二次触发的时间
		}
	}
}

export function playAudo(e) {
	const innerAudioContext = uni.createInnerAudioContext();
	innerAudioContext.src = e;
	innerAudioContext.onCanplay(() => {
		innerAudioContext.play()
	});
	innerAudioContext.onError((res) => {
		console.log(res);
	})
}
var site = {
	version: "1.0",
	// siteroot: "https://ybv3.b-ke.cn",
	siteroot: uni.getStorageSync('siteroot'),
}
if (process.env.NODE_ENV !== 'development') {
	// #ifdef H5
	// site.siteroot = site.siteroot.replace('ybv3.b-ke.cn', location.hostname)
	// #endif
	console.log('produce')
} else {
	console.log('development')
}
module.exports = site
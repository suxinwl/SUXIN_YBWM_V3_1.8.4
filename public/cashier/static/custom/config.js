export default {
	contentType: 'application/x-www-form-urlencoded',
	tokenErrorMessage: function(m) {
		uni.showToast({
			title: m || "请求失败, 请重试",
			icon: "none"
		})
	}
}
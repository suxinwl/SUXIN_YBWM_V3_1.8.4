<script>
	import Vue from 'vue'
	import Socket from "@/common/socket.js"
	export default {
		onLaunch: function() {
			// #ifdef APP-PLUS
				plus.screen.lockOrientation('landscape-primary'); 
				plus.navigator.setFullscreen(true); 
					
			// #endif
			uni.getSystemInfo({
				success: (res)=>{
					// console.log("屏幕尺寸：", res.windowWidth, res.windowHeight)
					Vue.prototype.phone = false
					Vue.prototype.cash = false
					Vue.prototype.pad = false
					Vue.prototype.pc = false
					if(res.windowWidth > 0 && res.windowWidth <=500){
						Vue.prototype.phone = true
					}else if(res.windowWidth > 500 && res.windowWidth <=1150){
						Vue.prototype.pad = true
					}else if(res.windowWidth > 1150 && res.windowWidth <=1500){
						Vue.prototype.cash = true
					}else if(res.windowWidth > 1500 && res.windowWidth <=3280){
						Vue.prototype.pc = true
					}
				}
			});
			this.getSocket()
		},
		onShow: function() {},
		onHide: function() {},
		methods: {
			getSocket(){
				uni.$on('socketInit', this.socketMsg);
				this.createInnerAudio()
				let token = uni.getStorageSync('token'),
					storeId = uni.getStorageSync('storeId'),
					uniacid = uni.getStorageSync('uniacid');
				if(token && storeId && uniacid){
					this.socketMsg()
				}
			},
			createInnerAudio(){
				this.bgAudioMannager = uni.createInnerAudioContext();
				this.bgAudioMannager.title = '订单提醒';
				console.log('rm',this.bgAudioMannager)
			},
			socketMsg(){
				let wsUrl = uni.getStorageSync('siteroot').replace(/(https|http)/,'wss'),
				chatConfig = {}
				chatConfig.url = `${wsUrl}/ws`
				this.socket = new Socket(chatConfig)
				var addNum = 0,
				voiceList = [],
				autoText = this.bgAudioMannager
				console.log('msrm',this.bgAudioMannager)
				this.socket.onMessage((msg) => {
					console.log('ping', msg)
					if(msg.type && msg.type =='voice'){
						console.log('voice',msg)
						var msgs = msg.msg,
							voiceNum = msg.msg.num
						autoText.src = msgs.voiceUrl
						autoText.onCanplay(a => {
							autoText.play()
							// console.log('play',msgs)
						});
						autoText.onEnded((res) =>{
							// console.log('end',voiceNum)
							if(voiceNum>=1){
								autoText.play()
								voiceNum -- 
							}else{
								autoText.destroy()
								autoText = uni.createInnerAudioContext()
							}
						})
						autoText.onError((res) => {
						  console.log('errMsg',res.errMsg);
						  console.log('errCode',res.errCode);
						});
					}
				})
			}
		},
	}
</script>

<style lang="scss">
	/*每个页面公共css */
	@import "@/uni_modules/uview-ui/index.scss";
	@import "./common/icons/iconfont.css";
	@import "./common/icon/iconfont.css";
	@import './common/styles/index.css';
	@import './common/styles/my.css';
	@import "./common/styles/media.css";

	$pc:"(min-width: 1500px) and (max-width: 3280px)"; 
	$cash: "(min-width: 1150px) and (max-width: 1500px)"; 
	$pad: "(min-width: 500px) and (max-width: 1150px)"; 
	$phone: "(min-width: 0px) and (max-width: 500px)"; 

	page {
		width: 100%;
		height: 100vh;
		max-height: 100vh;
		box-sizing: border-box;
	}

	/* #ifdef H5 */
	uni-page-head {
		display: none;
	}

	/* #endif */
</style>
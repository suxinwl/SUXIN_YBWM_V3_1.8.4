<template>
	<view class="f-y-bt h100">
		<setGoods ref="basicRef" v-show="current==0" @fetchData="fetchData" />
		<voiceSet ref="voiceRef" v-show="current==1" @fetchData="fetchData" />	
	</view>
</template>

<script>
	import {
		mapState,
		mapMutations,
	} from 'vuex'
	import setGoods from '../components/setGoods/setGoods.vue';
	import voiceSet from '../components/setGoods/voiceSet.vue';
	export default {
		components: {
			setGoods,
			voiceSet,
		},
		data() {
			return {
				current: 0,
			}
		},
		methods: {
			...mapMutations(["setConfig"]),
			init() {
				this.$refs["basicRef"].fetchData();
			},
			handTabs(e) {
				this.current = e.index
				if (e.index == 0) {
					this.$refs["basicRef"].fetchData();
				} else if (e.index == 1) {
					this.$refs["voiceRef"].fetchData();
				}
			}
		}
	}
</script>

<style lang="scss" scoped>
	
</style>
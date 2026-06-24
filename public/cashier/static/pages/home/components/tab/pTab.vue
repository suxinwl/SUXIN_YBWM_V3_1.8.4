<template>
	<view>
		<callOrderTab v-if="current==2" @handTabs="handTabs"></callOrderTab>
		<recontionTab v-else-if="current==3" @handTabs="handTabs"></recontionTab>
		<orderTab v-else-if="current==4" @handTabs="handTabs"></orderTab>
		<memberTab v-else-if="current==5" @handTabs="handTabs"></memberTab>
		<setTab v-else-if="current==15" @handTabs="handTabs"></setTab>
		<hxTab v-else-if="current==61" @handTabs="handTabs"></hxTab>
	</view>
</template>

<script>
	import callOrderTab from './callOrderTab.vue'
	import recontionTab from './recontionTab.vue'
	import orderTab from './orderTab.vue'
	import memberTab from './memberTab.vue'
	import setTab from './setTab.vue'
	import hxTab from './hxTab.vue'
	export default ({
		components: {
			callOrderTab,
			recontionTab,
			orderTab,
			memberTab,
			setTab,
			hxTab,
		},
		props: {
			current: {
				type: Number,
				default: 0
			}
		},
		data() {
			return {
				
			}
		},
		methods: {
			handTabs(e) {
				this.$emit('handTabs', e)
			},
		}
	})
</script>

<style lang="scss" scoped>
	
</style>
<template>
	<view class="f-y-bt h100">
		<view class="f-1 bf">
			<tradeReport ref="tradeRef" v-if="tabval=='tradeReport'" />
			<goodsReport ref="goodsRef" v-if="tabval=='goodsReport'" />
		</view>
	</view>
</template>

<script>
	import tradeReport from './recontion/tradeReport.vue';
	import goodsReport from './recontion/goodsReport.vue';
	import {
		mapState,
		mapMutations,
	} from 'vuex'
	export default ({
		components: {
			tradeReport,
			goodsReport,
		},
		props: {
			
		},
		data() {
			return {
				tabval:'tradeReport',
			}
		},
		computed: {
			
		},
		methods: {
			init() {
				// this.fetchData()
				this.$refs['tradeRef'].fetchData()
			},
			handTabs(e) {
				this.tabval = e.value
				this.$nextTick(()=> {
					if (e.value == 'tradeReport') {
						this.$refs['tradeRef'].fetchData()
					} else if (e.value == 'goodsReport') {
						this.$refs['goodsRef'].fetchData()
					}
				})
			},
		}
	})
</script>

<style lang="scss" scoped>
	
</style>
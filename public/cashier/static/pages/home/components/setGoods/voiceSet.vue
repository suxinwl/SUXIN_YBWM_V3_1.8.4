<template>
	<view class="right f-1 p15 bf f16 f-y-bt">
		<uni-table ref="table" border stripe emptyText="暂无明细">
			<uni-tr>
				<uni-th align="center">提醒场景</uni-th>
				<uni-th align="center">提示次数</uni-th>
				<uni-th align="center">提示语音</uni-th>
				<uni-th align="center">操作</uni-th>
			</uni-tr>
			<uni-tr v-for="(row, i) in tableData" :key="i">
				<uni-td>
					{{row.name}}
				</uni-td>
				<uni-td>
					<view>
						<u-radio-group v-model="row.num" size="18" iconSize="18" iconColor="#fff" activeColor="#4275F4"
							@change="changeState(row)">
							<u-radio :customStyle="{marginRight: '30px'}" label="提示1次" name="1" />
							<u-radio :customStyle="{marginRight: '30px'}" label="提示3次" name="3" />
							<u-radio :customStyle="{marginRight: '30px'}" label="循环提示" name="999" />
							<u-radio label="不提示" name="0" />
						</u-radio-group>
					</view>
				</uni-td>
				<uni-td align="center">经典女生版</uni-td>
				<uni-td align="center">
					<text class="cf06" style="color: #4275F4;" @click.stop="audition(row)">试听</text>
				</uni-td>
			</uni-tr>
		</uni-table>
		<audio ref="audioRef"></audio>
	</view>
</template>

<script>
	import {
		mapState,
		mapMutations,
	} from 'vuex'
	export default {
		components: {},
		data() {
			return {
				tableData: [],
				audioRef: null,
			}
		},
		methods: {
			...mapMutations(["setConfig"]),

			async fetchData() {
				let {
					data
				} = await this.beg.request({
					url: this.api.voiceMessage
				})
				this.tableData = data ? data : [],
					this.audios = uni.createInnerAudioContext();
			},
			async changeState(e) {
				let {
					msg
				} = await this.beg.request({
					url: `${this.api.voiceMessage}/${e.id}`,
					method: 'PUT',
					data: {
						num: e.num,
						voicType: e.voicType,
						url: e.voiceUrl
					}
				})
				uni.$u.toast(msg)
				this.fetchData()
			},
			audition(row) {
				this.audios.src = row.voiceUrl
				this.audios.onCanplay(a => {
					this.audios.play()
				});
			},
		}
	}
</script>

<style lang="scss" scoped>
	/deep/.uni-table-tr {
	  height: 50px;
	}
</style>
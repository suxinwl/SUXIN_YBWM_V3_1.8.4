<template>
	<view class="f-y-bt h100">
		<view class="top bf f-x-bt p15 bg0 cf f18">
			<view class="dfa top_l">
				<text class="iconfont icon-fanhui cf" style="font-size: 26px;" @click="back"></text>
				<view class="p-0-20">桌台 {{name}}</view>
				<view class="num f16 dfa">人数：{{num}}人
					<view @click="share=true">
						<text class="f15 pl10">修改</text><text class="iconfont icon-youbian f14"></text>
					</view>
				</view>
			</view>
			<view class="f-1 p-5-0" style="padding-right: 100px;">
				<u--input placeholder="菜名/首字母/助记码" border="none" v-model="value"></u--input>
			</view>
			<view class="dfa">
				<text class="ml25 iconfont icon-paiduijiaohao" style="font-size:24px"></text>
				<text class="ml25 iconfont icon-lianxi2hebing_dayin" style="font-size:24px" @click="isType=true"></text>
				<text class="ml25 iconfont icon-xiaoxi" style="font-size:24px" @click="isNotice=true"></text>
				<text class="ml25 iconfont icon-gerenzhongxin-xuanzhong" style="font-size:21px"
					@click="isCenter=true"></text>
			</view>
		</view>
		<view class="f-1 bf f-bt">
			<view class="left br1 f-y-bt f18">
				<view v-if="!showVip" class="user p15 bd1 f-x-bt">
					<view @click="isVip=true">
						<text class="iconfont icon-huangguan" style="color:#b5873a;font-size:24px;"></text>
						<text class="pl10">会员登录</text>
					</view>
					<view @click="clearAll">清空</view>
				</view>
				<view v-else class="user p15 bd1">
					<view class="user_cont f-x-bt  bs6 p10">
						<view v-if="!showVip" class="f-c">
							<u-avatar :src="vipForm.avatar" size="50"></u-avatar>
							<text class="pl10 f20 c0">散客</text>
						</view>
						<view v-else class="f-1 dfa pr10">
							<u-avatar :src="vipForm.avatar" size="50"></u-avatar>
							<view class="ml10 f-y-bt f12">
								<view class="dfa mb10 f16">
									<view class="nowrap" style="max-width: 80px;">{{vipForm.name}}</view>
									<view class="grade f-c-c f12">
										{{vipForm.grade==0?'普通会员':vipForm.grade==1?'大众会员':vipForm.grade==2?'标准会员':vipForm.grade==3?'白银会员':''}}
									</view>
								</view>
								<view class="mb10 f12">{{vipForm.phone}}</view>
								<view>
									<text class="pr10">余额：{{vipForm.balance}}</text>
									<text class="pr10">积分：{{vipForm.integral}}</text>
								</view>
							</view>
						</view>
						<view class="dfa">
							<u-button color="#4275F4" size="small" text="更换会员"
								:customStyle="{color:'#000',marginRight:'10px'}" @click="isVip=true"></u-button>
							<view class="sk">
								<u-button color="#4275F4" size="small" text="退出" :customStyle="{color:'#000'}"
									@click="vipForm={};showVip=false"></u-button>
							</view>
						</view>
					</view>
				</view>
				<view class="f-1 p15">
					<view class="f-x-bt f20 mb10">
						<view class="">结算清单（{{numAll}}）</view>
						<view class="f-c" @click="clearAll">清空</view>
					</view>
					<view class="f-x-bt f20 wei6 mb10">
						<text class="w50">商品信息</text>
						<view class="f-1 f-bt">
							<text>小计</text>
							<text>数量</text>
						</view>
					</view>
					<view class="f-y-bt">
						<view v-if="list&&list.length>0" class="f-1 list" style="overflow-y:auto">
							<uni-table v-if="batch" ref="table" type="selection" emptyText="暂无更多数据"
								@selection-change="selectionChange">
								<uni-tr>
									<uni-th class="f-1 f-x-bt">
										<view class="f-x-bt f18" style="width:303px">
											<view>全选</view>
											<view>已选<text style="padding:0 3px;color:#FD8906">0</text>件</view>
										</view>
									</uni-th>
								</uni-tr>
								<uni-tr :class="selectItem==item?'isSelect':''" class="bd1 p-10-5 f16"
									v-for="(item,index) in list" :key="index" @click="chooseGood(item,index)">
									<uni-td>
										<view class="f-x-bt">
											<u--image v-if="product_show==1||product_show==3" :src="item.img"
												:radius="6" width="60px" height="60px"></u--image>
											<view class="f-1">
												<view class="f-bt mb10">
													<!-- 是否打包 -->
													<view class="f-1 overflowlnr f20">
														<u-tag v-if="item.ispack" class="mr5" text="包" size="small"
															bgColor="#1c9945" borderColor="#1c9945"
															style="display: inline-block;"></u-tag>
														<view class="overflowlnr" style="max-width:260px">{{item.name}}
														</view>
														<!-- 赠菜 -->
														<view v-if="item.isgift" class="i_tag ml5 f16 cf5">赠</view>
														<!-- 优惠 -->
														<view v-if="item.dis_cont" class="i_tag ml5 f16 cf5">
															{{item.dis_cont}}
														</view>
													</view>
													<view class="f18" style="color:#FD8906"
														@click="delItem(item,index)">删除
													</view>
												</view>
												<view class="f-x-bt f20">
													<!-- <text style="width: 100px;">￥{{item.price.toFixed(2)}}</text> -->
													<view class="f-1 f-bt">
														<view v-if="item.isgift" class="dfa">
															<view class="c9 f16" style="text-decoration: line-through;">
																￥{{(item.price*item.num).toFixed(2)}}</view>
															<view class="ml10">￥0.00</view>
														</view>
														<text v-else
															class="c6">￥{{(item.price*item.num).toFixed(2)}}</text>
														<text>x{{item.num}}</text>
													</view>
												</view>
											</view>
										</view>
									</uni-td>
								</uni-tr>
							</uni-table>
							<view v-else :class="actgood==item.id?'isSelect':''" class="bd1 p-10-5 f16"
								v-for="(item,index) in list" :key="index" @click="chooseGood(item,index)">
								<view class="f-x-bt">
									<u--image v-if="product_show==1||product_show==3" class="mr10" :src="item.img"
										:radius="6" width="60px" height="60px"></u--image>
									<view class="f-1">
										<view class="f-bt mb10">
											<view class="f-1 overflowlnr f20 dfa">
												<!-- <u-tag v-if="item.ispack" text="包" size="small" bgColor="#1c9945"
													borderColor="#1c9945" style="display: inline-block;">
												</u-tag> -->
												<!-- 打包 -->
												<u-tag v-if="item.ispack" class="mr5" text="包" size="small"
													bgColor="#1c9945" borderColor="#1c9945"
													style="display: inline-block;font-size: 14px;"></u-tag>
												<view class="overflowlnr" style="max-width:260px">{{item.name}}</view>
												<!-- <view v-if="item.isgift" class="i_tag ml5 f12 cf5">赠</view> -->
												<!-- 赠菜 -->
												<view v-if="item.isgift" class="i_tag ml5 f16 cf5">赠</view>
												<!-- <view v-if="item.dis_cont" class="i_tag ml5 f12 cf5">{{item.dis_cont}}
												</view> -->
												<!-- 优惠 -->
												<view v-if="item.dis_cont" class="i_tag ml5 f16 cf5">{{item.dis_cont}}
												</view>
											</view>
											<view class="f18" style="color:#FD8906" @click="delItem(item,index)">删除
											</view>
										</view>
										<view class="f-x-bt f18">
											<view v-if="item.dis_cont" class="" style="width:100px">
												<view class="c9 f14" style="text-decoration: line-through;">
													￥{{item.old_price.toFixed(2)}}</view>
											</view>
											<!-- <text v-else style="width: 100px;">
												￥{{item.price.toFixed(2)}}</text> 
											<view class="f-1 f-bt">
												<view v-if="item.isgift" class="dfa">
													<view class="">
														￥{{(item.price*(item.num-item.gift_count)).toFixed(2)}}
													</view>
													<view class="c9 f14" style="text-decoration: line-through;">
														￥{{(item.price*item.num).toFixed(2)}}</view>
												</view>
												<text v-else>￥{{(item.price*item.num).toFixed(2)}}</text>
												<text>{{item.num}}</text>
											</view>-->
											<view class="f-1 f-bt">
												<view v-if="item.isgift" class="dfa">
													<view class="c9 f16" style="text-decoration: line-through;">
														￥{{(item.price*item.num).toFixed(2)}}</view>
													<view class="ml10">￥0.00</view>
												</view>
												<text v-else class="c6">￥{{(item.price*item.num).toFixed(2)}}</text>
												<text class="f18 c6">x{{item.num}}</text>
											</view>
										</view>
									</view>
								</view>
								<view v-if="item.remarks.join('，')!=''" class="f15 c9 mt10">
									备注：{{item.remarks.join('，')}}</view>
							</view>
							<view v-if="allNotes" class="c9 f15 mt10">整单备注：{{allNotes}}</view>
						</view>
						<view v-else class="f-1 f-c-c list" style="overflow-y:auto">
							<image src="@/static/imgs/car.png" mode="" style="width: 180px;height:180px"></image>
							<view class="f15" style="color:#c0c4cc">点击右侧商品，选择商品进行结账</view>
						</view>
					</view>

				</view>
				<view class="f-x-bt p15 bd1">
					<view class="">
						<u-checkbox-group v-model="wholePack" @change="allPack">
							<u-checkbox size="22" labelSize="20" iconSize="18" iconColor="#000" activeColor="#4275F4"
								:customStyle="{fontSize:'20px'}" label="整单打包" :name="0">
							</u-checkbox>
						</u-checkbox-group>
					</view>
					<view class="dfa">
						<view class="c9 f20 mr10">共{{numAll}}件</view>
						<view class="f27 cf5 tar">
							<view class="">￥{{moneyAll}}</view>
							<view v-if="coupons>0" class="c9 f18" style="text-decoration: line-through;">￥{{coupons}}
							</view>
						</view>
					</view>
				</view>
				<view class="f-c" style="padding:30px 15px">
					<u-button :customStyle="{color:'#000',width:'175px',height:'70px',marginRight:'15px'}"
						@click="settleAcc">
						<text class="f22 wei6">下单并结账</text></u-button>
					<u-button v-if="!isOrder" color="#4275F4" @click="takeOrder"
						:customStyle="{color:'#000',width:'175px',height:'70px'}">
						<text class="f22 wei6">下单</text></u-button>
					<u-button v-else color="#4275F4" :customStyle="{color:'#000',width:'150px',height:'55px'}"
						@click="addMenu">
						<text class="f18 wei6">加菜</text></u-button>
				</view>
			</view>
			<!-- 操作 -->
			<view class="l_but br1 p-15-10">
				<u-number-box v-model="selectItem.num" :disabled="list.length<1">
					<view slot="minus" class="minus">
						<u-icon name="minus" size="20"></u-icon>
					</view>
					<text slot="input" class="input">{{selectItem.num?selectItem.num:1}}</text>
					<view slot="plus" class="plus">
						<u-icon name="plus" size="20"></u-icon>
					</view>
				</u-number-box>
				<u-button class="mb10" :disabled="list.length<1" @click="remark=true"
					:customStyle="{borderRadius:'6px',border:'1px solid #e6e6e6',color:'#000'}">
					<text class="f20">单品备注</text></u-button>
				<u-button class="mb10" :disabled="list.length<1"
					:customStyle="{borderRadius:'6px',border:'1px solid #e6e6e6',color:'#000'}"
					@click="selectItem.ispack=!selectItem.ispack">
					<text class="f20">{{!selectItem.ispack?'打包':'取消打包'}}</text>
				</u-button>
				<u-button v-if="selectItem.dis_cont" class="mb10 gift" @click="cancelDis" :disabled="list.length<1"
					:customStyle="{borderRadius:'6px',border:'1px solid #e6e6e6',color:'#000'}">
					<text class="f20">{{selectItem.dis_type==0?'取消折扣':'取消减免'}}</text></u-button>
				<u-button v-if="!selectItem.dis_cont" class="mb10 gift" @click="reduce=true" :disabled="list.length<1"
					:customStyle="{borderRadius:'6px',border:'1px solid #e6e6e6',color:'#000'}">
					<text class="f16">商品打折/减免</text></u-button>
				<u-button v-if="selectItem.gift_count>0" class="mb10" @click="cancelGift" :disabled="list.length<1"
					:customStyle="{borderRadius:'6px',border:'1px solid #e6e6e6',color:'#000'}">
					<text class="f20">取消赠菜</text></u-button>
				<u-button v-if="selectItem.gift_count<1" class="mb10" @click="giftDish=true" :disabled="list.length<1"
					:customStyle="{borderRadius:'6px',border:'1px solid #e6e6e6',color:'#000'}">
					<text class="f20">赠菜</text></u-button>
				<u-button class="mb10" @click="saveOrder=true" :disabled="list.length<1"
					:customStyle="{borderRadius:'6px',border:'1px solid #e6e6e6',color:'#000'}">
					<text class="f20">存单</text></u-button>
				<u-button class="mb10" @click="getOrder=true" :disabled="list.length<1"
					:customStyle="{borderRadius:'6px',border:'1px solid #e6e6e6',color:'#000'}">
					<text class="f20">取单</text></u-button>
				<u-button class="mb10" @click="allDesc=true"
					:customStyle="{borderRadius:'6px',border:'1px solid #e6e6e6',color:'#000'}">
					<text class="f20">整单备注</text></u-button>
				<u-button class="mb10" :disabled="list.length<1" @click="batch=!batch"
					:customStyle="{borderRadius:'6px',border:'1px solid #e6e6e6',color:'#000'}">
					<text class="f20">{{!batch?'批量操作':'取消批量'}}</text></u-button>
				<u-button class="mb10" @click="service=true"
					:customStyle="{borderRadius:'6px',border:'1px solid #e6e6e6',color:'#000'}">
					<text class="f20">服务费</text></u-button>
				<u-button class="mb10" @click="share=true"
					:customStyle="{borderRadius:'6px',border:'1px solid #e6e6e6',color:'#000'}">
					<text class="f20">拼桌</text></u-button>
				<u-button class="mb10" @click="turntable"
					:customStyle="{borderRadius:'6px',border:'1px solid #e6e6e6',color:'#000'}">
					<text class="f20">转台</text></u-button>
				<u-button class="mb10" @click="combine"
					:customStyle="{borderRadius:'6px',border:'1px solid #e6e6e6',color:'#000'}">
					<text class="f20">并台</text></u-button>
				<u-button @click="rescind=true"
					:customStyle="{borderRadius:'6px',border:'1px solid #e6e6e6',color:'#000'}">
					<text class="f20">撤台</text></u-button>
			</view>
			<!-- 右侧 -->
			<view v-if="!isOrder" class="right f-1 f-y-bt p-0-15 f18">
				<view class="p-10-0 bd1 f16" style="overflow-x: auto;">
					<view class="p-10-15 mr10 be6 bs10" :class="kind==index?'bffd wei6':''"
						style="display: inline-block;" v-for="(item,index) in kinds" :key="index"
						@click="changeKind(index)">{{item}}</view>
				</view>
				<view class="f-1 f-bt p-15-0">
					<view class="f-1 f-y-bt">
						<view class="f-1 r_cont">
							<view class="r_item p10 bs6" @click="addDish=true">
								<view class="f-c-c" style="width:100%;height:100%;">
									<view class="iconfont icon-jia" style="font-size: 26px;"></view>
									<view class="c3 mt10">添加临时商品</view>
								</view>
							</view>
							<view :class="list.some(v=>v.id==item.id)?'check':''" class="r_item p10 bs6"
								v-for="(item,index) in listData" :key="index" @click="clickItem(item,index)">
								<view class="f-y-bt" style="width:100%;height:100%;">
									<view class="f20 mb5 wordall2">{{item.name}}</view>
									<view class="dfa f20">
										<u--image v-if="product_show==1||product_show==3" class="mr10" :src="item.img"
											:radius="6" shape="square" width="65px" height="65px"></u--image>
										<view>
											<view class="mb5 cf5 f20">￥{{item.price.toFixed(2)}}</view>
											<view v-if="product_show>1">库存：{{item.inventory}}</view>
										</view>
									</view>
									<view v-if="item.num" class="badge"><u-badge type="error" :value="item.num"
											:show="item.num>1"></u-badge></view>
								</view>
							</view>
						</view>
						<view class="pagona mt10" style="height:30px">
							<uni-pagination :total="total" title="标题文字" />
						</view>
					</view>
				</view>
			</view>
			<view v-else class="right f-1 f-bt f18">
				<view class="w55 br1 p15">
					<view class="f-x-bt mb15">
						<text class="f20 wei6">选择优惠</text>
						<view><u-button text="会员登录" @click="searchMember"></u-button></view>
					</view>
					<view class="f-x-bt mb15">
						<u-button text="整单手动打折/减免"></u-button>
						<u-button text="免单" :customStyle="{margin:'0 10px'}"></u-button>
						<u-button text="不开票9.5折"></u-button>
					</view>
					<view class="mb20 f20 wei6">选择支付方式 <text class="iconfont icon-wenhao wei5 c6 pl10"
							style="font-size: 19px;"></text></view>
					<!-- <view class="f-x-bt mb20">
						<u--input placeholder="请输入支付方式名称" :customStyle="{padding:'1px 9px'}" clearable></u--input>
						<u-button text="搜索" :customStyle="{width:'50px',height:'30px',color:'#4275F4'}"></u-button>
					</view> -->
					<view class="ways">
						<view class="way f-c-c mb50" v-for="(item,index) in ways" :key="index" @click="showItem(index)">
							<image :src="item.img" mode="" style="width:45px;height:45px"></image>
							<view class="f14 mt5">{{item.title}}</view>
						</view>
					</view>
				</view>
				<view class="f-1 p15 f-y-bt">
					<view class="mb15 f18 wei6">选择支付方式</view>
					<view class="f-x-bt mb15">
						<view class="">菜单价格合计</view>
						<view class="f24">￥{{moneyAll}}</view>
					</view>
					<u-divider text="营销优惠" :dashed="true" :textSize="16"></u-divider>
					<view class="f-x-bt m15">
						<view class="">减免金额</view>
						<view class="cf5 f24">-￥{{discounts}}</view>
					</view>
					<u-divider text="支付方式" :dashed="true" :textSize="16"></u-divider>
					<view class="f-x-bt mb10">
						<view class="">现金</view>
						<view class="f24">￥{{form.cash_money}}</view>
					</view>
					<u-divider text="" :dashed="true" :textSize="16"></u-divider>
					<view class="f-x-bt mb10">
						<view class="">需支付</view>
						<view class="f24">￥{{form.cash_money}}</view>
					</view>
					<view class="f-x-bt mb10">
						<view class="">实付</view>
						<view class="f24">￥{{form.cash_money}}</view>
					</view>
					<view class="f-1 r_b" style="display: flex;flex-direction: column;justify-content: flex-end;">
						<u-checkbox-group v-model="invoice" placement="row" activeColor="#4275F4" iconSize="18"
							iconColor="#000" size="22" :labelSize="18">
							<u-checkbox label="打印结账单" :labelSize="18" :name="0"></u-checkbox>
						</u-checkbox-group>
						<u-button class="mt20 mb15" text="" color="#4275F4" :customStyle="{height:'55px'}"><text
								class="wei6 f18">付款完成,确认结账</text></u-button>
					</view>
				</view>
			</view>
		</view>
		<!-- 单品备注 -->
		<remarks :remark="remark" :reson="selectItem.remarks" @closeRemark="remark=false" @itemRemark="itemRemark" />
		<!-- 菜品打折/减免 -->
		<goodsReduce :reduce="reduce" :name="selectItem.name" :price="selectItem.price" @closeReduce="reduce=false"
			@changeMonry="changeMonry" />
		<!-- 赠菜 -->
		<giftDish :giftDish="giftDish" :name="selectItem.name" :num="selectItem.num" @closeDish="giftDish=false"
			@changeNumber="changeNumber" />
		<!-- 整单备注 -->
		<wholenote :allDesc="allDesc" @closeDesc="allDesc=false" @returnRemark="returnRemark" />
		<!-- 团购券先核销 -->
		<u-modal :show="recharge" width="300px" title=" " content="请先开通后再使用" confirmColor="#000"
			@confirm="recharge=false"></u-modal>
		<!-- 服务费 -->
		<serviceCharge :service="service" @closeService="service=false" />
		<!-- 拼桌 -->
		<share :share="share" @closeShare="share=false" />
		<!-- 转台 -->
		<!-- 并台 -->
		<!-- 撤台 -->
		<u-modal :show="rescind" :showCancelButton="true" width="300px" title=" " content="请确认是否需要撤销此桌台？"
			confirmColor="#000" @cancel="rescind=false" @confirm="pullpack"></u-modal>
		<!-- 添加临时菜 -->
		<addDish :addDish="addDish" @closeAdd="addDish=false" />

		<u-toast ref="uToast"></u-toast>
		<member :isVip="isVip" :vipData="vipData" @closeVip="isVip=false" @chooseMember="chooseMember" />
		<scan :isCode="isCode" :moneyAll="moneyAll" @closeCode="isCode=false" />
		<cash :isCash="isCash" :cash_money="form.cash_money" @closeCash="isCash=false" @changeMoney="changeMoney" />
		<vipCard :isCard="isCard" @closeCard="isCard=false" />

		<!-- 个人中心 -->
		<typer :isType="isType" @closeType="isType=false" />
		<notice :isNotice="isNotice" @closeNotice="isNotice=false" />
		<center :isCenter="isCenter" @closeCenter="isCenter=false" />
	</view>
</template>

<script>
	import keybored from '@/components/liujto-keyboard/keybored.vue';
	import member from '@/components/user/member.vue';
	import scan from './components/scan.vue';
	import cash from './components/cash.vue';
	import vipCard from './components/vipCard.vue';
	import addDish from './components/addDish.vue';
	import share from './components/share.vue';
	import serviceCharge from './components/serviceCharge.vue';
	import goodsReduce from './components/goodsReduce.vue';
	import giftDish from './components/giftDish.vue';
	import remarks from './components/remarks.vue';
	import wholenote from './components/wholenote.vue';
	import typer from '@/components/tool/typer.vue';
	import notice from '@/components/tool/notice.vue';
	import center from '@/components/tool/center.vue';
	export default {
		components: {
			member,
			scan,
			cash,
			vipCard,
			keybored,
			addDish,
			share,
			serviceCharge,
			goodsReduce,
			giftDish,
			remarks,
			wholenote,
			typer,
			notice,
			center,
		},
		data() {
			return {
				showVip: false,
				isVip: false,
				isOrder: false,
				isCode: false,
				isCash: false,
				isCard: false,

				isType: false, //打印机
				isNotice: false, //消息
				isCenter: false, //个人中心

				remark: false, //单品备注
				reduce: false, //菜品打折/减免
				giftDish: false, //赠菜
				allDesc: false, //整单备注
				saveOrder: false, //存单
				getOrder: false, //取单
				batch: false, //批量操作
				recharge: false, //团购券
				service: false, //服务费
				share: false, //拼桌
				rescind: false, //撤台
				addDish: false, //加菜
				tab: 0,
				kind: 0,
				actgood: 0,
				total: 117,
				id: 0,
				product_show: 0, //菜单样式
				wholePack: [], //整单打包
				invoice: [], //发票，结账单
				type: '',
				value: '',
				name: '',
				num: '',
				isBut: '',
				phone: '',
				discounts: '0.00',
				i_remark: '',

				allNotes: '', //整单

				selectItem: {},
				form: {
					cash_money: '',
					resons: []
				},
				list: [],
				dis_list: [],
				kinds: ['全部', '锅底、汤水', '养生素菜', '加菜'],
				listData: [{
						id: 0,
						name: '韭菜红牛汤（大份）韭菜红牛汤（大份）韭菜红牛汤（大份）韭菜红牛汤（大份）韭菜红牛汤（大份）韭菜红牛汤（大份）韭菜红牛汤（大份）韭菜红牛汤（大份）韭菜红牛汤（大份）韭菜红牛汤（大份）韭菜红牛汤（大份）',
						num: 1,
						price: 59,
						old_price: 59,
						img: require('@/static/imgs/t1.jpg'),
						inventory: 42,
						ispack: false,
						isgift: false,
						dis_cont: '',
						dis_type: 0,
						gift_count: 0,
						remarks: [],
					},
					{
						id: 1,
						name: '天麻土鸡汤',
						num: 1,
						price: 69,
						old_price: 69,
						img: require('@/static/imgs/t2.jpg'),
						inventory: 62,
						ispack: false,
						isgift: false,
						dis_cont: '',
						dis_type: 0,
						gift_count: 0,
						remarks: [],
					},
					{
						id: 2,
						name: '肉丸杂菌汤（大份）',
						num: 1,
						price: 79,
						old_price: 79,
						img: require('@/static/imgs/t3.jpg'),
						inventory: 0,
						ispack: false,
						isgift: false,
						dis_cont: '',
						dis_type: 0,
						gift_count: 0,
						remarks: [],
					},
					{
						id: 3,
						name: '鸳鸯锅（小锅）',
						num: 1,
						price: 89,
						old_price: 89,
						img: require('@/static/imgs/t4.jpg'),
						inventory: 177,
						ispack: false,
						isgift: false,
						dis_cont: '',
						dis_type: 0,
						gift_count: 0,
						remarks: [],
					},
					{
						id: 4,
						name: '鸳鸯锅（大锅）',
						num: 1,
						price: 99,
						old_price: 99,
						img: require('@/static/imgs/t4.jpg'),
						inventory: 948,
						ispack: false,
						isgift: false,
						dis_cont: '',
						dis_type: 0,
						gift_count: 0,
						remarks: [],
					},
					{
						id: 5,
						name: '韭菜红牛汤（大份）',
						num: 1,
						price: 59,
						old_price: 59,
						img: require('@/static/imgs/t1.jpg'),
						inventory: 42,
						ispack: false,
						isgift: false,
						dis_cont: '',
						dis_type: 0,
						gift_count: 0,
						remarks: [],
					},
					{
						id: 6,
						name: '天麻土鸡汤',
						num: 1,
						price: 69,
						old_price: 69,
						img: require('@/static/imgs/t2.jpg'),
						inventory: 62,
						ispack: false,
						isgift: false,
						dis_cont: '',
						dis_type: 0,
						gift_count: 0,
						remarks: [],
					},
					{
						id: 7,
						name: '肉丸杂菌汤（大份）',
						num: 1,
						price: 79,
						old_price: 79,
						img: require('@/static/imgs/t3.jpg'),
						inventory: 0,
						ispack: false,
						isgift: false,
						dis_cont: '',
						dis_type: 0,
						gift_count: 0,
						remarks: [],
					},
					{
						id: 8,
						name: '鸳鸯锅（小锅）',
						num: 1,
						price: 89,
						old_price: 89,
						img: require('@/static/imgs/t4.jpg'),
						inventory: 177,
						ispack: false,
						isgift: false,
						dis_cont: '',
						dis_type: 0,
						gift_count: 0,
						remarks: [],
					},
					{
						id: 9,
						name: '鸳鸯锅（大锅）',
						num: 1,
						price: 99,
						old_price: 99,
						img: require('@/static/imgs/t4.jpg'),
						inventory: 948,
						ispack: false,
						isgift: false,
						dis_cont: '',
						dis_type: 0,
						gift_count: 0,
						remarks: [],
					},
					{
						id: 10,
						name: '韭菜红牛汤（大份）',
						num: 1,
						price: 59,
						old_price: 59,
						img: require('@/static/imgs/t1.jpg'),
						inventory: 42,
						ispack: false,
						isgift: false,
						dis_cont: '',
						dis_type: 0,
						gift_count: 0,
						remarks: [],
					}
				],
				ways: [{
						img: require('@/static/imgs/way4.png'),
						title: '现金-人民币'
					},
					{
						img: require('@/static/imgs/way5.png'),
						title: '扫码支付'
					},
					{
						img: require('@/static/imgs/way6.png'),
						title: '会员卡'
					},
					{
						img: require('@/static/imgs/way1.png'),
						title: '挂账消费'
					},
					{
						img: require('@/static/imgs/way3.png'),
						title: '前台码'
					},
					{
						img: require('@/static/imgs/way2.png'),
						title: '代金券'
					}
				],
				vipData: [{
						id: 0,
						grade: 1,
						sex: 2,
						avatar: 'https://v5.niuteam.cn/upload/1/headimg/20230809/20230809105710169154983088277.png',
						name: '王力师傅',
						phone: '18674549464',
						balance: '20.00',
						birthday: '',
						creat_at: '2023-08-09 10:57:01',
						integral: 0,
						cash: '0.00',
						growth: 100,
						coupon: 1,
						card: 0,
					},
					{
						id: 1,
						grade: 1,
						sex: 2,
						avatar: 'https://v5.niuteam.cn/upload/1/headimg/20230809/20230809013244169155916420431.png',
						name: '15077448596',
						phone: '15077448596',
						balance: '20.00',
						birthday: '',
						creat_at: '2023-08-09 13:57:01',
						integral: 100,
						cash: '0.00',
						growth: 100,
						coupon: 1,
						card: 0,
					},
					{
						id: 2,
						grade: 1,
						sex: 2,
						avatar: '',
						name: '苹果',
						phone: '13699859685',
						balance: '20.00',
						birthday: '',
						creat_at: '2023-08-09 10:57:01',
						integral: 0,
						cash: '0.00',
						growth: 100,
						coupon: 1,
						card: 0,
					}
				],
			}
		},
		computed: {
			numAll: function() {
				let num = 0
				this.list.forEach(v => {
					num += v.num
				})
				return num
			},
			coupons: function() {
				let money = 0
				this.list.forEach(v => {
					money += ((v.old_price - v.price) * v.num)
				})
				return money.toFixed(2)
			},
			moneyAll: function() {
				let money = 0
				this.list.forEach(v => {
					money += (v.price * (v.num - v.gift_count))
				})
				return money.toFixed(2)
			},
		},
		onLoad: function(option) {
			if (option) {
				this.id = option.id
				this.name = option.name
				this.num = option.num
			}
		},
		methods: {
			back() {
				uni.reLaunch({
					url: '/pages/home/index?current=1'
				})
			},
			//清空
			clearAll() {
				this.list.forEach(v => v.num = 1)
				this.list = []
			},
			//选择商品
			chooseGood(item, index) {
				this.actgood = item.id
				this.selectItem = item
			},
			//删除
			delItem(item, index) {
				item.num = 1
				item.ispack = false
				item.remarks = []
				this.list.splice(index, 1)
				this.actgood = 0
			},
			//下单
			takeOrder() {
				if (this.list && this.list.length > 0) {
					this.isOrder = true
				} else {
					this.$refs.uToast.show({
						message: '请先选择商品',
						position: 'top',
					})
				}
			},
			//加菜
			addMenu() {
				this.isOrder = false
			},
			//切换种类
			changeKind(index) {
				this.kind = index
			},
			clickItem(item, index) {
				if (this.list.some(v => v.id == item.id)) {
					item.num += 1
				} else {
					this.list.unshift(item)
				}
				this.actgood = 0
				this.selectItem = this.list[0]
			},
			doneAdd() {
				this.list.unshift({
					name: '临时商品',
					num: 1,
					price: this.money
				})
			},
			//选择支付方式
			showItem(index) {
				if (index == 0) {
					this.isCash = true
				} else if (index == 1) {
					this.isCode = true
				} else if (index == 2) {
					this.isCard = true
				}
			},
			//手动输入二维码
			manualInput() {},
			//选择会员
			chooseMember(e) {
				this.vipForm = e.item
				this.showVip = true
				this.isVip = e.isVip
			},
			changeMoney(e) {
				this.isCash = e.isCash
			},
			closeAdd(e) {
				this.addDish = e
			},
			//撤单
			pullpack() {
				this.rescind = false
				uni.reLaunch({
					url: '/pages/home/index?current=1'
				})
			},
			//批量操作
			selectionChange(e) {
				console.log(e.detail.index);
			},
			//单品备注
			itemRemark(e) {
				console.log(e);
				this.selectItem.remarks = e
			},
			//整单备注
			returnRemark(e) {
				this.allNotes = e.join('，')
			},
			//菜品打折，减免
			cancelDis() {
				this.selectItem.price = this.selectItem.old_price
				this.selectItem.total = this.selectItem.old_price * this.selectItem.num
				this.selectItem.dis_cont = ''
				this.selectItem.dis_type = 0
			},
			changeMonry(e) {
				this.selectItem.price = e.money
				this.selectItem.total = this.selectItem.price * this.selectItem.num
				this.selectItem.dis_type = e.type
				this.selectItem.dis_cont = this.selectItem.dis_type == 0 ? e.discount / 10 + '折' : `减${e.discount}元`
				this.reduce = false
			},
			//赠菜
			cancelGift() {
				this.selectItem.gift_count = 0
				this.selectItem.isgift = false
			},
			changeNumber(e) {
				this.selectItem.gift_count = e.num
				this.selectItem.isgift = true
				this.giftDish = false
			},
			//转台
			turntable() {
				uni.reLaunch({
					url: `/pages/table/table?id=${this.id}`
				})
			},
			//并台
			combine() {
				uni.reLaunch({
					url: `/pages/table/merge?id=${this.id}`
				})
			}
		}
	}
</script>

<style lang="scss" scoped>
	/deep/.u-modal__button-group__wrapper--confirm {
		background: #4275F4;
	}

	/deep/.u-modal__content__text {
		font-size: 16px !important;
		color: #000 !important;
	}

	.top {
		height: 110rpx;

		.top_l {
			width: 500px;
		}

		.num {
			padding: 5px 8px;
			border-radius: 5px;
			background: #434453;
			color: #f9f9f9;
		}

		/deep/.u-input {
			padding: 3px 9px !important;
			background: #434453 !important;

			.uni-input-input {
				color: #fff;
			}
		}
	}

	.left {
		width: 800rpx;

		.user_cont {
			height: 194rpx;
			border: 2px solid #4275F4;
			background: #fff6f1;

			.grade {
				margin-left: 10px;
				background: #fff;
				color: #FD8906;
				border: 1px solid #FD8906;
				width: 55px;
			}

			/deep/.ul-button {
				.u-button__text {
					font-size: 18px !important;
				}
			}
		}

		.list {
			max-height: calc(100vh - 475px);
			overflow-y: auto;

			.isSelect {
				background: rgba(#fff0a9, .4);
			}

			.i_tag {
				padding: 2px 3px;
				border: 1px solid #FD8906;
				border-radius: 3px;
				background: #fff9ec;
			}

			/deep/.u-empty {
				height: 500px;
			}

			/deep/.u-tag-wrapper {
				width: 25px;

				span {
					padding-left: 3px
				}
			}

			/deep/.u-button {
				border-radius: 6px !important;
			}

			/deep/.checkbox {
				width: 10px !important;

				.checkbox--indeterminate,
				.is-checked {
					background: #4275F4 !important;
					border: 1px solid #4275F4;
				}

				.uni-table-checkbox:hover {
					border-color: #4275F4;
				}
			}
		}
	}

	.l_but {
		width: 200rpx;
		height: calc(100vh - 60px);
		overflow-y: auto;
		background: #eff0f4;

		/deep/.u-button {
			padding: 10px;
			width: 80px;
			height: 75px !important;

			.u-button__text {
				font-size: 18px !important;
			}
		}

		/deep/ .gift {
			.u-button__text {
				font-size: 14px !important;
			}
		}

		.isBut {
			background: #4275F4;
		}

		/deep/.u-number-box {
			display: flex;
			flex-direction: column;
			margin-bottom: 10px;
			width: 80px;
			border-radius: 6px;
			border: 1px solid #e6e6e6;
			background: #fff;

			.minus {
				width: 80px;
				height: 60px;
				@include flex;
				justify-content: center;
				align-items: center;

				.u-icon__icon {
					font-size: 24px;
				}
			}

			.input {
				width: 80px;
				height: 60px;
				border-top: 1px solid #e6e6e6;
				border-bottom: 1px solid #e6e6e6;
				text-align: center;
				line-height: 60px;
				font-size: 24px;
				font-weight: 600;
			}

			.plus {
				width: 80px;
				height: 60px;
				font-size: 24px;
				color: #000;
				/* #ifndef APP-NVUE */
				display: flex;
				/* #endif */
				justify-content: center;
				align-items: center;

				.u-icon__icon {
					font-size: 24px;
				}
			}
		}

		.remark {
			padding: 5px;
			width: 55px;
			height: 55px;
			border: 1px solid #e6e6e6;
		}

		.pack {
			width: 55px;
			height: 55px;
			border: 1px solid #e6e6e6;
		}

		.discount {
			padding: 5px;
			width: 55px;
			border: 1px solid #e6e6e6;
		}
	}

	.right {
		/deep/.u-subsection--subsection {
			height: 40px !important;
			border-radius: 6px;

			.u-subsection__item__text {
				span {
					color: #000;
					font-size: 18px !important;
				}
			}
		}

		.r_cont {
			max-height: calc(100vh - 215px);
			overflow: auto;

			.r_item {
				position: relative;
				display: inline-block;
				margin-right: 20rpx;
				margin-bottom: 20rpx;
				width: 450rpx;
				height: 280rpx;
				border: 2rpx solid #e6e6e6;
				border-radius: 10px;

				.badge {
					position: absolute;
					top: 0px;
					right: 0px;

					/deep/.u-badge {
						line-height: 16px;
						font-size: 16px;
					}
				}
			}

			.check {
				border: 2rpx solid #FD8906;
			}
		}

		.pagona {
			height: 50px;

			/deep/.uni-pagination {
				.page--active {
					display: inline-block;
					width: 30px;
					height: 30px;
					background: #4275F4 !important;
					color: #000 !important;
				}

				.uni-pagination__total {
					font-size: 20px;
				}

				span {
					font-size: 20px;
				}
			}
		}


		/deep/.ljt-keyboard-body {
			border: 1px solid #e5e5e5;

			.ljt-keyboard-number-body {
				width: 480px !important;
				height: 220px !important;
			}

			.ljt-number-btn-confirm-2 {
				background: #4275F4 !important;

				span {
					color: #000;
					font-size: 20px;
				}
			}
		}

		.ways {
			display: flex;
			flex-wrap: wrap;

			.way {
				width: 33.3%;
			}
		}

		.r_b {
			/deep/.u-button {
				span {
					color: #000;
				}
			}
		}
	}

	.remark {
		width: 500px;
	}

	.reduce {
		position: absolute;
		top: 55px;
		left: 515px;
		width: 390px;
		height: calc(100vh - 55px);
		border-radius: 10px;

		.tabs {
			display: inline-flex;
			border-radius: 6px;
			background: #eeeeee;

			.tab_i {
				padding: 8px 15px;
			}
		}

		.dis {
			padding: 8px 0;
			width: 23%;
			border: 1px solid #e6e6e6;
		}

		.key {
			/deep/.ljt-keyboard-body {
				border-radius: 10px;
				border: 1px solid #e5e5e5;

				.ljt-keyboard-number-body {
					width: 360px !important;
					height: 275px !important;
				}

				.ljt-number-btn-ac {
					width: 90px !important;
				}

				.ljt-number-btn-confirm-2 {
					width: 100px !important;
					background: #4275F4 !important;

					span {
						color: #000;
					}
				}
			}
		}

		.reson_i {
			position: relative;
			display: inline-block;
			border: 1px solid #e6e6e6;
			padding: 8px 15px;

			.r_gou {
				display: none;
				position: absolute;
				top: 0px;
				right: 0px;
				width: 0;
				height: 0;
				border-top: 10px solid #4275F4;
				border-right: 10px solid #4275F4;
				border-left: 10px solid transparent;
				border-bottom: 10px solid transparent;
			}

			.icon-duigou {
				display: none;
				position: absolute;
				top: -2px;
				right: -2px;
				transform: scale(0.6);
			}
		}

		.acreson_i {
			border: 1px solid #FD8906;
			background: #fff9dd;

			.r_gou,
			.icon-duigou {
				display: block;
			}
		}
	}
</style>
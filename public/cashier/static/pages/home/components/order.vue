<template>
	<view class="f-y-bt h100 bf p10" style="padding-top: 0;">
		<view class="f-bt f-y-c search">
			<u--form labelPosition="left" :model="queryForm" ref="uForm" labelWidth="100px" labelAlign="right"
				:labelStyle="{fontSize:'14px'}">
				<u-form-item label="订单类型：" prop="appointment" ref="item1"
					v-if="tabval != 'valueRef' && tabval != 'billRef'">
					<view class="sw">
						<uni-data-select v-model="queryForm.appointment" :localdata="channels" placeholder="请选择订单渠道"
							@change="handDiningType"></uni-data-select>
					</view>
				</u-form-item>
				<u-form-item label="支付方式：" prop="payType" ref="item1">
					<view class="sw">
						<uni-data-select v-model="queryForm.payType"
							:localdata="tabval == 'selfRef' || tabval == 'sideRef'  ? classfiys : classfiy"
							placeholder="请选择订单来源" @change="handSource"></uni-data-select>
					</view>
				</u-form-item>
				<u-form-item label="下单时间：" prop="timeType" ref="item1">
					<view class="sw">
						<uni-data-select v-model="queryForm.timeType" :localdata="dates" placeholder="请选择"
							@change="handDate" :clear="false"></uni-data-select>
					</view>
				</u-form-item>
				<u-form-item label="时间类型：" prop="timeChannel" ref="item1" v-if="tabval != 'billRef' && tabval != 'valueRef'">
					<view class="sw">
						<uni-data-select v-model="queryForm.timeChannel" :localdata="dateTime" placeholder="请选择"
							@change="handChannel" :clear="false"></uni-data-select>
					</view>
				</u-form-item>
				<u-form-item label="" ref="item1" class="ml20">
					<view class="iw">
						<u--input placeholder="请输入流水号/订单/用户手机号" prefixIcon="search"
							prefixIconStyle="color: #909399" v-model="queryForm.keyword"
							@input="fetchData" clearable></u--input>
					</view>
				</u-form-item>
			</u--form>
		</view>
		<view class="main f-1 f-bt f16" v-if="orderList&&orderList.length>0">
			<view class="f-g-0 f-y-bt left">
				<view class="lwrap f-g-1">
					<view class="list p10 mb10" :class="current == i ? 'lcur':''" v-for="(v,i) in orderList" :key="i"
						@click="clickItem(v,i)">
						<view class="f-bt">
							<view class="wei f20" v-if="tabval == 'valueRef'">会员储值</view>
							<view class="wei f20" v-else-if="tabval == 'integralRef'">积分商城</view>
							<view class="wei f20" v-else-if="tabval == 'inStoreRef' && v.diningType==4 && v.table">{{v.table.type.name}}{{v.table.name}}</view>
							<view class="wei f20" v-else-if="tabval == 'inStoreRef' && (v.diningType==5 || v.diningType==6)">取单号：{{v.pickNo}}</view>
							<view class="wei f20" v-else>
								{{tabval == 'selfRef' ? '取单号' :'流水号'}}：{{v.pickNo}}
							</view>
							<view class="">
								<view class="f-y-c">{{v.stateFormat || v.stateForamt}}
									<text style="color: #4275F4;" class="f14 ml5" v-if="v.orderIndex && v.orderIndex.payStateFormat">({{v.orderIndex && v.orderIndex.payStateFormat}})</text>
								</view>
								<view class="mt5" v-if="v.created_at">下单：{{v.created_at.substr(11,5)}}</view>
							</view>
						</view>
						<view v-if="v.orderSn" class="mt10">订单尾号：{{v.orderSn.substr(14,6)}}</view>
						<view class="f-bt mt10 f-y-c">
							<view v-if="tabval == 'integralRef'" class="flex">
								支付金额：
								<view class="t-o-e wei4" style="color: #4275F4;" v-if="v.goods">
									<text v-if="v.goods.integral>0">{{v.goods.integral}}</text>
									<text v-if="v.goods.integral>0" class="f13 nowei">积分</text>
									<text v-if="v.goods.integral>0 && v.goods.money>0" class="nowei">+</text>
									<text v-if="v.goods.money>0"><text class="f12">￥</text>{{v.goods.money}}</text>
								</view>
							</view>
							<view v-else>支付金额：<text style="color: #4275F4;">￥{{v.money}}</text></view>
							<view class="f14">
								<text class="iconfont icon-huabanfuben f24" v-if="v.source ==11"></text>
								<text class="iconfont icon-shouyintai  f24" v-if="v.source ==10"></text>
								<text class="iconfont icon-weixinxiaochengxu f24" v-if="v.source ==1"></text>
							</view>
						</view>
					</view>
				</view>
				<view class="bf p-10-0 l_bot f-g-0">
					<uni-pagination :current="queryForm.pageNo" :total="total" @change="change" showIcon />
				</view>
			</view>
			<view class="f-bt f-g-1 right pl10">
				<view class="f-g-0 goods p10" v-if="tabval != 'billRef' && tabval != 'valueRef' &&  tabval != 'integralRef'">
					<view class="bbae6 pb10">
						<view class="wei f24">
							<view v-if="tabval == 'inStoreRef' && itemForm.diningType==4 && itemForm.table">
								{{itemForm.table.type.name}}{{itemForm.table.name}}
								<text class="f18 c9 nowei ml10">{{itemForm.tableNum}}人</text>
							</view>
							<view v-else-if="tabval == 'inStoreRef' && (itemForm.diningType==5 || itemForm.diningType==6)">取单号：{{itemForm.pickNo}}</view>
							<view v-else>{{tabval == 'selfRef' ? '取单号' :'流水号'}}：{{itemForm.pickNo}}</view>
						</view>
						<view class="f-bt f-y-c mt10">
							<view class="" style="color: #4275F4;">
								共{{itemForm.goodsNum}}项，合计￥{{itemForm.goodsMoney}}
								<text v-if="itemForm.goodsSellMoney > itemForm.goodsMoney" class="t-d-l c9 ml10 f14">￥{{itemForm.goodsSellMoney}}</text>
							</view>
							<view v-if="itemForm.refundFormat" class="cf5f">
								{{itemForm.refundFormat}}
							</view>
						</view>
					</view>
					<view class="f-1 mt10"
						v-if="tabval == 'inStoreRef' && itemForm.generalGoods || itemForm.discountsGoods">
						<block v-for="(v,i) in itemForm.generalGoods" :key="i">
							<view class="f-bt wei mt10 f-y-t">
								<view class="f-g-1 f18 flex f-y-t">
									<block v-if="v.discountLabel">
										<view class="i_tag mr5 f10 cf f-c f-g-0 i_tag2" v-if="v.state==8">{{v.discountLabel}}</view>
										<view class="i_tag mr5 f10 cf5 f-c f-g-0" v-else>{{v.discountLabel}}</view>
									</block>
									<view class="l-h1 t-o-e2">{{v.name}}</view>
								</view>
								<view class="f-g-0 flex">
									<view class="nowei mr30">x{{v.num}}</view>
									<view class="nowei t-r t-o-e" style="width: 70px;">
										<view>￥{{v.money}}</view>
										<view v-if="v.sellMoney>v.money" class="t-d-l f14 c9">￥{{v.sellMoney}}</view>
									</view>
								</view>
							</view>
							<view class="flex f-w f14 c9 mt5">
								<view v-if="v.attrData && v.attrData.spec">
									[{{ v.attrData.spec }}]</view>
								<view v-if="v.attrData && v.attrData.attr">
									[{{ v.attrData.attr }}]</view>
								<view v-if="v.attrData && v.attrData.matal">
									{{ v.attrData.matal }}
								</view>
							</view>
							<view class="flex f-w f14 c9 mt5" v-if="v.setMealData && v.setMealData.length">
								<view v-for="(cv,ci) in v.setMealData" :key="ci">{{cv.name}}*{{cv.num}}
									<text v-if="cv.attrData && cv.attrData.attr" class="ml10">[{{ cv.attrData.attr }}]</text>
									<text v-if="cv.attrData && cv.attrData.matal" class="ml10">[{{ cv.attrData.matal }}]</text>
								</view>
							</view>
							<view class="flex f-w f14 c9 mt5 t-o-e2" v-if="v.state==8 && v.discountLabel && v.reason">
								退菜原因：{{v.reason}}
							</view>
						</block>
						<block v-for="(v,i) in itemForm.discountsGoods" :key="i">
							<view class="f-bt wei mt10 f-y-t">
								<view class="f-g-1 f18 flex f-y-t">
									<view v-if="v.discountLabel"
										class="i_tag mr5 f10 cf5 f-c f-g-0">{{v.discountLabel}}
									</view>
									<view class="l-h1 t-o-e2">{{v.name}}</view>
								</view>
								<view class="f-g-0 flex">
									<view class="nowei mr30">x{{v.num}}</view>
									<view class="nowei t-r t-o-e" style="width: 70px;">
										<view>￥{{v.money}}</view>
										<view v-if="v.sellMoney>v.money" class="t-d-l f14 c9">￥{{v.sellMoney}}</view>
									</view>
								</view>
							</view>
							<view class="flex f-w f14 c9 mt5">
								<view v-if="v.attrData && v.attrData.spec">
									[{{ v.attrData.spec }}]</view>
								<view v-if="v.attrData && v.attrData.attr">
									[{{ v.attrData.attr }}]</view>
								<view v-if="v.attrData && v.attrData.matal">
									{{ v.attrData.matal }}
								</view>
							</view>
							<view class="flex f-w f14 c9 mt5" v-if="v.setMealData && v.setMealData.length">
								<view v-for="(cv,ci) in v.setMealData" :key="ci">{{cv.name}}*{{cv.num}}
									<text v-if="cv.attrData && cv.attrData.attr" class="ml10">[{{ cv.attrData.attr }}]</text>
									<text v-if="cv.attrData && cv.attrData.matal" class="ml10">[{{ cv.attrData.matal }}]</text>
								</view>
							</view>
						</block>
					</view>
					<view class="f-1 mt10" v-else>
						<block v-for="(v,i) in itemForm.goods" :key="i">
							<view class="f-bt wei mt10 f-y-t">
								<view class="f-g-1 f18 flex">
									<view v-if="v.discountType"
										class="i_tag mr5 f10 cf5 f-c f-g-0">{{v.discountLabel}}
									</view>
									{{v.name}}
								</view>
								<view class="f-g-0 flex">
									<view class="nowei mr30">x{{v.num}}</view>
									<view class="nowei t-r t-o-e" style="width: 70px;">
										<view>￥{{v.money}}</view>
										<view v-if="v.sellMoney>v.money" class="t-d-l f14 c9">￥{{v.sellMoney}}</view>
									</view>
								</view>
							</view>
							<view class="flex f-w f14 c9 mt5">
								<view v-if="v.attrData && v.attrData.spec">
									[{{ v.attrData.spec }}]</view>
								<view v-if="v.attrData && v.attrData.attr">
									[{{ v.attrData.attr }}]</view>
								<view v-if="v.attrData && v.attrData.matal">
									{{ v.attrData.matal }}
								</view>
							</view>
							<view class="flex f-w f14 c9 mt5" v-if="v.setMealData && v.setMealData.length">
								<view v-for="(cv,ci) in v.setMealData" :key="ci">{{cv.name}}*{{cv.num}}
									<text v-if="cv.attrData && cv.attrData.attr" class="ml10">[{{ cv.attrData.attr }}]</text>
									<text v-if="cv.attrData && cv.attrData.matal" class="ml10">[{{ cv.attrData.matal }}]</text>
								</view>
							</view>
						</block>
					</view>
				</view>
				<view class="f-g-0 goods p10" v-if="tabval == 'billRef'">
					<view class="bbae6 pb10">
						<view class="wei f24">
							{{tabval == 'selfRef' || tabval == 'inStoreRef' ? '取单号' :'流水号'}}：{{itemForm.pickNo}}
						</view>
						<view class="f-bt f-y-c mt10">
							<view class="" style="color: #4275F4;">
								合计￥{{itemForm.money}}</view>
							<view v-if="itemForm.refundMoney>0" class="cf5f">已退款</view>
						</view>
					</view>
					<view class="f-1 mt10">
						<view class="flex">
							<view>下单人：</view>
							<view class="flex" v-if="itemForm.admin">
								<view>{{itemForm.admin && itemForm.admin.nickname || '-'}}-</view>
								<view>{{itemForm.admin && itemForm.admin.mobile}}</view>
							</view>
							<!-- <view class="flex" v-if="itemForm.user">
								<view>{{itemForm.user && itemForm.user.nickname || '-'}}-</view>
								<view>{{itemForm.user && itemForm.user.mobile}}</view>
							</view> -->
						</view>
					</view>
				</view>
				<view class="f-g-0 goods p10" v-if="tabval == 'valueRef'">
					<view class="bbae6 pb10">
						<view class="wei f24">会员储值</view>
						<view class="f-bt f-y-c mt10">
							<view class="" style="color: #4275F4;">
								合计￥{{itemForm.money}}</view>
						</view>
					</view>
					<view class="f-1 mt10">
						<view class="flex">
							<view>下单人：</view>
							<view class="flex" v-if="itemForm.admin">
								<view>{{itemForm.admin && itemForm.admin.nickname || '-'}}-</view>
								<view>{{itemForm.admin && itemForm.admin.mobile}}</view>
							</view>
							<view class="flex" v-if="itemForm.user">
								<view>{{itemForm.user && itemForm.user.nickname || '-'}}-</view>
								<view>{{itemForm.user && itemForm.user.mobile}}</view>
							</view>
						</view>
						<view class="f-g-1 mt20">
							<view class="f-bt">
								<view class="f-g-0">储值金额</view>
								<view class="f-g-1 f-x-e">{{itemForm.money}}</view>
							</view>
							<view class="p10 mt10" style="padding-right: 0;" v-if="itemForm.data">
								<view class="f-bt c6" v-if="itemForm.data.balanceSwitch==1">
									<view class="f-g-0">赠：金额</view>
									<view class="f-g-1 f-x-e">￥{{itemForm.data.balanceGive}}</view>
								</view>
								<view class="f-bt mt10 c6" v-if="itemForm.data.integralSwitch==1">
									<view class="f-g-0">赠：积分</view>
									<view class="f-g-1 f-x-e">{{itemForm.data.integralGive}}</view>
								</view>
								<view class="f-bt mt10 c6" v-if="itemForm.data.couponSwitch==1">
									<view class="f-g-0">赠：优惠券</view>
									<view class="f-g-1 f-x-e">
										<view>
											<view v-if="itemForm.data.couponGive">
												<block v-for="(v,i) in itemForm.data.couponGive" :key='i'>
													{{v.name}} <text class=""
														:style="{color:'#4275F4'}">x{{v.num}}</text>
												</block>
											</view>
										</view>
									</view>
								</view>
								<view class="f-bt mt10 c6" v-if="itemForm.data.levelSwitch==1">
									<view class="f-g-0">赠：会员等级提升至</view>
									<view class="f-g-1 f-x-e">{{itemForm.data.levelGive}}</view>
								</view>
							</view>
						</view>
					</view>
				</view>
				<view class="f-g-0 goods p10" v-if="tabval == 'integralRef'">
					<view class="bbae6 pb10">
						<view class="wei f24" v-if="itemForm.user && itemForm.user.mobile">
							手机尾号{{itemForm.user.mobile.substr(itemForm.user.mobile.length-4)}}
						</view>
						<view class="f-bt f-y-c mt10">
							<view class="flex">
								合计：
								<view class="cfa t-o-e wei4" style="color: #4275F4;" v-if="itemForm.goods">
									<text v-if="itemForm.goods.integral>0">{{itemForm.goods.integral}}</text>
									<text v-if="itemForm.goods.integral>0" class="f13 nowei">积分</text>
									<text v-if="itemForm.goods.integral>0 && itemForm.goods.money>0" class="nowei">+</text>
									<text v-if="itemForm.goods.money>0"><text class="f12">￥</text>{{itemForm.goods.money}}</text>
								</view>
							</view>
							<view v-if="itemForm.refundMoney>0" class="cf5f">已退款</view>
						</view>
					</view>
					<view class="f-1 mt10">
						<view class="f-bt wei mt10 f-y-t" v-if="itemForm.goods">
							<view class="f-g-1 f18 flex">
								<view v-if="itemForm.goods.discountType"
									class="i_tag mr5 f10 cf5 f-c f-g-0">{{itemForm.goods.discountLabel}}
								</view>
								{{itemForm.goods.name}}
							</view>
							<view class="f-g-0 flex">
								<view class="nowei mr30">x{{itemForm.goods.num || 1}}</view>
								<view class="nowei t-r t-o-e">
									<view class="t-o-e" v-if="itemForm.goods">
										<text v-if="itemForm.goods.integral>0">{{itemForm.goods.integral}}</text>
										<text v-if="itemForm.goods.integral>0" class="f13 nowei">积分</text>
										<text v-if="itemForm.goods.integral>0 && itemForm.goods.money>0" class="nowei">+</text>
										<text v-if="itemForm.goods.money>0"><text class="f12">￥</text>{{itemForm.goods.money}}</text>
									</view>
								</view>
							</view>
						</view>
					</view>
				</view>
				<view class="f-g-1 f-y-bt ml10">
					<view class="order f-g-1 p10">
						<view class="bbae6 pb10">
							<view class="flex">
								<view class="fg0">订单号:</view>
								<view>{{itemForm.orderSn}}</view>
								<view class="fz ml5" style="color: #4275F4;" @click="handFz(itemForm.orderSn)">复制</view>
							</view>
							<view class="flex mt10" v-if="itemForm.table">
								<view class="fg0">桌台号:</view>
								<view>
									{{itemForm.table.type.name}}{{itemForm.table.name}}
									<text class="f14 c9 nowei ml5">{{itemForm.tableNum}}人</text>
								</view>
							</view>
							<view class="flex mt10">
								<view class="fg0">下单时间:</view>
								<view>{{itemForm.created_at}}</view>
							</view>
							<view class="flex mt10">
								<view class="fg0">下单渠道:</view>
								<view>{{itemForm.sourceFormat||'-'}}</view>
							</view>
							<view class="flex mt10">
								<view class="fg0">订单备注:</view>
								<view>{{itemForm.notes||'-'}}</view>
							</view>
							<view class="flex mt10">
								<view class="fg0">门店备注:</view>
								<view>{{itemForm.storeNotes||'-'}}</view>
							</view>
						</view>
						<view class="f-bt mt10">
							<view class="flex f-y-c">
								<view>{{itemForm.sourceFormat || '-'}}</view>
								<view class="line"></view>
								<view v-if="tabval == 'valueRef'">储值订单</view>
								<view v-else-if="tabval == 'inStoreRef'">{{itemForm.diningTypeFormat}}</view>
								<view v-else-if="tabval == 'billRef'">买单订单</view>
								<view v-else-if="tabval == 'integralRef'">积分商城</view>
								<view v-else>{{itemForm.orderTypeFormat}}</view>
								<view class="line"></view>
								<view class="pr20">{{itemForm.stateFormat || '-'}}</view>
							</view>
							<view v-if="itemForm.scene == 1 && itemForm.state >= 2 && itemForm.deliveryOrder">
								<u-button color="#4275F4" size="small"
									:customStyle="{color:'#fff',height:'40px',fontSize:14}" type="primary"
									@click="seePs(itemForm)">配送信息</u-button>
							</view>
						</view>
						<view class="mt10" v-if="itemForm.pickNo">
							流水号：{{itemForm.pickNo}}
						</view>
						<view class="mt10 bbae6 pb10">
							<view class="flex" v-if="itemForm.admin">
								<view>下单人：</view>
								<view class="flex">
									<view>{{itemForm.admin && itemForm.admin.nickname || '-'}}-</view>
									<view>{{itemForm.admin && itemForm.admin.mobile}}</view>
								</view>
							</view>
							<view class="flex mt10" v-if="itemForm.user">
								<view>用户信息：</view>
								<view class="flex">
									<view>{{itemForm.user && itemForm.user.nickname || '-'}}-</view>
									<view>{{itemForm.user && itemForm.user.mobile}}</view>
								</view>
							</view>
							<view v-if="itemForm.scene == 1 && itemForm.state >= 2 && itemForm.address">
								<view class="flex mt10">
									<view class="fg0">配送地址：</view>
									<view>
										<text>{{itemForm.address.address}}</text>
										<text>{{itemForm.address.description}}</text>
									</view>
								</view>
								<view class="flex mt10">
									<view class="fg0">收货人：</view>
									<view>{{itemForm.address.contact}}<text
											class="ml10">{{itemForm.address.mobile}}</text></view>
								</view>
							</view>
							<view class="mt20 tit f-bt bbae6 pb10">
								<view class="c9">支付信息</view>
								<view style="color: #4275F4;">
									{{itemForm.orderIndex && itemForm.orderIndex.payStateFormat}}
								</view>
							</view>
							<view class="f-bt mt10" v-if="itemForm.goodsSellMoney>0">
								<view class="">商品金额合计：</view>
								<view>￥{{itemForm.goodsSellMoney}}</view>
							</view>
							<view class="f-bt mt10" v-if="itemForm.boxMoney>0">
								<view class="">包装费：</view>
								<view>￥{{itemForm.boxMoney}}</view>
							</view>
							<view class="f-bt mt10" v-if="itemForm.deliveryMoney && tabval != 'integralRef'">
								<view class="">配送费：</view>
								<view>￥{{itemForm.deliveryMoney}}</view>
							</view>
							<view class="f-bt mt10" v-if="itemForm.tableMoney>0">
								<view class="">{{itemForm.tableFormat || '服务费'}}：</view>
								<view>￥{{itemForm.tableMoney}}</view>
							</view>
							<view class="f-bt wei mt15 f18" v-if="tabval == 'integralRef'">
								<view>订单金额</view>
								<view class="t-o-e" v-if="itemForm.goods">
									<text v-if="itemForm.goods.integral>0">{{itemForm.goods.integral}}</text>
									<text v-if="itemForm.goods.integral>0" class="f13 nowei">积分</text>
									<text v-if="itemForm.goods.integral>0 && itemForm.goods.money>0" class="nowei">+</text>
									<text v-if="itemForm.goods.money>0"><text class="f12">￥</text>{{itemForm.goods.money}}</text>
								</view>
							</view>
							<view class="f-bt wei mt15 f18" v-else>
								<view>订单金额</view>
								<view>￥{{tabval == 'valueRef' ? itemForm.money : itemForm.sellMoney}}</view>
							</view>
						</view>
						<view class="mt15 bbae6 pb15" v-if="tabval != 'integralRef'">
							<view v-if="itemForm.discountsPlus">
								<view class="f-bt mt10" v-for="(v,i) in itemForm.discountsPlus" :key="i">
									<view class="">{{v.activityName}}：</view>
									<view>-￥{{v.money}}</view>
								</view>
							</view>
							<view class="f-bt wei f18 mt10">
								<view>优惠合计</view>
								<view>￥{{itemForm.discountMoney || 0}}</view>
							</view>
						</view>
						<view class="mt15 bbae6 pb15" v-if="tabval != 'integralRef'">
							<view v-if="itemForm.discountsPlus">
								<view class="f-bt mt10" v-for="(v,i) in itemForm.discountsPlus" :key="i">
									<view class="">{{v.activityName}}：</view>
									<view>-￥{{v.money}}</view>
								</view>
							</view>
							<view class="f-bt wei f18 mt10">
								<view>订单服务费</view>
								<view>￥{{itemForm.service_money || 0}}</view>
							</view>
						</view>
						<view class="mt15 bbae6 pb15">
							<view v-if="itemForm.orderIndex && itemForm.orderIndex.orderPay && itemForm.orderIndex.orderPay.length">
								<view class="f-bt mt10" v-for="(v,i) in itemForm.orderIndex.orderPay" :key="i">
									<view class="">{{itemForm.costomPayFormat>''?itemForm.costomPayFormat:v.payTypeFormat}}</view>
									<view><text v-if="v.payType==9">-</text>￥{{itemForm.money}}</view>
								</view>
							</view>
							<block v-else>
								<view v-if="itemForm.orderIndex">
									<view class="f-bt mt10">
										<view class="">{{itemForm.orderIndex.payTypeFormat}}</view>
										<view>￥{{itemForm.money}}</view>
									</view>
								</view>
							</block>
							<view class="f-bt wei f18 mt10">
								<view>支付合计</view>
								<view>￥{{itemForm.money}}</view>
							</view>
						</view>
						<!-- <view class="mt15 bbae6 pb15">
							<view class="f-bt wei f20">
								<view>支付优惠</view>
								<view>￥0</view>
							</view>
						</view> -->
						<view class="mt15 pb15" v-if="tabval != 'integralRef'">
							<view v-if="itemForm.orderIndex">
								<view class="f-bt mt10">
									<view class="">订单抽佣</view>
									<view>￥0</view>
								</view>
							</view>
							<view class="f-bt wei f18 mt10">
								<view>本单预计收入</view>
								<view style="color: #4275F4;" v-if="itemForm.state==8">
									￥{{itemForm.money - itemForm.refundMoney}}</view>
								<view style="color: #4275F4;" v-else>￥{{itemForm.money}}</view>
							</view>
						</view>
					</view>
					<view class="f-g-0 flex p15 f-x-e btn">
						<block v-if="tabval == 'selfRef' || tabval == 'sideRef'">
							<view class="f-g-1 mr10" v-if="itemForm.state == 1">
								<u-button size="small" :customStyle="{color:'#000',height:'40px',fontSize:14}"
									type="warning" @click="opClick('cancel')">取消订单</u-button>
							</view>
							<view class="f-g-1 mr10" v-if="itemForm.state == 2">
								<u-button color="#4275F4" size="small"
									:customStyle="{color:'#fff',height:'40px',fontSize:14}" type="primary"
									@click="opClick('receiving')">立即接单</u-button>
							</view>
							<view class="f-g-1 mr10" v-if="itemForm.state == 2">
								<u-button size="small" :customStyle="{color:'#fff',height:'40px',fontSize:14}"
									type="error" @click="opClick('refOrder')">拒单</u-button>
							</view>
							<view class="f-g-1 mr10" v-if="itemForm.state == 3">
								<u-button size="small" :customStyle="{color:'#fff',height:'40px',fontSize:14}"
									type="success" @click="opClick('makeding')">制作完成</u-button>
							</view>
							<view class="f-g-1 mr10" v-if="tabval == 'selfRef' && itemForm.state == 4">
								<u-button color="#4275F4" size="small"
									:customStyle="{color:'#fff',height:'40px',fontSize:14}" type="primary"
									@click="opClick('completeing')">确认取单</u-button>
							</view>
							<view class="f-g-1 mr10" v-if="tabval == 'sideRef' && itemForm.state == 4">
								<u-button color="#4275F4" size="small"
									:customStyle="{color:'#fff',height:'40px',fontSize:14}" type="primary"
									@click="opClick('gotoPs')">发起配送</u-button>
							</view>
							<view class="f-g-1 mr10" v-if="itemForm.scene == 1 && itemForm.state == 5">
								<u-button size="small" color="#4275F4"
									:customStyle="{color:'#fff',height:'40px',fontSize:14}" type="primary"
									@click="opClick('completeOrder')">完成订单</u-button>
							</view>
							<view class="f-g-1 mr10"
								v-if="itemForm.state > 2 && itemForm.state < 7 && itemForm.orderIndex && itemForm.orderIndex.state!=10">
								<u-button size="small" color="#F74A33"
									:customStyle="{color:'#fff',height:'40px',fontSize:14}" type="error"
									@click="opClick('refund')">退款</u-button>
							</view>
							<view class="f-g-1 mr10" v-if="itemForm.state == 7">
								<u-button size="small" color="#F74A33"
									:customStyle="{color:'#fff',height:'40px',fontSize:14}" type="error"
									@click="opClick('refound')">同意退款</u-button>
							</view>
							<view class="f-g-1 mr10" v-if="itemForm.state >= 1">
								<u-button size="small" :customStyle="{color:'#4275F4',height:'40px',fontSize:14}"
									type="primary" :plain="true" @click="opClick('gotoRemark')">商家备注</u-button>
							</view>
							<view class="f-g-1 p-r" v-if="itemForm.state >= 2 && itemForm.state < 8">
								<u-button size="small" type="primary" :plain="true"
									:customStyle="{color:'#4275F4',height:'40px',fontSize:14}"
									@click="outShow = !outShow">打印订单</u-button>
								<view class="dayin p10 bf f16" v-if="outShow">
									<view class="item pb10" @click="opClick('inOrderPrint',15)">商家联</view>
									<view class="item pb10 pt10" @click="opClick('inOrderPrint',16)">顾客联</view>
									<view class="item pb10 pt10" @click="opClick('inOrderPrint',17)">制作总单</view>
									<view class="item pt10 ib" @click="opClick('inOrderPrint',18)">制作分单</view>
								</view>
							</view>
						</block>
						<block v-if="tabval == 'inStoreRef'">
							<view class="f-g-1 mr10" v-if="itemForm.state == 2">
								<u-button color="#4275F4" size="small"
									:customStyle="{color:'#fff',height:'40px',fontSize:14}" type="primary"
									@click="opClick('inReceiving')">立即接单</u-button>
							</view>
							<view class="f-g-1 mr10" v-if="itemForm.state == 2">
								<u-button size="small" :customStyle="{color:'#fff',height:'40px',fontSize:14}"
									type="error" @click="opClick('inCanceling')">拒单</u-button>
							</view>
							<view class="f-g-1 mr10"
								v-if="itemForm.state == 3 && (itemForm.diningType == 5 || itemForm.diningType == 6)">
								<u-button size="small" :customStyle="{color:'#fff',height:'40px',fontSize:14}"
									type="success" @click="opClick('inMakeding')">制作完成</u-button>
							</view>
							<view class="f-g-1 mr10"
								v-if="itemForm.state == 4 && (itemForm.diningType == 6 || itemForm.diningType == 5)">
								<u-button color="#4275F4" size="small"
									:customStyle="{color:'#fff',height:'40px',fontSize:14}" type="primary"
									@click="opClick('callOrder')">叫号</u-button>
							</view>
							<view class="f-g-1 mr10"
								v-if="itemForm.isPay == 1 && itemForm.state > 2 && itemForm.state < 7 && itemForm.orderIndex && itemForm.orderIndex.state!=10">
								<u-button size="small" color="#F74A33"
									:customStyle="{color:'#fff',height:'40px',fontSize:14}" type="error"
									@click="opClick('inRefunding')">退款</u-button>
							</view>
							<view class="f-g-1 mr10"
								v-if="itemForm.state == 4 && (itemForm.diningType == 6 || itemForm.diningType == 5)">
								<u-button color="#4275F4" size="small"
									:customStyle="{color:'#fff',height:'40px',fontSize:14}" type="primary"
									@click="opClick('inCompleteOrder')">完成订单</u-button>
							</view>
							<view class="f-g-1 mr10" v-if="itemForm.state >= 1">
								<u-button size="small" :customStyle="{color:'#4275F4',height:'40px',fontSize:14}"
									type="primary" :plain="true" @click="opClick('gotoRemark')">商家备注</u-button>
							</view>
							<view class="f-g-1 p-r" v-if="itemForm.state >= 2 && itemForm.state < 8">
								<u-button size="small" :customStyle="{color:'#4275F4',height:'40px',fontSize:14}"
									type="primary" :plain="true" @click="inpShow = !inpShow">打印订单</u-button>
								<view class="dayin p10 bf f16" v-if="inpShow">
									<view class="item pb10" @click="opClick('inOrderPrint',3)">结账单</view>
									<view class="item pb10 pt10" @click="opClick('inOrderPrint',7)">客单</view>
									<view class="item pb10 pt10" @click="opClick('inOrderPrint',6)">预结单</view>
									<view class="item pb10 pt10" @click="opClick('inOrderPrint',13)">制作总单</view>
									<view class="item pt10 ib" @click="opClick('inOrderPrint',14)">制作分单</view>
								</view>
							</view>
						</block>
						<block v-if="tabval == 'billRef'">
							<view class="f-g-1 mr10" v-if="itemForm.state==6">
								<u-button size="small" color="#F74A33"
									:customStyle="{color:'#fff',height:'40px',fontSize:14}" type="error"
									@click="opClick('billRefunding')">退款</u-button>
							</view>
							<view class="f-g-1">
								<u-button size="small" :customStyle="{color:'#4275F4',height:'40px',fontSize:14}"
									type="primary" :plain="true" @click="opClick('billOrderPrint')">打印订单</u-button>
							</view>
						</block>
						<block v-if="tabval == 'valueRef'">
							<view class="f-g-1">
								<u-button size="small" :customStyle="{color:'#4275F4',height:'40px',fontSize:14}"
									type="primary" :plain="true" @click="opClick('valueOrderPrint')">打印订单</u-button>
							</view>
						</block>
						<block v-if="tabval == 'integralRef'">
							<view class="f-g-1 mr10" v-if="itemForm.state == 2">
								<u-button color="#4275F4" size="small"
									:customStyle="{color:'#fff',height:'40px',fontSize:14}" type="primary"
									@click="opClick('integralSmhx')">扫码核销</u-button>
							</view>
							<view class="f-g-1 mr10" v-if="itemForm.state == 2">
								<u-button color="#4275F4" size="small"
									:customStyle="{color:'#fff',height:'40px',fontSize:14}" type="primary"
									@click="opClick('integralSdhx')">手动核销</u-button>
							</view>
							<!-- <view class="f-g-1">
								<u-button size="small" :customStyle="{color:'#4275F4',height:'40px',fontSize:14}"
									type="primary" :plain="true" @click="opClick('integralPrint')">打印订单</u-button>
							</view> -->
						</block>
					</view>
				</view>
			</view>
		</view>
		<!-- <view v-else class="f-1 f-c-c" style="overflow-y:auto">
			<u-empty mode="order" :icon="'@/static/imgs/data.png'"></u-empty>
		</view> -->
		<empty v-else txt="暂无订单" t="dd" />
		<u-modal :show="show" :showCancelButton="true" width="250px" title=" " cancelText="取消" :content='showMsg'
			@confirm="confirm" @cancel="show=false" confirmColor="#fff"></u-modal>
		<remarkMask ref="remarkMaskRef" @itemRemark="itemRemark"></remarkMask>
		<hexiaoMask ref="hexiaoMaskRef" @itemRemark="hexiao"></hexiaoMask>
		<psChannel ref="psChannelRef" @handPsChannel="handPsChannel"></psChannel>
		<psDl ref="psDlRef"></psDl>
	</view>
</template>

<script>
	import remarkMask from './order/remarkMask.vue';
	import psChannel from './order/psChannel.vue';
	import psDl from './order/psDl.vue';
	import empty from '@/components/other/empty.vue';
	import hexiaoMask from './order/hexiaoMask.vue';
	import {
		fuzhi,
	} from "@/common/handutil.js"
	export default ({
		components: {
			remarkMask,
			psChannel,
			psDl,
			empty,
			hexiaoMask,
		},
		data() {
			return {
				list1: [{
					name: '自提订单',
					value: 'selfRef',
				}, {
					name: '外送订单',
					value: 'sideRef',
				}, {
					name: '店内订单',
					value: 'inStoreRef',
				}, {
					name: '买单订单',
					value: 'billRef',
				}, {
					name: '储值订单',
					value: 'valueRef',
				}],
				current: 0,

				tabval: 'inStoreRef',
				// tab2: 0,
				// isItem: 0,
				tabs: ['外卖/自提', '堂食/快餐', '收银', '储值'],
				tabs2: ['基础信息', '商品信息', '订单日志'],
				list: [],
				itemForm: {},

				pageUrl: this.api.inStoreOrder,
				queryForm: {
					keyword: "",
					userKeyword: "",
					state: "",
					scene: 2,
					pageNo: 1,
					pageSize: 10,
					appointment: '',
					payType: '',
					timeType: 2,
					timeChannel: 'created_at',
				},
				total: 0,
				orderList: [],
				show: false,
				showMsg: '',
				channels: [{
						value: '',
						text: '全部类型'
					},
					{
						value: 'instant',
						text: '即时单'
					},
					{
						value: 'appointment',
						text: '预约单'
					}
				],
				classfiy: [{
						value: '',
						text: '全部方式'
					},
					{
						value: 'wexin',
						text: '微信支付'
					},
					{
						value: 'ali',
						text: '支付宝支付'
					},
					{
						value: 'balance',
						text: '余额支付'
					},
				],
				classfiys: [{
						value: '',
						text: '全部方式'
					},
					{
						value: 'wexin',
						text: '微信支付'
					},
					{
						value: 'ali',
						text: '支付宝支付'
					},
					{
						value: 'balance',
						text: '余额支付'
					},
					{
						value: 'cash',
						text: '现金支付'
					},
				],
				dates: [{
						value: 2,
						text: '今日'
					},
					{
						value: -1,
						text: '昨日'
					},
					{
						value: 7,
						text: '7日内'
					}
				],
				dateTime: [{
						value: 'created_at',
						text: '下单时间'
					},
					{
						value: 'payTime',
						text: '支付时间'
					},
					{
						value: 'completionTime',
						text: '完成时间'
					}
				],
				inpShow:false,
				outShow:false,
			}
		},
		methods: {
			init() {
				this.fetchData()
				this.getSetInfo()
			},
			async getSetInfo() {
				let {
					data
				} = await this.beg.request({
					url: this.api.systemConfig
				})
				uni.setStorageSync('setInfo', data)
			},
			async fetchData() {
				let {
					data: {
						list,
						pageNo,
						pageSize,
						total
					},
				} = await this.beg.request({
					url: this.pageUrl,
					data: this.queryForm,
				})
				this.orderList = list ? list : []
				this.total = total
				if (list && list.length) {
					if (this.tabval == 'selfRef' || this.tabval == 'sideRef' || this.tabval == 'inStoreRef') {
						this.isItem = list[0].id
						this.orderDl(this.tabval == 'selfRef' || this.tabval == 'sideRef' ? list[0].id : list[0]
							.orderSn)
					} else {
						this.isItem = list[0].id
						this.itemForm = list[0]
					}
				} else {
					this.itemForm = {}
				}
			},
			handDiningType(e) {
				this.queryForm.appointment = e
				this.fetchData()
			},
			handSource(e) {
				this.queryForm.payType = e
				this.fetchData()
			},
			handDate(e) {
				this.queryForm.timeType = e
				this.fetchData()
			},
			handChannel(e) {
				this.queryForm.timeChannel = e
				this.fetchData()
			},
			change(e) {
				this.queryForm.pageNo = e.current;
				this.fetchData()
			},
			handTabs(e) {
				this.queryForm.pageNo = 1
				this.queryForm.appointment = ''
				this.queryForm.payType = ''
				this.queryForm.timeType = 2
				this.queryForm.timeChannel = 'created_at'
				this.tabval = e.value
				if (e.value == 'selfRef') {
					this.queryForm.scene = 2
					this.pageUrl = this.api.orderList
				} else if (e.value == 'sideRef') {
					this.queryForm.scene = 1
					this.pageUrl = this.api.orderList
				} else if (e.value == 'inStoreRef') {
					this.queryForm.scene = 3
					this.pageUrl = this.api.inStoreOrder
				} else if (e.value == 'billRef') {
					this.pageUrl = this.api.personPay
				} else if (e.value == 'valueRef') {
					this.pageUrl = this.api.storedValueOrder
				}else if (e.value == 'integralRef') {
					this.queryForm.scene = 4
					this.pageUrl = this.api.pointsMallOrder
				}
				this.fetchData()
				this.outShow = false
				this.inpShow = false
			},
			async itemRemark(e) {
				let {
					data,
					msg
				} = await this.beg.request({
					url: `${this.itemForm.scene>2 ? this.api.inNotes : this.api.oNotes}/${this.itemForm.id}`,
					method: "POST",
					data: {
						notes: e
					},
				})
				uni.$u.toast(msg)
				this.orderDl(this.tabval == 'selfRef' || this.tabval == 'sideRef' ? this.itemForm.id : this
					.itemForm.orderSn)
				this.$refs['remarkMaskRef'].close()
			},
			handPsChannel() {
				this.orderDl(this.tabval == 'selfRef' || this.tabval == 'sideRef' ? this.itemForm.id : this.itemForm
					.orderSn)
			},

			clickItem(item, index) {
				this.isItem = item.id
				this.current = index
				if (this.tabval == 'selfRef' || this.tabval == 'sideRef' || this.tabval == 'inStoreRef') {
					this.orderDl(this.tabval == 'selfRef' || this.tabval == 'sideRef' ? item.id : item.orderSn)
				} else {
					this.itemForm = item
				}
			},
			async orderDl(i) {
				let {
					data
				} = await this.beg.request({
					url: `${this.tabval == 'inStoreRef' ? this.api.inStoreOrder : this.api.orderList}/${i}`
				})
				this.itemForm = data
				if (this.tabval == 'inStoreRef' && data.diningType == 6) {
					this.itemForm.goods = data.goods
				} else if (this.tabval == 'selfRef' || this.tabval == 'sideRef') {
					this.itemForm.goods = data.subGoods && data.subGoods.length && data.subGoods || data.goods
						.length && data.goods
				}
			},
			opClick(v,t) {
				this.showType = v
				switch (v) {
					case "cancel":
						this.showMsg = '你确定取消订单吗?'
						this.show = true
						break;
					case "receiving":
						this.showMsg = '你确定立即接单吗?'
						this.show = true
						break;
					case "refOrder":
						this.showMsg = '你确定拒单吗?'
						this.show = true
						break;
					case "makeding":
						this.showMsg = '你确定制作完成吗?'
						this.show = true
						break;
					case "completeing":
						this.showMsg = '你确定确认取单吗?'
						this.show = true
						break;
					case "refound":
						this.showMsg = '你确定同意退款吗?'
						this.show = true
						break;
					case "refund":
						this.showMsg = '你确定退款吗?'
						this.show = true
						break;
					case "refuse":
						this.showMsg = '你确定拒绝退款吗?'
						this.show = true
						break;
					case "completeOrder":
						this.showMsg = '你确定完成订单吗?'
						this.show = true
						break;
					case "gotoPs":
						this.$refs['psChannelRef'].open(this.itemForm)
						break;
					case "print":
						this.handRequest(this.itemForm, 'printOrder');
						break;
					case "gotoRemark":
						this.$refs['remarkMaskRef'].open(this.itemForm)
						break;
					case "inReceiving":
						this.showMsg = '你确定立即接单吗?'
						this.show = true
						break;
					case "inCanceling":
						this.showMsg = '你确定拒单吗?'
						this.show = true
						break;
					case "inMakeding":
						this.showMsg = '你确定制作完成吗?'
						this.show = true
						break;
					case "callOrder":
						this.handRequest(this.itemForm, 'callNum');
						break;
					case "inRefunding":
						this.showMsg = '你确定退款吗?'
						this.show = true
						break;
					case "inCompleteOrder":
						this.showMsg = '你确定完成订单吗?'
						this.show = true
						break;
					case "inOrderPrint":
						this.handInOrderPrint(this.itemForm, 'printOrder', t);
						this.inpShow = false
						this.outShow = false
						break;
					case "billRefunding":
						this.showMsg = '你确定退款吗?'
						this.show = true
						break;
					case "billOrderPrint":
						this.handInOrderPrint(this.itemForm, 'printOrder', 4);
						break;
					case "valueOrderPrint":
						this.handInOrderPrint(this.itemForm, 'printOrder', 5);
						break;
					case "integralPrint":
						this.handInOrderPrint(this.itemForm, 'printOrder', 6);
						break;
					case "integralSmhx":
						var that = this
						uni.scanCode({
							onlyFromCamera: true,
							success: function(res) {
								if (res.result) {
									that.hexiao(res.result,this.itemForm)
								}
							}
						});
						break;
					case "integralSdhx":
						this.$refs['hexiaoMaskRef'].open(this.itemForm)
						break;
					default:
						break;
				}
			},
			async confirm(e) {
				switch (this.showType) {
					case "cancel":
						this.handRequest(this.itemForm, 'oClose');
						break;
					case "receiving":
						this.handRequest(this.itemForm, 'receiving');
						break;
					case "refOrder":
						this.handRequest(this.itemForm, 'refund');
						break;
					case "makeding":
						this.handRequest(this.itemForm, 'maked');
						break;
					case "completeing":
						this.handRequest(this.itemForm, 'complete');
						break;
					case "refound":
						this.handRequest(this.itemForm, 'refund');
						break;
					case "refund":
						this.handRequest(this.itemForm, 'refund');
						break;
					case "refuse":
						this.handRequest(this.itemForm, 'reject');
						break;
					case "completeOrder":
						this.handRequest(this.itemForm, 'complete');
						break;
					case "inReceiving":
						this.handRequest(this.itemForm, 'inStoreReceived');
						break;
					case "inCanceling":
						this.handRequest(this.itemForm, 'inOClose');
						break;
					case "inMakeding":
						this.handRequest(this.itemForm, 'inStoreMaked');
						break;
					case "inRefunding":
						this.handRequest(this.itemForm, 'inRefund');
						break;
					case "inCompleteOrder":
						this.handRequest(this.itemForm, 'inStoreComplete');
						break;
					case "billRefunding":
						this.handOrderSn(this.itemForm, 'ppRefund');
						break;

				}
			},
			async hexiao(i,v) {
				let {
					data,
					msg,
				} = await this.beg.request({
					url: `${this.api.pointVerification}/${v.orderSn}`,
					method: 'POST',
					data:{
						code:i
					}
				})
				uni.$u.toast(msg)
				this.fetchData()
				this.$refs['hexiaoMaskRef'].close()
			},
			async handRequest(v, a) {
				let {
					data,
					msg
				} = await this.beg.request({
					url: `${this.api[a]}/${v.id}`,
					method: "POST",
					data: {
						storeId: v.storeId
					},
				})
				this.show = false
				uni.$u.toast(msg)
				this.fetchData()
			},
			async handOrderSn(v, a) {
				let {
					data,
					msg
				} = await this.beg.request({
					url: `${this.api[a]}/${v.orderSn}`,
					method: "POST",
					data: {
						storeId: v.storeId
					},
				})
				this.show = false
				uni.$u.toast(msg)
				this.fetchData()
			},
			async handInOrderPrint(v, a, t) {
				let {
					data,
					msg
				} = await this.beg.request({
					url: `${this.api[a]}/${v.id}`,
					method: "POST",
					data: {
						storeId: v.storeId,
						scene: t,
					},
				})
				uni.$u.toast(msg)
				this.fetchData()
			},
			handFz(n) {
				fuzhi(n)
			},
			seePs(v) {
				this.$refs['psDlRef'].open(v)
			},
		}
	})
</script>

<style lang="scss" scoped>
	.main {
		.left {
			width: 29.2825vw;

			.lwrap {
				// height: 74.5098vh;
				max-height: calc(100vh - 190px);
				overflow: hidden;
				overflow-y: scroll;

				.list {
					background: #fff;
					border: 2px solid #EBEAF0;
					border-left: 6px solid #4275F4;
					border-radius: 10px;
				}

				.lcur {
					background: #E3EDFE;
					border: 2px solid #4275F4;
					border-left: 6px solid #4275F4;
				}
			}
		}

		.right {
			.goods {
				width: 27.8184vw;
				// height: 80.0312vh;
				max-height: calc(100vh - 190px);
				overflow: hidden;
				overflow-y: scroll;
				border: 1px solid #e6e6e6;
			}

			.order {
				// height: 71.6145vh;
				max-height: calc(100vh - 190px);
				overflow: hidden;
				overflow-y: scroll;
				border: 1px solid #e6e6e6;

				.fg0 {
					width: 5.8565vw;
				}

				.tit {
					// background: #e6e6e6;
				}
			}
		}

		.bbae6 {
			border-bottom: 2px dotted #e6e6e6;
		}

		.line {
			width: 2px;
			height: 18px;
			margin: 0 10px;
			background: #e6e6e6;
		}

	}

	.l_bot {
		border-top: 1px solid #ddd;
		white-space: nowrap;
		overflow: hidden;
		overflow-x: scroll;
	}

	/deep/.uni-pagination {
		.page--active {
			// display: inline-block;
			// width: 2.1961vw;
			// height: 2.1961vw;
			background: #4275F4 !important;
			color: #fff !important;
		}

		// .is-phone-hide {
		// 	width: 2.1961vw;
		// 	height: 2.1961vw;
		// }

		// .uni-pagination__total {
		// 	font-size: 1.3177vw;
		// 	width: auto;
		// 	display: -webkit-box;
		// 	display: -webkit-flex;
		// 	display: flex;
		// 	align-items: center;
		// }

		// span {
		// 	font-size: 1.3177vw;
		// }
	}

	.btn {
		box-shadow: 0px 0px 10px 0px #e6e6e6;
	}

	.u-button--warning {
		background-color: #4275F4;
		border-color: #4275F4;
	}

	.u-popup {
		flex: 0;
	}

	.search {
		.sw {
			width: 8.7847vw;
		}

		.iw {
			width: 14.6412vw;
		}

		/deep/.u-form {
			display: flex !important;
			// overflow-x: scroll;
			flex-wrap: wrap;

			.u-input {
				background: #fff;

				.input-placeholder,
				.uni-input-input {
					font-size: 16px;
				}
			}

			.uni-select {
				height: 38px !important;
				background: #fff;

				.uni-select__input-placeholder {
					font-size: 16px !important;
					color: #ccc;
				}

				.uni-select__selector-item {
					span {
						font-size: 16px;
					}
				}
			}
		}
	}
	.i_tag {
		padding: 0 0.2196vw;
		border: 1px solid #FD8906;
		border-radius: 3px;
		background: #fff9ec;
	}
	
	.i_tag2{
		background: #3E77B9;
		border: 1px solid #3E77B9;
	}
	
	.dayin{
		width: 100%;
		position: absolute;
		bottom: 50px;
		left: 0;
		box-shadow: 0px 0px 10px 0px #e6e6e6;
		.item{
			border-bottom: 1px solid #e6e6e6;
			cursor: pointer;
		}
		.ib{
			border-bottom: none;
		}
	}

	@media (min-width: 1500px) and (max-width: 3280px) {
		.main {
			.left {
				width: 400px;

				.lwrap {
					// height: 570px;
					max-height: calc(100vh - 190px);
				}
			}

			.right {
				.goods {
					max-height: calc(100vh - 190px);
					width: 380px;
					height: auto;
				}

				.order {
					max-height: calc(100vh - 190px);
					// height: 550px;
				}
			}
		}

		.search {
			.sw {
				width: 120px;
			}

			.iw {
				width: 200px;
			}
		}
	}
</style>
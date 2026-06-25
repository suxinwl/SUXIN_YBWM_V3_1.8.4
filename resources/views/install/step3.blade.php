<html>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1.0" />
    <title>速信V3安装</title>
    <script src="js/vue.js"></script>
    <link rel="stylesheet" href="element-plus/index.css">
    <link rel="stylesheet" type="text/css" href="css/reset.css" />
    <link rel="stylesheet" type="text/css" href="css/Installation.css" />
    <script src="element-plus/index.js"></script>
    <script src="js/request.js"></script>
    <script src="js/axios.min.js"></script>
    <style>
        .tag{
            width: 65px;
            height: 20px;
            line-height: 20px;
            background: #DCEAFE;
            color: #0870FF;
            border-radius: 4px;
            text-align: center;
            font-size: 14px;
        }
        .tag.no{
            background-color: #FDECE5;
            color: #F44613;
        }
    </style>
</head>
<body>
<div id="app">
    <div class="container">
        <div class="public_width">
            <h2 class="title">欢迎您使用速信V3独立版</h2>
            <!--<div class="leftbg"></div>-->
            <!--<div class="rightbg"></div>-->
            <div class="main">
                <!-- 步骤 -->
                <div class="step">
                    <div class="step_width">
                        <!-- 进度条高亮 -->
                        <div class="step_active" style="width: 60%;"></div>
                        <div class="step_box">
                            <div class="step_bin step_first_txt step_textactive">
                                <span>01</span>
                                <p>许可协议</p>
                            </div>
                            <div class="step_bin step_second_txt step_textactive">
                                <span>02</span>
                                <p>站点信息</p>
                            </div>
                            <div class="step_bin step_third_txt step_textactive">
                                <span>03</span>
                                <p>环境监测</p>
                            </div>
                            <div class="step_bin step_fourth_txt">
                                <span>04</span>
                                <p>  数据库写入</p>
                            </div>
                            <div class="step_bin step_fourth_txt">
                                <span>05</span>
                                <p>安装完成</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="main_box">
                    <div style="position: relative;top: -40px;left: 20px;font-size: 24px;color: #333333;font-weight: 600;">服务器环境检测</div>
                    <!-- 环境监测 -->
                    <div class="monitor">
                        <div class="monitor_list">
                            <ul>
                                <!-- 环境监测 -->
                                <li>
                                    <p>环境监测</p>
                                    <ul class="monitor_list_in">
                                        <!-- <li>服务器环境</li> -->
                                        <li>操作系统</li>
                                        <li>服务器配置</li>
                                        <li>web服务器</li>
                                        <li>PHP版本</li>
                                        <li>php执行最大内存限制</li>
                                        <li>php文件上传大小限制</li>
                                    </ul>
                                </li>
                                <!-- 推荐配置 -->
                                <li>
                                    <p>推荐配置</p>
                                    <ul class="monitor_list_in">
                                        <li>Linux Centos 7.2 64位以上</li>
                                        <li>不低于2核4G</li>
                                        <li>Nginx>=1.1.5</li>
                                        <li>>=8.0</li>
                                        <li>>=128M</li>
                                        <li>>=50M</li>
                                    </ul>
                                </li>
                                <!-- 当前状态 -->
                                <li>
                                    <p>当前配置</p>
                                    <ul class="monitor_list_in">
                                        <li> @{{tableData. system_version}}</li>
                                        <li> @{{tableData.cpu_core}}</li>
                                        <li> @{{tableData.server_version}}</li>
                                        <li> @{{tableData.php_version}}</li>
                                        <li> @{{tableData.memory_limit}}</li>
                                        <li> @{{tableData.execute_max}}</li>
                                    </ul>
                                </li>
                            </ul>
                            <ul>
                                <li>
                                    <p>PHP扩展</p>
                                    <ul class="monitor_list_in">
                                        <li>redis</li>
                                        <li>swoole</li>
                                        <li>SG13</li>
                                    </ul>
                                </li>
                                <li>
                                    <p>扩展安装</p>
                                    <ul class="monitor_list_in">
                                        <li>必须安装</li>
                                        <li>必须安装</li>
                                        <li>必须安装</li>
                                    </ul>
                                </li>
                                <li>
                                    <p>安装状态</p>
                                    <ul class="monitor_list_in">
                                        <li>
                                            <div :class="['tag',tableData.redis_extend ? '' : 'no']"> @{{tableData.redis_extend ? '已安装' : '未安装'}}</div>
                                            <!-- <img v-if="tableData.redis_extend" src="image/success_icon.png" alt="">
                                            <img v-else src="image/close.png"/> -->
                                        </li>
                                        <li>
                                            <div :class="['tag',tableData.swoole_extend ? '' : 'no']"> @{{tableData.swoole_extend ? '已安装' : '未安装'}}</div>
                                            <!-- <img v-if="tableData.swoole_extend" src="image/success_icon.png" alt="">
                                            <img v-else src="image/close.png"/> -->
                                        </li>
                                        <li>
                                            <div :class="['tag',tableData.sg13 ? '' : 'no']"> @{{tableData.sg13 ? '已安装' : '未安装'}}</div>
                                            <!-- <img v-if="tableData.sg13" src="image/success_icon.png" alt="">
                                            <img v-else src="image/close.png"/> -->
                                        </li>
                                    </ul>
                                </li>
                            </ul>
                            <ul>
                                <li>
                                    <p>站点内容</p>
                                    <ul class="monitor_list_in">
                                        <li>授权域名</li>
                                        <li>服务器IP</li>
                                        <li>服务器ID</li>
                                    </ul>
                                </li>
                                <li>
                                    <p>站点信息</p>
                                    <ul class="monitor_list_in">
                                        <li> @{{tableData.domain_url}}</li>
                                        <li> @{{tableData.ip_address}}</li>
                                        <li> @{{tableData.server_id}}</li>
                                    </ul>
                                </li>
                                <li>
                                    <p>获取状态</p>
                                    <ul class="monitor_list_in">
                                        <li><div :class="['tag',tableData.domain_url ? '' : 'no']"> @{{tableData.domain_url ? '已获取' : '未获取'}}</div><span v-if="tableData.domain_url"></span></li>
                                        <li><div :class="['tag',tableData.ip_address ? '' : 'no']"> @{{tableData.ip_address ? '已获取' : '未获取'}}</div></li>
                                        <li><div :class="['tag',tableData.server_id ? '' : 'no']"> @{{tableData.server_id ? '已获取' : '未获取'}}</div></li>
                                    </ul>
                                </li>
                            </ul>
                        </div>
                        <div class="monitor_operation">
                            <button @click="reset">重新监测</a></button>
                            <button class="step_second"  @click="gotoStep" :disabled="disabled">下一步</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer">
            <div class="center">
                <p>Copyright© 2019-2023 速信版权所有</p>
                <p>网站备案号：鄂ICP备19023529号-1</p>
            </div>
        </div>
    </div>
</div>
</div>
<script>
    const App = {
        data() {
            return {
                tableData: '',
                errMsg:false,
                disabled:true,
                check:false,
            }
        },
        created() {
            this.getCheckEnvironment('check');
        },
        methods: {
            async getCheckEnvironment(type){
                const res = await shttp.post(`/install/check-environment`).send({"type":type}).end();

                if (res.code == 200) {
                    // this.tableData = res.data;
                    this.errMsg = false
                    this.disabledNext = false;
                    if(type == "reset"){
                        this.$message({
                            message: "重新检测成功",
                            type: 'success',
                        })
                    }
                    if(res.data.redis_extend && res.data.swoole_extend && res.data.sg13 ){
                        this.disabled=false;
                    }
                    this.tableData = res.data;
                    if(type =='msg'){
                        this.check=true;
                        window.location.href="/install/step4"
                    }
                }else{
                    this.$message({
                        message: res.msg,
                        type: 'error',
                    })
                    this.errMsg = true

                }
            },
            reset(){
                this.getCheckEnvironment("reset");
            },
            gotoStep(){
                this.getCheckEnvironment("msg");
                if(this.check){
                    window.location.href="/install/step4"
                }
            }
        },
    };
    const app = Vue.createApp(App);
    app.use(ElementPlus);
    app.mount("#app");
</script>
</body>
</html>

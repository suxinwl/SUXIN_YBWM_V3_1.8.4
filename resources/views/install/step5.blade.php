<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>速信V3安装</title>
    <script src="js/vue.js"></script>
    <link rel="stylesheet" type="text/css" href="css/public.css"/>
    <link rel="stylesheet" type="text/css" href="element-plus/index.css"/>
    <link rel="stylesheet" type="text/css" href="css/reset.css"/>
    <link rel="stylesheet" type="text/css" href="css/Installation.css"/>
    <script src="element-plus/index.js"></script>
    <script src="js/request.js"></script>
    <script src="js/axios.min.js"></script>
    <style>
        #app{
            height: 100vh;
        }
        .main_box{
            padding-bottom: 76px;
        }
        .gotoLogin{
            width: 180px;
            cursor: pointer;
            transition: all 0.5s;
            font-family: MicrosoftYaHei-Bold;
            font-size: 15px;
            background-color: #006CFF;
            font-family: MicrosoftYaHei-Bold;
            height: 50px;
        }
        .concat{
            position: relative;
            margin-right: 20px;
            z-index: 999;
        }
        .code{
            position: absolute;
            top: 0;
            width: 293px;
            height: 395px;
            background:green url("./image/FjmQy3mwmDNSENpQfDwktA55C5zA.png") center/cover;
            background-size: 100% 100%;
            background-color: #fff;
            padding: 20px 20px;
            border-radius: 2px;
            left: -302px;
            top: -312px;
        }
        .code--title{
            font-size: 24px;
            line-height: 33px;
            color: #323233;
            font-weight: 600;
            text-align: left;
        }
        .code--smallTitle{
            font-size: 15px;
            line-height: 22px;
            color: #323233;
            text-align: left;
        }
        .code--img{
            display: block;
            width: 196px;
            margin: 37px auto 0;
        }
        .code--placeholder{
            font-size: 16px;
            line-height: 22px;
            color: #323233;
            text-align: center;
            margin-top: 16px;
        }
        .addressList{
            background-color: #F7F9FF;
            width: 508px;
            margin: 50px auto;
            padding: 20px;
        }
        .addressList .title{
            color: #006CFF;
            font-size: 21px;
            margin-bottom: 24px;
        }
        .addressList .item{
            display: flex;
            /* justify-content: center; */
            margin-bottom: 12px;
        }
        .addressList .item div:nth-child(1){
            font-size: 14px;
            color:#8B8E8F;
            border-radius: 8px;
        }
        .addressList .item div:nth-child(2){
            color: #333;
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
                        <div class="step_active" style="width: 100%;"></div>

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
                            <div class="step_bin step_fourth_txt step_textactive">
                                <span>04</span>
                                <p>  数据库写入</p>
                            </div>
                            <div class="step_bin step_fourth_txt step_textactive">
                                <span>05</span>
                                <p>安装完成</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="main_box t_c">
                    <img src="image/zhanWei.png" />
                    <div class="flex-x-center">
                        <img src="image/zhanWei_l.png">
                        <div class="fon_26" style="color: #006CFF;font-family: ArialNarrow-BoldItalic;">恭喜您，系统已成功安装!</div>
                        <img src="image/zhanWei_r.png">
                    </div>
                    <div class="fon_14 mar_t20" style="color: #8B8E8F;">希望速信V3独立版能够为您的企业带来客户并创造价值；在后期的使用当中，如您有更好的产品建议和意见，请及时的反馈给我们哦！</div>
                    <div class="addressList">
                        <div class="title">管理端后台账号地址信息</div>
                        <div style="margin-left: 78px;">
                            <div class="item">
                                <div style="width:160px">管理端地址：</div>
                                <div style="overflow-wrap: break-word;width: 300px;"><a href="/super">https://@{{host}}</a></div>
                            </div>
                            <div class="item">
                                <div style="width:160px">管理员账号：</div>
                                <div style="overflow-wrap: break-word;width: 300px;">@{{userInfo.username}}</div>
                            </div>
                            <div class="item">
                                <div style="width:160px">管理员密码：</div>
                                <div style="overflow-wrap: break-word;width: 300px;">@{{userInfo.password}}</div>
                            </div>
                        </div>
                    </div>
                    <div class="flex-x-center">
                        <div class="concat">
                            <el-button color="#006CFF" class="gotoLogin" size="large" @click="gotoConcat">联系我们</el-button>
                            <div class="code" :style="{ opacity: concatCard ? 1 : 0 }">
                                <div class="code--title">微信服务群</div>
                                <div class="code--smallTitle">请扫描下方二维码，加入相关售后群</div>
                                <img class="code--img" src="./image/ewm.png" />
                                <div class="code--placeholder">手机扫码加我微信</div>
                            </div>
                        </div>
                        <el-button color="#006CFF" class="gotoLogin" @click="goAdmin" size="large">登录后台</el-button>
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
                concatCard:false,
                userInfo:"",
                host:"",
            }
        },
        created(){
            var address=window.location.href; //url
            var hostport=document.location.host;//ip:端口号
            this.host = hostport + '/super/#/login'
            const userInfo = localStorage.getItem('userInfo');
            if(userInfo){
                this.userInfo = JSON.parse(userInfo);
            }
        },
        methods: {
            goAdmin(){
                window.location.href = '/super/#/login';
            },
            gotoConcat(){
                this.concatCard = !this.concatCard;
            }
        },
    };
    const app = Vue.createApp(App);
    app.use(ElementPlus);
    app.mount("#app");
</script>
</body>
</html>

<html>

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1.0" />
    <script src="js/vue.js"></script>
    <link rel="stylesheet" href="element-plus/index.css">
    <link rel="stylesheet" type="text/css" href="css/reset.css" />
    <link rel="stylesheet" type="text/css" href="css/Installation.css" />
    <script src="element-plus/index.js"></script>
    <script src="js/request.js"></script>
    <script src="js/axios.min.js"></script>
    <title>速信V3安装</title>
    <style>
        /* ::v-deep */
        :root {
            --fill-color: #006CFF;
        }

        .el-checkbox .el-checkbox__label {
            color: #333333 !important;
        }

        .el-checkbox .el-checkbox__input.is-checked .el-checkbox__inner,
        .el-checkbox .el-checkbox__input.is-indeterminate .el-checkbox__inner {
            background-color: var(--fill-color);
            border-color: var(--fill-color)
        }

        .el-checkbox .el-checkbox__input.is-focus .el-checkbox__inner,
        .el-checkbox .el-checkbox__inner:hover {
            border-color: var(--fill-color);
        }
    </style>
</head>

<body>
<div id="app">
    <div class="container">
        <div class="public_width">
            <h2 class="title">欢迎您使用速信V3</h2>
            <!--<div class="leftbg"></div>-->
            <!--<div class="rightbg"></div>-->
            <div class="main">
                <!-- 步骤 -->
                <div class="step">
                    <div class="step_width">
                        <!-- 进度条高亮 -->
                        <div class="step_active"></div>

                        <div class="step_box">
                            <div class="step_bin step_first_txt step_textactive">
                                <span>01</span>
                                <p>许可协议</p>
                            </div>
                            <div class="step_bin step_second_txt">
                                <span>02</span>
                                <p>站点信息</p>
                            </div>
                            <div class="step_bin step_third_txt">
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
                    <div
                        style="position: relative;top: -40px;left: 20px;font-size: 24px;color: #333333;font-weight: 600;">
                        安装许可协议</div>
                    <!-- 许可协议 -->
                    <div class="agreement">
                        <div class="agreement_text">
                            <div class="agreement_text_in">
                                <h1>
                                    速信V3独立版用户使用协议
                                </h1>
                                <p style="text-indent:28px">
                                    <strong>为了使您正确并合法的使用本系统，请您在安装前务必阅读并清楚下面的协议条款：</strong>
                                </p>
                                <p style="text-indent:28px">
                                    &nbsp;
                                </p>
                                <p style="text-indent:32px">
                                    <strong><span
                                            style="font-size:16px"><strong>一、本授权协议适用且仅适用于在我司购买的商业授权的用户，速信（以下简称我司）对本授权协议有最终的解释权。</strong></span></strong>
                                </p>
                                <p style="text-indent:32px">
                                    <strong><span style="font-size:16px"><strong>二、协议许可的权利</strong></span></strong>
                                </p>
                                <p style="text-indent:28px">
                                    1、您可以在完全遵守本授权协议的基础上，将本系统应用于商业用途。
                                </p>
                                <p style="text-indent:28px">
                                    2、您可以在协议规定的约束和限制范围内修改系统底部版权或界面风格以适应您的商业要求。
                                </p>
                                <p style="text-indent:28px">
                                    3、您拥有使用本系统全部内容所有权，并独立承担与这些内容的相关法律义务。
                                </p>
                                <p style="text-indent:28px">
                                    4、您获得商业授权之后，您可以将本系统应用于商业用途，同时依据所购买的授权类型中确定相关的技术支持内容；
                                </p>
                                <p style="text-indent:28px">
                                    5、商业授权用户享有反映和提出意见的权力，相关意见将被作为我司首要考虑的范围，但没有一定被采纳的承诺或保证。
                                </p>
                                <p style="text-indent:28px">
                                    6、授权方保证本系统不含任何病毒、后门、无明显错误等问题，在符合软件需求的系统环境下能正常使用。
                                </p>
                                <p style="text-indent:28px">
                                    7、授权方保证对授权商业用户提供必要的技术支持和售后服务；在自购买时起，在技术支持期限内拥有通过官方指定的方式获得指定范围内的技术更新服务（系统技术服务期默认为一年）。
                                </p>
                                <p style="text-indent:32px">
                                    <strong><span
                                            style="font-size:16px"><strong>三、协议规定的约束和限制</strong></span></strong>
                                </p>
                                <p style="text-indent:28px">
                                    1、未获我司商业授权之前，不得将本系统用于任何用途；如需购买系统商业授权请联系速信官方售前客服。
                                </p>
                                <p style="text-indent:28px">
                                    2、授权的系统只限被授权方主体的授权域名、授权IP、授权服务器ID使用，未经官方许可，如转让或转卖，授权方将不提供任何技术支持和售后服务。
                                </p>
                                <p style="text-indent:28px">
                                    3、在商业授权用户系统服务期到期后，在不续服务费的情况下，仍然可以正常使用本系统，但禁止系统升级以及小程序上传更新服务。
                                </p>
                                <p style="text-indent:28px">
                                    4、未经我司许可，禁止在本系统的整体或部分基础上发展任何派生版本、修改版本或第三方版本用于重新分发。
                                </p>
                                <p style="text-indent:28px">
                                    5、本系统代码为加密版本，任何团体或个人禁止反编译、解密或其他破坏原始系统的操作。
                                </p>
                                <p style="text-indent:28px">
                                    6、禁止任何团体或个人在任何场景下贩卖我司系统破解版、解密版、二开版等非商业授权的版本。
                                </p>
                                <p style="text-indent:28px">
                                    7、如果您未能遵守本协议的条款，您的商业授权将被终止，所被许可的权利将被收回，并承担相应法律责任。
                                </p>

                                <p style="text-indent:28px">
                                    <strong>四、有限担保和免责声明</strong>
                                </p>
                                <p style="text-indent:28px">
                                    1、本系统及所附带的文件是作为不提供任何明确的或隐含的赔偿或担保的形式提供的。
                                </p>
                                <p style="text-indent:28px">
                                    2、在您购买本系统商业授权服务之后，我们将承诺对您提供相关的技术支持、使用说明、售后服务、BUG修复、系统升级等权益；但因系统BUG问题或用户使用操作不当等问题，我司不承担任何因使用本系统而产生的损失承担相关责任。用户出于自愿而购买本系统，您必须了解使用本系统的风险；
                                </p>
                                <p style="text-indent:28px">
                                    3、电子文本形式的授权协议如同双方书面签署的协议一样，具有完全的和等同的法律效力。您一旦开始确认本系统并安装速信V3独立版，即被视为完全理解并接受本协议的各项条款，在享有上述条款授予的权力的同时，受到相关的约束和限制。协议许可范围以外的行为，将直接违反本授权协议并构成侵权，我们有权随时终止授权，责令停止损害，并保留追究相关责任的权力。
                                </p>
                                <p style="text-indent:28px">
                                    4、本协议的效力、解释及纠纷的解决，适用于中华人民共和国法律。若用户和我司之间发生任何纠纷或争议，首先应友好协商解决，协商不成的，用户同意将纠纷或争议提交至我司注册所在地管辖权的人民法院管辖。
                                </p>
                                <p style="text-indent:28px">
                                    &nbsp;
                                </p>
                                <div class="data">
                                    <p style="text-align: right;">速信</p>
                                    <p style="text-align: right;">2023年3月15日</p>
                                </div>
                            </div>
                        </div>
                        <div class="agreement_operation">
                            <el-checkbox-group v-model="agree" @change="changAgreement">
                                <el-checkbox label="true" />
                                同意<span class="color_b">速信V3独立版商业授权安装许可协议</span>
                            </el-checkbox-group>
                            <input type="button" :value='counterDownTimer' :disabled="disabledNext" id="buu"
                                   class="step_first" @click="gotoNextStep()">
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
<script>
    const App = {
        data() {
            return {
                counterDownTimer: '请仔细阅读，剩余3秒',
                agree: [],
                disabledNext: true,//继续按照按钮禁用 true 禁用 false 启用
                time: 3,// 协议倒计时
            }
        },
        beforeCreate() {
            const counter = setInterval(() => {
                this.time--;
                this.counterDownTimer = `请仔细阅读，剩余${this.time}秒`
                if (this.time < 0) {
                    clearInterval(counter)
                    this.counterDownTimer = '继续安装'
                    if (this.agree.length > 0 && this.agree[0] == 'true') {
                        this.disabledNext = false
                    }
                }
            }, 1000)
        },
        methods: {
            gotoNextStep() {
                if (!this.agree || this.agree.length < 1) {
                    this.$message({
                        message: '请先勾选服务协议',
                        type: 'warning',
                    })
                    return
                }
                // window.location.href="step2.html"
                window.location.href = "/install/step2"
            },
            changAgreement(e) {
                if (this.time < 0) {
                    if (e[0] == 'true') {
                        this.disabledNext = false
                    } else {
                        this.disabledNext = true
                    }
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

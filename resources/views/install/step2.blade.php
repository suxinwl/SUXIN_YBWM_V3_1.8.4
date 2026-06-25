<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>速信V3安装</title>
    <script src="js/vue.js"></script>
    <link rel="stylesheet" type="text/css" href="element-plus/index.css"/>
    <link rel="stylesheet" type="text/css" href="css/reset.css"/>
    <link rel="stylesheet" type="text/css" href="css/Installation.css"/>
    <script src="element-plus/index.js"></script>
    <script src="js/request.js"></script>
    <script src="js/axios.min.js"></script>
    <style>
        :root{
            --el-text-color-regular: #000000;
            --el-font-size-base: 15px;
        }
        .el-input__inner{
            height: 40px;
            line-height: 40px;
        }
        .el-row{
            align-items: center;
        }
        .database{
            width: 47%;
        }
        .el-button{
            height: 100%;
        }
        .el-form-item{
            margin-bottom: 32px;
        }
        .el-form-item__error{
            padding-top: 18px;
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
                        <div class="step_active" style="width: 40%;"></div>

                        <div class="step_box">
                            <div class="step_bin step_first_txt step_textactive">
                                <span>01</span>
                                <p>许可协议</p>
                            </div>
                            <div class="step_bin step_second_txt step_textactive">
                                <span>02</span>
                                <p>站点信息</p>
                            </div>
                            <div class="step_bin step_third_txt">
                                <span>03</span>
                                <p>环境监测</p>
                            </div>
                            <div class="step_bin step_fourth_txt">
                                <span>04</span>
                                <p>数据库写入</p>
                            </div>
                            <div class="step_bin step_fourth_txt">
                                <span>05</span>
                                <p>安装完成</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="main_box">
                    <!-- 配置系统 -->
                    <div class="configure">
                        <el-form ref="ruleFormRef" :model="form" :rules="rules" label-width="120px">
                            <div class="flex">
                                <div class="database">
                                    <h3>验证信息</h3>
                                    <el-form-item label="手机号码" prop="mobile">
                                        <el-input v-model="form.mobile" placeholder="请输入您的手机号码" :disabled="disable" />
                                    </el-form-item>
                                    <el-form-item label="验证码" prop="code">
                                        <el-row :align="middle">
                                            <div class="flex-1">
                                                <el-input v-model="form.code" placeholder="请输入验证码" />
                                            </div>
                                            <div style="margin-left: 5px;">
                                                <el-button color="#006CFF" @click="getCode" :disabled="startCountdown">@{{codeName}}</el-button>
                                            </div>
                                        </el-row>
                                    </el-form-item>
                                    <div style="color: #A8ABB2;font-size: 12px;margin-left: 120px;position: relative;top: -28px">  可通过此手机号找回管理员密码</div>
                                    <el-form-item label="安装密钥" prop="auth_code" style="margin-bottom: 17px;">
                                        <el-input v-model="form.auth_code" placeholder="请输入安装密钥" :disabled="disable" />
                                    </el-form-item>
                                    <div style="color: #A8ABB2;font-size: 12px;margin-left: 120px;margin-top: -15px;">  安装密钥请联系速信客服获取</div>
                                </div>
                                <div class="database">
                                    <h3>站点信息</h3>
                                    <div class="database_form">
                                        <el-form-item label="站点名称" prop="domain_name">
                                            <el-input v-model="form.domain_name" placeholder="请输入站点名称" />
                                        </el-form-item>
                                        <el-form-item label="管理员账号" prop="username">
                                            <el-input v-model="form.username" placeholder="请输入管理员账号" />
                                        </el-form-item>
                                        <div style="color: #A8ABB2;font-size: 12px;margin-left: 120px;position: relative;top: -28px">  系统管理员登录账号</div>
                                        <el-form-item label="管理员密码" prop="password">
                                            <el-input v-model="form.password" placeholder="请输入管理员登录密码" show-password />
                                        </el-form-item>
                                        <div style="color: #A8ABB2;font-size: 12px;margin-left: 120px;position: relative;top: -28px">  系统管理员登录密码，密码长度不小于8个字符</div>
                                        <el-form-item label="公司名称" prop="corporate_name">
                                            <el-input v-model="form.corporate_name" placeholder="请输入您的公司名称" :disabled="disable" />
                                        </el-form-item>
                                        <div style="color: #A8ABB2;font-size: 12px;margin-left: 120px;position: relative;top: -28px">  请填写您的真实公司名称</div>
                                        <el-form-item label="电子邮箱" prop="email">
                                            <el-input v-model="form.email" placeholder="请输入您的电子邮箱"  />
                                        </el-form-item>
                                        <div style="color: #A8ABB2;font-size: 12px;margin-left: 120px;position: relative;top: -28px">  系统更新、通知等消息通过此邮箱接收</div>
                                    </div>
                                </div>
                            </div>
                        </el-form>


                        <div class="configure_operation">
                            <button @click="gotoBack">上一步</button>
                            <button class="step_third" @click="submit">下一步</button>
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
                    rules: {
                        mobile: [
                            {
                                required: true,
                                trigger: 'blur',
                                message: '请输入您的手机号码'
                            },
                        ],
                        code: [{
                            required: true,
                            trigger: 'blur',
                            message: '请输入验证码'
                        }],
                        auth_code: [{
                            required: true,
                            trigger: 'blur',
                            message: '请输入安装密钥'
                        }],
                        username: [{
                            required: true,
                            trigger: 'blur',
                            message: '请输入管理员登录账号'
                        }],
                        password: [{
                            required: true,
                            trigger: 'blur',
                            message: '请输入管理员登录密码'
                        }],
                        domain_name: [{
                            required: true,
                            trigger: 'blur',
                            message: '请输入您的站点名称'
                        }],
                        corporate_name: [{
                            required: true,
                            trigger: 'blur',
                            message: '请输入您的公司名称'
                        }],
                        email: [{
                            required: true,
                            trigger: 'blur',
                            message: '请输入您的电子邮箱'
                        }],
                    },
                    form: {
                        mobile: '', //手机号
                        code: '', //验证码
                        auth_code: '',//安装密钥
                        username: '', //管理员用户名
                        password: '', //管理员密码
                        domain_name: '', //站点名称
                        corporate_name: '', //公司名称
                        email: '', //电子邮箱
                    },
                    codeName: "获取验证码",
                    startCountdown: false,
                    disable:false,//安装密钥是否可以输入
                    isStep:"",
                }
            },
            created() {
                this.getDomainInfo();
            },
            methods: {
                gotoBack(){
                    // window.location.href = "step2.html"
                    window.location.href="/install/step2"
                },
                getCode() {
                    var myreg = /^[1][3,4,5,7,8,9][0-9]{9}$/;
                    if (!myreg.test(this.form.mobile) || !this.form.mobile) {
                        this.$message({
                            message: !this.form.mobile ? '请输入手机号' : '手机号格式不正确',
                            type: 'error',
                        })
                        return
                    }
                    this.getInstallCode();
                    let timer = 60
                    let countdown = setInterval(() => {
                        timer--;
                        this.codeName = `倒计时${timer}秒`
                        this.startCountdown = true;
                        if (timer < 0) {
                            clearInterval(countdown)
                            this.codeName = '重新发送'
                            this.startCountdown = false;
                        }
                    }, 1000)
                },
                submit() {
                    this.$refs.ruleFormRef.validate((valid) => {
                        if (!valid) {
                            this.$message({
                                message: '请填写完整必填项',
                                type: 'warning',
                            })
                            return
                        }
                        var myreg = /^[1][3,4,5,7,8,9][0-9]{9}$/;
                        if (!myreg.test(this.form.mobile)) {
                            this.$message({
                                message: '手机号格式不正确',
                                type: 'error',
                            })
                            return
                        }
                        if(this.form.password.length < 8){
                            this.$message({
                                message: '密码长度不小于8个字符',
                                type: 'error',
                            })
                            return
                        }
                        // if (!myreg.test(this.form.username)) {
                        // 	this.$message({
                        // 		message: '管理员账号请输入手机号',
                        // 		type: 'error',
                        // 	})
                        // 	return
                        // }
                        if(this.isStep){
                            this.$message({
                                message: this.isStep,
                                type: 'error',
                            })
                            return
                        }

                        this.installActivation();
                    })
                },
                async getInstallCode() {
                    let res = await shttp.post('/install/get-code').send({
                        mobile: this.form.mobile
                    }).end();
                    if (res.code == 200) {
                        this.$message({
                            message: '发送成功',
                            type: 'success',
                        })
                    }else{
                        this.$message({
                            message: res.msg,
                            type: 'error',
                        })
                    }
                },
                async installActivation() {
                    let res = await shttp.post('/install/activation').send(this.form).end();
                    // this.$message({
                    // 	message: res.msg == 'success' ? '成功' : res.msg,
                    // 	type: res.code == 200 ? 'success' : 'error',
                    // })
                    if (res.code == 200) {
                        localStorage.setItem('userInfo',JSON.stringify(this.form))
                        // window.location.href = "step4.html"
                        window.location.href="/install/step3"
                    }else{
                        this.$message({
                            message: res.msg,
                            type: 'error',
                        })
                    }
                },
                async getDomainInfo(){
                    const res = await shttp.post(`/install/getDomainInfo`).end();
                    if(res.authData){
                        this.form = res.authData;
                        this.form.mobile = res.authData.phone
                        this.disable = true;
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

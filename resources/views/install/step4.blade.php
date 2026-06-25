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
        .progress{
            position: fixed;
            top: 0;
            left: 0;
            z-index: 10;
            width: 100%;
            height: 100vh;
            background-color: rgba(0,0,0,0.6);
        }
        .progressItem{
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%,-50%);
            z-index: 999;
            /* width: 40%; */
        }
        .el-progress__text span{
            color: #FFFFFF;
        }
        .database {
            width: 47%;
        }
    </style>
</head>
<body>
<div id="app">
    <div class="progress" v-if="showProgress">
        <div class="progressItem">
            <el-progress type="circle" :width="200" :height="200" :percentage="progress" :color="progressColor[progressIndex]" :stroke-width="22">
                <span>@{{progress}}%</span>
                <div style="color: #FFFFFF;font-size: 8px;margin-top: 6px;">正在安装中，请勿刷新...</div>
            </el-progress>
            <!-- <el-progress :text-inside="true" :stroke-width="30" :percentage="progress" :color="progressColor[progressIndex]" /> -->
        </div>
    </div>
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
                        <div class="step_active" style="width: 80%;"></div>

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
                        <el-form
                            ref="ruleFormRef"
                            :model="form"
                            :rules="rules"
                            label-width="120px"
                        >
                            <div class="flex">
                                <div class="database">
                                    <h3>MySQl数据库信息</h3>
                                    <div class="database_form">
                                        <el-form-item label="数据库主机" prop="db_host">
                                            <el-input v-model="form.db_host" />
                                        </el-form-item>
                                        <el-form-item label="数据库用户" prop="db_username">
                                            <el-input v-model="form.db_username" placeholder="请输入数据库用户"  />
                                        </el-form-item>
                                        <el-form-item label="数据库密码" prop="db_password">
                                            <el-input type="password" v-model="form.db_password" placeholder="请输入数据库密码" />
                                        </el-form-item>
                                        <el-form-item label="数据库名称" prop="db_database">
                                            <el-input v-model="form.db_database" placeholder="请输入数据库名称" />
                                        </el-form-item>
                                        <el-form-item label="数据库端口" prop="db_port">
                                            <el-input v-model="form.db_port" placeholder="请输入数据库端口" />
                                        </el-form-item>
                                        <el-form-item label="数据表前缀" prop="db_prefix">
                                            <el-input v-model="form.db_prefix" disabled />
                                        </el-form-item>
                                    </div>
                                </div>
                                <div class="database">
                                    <h3>Redis缓存信息</h3>
                                    <div class="database_form">
                                        <el-form-item label="Redis地址" prop="redis_host">
                                            <el-input v-model="form.redis_host" placeholder="请输入Redis地址" />
                                        </el-form-item>
                                        <el-form-item label="Redis端口" prop="redis_port">
                                            <el-input v-model="form.redis_port" placeholder="请输入Redis端口" />
                                        </el-form-item>
                                        <el-form-item label="Redis密码" prop="redis_password">
                                            <el-input v-model="form.redis_password" placeholder="请输入Redis密码" />
                                        </el-form-item>
                                    </div>
                                </div>
                            </div>
                        </el-form>


                        <div class="configure_operation">
                            <button @click="goback">上一步</button>
                            <button class="step_third" @click="submit">  数据库写入</button>
                        </div>
                        </form>
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
                    showProgress: false,
                    progress:0,
                    progressColor:['#E53E30','#FBC016','#FFE680','#259444','#55aa00','#0681D7','#006CFF'],
                    progressIndex:0,
                    flag:true,
                    timer:null,
                    twoSuccess:false,
                    rules:{
                        db_host: [
                            {
                                required: true,
                                trigger: 'blur',
                                message: '请输入数据库主机',
                            },
                        ],
                        db_username: [
                            {
                                required: true,
                                trigger: 'blur',
                                message: '请输入数据库用户',
                            },
                        ],
                        db_password: [
                            {
                                required: true,
                                trigger: 'blur',
                                message: '请输入数据库密码',
                            },
                        ],
                        db_database: [
                            {
                                required: true,
                                trigger: 'blur',
                                message: '请输入数据库名称',
                            },
                        ],
                        db_port: [
                            {
                                required: true,
                                trigger: 'blur',
                                message: '请输入数据库端口',
                            },
                        ],
                        db_prefix: [
                            {
                                required: true,
                                trigger: 'blur',
                                message: '请输入数据库前缀',
                            },
                        ],
                        redis_host: [
                            {
                                required: true,
                                trigger: 'blur',
                                message: '请输入redisPort端口',
                            },
                        ],
                        redis_host: [
                            {
                                required: true,
                                trigger: 'blur',
                                message: '请输入redisPort地址',
                            },
                        ],
                        redis_password: [
                            {
                                // required: true,
                                trigger: 'blur',
                                message: '请输入redisPort密码',
                            },
                        ],

                    },
                    form: {
                        db_host: "127.0.0.1",//数据库主机
                        db_database: "",//数据库名称
                        db_port: "3306",//数据库端口
                        db_username: "",//数据库用户名
                        db_password: "",//数据库密码
                        db_prefix: "ybwm_v3_",//数据库前缀
                        redis_port: "6379",//redisPort端口
                        redis_host: "127.0.0.1",//redisPort地址
                        redis_password: "",//redisPort密码
                    },
                }
            },
            created() {

            },
            methods: {
                goback(){
                    window.location.href = "/install/step3"
                },
                submit(){
                    this.$refs.ruleFormRef.validate((valid) => {
                        if (!valid) {
                            this.$message({
                                message: '请输入完整',
                                type: 'warning',
                            })
                            return
                        }
                        this.showProgress = true;
                        this.timer = setInterval(()=>{
                            this.progress = this.progress + 10;
                            if(this.progress < 20){
                                this.progressIndex = 0;
                            }else if(this.progress < 30){
                                this.progressIndex = 1;
                            }else if(this.progress < 40){
                                this.progressIndex = 2;
                            }else if(this.progress < 50){
                                this.progressIndex = 3;
                            }else if(this.progress < 60){
                                this.progressIndex = 4;
                            }else if(this.progress < 70){
                                this.progressIndex = 5;
                            }else if(this.progress < 80){
                                this.progressIndex = 6;
                            }
                            if(this.progress > 100){
                                this.progress = 100;
                                this.showProgress = true;
                                clearInterval(this.timer)
                            }
                        },500)
                        this.configureMysql();
                    })
                },
                async configureMysql(){
                    let res = await shttp.post('/install/configure-mysql').send(this.form).end();
                    if(res.code == 200){
                        this.$message({
                            message: res.msg == 'success' ? '成功' : res.msg,
                            type: 'success',
                        })
                        this.showProgress = false;
                        clearInterval(this.timer)
                        window.location.href = "/install/step5";
                    }else{
                        this.showProgress = false;
                        this.progress = 0;
                        clearInterval(this.timer)
                        this.timer = null;
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

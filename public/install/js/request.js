const requestUrl = ''//测试
// const requestUrl = ''
class rp {
    constructor(url, type) {
        this.url = requestUrl + url;
        this.method = type;
        this.headers = {};
        this.qs = {};
        this.body = {};
    }
    header(v1, v2) {
        if (typeof v1 === 'object') {
            this.headers = v1;
        } else {
            this.headers[v1] = v2;
        }
        return this;
    }
    query(v1, v2) {
        if (typeof v1 === 'object') {
            this.qs = v1;
        } else {
            this.qs[v1] = v2;
        }
        return this;
    }

    send(data) {
        this.body = data;
        return this;
    }
    async end() {
        let auth = localStorage.getItem('token');
        if (auth) {
            this.header('Authorization', auth);
        }
        let rpOpt = {
            url: this.url,
            headers: this.headers,
            method: this.method,
            data: this.body
        };
        let isJsonType = this.method === 'POST' || this.method === 'PUT';
        let qs = [];
        for (let k in this.qs) {
            qs.push(`${k}=${this.qs[k]}`);
        }
        if (qs.length !== 0) {
            qs = qs.join('&');
            rpOpt.url += rpOpt.url.indexOf('?') === -1 ? `?${qs}` : `&${qs}`;
        }
        if (isJsonType) {
            rpOpt.data = this.body;
            rpOpt.dataType = 'json';
            if (!this.headers) {
                rpOpt.header['Content-Type'] = 'application/json';
            }
        }
        console.log('请求参数', rpOpt)
        let res = await axios(rpOpt);
        console.log(res, 'res')
        if (res.header?.authorization && res.header?.authorization !== auth) {
            uni.setStorageSync("token", res.header.authorization);
        }
        let info = res.data;
        if (info.code === 200) {
            return info;
        }
        if (info.message && !info.message) {
            info.moreInfo = info.message;
        }
        console.log(info)
        return info
    }
}

const shttp = {
    get: (url) => {
        return new rp(url, 'GET');
    },
    post: (url) => {
        return new rp(url, 'POST');
    },
    put: (url) => {
        return new rp(url, 'PUT');
    },
    delete: (url) => {
        return new rp(url, 'DELETE');
    },
};

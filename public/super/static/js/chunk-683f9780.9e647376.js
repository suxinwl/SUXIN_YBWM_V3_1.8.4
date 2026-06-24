/*!
 *  build: ybwmv3-supertube-admin
 *  copyright: ybwmv3-supertube-admin
 *  time: undefined
 */
/*!
 *  build: ybwmv3-supertube-admin
 *  copyright: ybwmv3-supertube-admin
 *  time: undefined
 */
(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["chunk-683f9780","chunk-683f9780"],{b4b6:function(e,t,r){},ca24:function(e,t,r){"use strict";r("b4b6")},d256:function(e,t,r){"use strict";r.r(t);var o=r("d8e8"),c=(r("c49e"),r("c349")),n=(r("c4aa"),r("f2bf"));function a(e,t,r,a,s,l){const f=c["a"],u=o["b"],m=Object(n["resolveComponent"])("ybDialog");return Object(n["withDirectives"])((Object(n["openBlock"])(),Object(n["createBlock"])(m,{ref:"remarkRef",formModel:e.form,"onUpdate:formModel":t[1]||(t[1]=t=>e.form=t),"form-rules":e.rules,title:"添加备注",onTest:e.testy},{content:Object(n["withCtx"])(()=>[Object(n["createVNode"])(u,{label:"备注",prop:"remark"},{default:Object(n["withCtx"])(()=>[Object(n["createVNode"])(f,{modelValue:e.form.remark,"onUpdate:modelValue":t[0]||(t[0]=t=>e.form.remark=t),placeholder:"请填写备注"},null,8,["modelValue"])]),_:1})]),_:1},8,["formModel","form-rules","onTest"])),[[n["vShow"],"remark"==e.type]])}var s=r("3ef4"),l=Object(n["defineComponent"])({name:"AlterInfoMask",props:{},setup(){const e=Object(n["reactive"])({type:"",remarkRef:null,form:{remark:""},rules:{remark:[{required:!0,trigger:"blur",message:"备注必填"}]}}),t=(t,r)=>{e.type=t,e.ref=r,e[""+r].open(t,r)},r=async(t,r,o)=>{let c;"remark"==e.type&&(c=!0),c?e[""+o].close():Object(s["a"])({message:"弹出错误信息",type:"error"})};return{...Object(n["toRefs"])(e),open:t,testy:r}}}),f=(r("ca24"),function(e){e.__source="src/views/user/noPass/components/editMask.vue"}),u=r("6b0d"),m=r.n(u);"function"===typeof f&&f(l);const p=m()(l,[["render",a],["__scopeId","data-v-48914bc2"]]);t["default"]=p}}]);
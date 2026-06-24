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
(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["chunk-2d0d34d4","chunk-2d0d34d4"],{"5bd5":function(e,t,o){"use strict";o.r(t);var n=o("d8e8"),r=(o("c49e"),o("c349")),c=(o("c4aa"),o("f2bf"));function s(e,t,o,s,l,d){const a=r["a"],f=n["b"],u=Object(c["resolveComponent"])("ybDialog");return Object(c["withDirectives"])((Object(c["openBlock"])(),Object(c["createBlock"])(u,{ref:"noteRef",formModel:e.form,"onUpdate:formModel":t[1]||(t[1]=t=>e.form=t),"form-rules":e.rules,title:"请填写备注",onTest:e.testy},{content:Object(c["withCtx"])(()=>[Object(c["createVNode"])(f,{label:"备注：",prop:"note"},{default:Object(c["withCtx"])(()=>[Object(c["createVNode"])(a,{modelValue:e.form.note,"onUpdate:modelValue":t[0]||(t[0]=t=>e.form.note=t),placeholder:"请填写备注"},null,8,["modelValue"])]),_:1})]),_:1},8,["formModel","form-rules","onTest"])),[[c["vShow"],"note"==e.type]])}var l=o("3ef4"),d=Object(c["defineComponent"])({name:"EditNote",props:{},setup(){const e=Object(c["reactive"])({noteRef:"",form:{note:""},rules:{note:[{required:!0,trigger:"blur",message:"备注必填哦"}]}}),t=(t,o)=>{e.type=t,e.ref=o,e[""+o].open(t,o)},o=async(t,o,n)=>{let r;"note"==e.type&&(r=!1),r?e[""+n].close():Object(l["a"])({message:"弹出错误信息",type:"error"})};return{...Object(c["toRefs"])(e),open:t,testy:o}}}),a=function(e){e.__source="src/views/user/list/components/editNote.vue"},f=o("6b0d"),u=o.n(f);"function"===typeof a&&a(d);const p=u()(d,[["render",s]]);t["default"]=p}}]);
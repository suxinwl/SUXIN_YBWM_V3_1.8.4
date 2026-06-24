/*!
 *  build: ybwmv3-supertube-admin
 *  copyright: ybwmv3-supertube-admin
 *  time: undefined
 */
(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["chunk-2d22d39b"],{f71e:function(e,s,c){"use strict";c.r(s),c.d(s,"handleClipboard",(function(){return t}));var n=c("2c28"),o=c("3787");function a(){o["gp"].$baseMessage("复制成功","success","vab-hey-message-success")}function i(){o["gp"].$baseMessage("复制失败","error","vab-hey-message-success")}function t(e){const{isSupported:s,copy:c}=Object(n["useClipboard"])();s||Object(n["usePermission"])("clipboard-write"),c(e).then(()=>{a(e)}).catch(s=>{console.log(s),i(e)})}}}]);
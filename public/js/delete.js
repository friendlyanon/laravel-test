!function(e){var t={};function n(o){if(t[o])return t[o].exports;var r=t[o]={i:o,l:!1,exports:{}};return e[o].call(r.exports,r,r.exports,n),r.l=!0,r.exports}n.m=e,n.c=t,n.d=function(e,t,o){n.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:o})},n.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},n.t=function(e,t){if(1&t&&(e=n(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var o=Object.create(null);if(n.r(o),Object.defineProperty(o,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var r in e)n.d(o,r,function(t){return e[t]}.bind(null,r));return o},n.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return n.d(t,"a",t),t},n.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},n.p="/",n(n.s=36)}({36:function(e,t,n){e.exports=n(37)},37:function(e,t,n){"use strict";var o={open:!1,deleting:!1,button:null},r=$("#delete_modal"),l=$("#delete_form"),a=function(){var e=o.button.closest(".card");return e.find("a").removeClass("disabled_deleting"),Object.assign(o,{open:!1,deleting:!1,button:null}),e},u=function(){var e=o.button,t=a(),n=$(document.createElement("span"));n.addClass("success_message").text("Success"),e.next().replaceWith(n),l.data("show")?window.location=l.data("show"):setTimeout(function(){t.fadeOut(500,function(){t.next("br").remove(),t.remove()})},1e3)},i=function(e,t,n){var r=o.button;a();var l=$(document.createElement("span"));l.addClass("failure_message").text("Error: "+n),r.next().replaceWith(l)};r.on("hide.bs.modal",function(){o.open&&!o.deleting&&null!=o.button&&Object.assign(o,{open:!1,deleting:!1,button:null})}),$("button.delete-modal").on("click",function(e){e.preventDefault(),o.open||o.deleting||null!=o.button||(r.modal("show"),Object.assign(o,{open:!0,deleting:!1,button:$(this)}))}),$("#delete_button").on("click",function(){if(o.open&&!o.deleting&&null!=o.button){var e=l.attr("action"),t=o.button;l.find("[name='id']").val(t.data("id")),t.nextAll().remove(),t.after('\n<div class="spinner-grow text-danger" role="status">\n  <span class="sr-only">Deleting...</span>\n</div>'),t.closest(".card").find("a").addClass("disabled_deleting"),r.modal("hide"),$.ajax({url:e,type:"POST",data:l.serialize(),success:u,error:i}),Object.assign(o,{open:!1,deleting:!0,button:t})}})}});
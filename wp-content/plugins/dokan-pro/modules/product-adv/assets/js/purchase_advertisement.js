!function(e,t,a){"use strict";const r={started:!1,init:function(){e("#dokan_advertise_single_product").on("click",r.handle_advertise_single_product_click),e("#dokan-product-list-table").on("click","span.dokan-product-advertisement",r.handle_advertise_from_list_table)},handle_advertise_from_list_table:async function(){let a=e(this),s=a.data("product-id"),n=a.data("already-advertised"),o="",d=a.data("product-status");if(!r.started&&!n){if(r.started=!0,r.__rotate(a),"publish"!==d)return dokan_sweetalert(dokan_purchase_advertisement.product_not_published,{icon:"error"}),r.__rotate(a,!1),void(r.started=!1);await Swal.fire({title:dokan_purchase_advertisement.on_load_advertisement_status,icon:"info",showCloseButton:!1,showCancelButton:!1,allowOutsideClick:!1,didOpen:()=>{Swal.showLoading();let e={product_id:s,advertise_product_nonce:dokan_purchase_advertisement.advertise_product_nonce};return wp.ajax.post("dokan_get_advertisement_status",e).then((async e=>{o=e.advertisement_text,Swal.close()})).fail((e=>{let t=r.__handle_error(e);t&&dokan_sweetalert(t,{action:"error",title:dokan_purchase_advertisement.on_error_message,icon:"error"}),r.started=!1,r.__rotate(a,!1)}))}}),""!==o&&await dokan_sweetalert("",{action:"confirm",icon:"warning",html:o}).then((async e=>{if(!e.isConfirmed)return a.prop("checked",!1),r.__rotate(a,!1),r.started=!1,!1;let n={product_id:s,advertise_product_nonce:dokan_purchase_advertisement.advertise_product_nonce};try{return await wp.ajax.post("dokan_add_advertise_product_to_cart",n).then((async e=>{a.addClass("advertised"),a.find("i.adv_icon_1").first().css("color",dokan_purchase_advertisement.advertise_active);let r={action:"confirm",title:dokan_purchase_advertisement.on_success_message,icon:"success",showCloseButton:!1,showCancelButton:!1,focusConfirm:!0};return await dokan_sweetalert(e.message,r).then((()=>(!0===e.free_purchase?t.location.reload():t.location.replace(dokan_purchase_advertisement.checkout_url),!0)))})).fail((e=>{let t=r.__handle_error(e);t&&dokan_sweetalert(t,{action:"error",title:dokan_purchase_advertisement.on_error_message,icon:"error"})})).always((e=>{r.__rotate(a,!1),r.started=!1}))}catch(e){}}))}},handle_advertise_single_product_click:function(){let t=e(this);if(!t.is(":checked"))return;let a=t.data("product-id");a&&dokan_sweetalert(dokan_purchase_advertisement.advertise_alert,{action:"confirm",icon:"warning"}).then((async e=>{if(!e.isConfirmed)return void t.prop("checked",!1);let s={product_id:a,advertise_product_nonce:dokan_purchase_advertisement.advertise_product_nonce};wp.ajax.post("dokan_add_advertise_product_to_cart",s).then((e=>{dokan_sweetalert(e.message,{action:"success",title:dokan_purchase_advertisement.on_success_message,icon:"success"}),t.prop("disabled",!0)})).fail((e=>{t.prop("checked",!1);let a=r.__handle_error(e);a&&dokan_sweetalert(a,{action:"error",title:dokan_purchase_advertisement.on_error_message,icon:"error"})}))}))},__handle_error:function(e){let t="";return e.responseJSON&&e.responseJSON.message?t=e.responseJSON.message:e.responseJSON&&e.responseJSON.data&&e.responseJSON.data.message?t=e.responseJSON.data.message:e.responseText&&(t=e.responseText),t},__rotate:function(e,t=!0){let a=e.find("i.adv_icon_2").first();a.length&&(t?a.addClass("fa-spin"):a.removeClass("fa-spin"))}};e((function(){r.init()}))}(jQuery,window,document);
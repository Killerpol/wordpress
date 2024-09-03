(()=>{"use strict";var t={n:e=>{var s=e&&e.__esModule?()=>e.default:()=>e;return t.d(s,{a:s}),s},d:(e,s)=>{for(var n in s)t.o(s,n)&&!t.o(e,n)&&Object.defineProperty(e,n,{enumerable:!0,get:s[n]})},o:(t,e)=>Object.prototype.hasOwnProperty.call(t,e)};const e=window.jQuery;var s=t.n(e);function n(t,e,s,n,i,o,r,a){var c,l="function"==typeof t?t.options:t;if(e&&(l.render=e,l.staticRenderFns=s,l._compiled=!0),n&&(l.functional=!0),o&&(l._scopeId="data-v-"+o),r?(c=function(t){(t=t||this.$vnode&&this.$vnode.ssrContext||this.parent&&this.parent.$vnode&&this.parent.$vnode.ssrContext)||"undefined"==typeof __VUE_SSR_CONTEXT__||(t=__VUE_SSR_CONTEXT__),i&&i.call(this,t),t&&t._registeredComponents&&t._registeredComponents.add(r)},l._ssrRegister=c):i&&(c=a?function(){i.call(this,(l.functional?this.parent:this).$root.$options.shadowRoot)}:i),c)if(l.functional){l._injectStyles=c;var d=l.render;l.render=function(t,e){return c.call(e),d(t,e)}}else{var u=l.beforeCreate;l.beforeCreate=u?[].concat(u,c):[c]}return{exports:t,options:l}}const i=n({name:"Subscriptions",components:{ListTable:dokan_get_lib("ListTable"),Multiselect:dokan_get_lib("Multiselect")},data(){return{showCb:!0,counts:{all:0},totalItems:0,perPage:10,totalPages:1,loading:!1,columns:{store_name:{label:this.__("Store","dokan")},subscription_title:{label:this.__("Subscription Pack","dokan")},start_date:{label:this.__("Start Date","dokan"),sortable:!0},end_date:{label:this.__("End Date","dokan")},status:{label:this.__("Status","dokan")},order_id:{label:this.__("Order","dokan")},action:{label:this.__("Action","dokan")}},actions:[],filter:{selected_pack:{id:0,title:this.__("All Subscription Pack","dokan")},selected_vendor:{id:0,store_name:this.__("All Subscription","dokan")},stores:[],subscription_packs:[]},bulkActions:[{key:"cancel",label:this.__("Cancel Subscription","dokan")},{key:"activate",label:this.__("Activate Subscription","dokan")}],vendors:[]}},watch:{"$route.query.status"(){this.getSubscribedVendorList()},"$route.query.page"(){this.getSubscribedVendorList()},"$route.query.orderby"(){this.getSubscribedVendorList()},"$route.query.order"(){this.getSubscribedVendorList()}},computed:{currentStatus(){return this.$route.query.status||"all"},currentPage(){let t=this.$route.query.page||1;return parseInt(t)},sortBy(){return this.$route.query.orderby||"start_date"},sortOrder(){return this.$route.query.order||"desc"}},created(){this.getSubscribedVendorList()},mounted(){this.getPackageList(),this.getStoreList(),this.mountToolTips()},updated(){this.mountToolTips()},methods:{toggleSubscription(t){t.status&&t.has_active_cancelled_sub?Swal.fire({title:this.__("Cancel Subscription","dokan"),icon:"warning",html:`Subscription will be cancelled at ${t.end_date} automatically`,showCancelButton:t.is_recurring,confirmButtonText:this.__("Cancel now","dokan"),cancelButtonText:this.__("Don't cancel subscription","dokan"),allowOutsideClick:!1}).then((async e=>{if(e.dismiss&&"overlay"===e.dismiss)return;this.loading=!0;let s="",n=!1;if(e.value&&(s="cancel",n=!0),e.dismiss&&"cancel"===e.dismiss&&(s="activate"),await this.updateSubscriptonStatus(t.id,s,n)){this.loading=!1;const t="cancel"===s?this.__("cancelled","dokan"):this.__("re-activated","dokan");Swal.fire({title:this.__(`Subscription has been ${t}`,"dokan"),icon:"success",showConfirmButton:!1,timer:2e3})}location.reload()})):Swal.fire({title:this.__("Cancel Subscription","dokan"),icon:"warning",showCancelButton:!0,cancelButtonText:this.__("Don't cancel","dokan"),confirmButtonText:this.__("Cancel subscription","dokan"),input:"radio",inputOptions:{immediately:this.__(`Immediately <span class="date">${t.current_date}</span>`,"dokan"),end_of_current_period:this.__(`End of the current period <span class="date">${t.end_date}</span>`,"dokan")},inputValidator:t=>{if(!t)return this.__("Please select an option!","dokan")},allowOutsideClick:!1}).then((async e=>{if(e.dismiss||!e.value)return;this.loading=!0;let s="immediately"===e.value;await this.updateSubscriptonStatus(t.id,"cancel",s)&&(this.loading=!1,Swal.fire({title:this.__("Subscription has been cancelled","dokan"),icon:"success",showConfirmButton:!1,timer:2e3})),location.reload()}))},buttonTitle(t){return t.status&&t.has_active_cancelled_sub?this.__("Reactivate","dokan"):this.__("Cancel","dokan")},updatedCounts(t){this.counts.all=parseInt(t.getResponseHeader("X-WP-Total")??0)},updatePagination(t){this.totalPages=parseInt(t.getResponseHeader("X-WP-TotalPages")),this.totalItems=parseInt(t.getResponseHeader("X-WP-Total"))},updateSubscriptonStatus(t,e,s){let n={action:e,immediately:s};return new Promise(((e,s)=>{dokan.api.put("/subscription/"+t,n).done(((t,n,i)=>{"success"===n?e(!0):s(new Error(__("Something went wrong","dokan")))}))}))},getSubscribedVendorList(){let t=this;t.loading=!0,dokan.api.get("/subscription",{per_page:t.perPage,paged:t.currentPage,vendor_id:this.filter.selected_vendor?this.filter.selected_vendor.id:0,pack_id:this.filter.selected_pack?this.filter.selected_pack.id:0,orderBy:this.sortBy,order:this.sortOrder}).done(((e,s,n)=>{t.vendors="no_subscription"===e.code?[]:e,t.loading=!1,this.updatedCounts(n),this.updatePagination(n)}))},goToPage(t){this.$router.push({name:"Subscriptions",query:{page:t}})},onBulkAction(t,e){const s="activate"===t?this.__("Want to activate the subscription again?","dokan"):this.__("Are you sure to cancel the subscription?","dokan");if(!confirm(s))return;let n={action:t,user_ids:e};this.loading=!0,dokan.api.put("/subscription/batch",n).done((t=>{location.reload()}))},doSort(t,e){this.$router.push({name:"Subscriptions",query:{page:1,orderby:t,order:e}})},subscriptionUrl:t=>dokan.urls.adminRoot+"post.php?post="+t+"&action=edit",subscriptionStatus(t){return t.status&&t.has_active_cancelled_sub?this.sprintf(this.__("Active (Cancels %s)","dokan"),t.end_date):t.status?this.__("Active","dokan"):this.__("Inactive","dokan")},subscriptionEndDate(t){return t.end_date?t.end_date:this.__("(no date)","dokan")},getPackageList(t=""){let e=this;dokan.api.get("/subscription/packages",{paged:1,search:t}).done((t=>{e.filter.subscription_packs="no_subscription"===t.code?[]:[{id:0,title:e.__("All Subscription Pack","dokan")}].concat(t)})).fail((t=>{e.filter.subscription_packs=[]}))},getStoreList(t=""){let e=this;dokan.api.get("/subscription",{paged:1,search:t}).done((t=>{e.filter.stores=[{id:0,store_name:e.__("All Subscription","dokan")}].concat(t)})).fail((t=>{e.filter.stores=[]}))},mountToolTips(){s()(".tips").tooltip()},orderUrl:t=>dokan.urls.adminRoot+"post.php?post="+t+"&action=edit",vendorUrl:t=>dokan.urls.adminRoot+"admin.php?page=dokan#/vendors/"+t}},(function(){var t=this,e=t._self._c;return e("div",{staticClass:"subscription-list"},[e("h1",{staticClass:"wp-heading-inline"},[t._v(t._s(t.__("Subscribed Vendor List","dokan")))]),t._v(" "),e("hr",{staticClass:"wp-header-end"}),t._v(" "),e("ul",{staticClass:"subsubsub"},[e("li",[e("router-link",{attrs:{to:"","active-class":"current",exact:""},domProps:{innerHTML:t._s(t.sprintf(t.__("Total Subscribed Vendors <span class='count'>(%s)</span>","dokan"),t.counts.all))}})],1)]),t._v(" "),e("list-table",{attrs:{columns:t.columns,loading:t.loading,rows:t.vendors,actions:t.actions,"show-cb":t.showCb,"total-items":t.totalItems,"bulk-actions":t.bulkActions,"total-pages":t.totalPages,"per-page":t.perPage,"current-page":t.currentPage,"not-found":"No subscribed vendors found.","sort-by":t.sortBy,"sort-order":t.sortOrder},on:{sort:t.doSort,pagination:t.goToPage,"bulk:click":t.onBulkAction},scopedSlots:t._u([{key:"store_name",fn:function(s){return[e("strong",[e("a",{attrs:{href:t.vendorUrl(s.row.id)}},[t._v(t._s(s.row.store_name?s.row.store_name:t.__("(no name)","dokan")))])])]}},{key:"subscription_title",fn:function(s){return[e("strong",[e("a",{attrs:{href:t.subscriptionUrl(s.row.subscription_id)}},[t._v(t._s(s.row.subscription_title?s.row.subscription_title:t.__("(no pack)","dokan")))])]),t._v(" "),s.row.is_on_trial?e("span",{staticClass:"tips label trial",attrs:{"data-title":s.row.subscription_trial_until?t.__("Trial Expires: ","dokan")+s.row.subscription_trial_until:""}},[t._v(t._s(t.__("On Trial","dokan")))]):t._e(),t._v(" "),s.row.is_recurring?e("span",{staticClass:"label"},[t._v(t._s(t.__("Recurring","dokan")))]):t._e()]}},{key:"end_date",fn:function(e){return[t._v("\n            "+t._s(t.subscriptionEndDate(e.row))+"\n        ")]}},{key:"status",fn:function(e){return[t._v("\n            "+t._s(t.subscriptionStatus(e.row))+"\n        ")]}},{key:"order_id",fn:function(s){return[e("strong",[e("a",{attrs:{href:s.row.order_link}},[t._v(t._s(s.row.order_id?s.row.order_id:t.__("(no order)","dokan")))])])]}},{key:"action",fn:function(s){return[e("span",{staticClass:"action-btn dashicons dashicons-edit",on:{click:function(e){return t.toggleSubscription(s.row)}}})]}}])},[t._v(" "),t._v(" "),t._v(" "),t._v(" "),t._v(" "),t._v(" "),e("template",{slot:"filters"},[e("div",{staticClass:"flex-container"},[e("div",{staticClass:"flex-child"},[e("multiselect",{attrs:{placeholder:this.__("Filter by store","dokan"),options:t.filter.stores,"track-by":"id",label:"store_name","internal-search":!1,"clear-on-select":!1,"allow-empty":!0,multiselect:!1,searchable:!0,showLabels:!1},on:{input:t.getSubscribedVendorList,"search-change":t.getStoreList},model:{value:t.filter.selected_vendor,callback:function(e){t.$set(t.filter,"selected_vendor",e)},expression:"filter.selected_vendor"}})],1),t._v(" "),e("div",{staticClass:"flex-child"},[e("multiselect",{attrs:{placeholder:this.__("Filter by subscription pack","dokan"),options:t.filter.subscription_packs,"track-by":"id",label:"title","internal-search":!1,"clear-on-select":!1,"allow-empty":!0,multiselect:!1,searchable:!0,showLabels:!1},on:{input:t.getSubscribedVendorList,"search-change":t.getPackageList},model:{value:t.filter.selected_pack,callback:function(e){t.$set(t.filter,"selected_pack",e)},expression:"filter.selected_pack"}})],1)])])],2)],1)}),[],!1,null,null,null).exports,o=n({name:"AssignSubscription",components:{Multiselect:dokan_get_lib("Multiselect")},props:{vendorInfo:{type:Object},errors:{type:Array,required:!1}},data:function(){return{subscriptions:dokan.non_recurring_subscription_packs,currentSelectedSubscription:this.vendorInfo.current_subscription,isSingleVendor:!1}},created(){this.vendorInfo.subscription_nonce=dokan.nonce,this.isSingleVendorEdit()},methods:{isSingleVendorEdit(){return void 0===this.$route.params.id||null===this.$route.params.id||"VendorSingle"!==this.$route.name?(this.isSingleVendor=!1,!1):(this.isSingleVendor=!0,this.$route.params.id)},pushSelectedSubscription(){let t=0;null!=this.currentSelectedSubscription&&(t=this.currentSelectedSubscription.name),this.vendorInfo.assigned_subscription=t},hasSubscription(){return void 0!==this.vendorInfo.assigned_subscription_info&&null!==this.vendorInfo.assigned_subscription_info&&this.vendorInfo.assigned_subscription_info.has_subscription},hasRecurringSubscription(){return this.hasSubscription()&&this.vendorInfo.assigned_subscription_info.recurring},getStartDate(){let t=this.__("No Start Date","dokan");return""!==this.vendorInfo.assigned_subscription_info.start_date&&(t=this.vendorInfo.assigned_subscription_info.start_date),t},getEndDate(){let t=this.__("No End Date","dokan"),e=this.vendorInfo.assigned_subscription_info.expiry_date;return"unlimited"===e&&(t=this.__("Lifetime","dokan")),""!==e&&"unlimited"!==e&&(t=e),t}}},(function(){var t=this,e=t._self._c;return t.isSingleVendor?e("div",{staticClass:"dokan-form-group"},[t._m(0),t._v(" "),t.hasSubscription()?e("div",{staticClass:"column"},[e("p",[e("strong",[t._v(t._s(t.__("Subscribed Pack:","dokan")))]),t._v(" "+t._s(t.vendorInfo.current_subscription.label))]),t._v(" "),e("p",[e("strong",[t._v(t._s(t.__("Subscription Type:","dokan")))]),t._v(" "+t._s(t.hasRecurringSubscription()?t.__("Recurring","dokan"):t.__("Non Recurring","dokan")))]),t._v(" "),e("p",[e("strong",[t._v(t._s(t.__("Start Date:","dokan")))]),t._v(" "+t._s(t.getStartDate()))]),t._v(" "),e("p",[e("strong",[t._v(t._s(t.__("End Date:","dokan")))]),t._v(" "+t._s(t.getEndDate()))])]):t._e(),t._v(" "),t.hasRecurringSubscription()?t._e():e("div",{staticClass:"column"},[e("label",[t._v(t._s(t.__("Assign Subscription Pack","dokan")))]),t._v(" "),e("Multiselect",{attrs:{options:t.subscriptions,"track-by":"name",label:"label","allow-empty":!0,multiselect:!1,searchable:!1,showLabels:!0},on:{input:t.pushSelectedSubscription},model:{value:t.currentSelectedSubscription,callback:function(e){t.currentSelectedSubscription=e},expression:"currentSelectedSubscription"}}),t._v(" "),e("span",{staticClass:"help-block"},[t._v("You can only assign non-recurring packs.")])],1)]):t._e()}),[function(){var t=this._self._c;return t("div",{staticClass:"column mb-2 text-[14px]"},[t("h3",{staticClass:"font-bold"},[this._v("Vendor Subscription")])])}],!1,null,null,null).exports;dokan.addFilterComponent("AfterPyamentFields","dokanVendor",o),dokan_add_route(i)})();
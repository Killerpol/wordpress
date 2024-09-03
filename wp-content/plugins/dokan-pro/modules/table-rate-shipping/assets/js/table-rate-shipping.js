!function(e,t,a){var n=a.template("table-rate-shipping-row-template"),i=e("#dokan-table-rate-shipping-setting-form"),o=e("#dokan_shipping_rates"),r=o.find("tbody.table_rates"),s={init:function(){i.on("change","#dokan_table_rate_calculation_type",this.onCalculationTypeChange),o.on("change",'select[name^="shipping_condition"]',this.onShippingConditionChange).on("change",'input[name^="shipping_abort["]',this.onShippingAbortChange).on("click","button.add-table-rate",this.onAddRate).on("click","button.remove-table-rate",this.onRemoveRate).on("click","button.remove-distance-rate",this.onRemoveDistanceRate).on("click","button.dupe-table-rate",this.onDupeRate);var t=r.data("rates");e(t).each((function(e){var a=r.find(".table_rate").length;r.append(n({rate:t[e],index:a}))})),e('label[for="dokan_table_rate_handling_fee"], label[for="dokan_table_rate_max_cost"], label[for="dokan_table_rate_min_cost"]',i).each((function(t,a){e(a).data("o_label",e(a).text())})),e('#dokan_table_rate_calculation_type, select[name^="shipping_condition"], input[name^="shipping_abort["]',i).change(),r.sortable({items:"tr",cursor:"move",axis:"y",handle:"td",scrollSensitivity:40,helper:function(t,a){return a.children().each((function(){e(this).width(e(this).width())})),a.css("left","0"),a},start:function(e,t){t.item.css("background-color","#f6f6f6")},stop:function(e,t){t.item.removeAttr("style"),s.reindexRows()}})},onCalculationTypeChange:function(){var a=e(this).val();"item"==a?e("td.cost_per_item input").attr("disabled","disabled").addClass("disabled"):e("td.cost_per_item input").removeAttr("disabled").removeClass("disabled"),a?(e("#dokan_table_rate_class_priorities").hide(),e("td.shipping_label, th.shipping_label").hide()):(e("#dokan_table_rate_class_priorities").show(),e("td.shipping_label, th.shipping_label").show()),a||(e("#dokan_table_rate_class_priorities p.description.per_order").show(),e("#dokan_table_rate_class_priorities p.description.per_class").hide());var n=t.i18n.order;"item"==a?n=t.i18n.item:"line"==a?n=t.i18n.line_item:"class"==a&&(n=t.i18n.class),e('label[for="dokan_table_rate_handling_fee"], label[for="dokan_table_rate_max_cost"], label[for="dokan_table_rate_min_cost"]').each((function(t,a){var i=e(a).data("o_label");i=i.replace("[item_label]",n),e(a).text(i)}))},onShippingConditionChange:function(){var t=e(this).val(),a=e(this).closest("tr");""==t?a.find('input[name^="shipping_min"], input[name^="shipping_max"]').val("").prop("disabled",!0).addClass("disabled"):a.find('input[name^="shipping_min"], input[name^="shipping_max"]').prop("disabled",!1).removeClass("disabled")},onShippingAbortChange:function(){var t=e(this).is(":checked"),a=e(this).closest("tr");t?(a.find("td.cost").hide(),a.find("td.abort_reason").show(),a.find('input[name^="shipping_per_item"], input[name^="shipping_cost_per_weight"], input[name^="shipping_cost_percent"], input[name^="shipping_cost"], input[name^="shipping_label"]').prop("disabled",!0).addClass("disabled")):(a.find("td.cost").show(),a.find("td.abort_reason").hide(),a.find('input[name^="shipping_per_item"], input[name^="shipping_cost_per_weight"], input[name^="shipping_cost_percent"], input[name^="shipping_cost"], input[name^="shipping_label"]').prop("disabled",!1).removeClass("disabled")),e("#dokan_table_rate_calculation_type").change()},onAddRate:function(t){t.preventDefault();var a=r,i=a.find(".table_rate").length;a.append(n({rate:{rate_id:"",rate_class:"",rate_condition:"",rate_min:"",rate_max:"",rate_priority:"",rate_abort:"",rate_abort_reason:"",rate_cost:"",rate_cost_per_item:"",rate_cost_per_weight_unit:"",rate_cost_percent:"",rate_label:""},index:i})),e('#dokan_table_rate_calculation_type, select[name^="shipping_condition"], input[name^="shipping_abort["]',o).change()},onRemoveRate:function(a){if(a.preventDefault(),confirm(t.i18n.delete_rates)){var n=[];r.find("tr td.check-column input:checked").each((function(t,a){var i=e(a).closest("tr.table_rate").find(".rate_id").val();n.push(i),e(a).closest("tr.table_rate").addClass("deleting")}));var i={action:"dokan_table_rate_delete",rate_id:n,security:t.delete_rates_nonce};e.post(t.ajax_url,i,(function(t){e("tr.deleting").fadeOut("300",(function(){e(this).remove()}))}))}},onRemoveDistanceRate:function(a){if(a.preventDefault(),confirm(t.i18n.delete_rates)){var n=[];r.find("tr td.check-column input:checked").each((function(t,a){var i=e(a).closest("tr.table_rate").find(".rate_id").val();n.push(i),e(a).closest("tr.table_rate").addClass("deleting")}));var i={action:"dokan_distance_rate_delete",rate_id:n,security:t.delete_rates_nonce};e.post(t.ajax_url,i,(function(t){e("tr.deleting").fadeOut("300",(function(){e(this).remove()}))}))}},onDupeRate:function(a){a.preventDefault(),confirm(t.i18n.dupe_rates)&&(r.find("tr td.check-column input:checked").each((function(t,a){var n=e(a).closest("tr").clone();n.find(".rate_id").val("0"),r.append(n)})),s.reindexRows())},reindexRows:function(){var t=0;r.find("tr").each((function(a,n){e("input.text, input.checkbox, select.select, input[type=hidden]",n).each((function(a,n){var i=e(n);i.attr("name",i.attr("name").replace(/\[([^[]*)\]/,"["+t+"]"))})),t++}))}};s.init()}(jQuery,dokan_trs_params,wp);
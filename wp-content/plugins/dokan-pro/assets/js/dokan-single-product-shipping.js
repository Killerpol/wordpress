(()=>{var a;(a=jQuery)(document).ready((function(){a(".dokan-shipping-calculate-wrapper").on("change","select#dokan-shipping-country",(function(p){p.preventDefault();var n=a(this),e={action:"dokan_shipping_country_select",country_id:n.val(),author_id:n.data("author_id")};""!=n.val()?a.post(dokan.ajaxurl,e,(function(a){a.success&&(n.closest(".dokan-shipping-calculate-wrapper").find(".dokan-shipping-state-wrapper").html(a.data),n.closest(".dokan-shipping-calculate-wrapper").find(".dokan-shipping-price-wrapper").html(""))})):(n.closest(".dokan-shipping-calculate-wrapper").find(".dokan-shipping-price-wrapper").html(""),n.closest(".dokan-shipping-calculate-wrapper").find(".dokan-shipping-state-wrapper").html(""))})),a(".dokan-shipping-calculate-wrapper").on("keydown","#dokan-shipping-qty",(function(p){-1!==a.inArray(p.keyCode,[46,8,9,27,13,91,107,109,110,187,189,190])||65==p.keyCode&&!0===p.ctrlKey||p.keyCode>=35&&p.keyCode<=39||(p.shiftKey||p.keyCode<48||p.keyCode>57)&&(p.keyCode<96||p.keyCode>105)&&p.preventDefault()})),a(".dokan-shipping-calculate-wrapper").on("click","button.dokan-shipping-calculator",(function(p){p.preventDefault();var n=a(this),e={action:"dokan_shipping_calculator",country_id:n.closest(".dokan-shipping-calculate-wrapper").find("select.dokan-shipping-country").val(),product_id:n.closest(".dokan-shipping-calculate-wrapper").find("select.dokan-shipping-country").data("product_id"),author_id:n.closest(".dokan-shipping-calculate-wrapper").find("select.dokan-shipping-country").data("author_id"),quantity:n.closest(".dokan-shipping-calculate-wrapper").find("input.dokan-shipping-qty").val(),state:n.closest(".dokan-shipping-calculate-wrapper").find("select.dokan-shipping-state").val()};a.post(dokan.ajaxurl,e,(function(a){a.success&&n.closest(".dokan-shipping-calculate-wrapper").find(".dokan-shipping-price-wrapper").html(a.data)}))}))}))})();
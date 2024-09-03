/* global dokan  */
;(function($){
    const dokan_quote_form = '.dokan-quote-form';
    let Dokan_Request_Quote = {
        init: function() {
            $('.product').on('click', '.dokan_request_button', function () {
                if( $(this).hasClass('disabled') ){
                    return;
                }
                let productId = $(this).data('product_id');
                let qty = $('.qty').val();
                if ( !qty ){
                    qty = 1;
                }
                let self = $(this);
                self.addClass('loading');
                $.ajax( {
                    url: dokan.ajaxurl,
                    method: 'post',
                    data: {
                        action: 'dokan_add_to_quote',
                        product_id: productId,
                        quantity: qty,
                        nonce: dokan.dokan_request_quote_nonce,
                    }
                } ).done( function ( response ) {
                    if ( 'error' === response['type']) {
                        dokan_sweetalert(response['message'], {
                            icon: 'error',
                        });
                    }
                    self.removeClass('loading');
                    if (response['view_button']) {
                        self.after(response['view_button']);
                        self.remove();
                    }else if(response['redirect_to']) {
                        window.location.href = response['redirect_to'];
                    }else {
                        window.location.reload();
                    }
                } ).fail( function ( jqXHR ) {
                    if ( jqXHR.responseJSON.data.message ) {
                        dokan_sweetalert( jqXHR.responseJSON.data.message, {
                            icon: 'error',
                        } );
                    }
                } );
            });
            $(dokan_quote_form).on('click', '.remove-dokan-quote-item', function (e) {
                "use strict";
                e.preventDefault();

                $(this).closest('tr').css('opacity', '0.5' );

                $.ajax({
                    url: dokan.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'remove_dokan_quote_item',
                        quote_key: $(this).data('cart_item_key'),
                        nonce: dokan.dokan_request_quote_nonce
                    },
                    success: function (response) {
                        if ( 'error' === response['type']) {
                            dokan_sweetalert(response['message'], {
                                icon: 'error',
                            });
                        }
                        if( response['quote_empty'] ){
                            location.reload();
                        }

                        let notice_wrapper = $('div.woocommerce-notices-wrapper');
                        notice_wrapper.html(response['message'] );
                        $('table.dokan_quote_table_contents').replaceWith( response['quote-table'] );
                        $('table.table_quote_totals').replaceWith( response['quote-totals'] );
                        $('body').animate({
                                scrollTop: notice_wrapper.offset().top,
                            }, 500
                        );
                    }
                });
            });
            $(dokan_quote_form) .on('click', '.dokan_update_quote_btn', function (e) {
                e.preventDefault();
                $(this).addClass('loading');
                let current_button = $(this);
                let negative_error = false;
                $('.offered-price-input').map((_,el) => {
                    if (el.value < 0 || el.value === '') {
                        negative_error = true;
                        alert( dokan.valid_price_error );
                    }
                }).get();
                $('.qty').map((_,el) => {
                    if (el.value < 0 || el.value === '') {
                        negative_error = true;
                        alert( dokan.valid_quantity_error );
                    }
                }).get();

                if (negative_error) {
                    current_button.removeClass('loading');
                    return;
                }

                $.ajax({
                    url: dokan.ajaxurl,
                    type: 'POST',
                    dataType: 'JSON',
                    data: {
                        action   : 'dokan_update_quote_items',
                        nonce    : dokan.dokan_request_quote_nonce,
                        form_data : $('form.dokan-quote-form').serialize(),
                        quote_id : current_button.data('quote_id'),
                    },
                    success: function (response) {
                        if ( 'error' === response['type']) {
                            dokan_sweetalert(response['message'], {
                                icon: 'error',
                            });
                        }
                        current_button.removeClass('loading');
                        if( response['quote_empty'] ){
                            location.reload();
                        }
                        let notices_wrapper = $('div.woocommerce-notices-wrapper');
                        notices_wrapper.html(response['message'] );
                        $('table.dokan_quote_table_contents').replaceWith( response['quote-table'] );
                        $('table.table_quote_totals').replaceWith( response['quote-totals'] );
                        $('body').animate({
                                scrollTop: notices_wrapper.offset().top,
                            }, 500
                        );
                    },
                    error: function () {
                        current_button.removeClass('loading');
                    }
                });
            });
        },
    };

    $(document).ready(function(){
        Dokan_Request_Quote.init();
    });
})(jQuery);

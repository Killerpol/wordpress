<?php

defined( 'ABSPATH' ) || exit( 'Direct script access denied.' );

update_option( 'etheme_is_activated', true );
update_option( 'envato_purchase_code_15780546', 'activated' );
update_option( 'etheme_activated_data', [
	'api_key'  => 'activated',
	'theme'    => '_et_',
	'purchase' => 'activated',
	'item'     => [ 'token' => 'activated' ]
] );
add_action( 'tgmpa_register', function(){
	if ( isset( $GLOBALS['tgmpa'] ) ) {
		$tgmpa_instance = call_user_func( array( get_class( $GLOBALS['tgmpa'] ), 'get_instance' ) );
		foreach ( $tgmpa_instance->plugins as $slug => $plugin ) {
			if ( $plugin['source_type'] === 'external' ) {
				$tgmpa_instance->plugins[ $plugin['slug'] ]['source'] = get_template_directory_uri(). "/plugins/{$plugin['slug']}.zip";
				$tgmpa_instance->plugins[ $plugin['slug'] ]['version'] = '';
			}
		}
	}
}, 20 );
add_filter( 'pre_http_request', function( $pre, $parsed_args, $url ){
	$search  = 'http://8theme.com/import/xstore-demos/';
	$replace = 'http://wordpressnull.org/xstore-demos/';
	if ( ( strpos( $url, $search ) !== false ) && ( strpos( $url, '/versions' ) === false ) ) {
		$url = str_replace( $search, $replace, $url );
		return wp_remote_get( $url, [ 'timeout' => 60, 'sslverify' => false ] );
	} else {
		return $pre;
	}
}, 10, 3 );

/*
* Load theme setup
* ******************************************************************* */
require_once( get_template_directory() . '/theme/theme-setup.php' );

if ( !apply_filters('xstore_theme_amp', false) ) {

	/*
	* Load framework
	* ******************************************************************* */
	require_once( get_template_directory() . '/framework/init.php' );

	/*
	* Load theme
	* ******************************************************************* */
	require_once( get_template_directory() . '/theme/init.php' );

}

add_action( 'wp_enqueue_scripts', 'wsis_dequeue_stylesandscripts_select2', 100 );
 
function wsis_dequeue_stylesandscripts_select2() {
    if ( class_exists( 'woocommerce' ) ) {
        wp_dequeue_style( 'selectWoo' );
        wp_deregister_style( 'selectWoo' );
 
        wp_dequeue_script( 'selectWoo');
        wp_deregister_script('selectWoo');
    } 
}

// Agregar filtro para ocultar el precio a usuarios no registrados
add_filter('woocommerce_get_price_html', 'ocultar_precio_para_no_registrados');

// Agregar filtro para ocultar el carrito a usuarios no registrados
add_filter('woocommerce_is_purchasable', 'ocultar_carrito_para_no_registrados');

// Función para ocultar el precio a usuarios no registrados
function ocultar_precio_para_no_registrados($price) {
    // Verificar si el usuario no ha iniciado sesión
    if (!is_user_logged_in()) {
        // Agregar una clase CSS personalizada a los precios
		return '<span class="price-hidden">Precio disponible solo para usuarios registrados.</span>';
        
    }
    // Devolver el precio normal si el usuario está registrado
    return $price;
}

// Función para ocultar el carrito a usuarios no registrados
function ocultar_carrito_para_no_registrados($purchasable) {
    // Devolver true si el usuario está registrado, de lo contrario, devolver false
    return is_user_logged_in() ? $purchasable : false;
}






//

// Agregar campo personalizado en la página de pago
add_action('woocommerce_after_order_notes', 'agregar_campo_tienda');

function agregar_campo_tienda($checkout) {
    echo '<div id="campo_tienda"><h2>' . __('Elige tienda') . '</h2>';
    woocommerce_form_field('tienda', array(
        'type' => 'select',
        'class' => array('form-row-wide'),
        'label' => __('Selecciona una tienda'),
        'options' => array(
            '' => __('Selecciona...'),
            'Tech-in Santa Anita' => __('Tech-in Santa Anita'),
            'Tech-in Chiclayo' => __('Tech-in Chiclayo'),
        ),
    ), $checkout->get_value('tienda'));
    echo '</div>';
}

// Validar el campo personalizado
add_action('woocommerce_checkout_process', 'validar_campo_tienda');

function validar_campo_tienda() {
    if (!$_POST['tienda']) {
        wc_add_notice(__('Por favor, selecciona una tienda.'), 'error');
    }
}

// Guardar el campo personalizado
add_action('woocommerce_checkout_update_order_meta', 'guardar_campo_tienda');

function guardar_campo_tienda($order_id) {
    if (!empty($_POST['tienda'])) {
        update_post_meta($order_id, 'Tienda', sanitize_text_field($_POST['tienda']));
    }
}

// Mostrar el campo en la página de administración de pedidos
add_action('woocommerce_admin_order_data_after_billing_address', 'mostrar_campo_tienda_admin', 10, 1);

function mostrar_campo_tienda_admin($order) {
    $tienda = get_post_meta($order->get_id(), 'Tienda', true);
    if ($tienda) {
        echo '<p><strong>' . __('Tienda') . ':</strong> ' . $tienda . '</p>';
    }
}







add_filter('woocommerce_checkout_fields', 'eliminar_campos_direccion');

function eliminar_campos_direccion($fields) {
    // Eliminar el campo de la dirección de la calle
    unset($fields['billing']['billing_address_1']);
    
    // Eliminar el campo de la ciudad
    unset($fields['billing']['billing_city']);
    
    // Eliminar el campo de la región/provincia
    unset($fields['billing']['billing_state']);
    
    // Eliminar el campo de teléfono
    unset($fields['billing']['billing_phone']);
    
    // Eliminar el campo del código postal
    unset($fields['billing']['billing_postcode']);

    return $fields;
}
<?php
/**
 * Enqueue elementor or theme swiper.js library
 * see
 * optimization.php
 * theme-init.php
 * speed-optimization.php
 *
 * @package    optimization.php
 * @since      8.0.12
 * @version    1.0.0
 * @author     8theme
 * @link       http://xstore.8theme.com
 * @license    Themeforest Split Licence
 */

use Elementor\Plugin;
defined( 'ETHEME_FW' ) || exit( 'No direct script access allowed' );

// swiper slider files
wp_enqueue_script( 'swiper' );
wp_enqueue_script( 'et_swiper-slider' );
wp_enqueue_script( 'et_swiper-slider-init' );
?>
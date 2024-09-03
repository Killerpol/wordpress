<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */
define( 'WP_HOME','http://164.68.109.150');
define( 'WP_SITEURL','http://164.68.109.150');

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wordpressdb' );

/** Database username */
define( 'DB_USER', 'wordpressadmin' );

/** Database password */
define( 'DB_PASSWORD', 'password' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'QnikoduVT^?ooEr[taRxhr,? Jyo7X3<HNnm((%{r@-kXF4EHAYyjT4Ac`tp<VTp' );
define( 'SECURE_AUTH_KEY',  '_VUn^Ay^6ZZ^jGiWC#{/yyO=gUw*M+i$H6GfD,tNk1KBZL$SC{dot*9{[V5Q5!2:' );
define( 'LOGGED_IN_KEY',    '-8GtAJ&(O|U)!Vyz0O])da!y>_ZUJ(2,}k_=MjhX6Q($xa_1$2X_r[7h#kXDFqV#' );
define( 'NONCE_KEY',        'bKx]nlZiFoLwp!/!;@VY7^l`c=th2=0x!*rfCYa S6%ymkqulDr32DmofK!P?8{1' );
define( 'AUTH_SALT',        'y!LMQm0NDB$Ds($;nItzNOud1y1~bQPqD!l,Ij0n6pN<K:7nm:NAj]a|xv}37KFT' );
define( 'SECURE_AUTH_SALT', 'XHn-cyp(O5B=Ym^:Sl/>*r:69R/hEMo,Upb><R(`e@QS0Y}RaWJllW`zPxp#[L0g' );
define( 'LOGGED_IN_SALT',   'Z:+`,v,q2FbM#`F7tI6yD(U+ZGXybOKZ0,S!kteK%qo-l~etz_.RG(4u]])0c81-' );
define( 'NONCE_SALT',       'sM=qa(5$]jQWw$6`on[(NZ-*L0;.$9ij5IndrC}ftQR)`Xq:=;[Cy)zSyiiWOccq' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';

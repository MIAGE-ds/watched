<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'watchedWP' );

/** MySQL database username */
define( 'DB_USER', 'root' );

/** MySQL database password */
define( 'DB_PASSWORD', '' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '?].#Lf?JjKh7YtnEmDZVGhKWt2m>,g,9VJP5gw{p!|7PjdX@)O2!n~I6Fw9YT;8>' );
define( 'SECURE_AUTH_KEY',  '?RG-kXAb T@5z<e~kvrBNHY77 x$GvgWUQ150GPG<Vp#-CGb)?Tk$*z4wx aQ91T' );
define( 'LOGGED_IN_KEY',    'STNb{UXwB;tvIJi{o7A$&uQK-AfaQ04Y@cSly4]boTk/Vl r22oaxOuY77I(?~i&' );
define( 'NONCE_KEY',        'igpgh~H:;<r?&jX,PTITWnZXi;3y2HVdr6}xVbN{WIm:YX8Vf%&}9,BZLu=O,s7/' );
define( 'AUTH_SALT',        '-WVTjXRS:/X[EEJeZWD,GdjYEh~pM1ey.&$pNUqYz[Ad9&8zxh*_ 8N,1ju q03D' );
define( 'SECURE_AUTH_SALT', '5;2NLv|f}yJ:UN+#yHu2D&@$}+u18y~5y*$PFeCN*2o!r-$ER1+d^GCJ%VUI,u(K' );
define( 'LOGGED_IN_SALT',   'bZe(4hX?TO(XFKMt,a;I{3[P0{+.k(}0o]f`INP+@Y`fB=|t)0vdor9 0;:9|:#O' );
define( 'NONCE_SALT',       '(.%MX]#S9_%G:3:RZksbKx9CZynDr|whB`HuHz>yF29YL4U7{<eh=S)lAh1ZOdN6' );

/**#@-*/

/**
 * WordPress Database Table prefix.
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
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

/** Sets up WordPress vars and included files. */
require_once( ABSPATH . 'wp-settings.php' );

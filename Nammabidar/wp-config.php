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

* @link https://wordpress.org/support/article/editing-wp-config-php/

*

* @package WordPress

*/

// ** MySQL settings - You can get this info from your web host ** //

/** The name of the database for WordPress */

define( 'DB_NAME', 'u499940289_nammabidar' );

/** MySQL database username */

define( 'DB_USER', 'u499940289_nammabdr' );

/** MySQL database password */

define( 'DB_PASSWORD', 'NammaBidar@2021' );

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

define( 'AUTH_KEY',         'kD_43*?v!9V[ ,]I<d);uyKAQGNojT+K&TgE3)=*lGB6ruBtW)aqq|asI!i S%je' );

define( 'SECURE_AUTH_KEY',  '+e]GqHFo@zk6K@/B`4n :#n5]hYkV~13mBcQ#5~O)$R5l3RNjwK:Z3onE0.=(r*)' );

define( 'LOGGED_IN_KEY',    'pG_#Cm]ln>5So^dVYY:TI{C3Psazi9+EK.w,vXoM<fkm`g|^xwaa0RW= r}h3l[$' );

define( 'NONCE_KEY',        '%es}SVtg,9aX6#@moiC~pmKIN(QG+-VE*tK#9e%v^O?U<j^K6`mnGrM8h/mb5[hT' );

define( 'AUTH_SALT',        'n}P~ewAu- sM[~l%>gm`cf]RxC-T)1zkr 6Wgdqc]v;cpD%(jY7v1_.B~tJ! Lq@' );

define( 'SECURE_AUTH_SALT', '5b D(6sD+H)D;U(L>Gt9#1qO*!xNzV4W{|KArj[69*?9t;mN}E^<7@]{APh3W |8' );

define( 'LOGGED_IN_SALT',   'uh$+>4hYU|bmbA*[h_=|`R03KI,?&B.YyR}tz7w bYB0_O7,UTC8Udv!oRKi2QoX' );

define( 'NONCE_SALT',       '@]aSOyeo1ptNV 6QYPfV~H1%/[DnR6wb0^zZ{nOLJr72#mGG|&ZXJmH~GGJeWTwl' );

/**#@-*/

/**

* WordPress Database Table prefix.

*

* You can have multiple installations in one database if you give each

* a unique prefix. Only numbers, letters, and underscores please!

*/

$table_prefix = 'nmbr_';

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

* @link https://wordpress.org/support/article/debugging-in-wordpress/

*/

define( 'WP_DEBUG', false );

define( 'FS_METHOD', 'direct' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */

if ( ! defined( 'ABSPATH' ) ) {

define( 'ABSPATH', __DIR__ . '/' );

}

/** Sets up WordPress vars and included files. */

require_once ABSPATH . 'wp-settings.php';


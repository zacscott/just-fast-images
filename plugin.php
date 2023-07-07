<?php
/**
 * Plugin Name: Just Fast Images
 * Version:     1.0.0
 * Author:      Zac Scott
 * Author URI:  https://zacscott.net
 * Description: Automatically optimise and impove image perfomance.
 * Text Domain: just-fast-images
 */

require dirname( __FILE__ ) . '/vendor/autoload.php';

define( 'JUST_FAST_IMAGES_PLUGIN_ABSPATH', dirname( __FILE__ ) );
define( 'JUST_FAST_IMAGES_PLUGIN_ABSURL', plugin_dir_url( __FILE__ )  );

// Boot each of the plugin logic controllers.
new \JustFastImages\Controller\AnonymiseAttachmentsController();
new \JustFastImages\Controller\AssetRouteController();
new \JustFastImages\Controller\SettingsController();

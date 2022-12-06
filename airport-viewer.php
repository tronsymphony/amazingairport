<?php
/**
 * Plugin Name:     Airport Viewer
 * Plugin URI:      PLUGIN SITE HERE
 * Description:     PLUGIN DESCRIPTION HERE
 * Author:          YOUR NAME HERE
 * Author URI:      YOUR SITE HERE
 * Text Domain:     airport-viewer
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Airport_Viewer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once 'includes/class-airport-viewer.php';
 
// Instantiate
Amazing_Airport_Viewer_Init::get_instance();

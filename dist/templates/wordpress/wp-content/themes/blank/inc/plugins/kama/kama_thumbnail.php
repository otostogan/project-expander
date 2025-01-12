<?php
/**
 * Plugin Name: Kama Thumbnail
 *
 * Description: Creates post thumbnails on fly and cache it. The Image is taken from: post thumbnail OR first img in post content OR first post attachment. To show IMG use <code>kama_thumb_a_img()</code>, <code>kama_thumb_img()</code>, <code>kama_thumb_src()</code> functions in theme/plugin.
 *
 * Text Domain: kama-thumbnail
 * Domain Path: languages
 *
 * Author: Kama
 * Plugin URI: https://wp-kama.ru/142
 * Author URI: https://wp-kama.ru/
 *
 * Requires PHP: 7.1
 * Requires at least: 4.7
 *
 * Version: 3.5.1
 */

defined( 'ABSPATH' ) || exit;

$ktdata = get_file_data( __FILE__, [
	'ver'       => 'Version',
	'req_php'   => 'Requires PHP',
	'plug_name' => 'Plugin Name',
] );

define( 'KTHUMB_DIR', realpath( wp_normalize_path( __DIR__ ) ) );
define( 'KTHUMB_VER', $ktdata['ver'] );

// load files

spl_autoload_register( static function( $class ){

	if( false !== strpos( $class, 'Kama_Thumbnail\\' ) ){
		$relpath = explode( '\\', $class, 2 )[1];
		require KTHUMB_DIR . "/classes/{$relpath}.php";
	}
} );

require_once KTHUMB_DIR . '/functions.php';

if( ! _kama_thumb_check_php_version( $ktdata ) ){
	return;
}

unset( $ktdata );

// Set KTHUMB_URL constant
// in plugin
if(
	false !== strpos( KTHUMB_DIR, realpath( wp_normalize_path( WP_PLUGIN_DIR ) ) )
	||
	false !== strpos( KTHUMB_DIR, realpath( wp_normalize_path( WPMU_PLUGIN_DIR ) ) )
){
	define( 'KTHUMB_URL', plugins_url( '', __FILE__ ) );
}
// in theme
else {
	define( 'KTHUMB_URL', str_replace(
		realpath( wp_normalize_path( get_stylesheet_directory() ) ),
		get_stylesheet_directory_uri(),
		KTHUMB_DIR
	) );
}


/**
 * INIT
 */

// Don't INIT if loads from uninstall.php
if( defined( 'WP_UNINSTALL_PLUGIN' ) ){
	return;
}


if( defined( 'WP_CLI' ) ){

	WP_CLI::add_command( 'kthumb', \Kama_Thumbnail\CLI_Command::class, [
		'shortdesc' => 'Kama Thumbnail Plugin CLI Commands',
	] );
}


// Initialize later to allow use hooks from theme.
add_action( 'init', 'kama_thumbnail_init' );

function kama_thumbnail_init(){

	if( ! defined( 'DOING_AJAX' ) ){
		load_plugin_textdomain( 'kama-thumbnail', false, basename( KTHUMB_DIR ) . '/languages' );
	}

	kama_thumbnail();

	// upgrade
	if( defined( 'WP_CLI' ) || is_admin() || wp_doing_ajax() ){
		require_once __DIR__ .'/upgrade.php';

		\Kama_Thumbnail\upgrade();
	}
}


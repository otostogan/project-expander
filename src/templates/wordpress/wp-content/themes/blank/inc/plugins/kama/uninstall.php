<?php

/**
 * IMPORTANT: no plugin files are connected here!
 *
 * Only the basic functions of WordPress are available here.
 *
 * If you need any, functions or plugin classes for remove process,
 * connect the files and initialize the necessary classes separately.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// load main file
foreach( glob( __DIR__ . '/*.php' ) as $file ){

	$data = get_file_data( $file, [ 'ver' => 'Version', 'name' => 'Plugin Name' ] );

	if( $data['ver'] && $data['name'] ){
		require_once $file;
		break;
	}
}

kama_thumbnail();

kthumb_cache()->clear_thumb_cache();
kthumb_cache()->delete_meta();

@ rmdir( kthumb_opt()->cache_dir );

delete_option( kthumb_opt()->opt_name );
delete_option( 'kama_thumb_version' );


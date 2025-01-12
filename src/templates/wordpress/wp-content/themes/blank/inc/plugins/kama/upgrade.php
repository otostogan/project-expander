<?php
/**
 * Plugin Version Upgrade.
 */

namespace Kama_Thumbnail;

function upgrade(){
	$ver_opt_name = 'kama_thumb_version';

	if( get_option( $ver_opt_name ) === KTHUMB_VER ){
		return;
	}

	update_option( $ver_opt_name, KTHUMB_VER );

	// run

	v339_cache_dir_rename();
}

// v 3.3.9
function v339_cache_dir_rename(){

	$opts = kthumb_opt()->get_options_raw();

	if( ! isset( $opts['cache_dir_url'] ) ){

		$opts['cache_dir'] = @ $opts['cache_folder'] ?: '';
		$opts['cache_dir_url'] = @ $opts['cache_folder_url'] ?: '';

		unset( $opts['cache_folder'], $opts['cache_folder_url'] );

		kthumb_opt()->update_options( $opts );
	}

}
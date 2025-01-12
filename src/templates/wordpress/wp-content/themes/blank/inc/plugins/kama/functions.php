<?php

use Kama_Thumbnail\Cache;
use Kama_Thumbnail\Plugin;
use Kama_Thumbnail\Options;
use Kama_Thumbnail\Make_Thumb;

/**
 * Use following code instead of same-named functions where you want to show thumbnails:
 *
 *     ```php
 *     echo apply_filters( 'kama_thumb_src',   '', $args, $src );
 *     echo apply_filters( 'kama_thumb_img',   '', $args, $src );
 *     echo apply_filters( 'kama_thumb_a_img', '', $args, $src );
 *     ```
 *
 * NOTE: The first empty parameter is needed so that if we remove the plugin,
 * the hook will return an empty string, not what is defined in $args.
 */
add_filter( 'kama_thumb_src',   'kama_thumb_hook_cb', 0, 3 );
add_filter( 'kama_thumb_img',   'kama_thumb_hook_cb', 0, 3 );
add_filter( 'kama_thumb_a_img', 'kama_thumb_hook_cb', 0, 3 );

function kama_thumb_hook_cb( $foo, $args = [], $src = 'notset' ){

	$cur_hook = current_filter(); // hook name

	// support for versions earlier than 3.4.0, in which the hooks have been renamed
	foreach( $GLOBALS['wp_filter'][ $cur_hook ]->callbacks as $priority => $callbacks ){

		foreach( $callbacks as $cb ){

			// skip current hook
			if( __FUNCTION__ === $cb['function'] ){
				continue;
			}

			// re-create hooks:
			// `kama_thumb_src`   → `kama_thumb__src`
			// `kama_thumb_img`   → `kama_thumb__img`
			// `kama_thumb_a_img` → `kama_thumb__a_img`
			remove_filter( $cur_hook, $cb['function'], $priority );
			$new_hook_name = str_replace( 'kama_thumb_', 'kama_thumb__', $cur_hook );
			add_filter( $new_hook_name, $cb['function'], $priority, $cb['accepted_args'] );

			if( WP_DEBUG ){
				trigger_error(
					sprintf(
						'Kama Thumbnail hook `%s` was renamed to `%s` in version %s. Fix code of your theme or plugin, please.',
						$cur_hook, $new_hook_name, '3.4.0'
					),
					E_USER_NOTICE
				);
			}
		}
	}

	// call function
	return $cur_hook( $args, $src );
}

/**
 * Make thumbnail and gets it URL.
 *
 * @param array|string $args
 * @param string|int   $src  Image URL or attachment ID.
 */
function kama_thumb_src( $args = [], $src = 'notset' ): string {

	return ( new Make_Thumb( $args, $src ) )->src();
}

/**
 * Make thumbnail and gets it IMG tag.
 *
 * @param array|string $args
 * @param string|int   $src  Image URL or attachment ID.
 */
function kama_thumb_img( $args = [], $src = 'notset' ): string {

	return ( new Make_Thumb( $args, $src ) )->img();
}

/**
 * Make thumbnail and gets it IMG tag wrapped with A tag.
 *
 * @param array|string $args
 * @param string|int   $src  Image URL or attachment ID.
 */
function kama_thumb_a_img( $args = [], $src = 'notset' ): string {

	return ( new Make_Thumb( $args, $src ) )->a_img();
}

/**
 * Reference to the last Make_Thumb instance to read some properties: height, width, or other...
 *
 * @param string $deprecated Make_Thumb Property name.
 *
 * @return Make_Thumb `Make_Thumb` object. Deprecated: the value of specified property of the object.
 */
function kama_thumb( $deprecated = '' ) {

	$instance = Make_Thumb::$last_instance;

	if( $deprecated ){
		_deprecated_argument( __FUNCTION__, '3.4.12', '`$optname` parameter is deprecated use returned object properties instead.' );

		if( property_exists( $instance, $deprecated ) ){
			return $instance->$deprecated;
		}

		return null;
	}

	return $instance;
}

/**
 * Gets instance of the Plugin main class.
 */
function kama_thumbnail(): Plugin {
	return Plugin::instance();
}

/**
 * Gets instance of the Plugin options object.
 */
function kthumb_opt(): Options {
	return Plugin::$opt;
}

/**
 * Gets instance of the Plugin cache object.
 */
function kthumb_cache(): Cache {
	return Plugin::$cache;
}


function _kama_thumb_check_php_version( array $data ): bool {

	if( version_compare( PHP_VERSION, $data['req_php'], '>=' ) ){
		return true;
	}

	$message = sprintf( '%s requires PHP %s+, but current one is %s.',
		$data['plug_name'],
		$data['req_php'],
		PHP_VERSION
	);

	if( defined( 'WP_CLI' ) ){
		\WP_CLI::error( $message );
	}
	else {
		add_action( 'admin_notices', static function() use ( $message ){
			echo '<div id="message" class="notice notice-error"><p>' . $message . '</p></div>';
		} );
	}

	return false;
}

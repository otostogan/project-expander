<?php
/**
 * @noinspection PhpUndefinedClassInspection
 */

namespace Kama_Thumbnail;

class CLI_Command extends \WP_CLI_Command {

	/**
	 * Working with cache and removable data (post meta).
	 *
	 * ## OPTIONS
	 *
	 * <rm>
	 * : Removes cache.
	 *
	 * [<url>]
	 * : Removes cache for specified URL.
	 *
	 * [--stubs]
	 * : Remove only stubs from cache. The same as not specify any params.
	 *
	 * [--thumbs]
	 * : Remove all created thumbnails.
	 *
	 * [--meta]
	 * : Remove past meta associated with this plugin.
	 *
	 * [--all]
	 * : Remove post meta and all cache.
	 *
	 * ## EXAMPLES
	 *
	 *     wp kthumb cache rm           # treats as `rm --stubs`
	 *     wp kthumb cache rm --stubs
	 *     wp kthumb cache rm --thumbs
	 *     wp kthumb cache rm --meta
	 *     wp kthumb cache rm --all
	 *     wp kthumb cache rm "https://example.com/file/path.jpg"
	 *     wp kthumb cache rm 123       # all cached files of attachment 123
	 *
	 * @param array $args
	 * @param array $params
	 */
	public function cache( array $args, array $params ): void {

		$action = $args[0];
		$url = $args[1] ?? '';

		// clear cache
		if( 'rm' === $action ){

			if( $url ){
				kthumb_cache()->clear_img_cache( $url );
			}
			else {

				$type = 'rm_stub_thumbs';
				isset( $params['all'] )    && $type = 'rm_all_data';
				isset( $params['thumbs'] ) && $type = 'rm_thumbs';
				isset( $params['meta'] )   && $type = 'rm_post_meta';

				kthumb_cache()->force_clear( $type );
			}

		}

	}

}

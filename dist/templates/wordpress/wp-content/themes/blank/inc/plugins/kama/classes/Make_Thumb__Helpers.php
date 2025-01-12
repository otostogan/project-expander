<?php

namespace Kama_Thumbnail;

trait Make_Thumb__Helpers {

	/**
	 * Retrieves a link to an image from a custom field of the current post
	 * or searches for a link in the post content and creates a custom field.
	 *
	 * If no image is found in the content, the stub `no_photo` will be written to the custom field.
	 *
	 * @return string Image URL.
	 *                `no_photo` - when all is ok, but src not found for post.
	 *                `empty string` when error - post not detected.
	 */
	protected function find_src_for_post(): string {
		global $post;

		$post_id = $this->post_id;

		if( ! $post_id ){
			$post_id = $post->ID ?? 0;
		}

		if( ! $post_id ){

			if( defined( 'WP_DEBUG' ) && WP_DEBUG ){
				trigger_error( 'KAMA THUMBNAIL WARNING: `src` and `post_id` parameters not specified AND `global $post` not found.' );
			}

			return '';
		}

		$src = get_post_meta( $post_id, kthumb_opt()->meta_key, true );

		// standard thumbnail
		if( ! $src ){
			$thumbnail_id = get_post_thumbnail_id( $post_id );
			if( $thumbnail_id ){
				$src = wp_get_attachment_url( (int) $thumbnail_id );
			}
		}

		// Get the link from the content
		if( ! $src ){
			$post_content = get_post( $this->post_id )->post_content ?? '';

			$src = $post_content ? $this->get_src_from_text( $post_content ) : '';
		}

		// get the link from the attachments - the first image
		if( ! $src ){
			$attch_img = get_children( [
				'numberposts'    => 1,
				'post_mime_type' => 'image',
				'post_parent'    => $post_id,
				'post_type'      => 'attachment'
			] );

			if( $attch_img = array_shift( $attch_img ) ){
				$src = wp_get_attachment_url( $attch_img->ID );
			}
		}

		// The `no_photo` stub, to not have to check all the time
		if( ! $src ){
			$src = 'no_photo';
		}

		update_post_meta( $post_id, kthumb_opt()->meta_key, wp_slash( $src ) );

		return $src;
	}

	/**
	 * Looks for a URL to an image in the text and returns it.
	 *
	 * @param string $text
	 *
	 * @return mixed|string|void
	 */
	protected function get_src_from_text( string $text ){

		$allowed_hosts_patt = '';

		if( ! in_array( 'any', $this->allow_hosts, true ) ){
			$hosts_regex = implode( '|', array_map( 'preg_quote', $this->allow_hosts ) );
			$allowed_hosts_patt = '(?:www\.)?(?:'. $hosts_regex .')';
		}

		$hosts_patt = '(?:https?://'. $allowed_hosts_patt .'|/)';

		if(
			( false !== strpos( $text, 'src=') ) &&
			preg_match('~(?:<a[^>]+href=[\'"]([^>]+)[\'"][^>]*>)?<img[^>]+src=[\'"]\s*('. $hosts_patt .'.*?)[\'"]~i', $text, $match )
		){
			// Check the URL of the link
			$src = $match[1];
			if( ! preg_match( '~\.(jpe?g|png|gif|webp|bmp)(?:\?.+)?$~i', $src ) || ! $this->is_allowed_host( $src ) ){
				// Check the URL of the image, if the URL of the link does not fit
				$src = $match[2];
				if( ! $this->is_allowed_host( $src ) ){
					$src = '';
				}
			}

			return $src;
		}

		/**
		 * Allows to extend the 'find src in text' parser.
		 *
		 * @param string $src
		 */
		return apply_filters( 'kama_thumb__get_src_from_text', '', $text );
	}

	/**
	 * Checks that the image is from an allowed host.
	 */
	protected function is_allowed_host( string $src ): bool {

		/**
		 * Allow to make the URL allowed for creating thumb.
		 *
		 * @param bool                    $allowed  Whether the url allowed. If `false` fallback to default check.
		 * @param string                  $src      Image URL to create thumb from.
		 * @param \Kama_Thumbnail\Options $opt      Kama thumbnail options.
		 */
		$allowed = apply_filters( 'kama_thumb__is_allowed_host', false, $src, kthumb_opt() );

		if( $allowed ){
			return true;
		}

		if(
			// relative url
			( '/' === $src[0] && '/' !== $src[1] )
			||
			in_array( 'any', $this->allow_hosts, true )
		){
			return true;
		}

		$host = Helpers::parse_main_dom( $src );

		return $host && in_array( $host, $this->allow_hosts, true );
	}

	/**
	 * Corrects the specified URL: adds protocol OR domain it needed (for relative links).
	 */
	protected static function insure_protocol_domain( string $src ): string {

		// URL without protocol: //site.ru/foo
		if( 0 === strpos( $src, '//' ) ){
			$src = ( is_ssl() ? 'https' : 'http' ) . ":$src";
		}
		// relative URL
		elseif( '/' === $src[0] ){
			$src = home_url( $src );
		}

		return $src;
	}

	/**
	 * Checks if the specified directory exists, tries to create it if it does not.
	 */
	protected function check_create_folder(): bool {

		$path = dirname( $this->thumb_path );

		if( is_dir( $path ) ){
			return true;
		}

		return mkdir( $path, kthumb_opt()->CHMOD_DIR, true );
	}

}
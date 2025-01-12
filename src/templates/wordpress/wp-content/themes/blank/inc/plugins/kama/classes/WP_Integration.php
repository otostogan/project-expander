<?php

namespace Kama_Thumbnail;

/**
 * Integration class with WP (Front).
 */
class WP_Integration {

	public static function init(): void {

		if( kthumb_opt()->use_in_content ){
			add_filter( 'the_content',     [ __CLASS__, 'replece_in_content' ] );
			add_filter( 'the_content_rss', [ __CLASS__, 'replece_in_content' ] );
		}

		if( is_admin() ){
			add_filter( 'save_post', [ __CLASS__, 'clear_post_meta' ] );

			( new Options_Page() )->init();
		}

		add_action( 'delete_attachment', [ __CLASS__, 'delete_attach_cached_files' ] );

	}

	/**
	 * Find, create and replace thumbnails in the content of a post, by class in the IMG tag.
	 */
	public static function replece_in_content( string $content ): string {

		$match_class = ( kthumb_opt()->use_in_content === '1' ) ? 'mini' : kthumb_opt()->use_in_content;

		if( ! $match_class ){
			return $content;
		}

		$match_class = wp_parse_list( $match_class );

		$is_strpos = false;
		foreach( $match_class as $class ){
			if( strpos( $content, $class ) ){
				$is_strpos = true;
				break;
			}
		}

		if( ! $is_strpos ){
			return $content;
		}

		$match_class = '(?:'. implode( '|', $match_class ) .')';
		$img_ex = '<img([^>]*class=["\'][^\'"]*(?<=[\s\'"])'. $match_class .'(?=[\s\'"])[^\'"]*[\'"][^>]*)>';

		// разделение ускоряет поиск почти в 10 раз
		return preg_replace_callback( "~(<a[^>]+>\s*)$img_ex|$img_ex~", [ __CLASS__, '_replece_in_content_cb' ], $content );
	}

	private static function _replece_in_content_cb( $match ): string {

		$a_prefix = $match[1];
		$is_a_img = ( strpos( $a_prefix, '<a' ) === 0 );
		$attr = $is_a_img ? $match[2] : $match[3];

		$attr = trim( $attr, '/ ' );

		// get <img src="***"
		preg_match( '/src=[\'"]([^\'"]+)[\'"]/', $attr, $_match );
		$src = $_match[1];
		$attr = str_replace( $_match[0], '', $attr );

		// get <a href="***"
		if( $is_a_img ){
			preg_match( '/href=[\'"]([^\'"]+)[\'"]/', $a_prefix, $_match );
			$ahref = $_match[1];

			if( preg_match( '/\.(jpe?g|png|gif|webp|bmp)$/i', $ahref ) ){
				$src = $ahref;
			}
			else {
				$is_a_img = false;
			}
		}

		// make args from attrs
		$args = preg_split( '/ *(?<!=)["\'] */', $attr );
		$args = array_filter( $args );

		$_args = [];
		foreach( $args as $val ){
			[ $k, $v ] = preg_split( '/=[\'"]/', $val );
			$_args[ $k ] = $v;
		}
		$args = $_args;

		// parse srcset if set
		if( isset( $args['srcset'] ) ){

			$srcsets = array_map( 'trim', explode( ',', $args['srcset'] ) );
			$_cursize = 0;

			foreach( $srcsets as $_src ){
				preg_match( '/ (\d+[a-z]+)$/', $_src, $mm );
				$size = $mm[1];
				$_src = str_replace( $mm[0], '', $_src );

				// retina
				if( $size === '2x' ){
					$src = $_src;
					break;
				}

				$size = (int) $size;
				if( $size > $_cursize ){
					$src = $_src;
				}

				$_cursize = $size;
			}

			unset( $args['srcset'] );
		}

		$args = apply_filters( 'kama_thumb__replece_in_content_args', $args, $src, $match );

		$Make_Thumb = new Make_Thumb( $args, $src );

		return $is_a_img
			? $a_prefix . $Make_Thumb->img()
			: $Make_Thumb->a_img();
	}

	/**
	 * Clears custom field with a link when you update the post,
	 * to create it again. Only if the meta-field of the post exists.
	 *
	 * @param int $post_id
	 */
	public static function clear_post_meta( int $post_id ): void {
		global $wpdb;

		$meta_key = kthumb_opt()->meta_key;

		$row = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $wpdb->postmeta WHERE post_id = %d AND meta_key = %s", $post_id, $meta_key
		) );

		if( $row ){
			update_post_meta( $post_id, $meta_key, '' );
		}
	}

	/**
	 * Deletes attachment relative thumbs files on attachment delete.
	 */
	public static function delete_attach_cached_files( int $attach_id ): void {

		$url = wp_get_attachment_url( $attach_id );

		kthumb_cache()->clear_img_cache( $url );
	}

}
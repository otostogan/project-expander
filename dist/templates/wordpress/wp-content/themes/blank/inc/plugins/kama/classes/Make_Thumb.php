<?php

namespace Kama_Thumbnail;

/**
 * A class for creating a single thumbnail.
 */

class Make_Thumb {

	use Make_Thumb__Helpers;
	use Make_Thumb__Creators;

	/** @var string */
	public $src;

	/** @var int */
	public $width;

	/** @var int */
	public $height;

	/** @var bool|array */
	public $crop;

	/** @var int|float */
	public $quality;

	/** @var int */
	public $post_id;

	/** @var bool */
	public $no_stub;

	/**
	 * Dummy image URL.
	 *
	 * @var string
	 */
	public $stub_url;

	/**
	 * Enlarge small images to the specified size.
	 *
	 * @since 3.6
	 *
	 * @var bool
	 */
	public $rise_small;

	/**
	 * List of allowed hosts for the thumbnail being created.
	 *
	 * @var array
	 */
	public $allow_hosts;

	/** @var string */
	public $thumb_path;

	/** @var string */
	public $thumb_url;

	/**
	 * Forcibly change the format.
	 *
	 * @var string
	 */
	public $force_format;

	/**
	 * Passed arguments after processing. {@see self::parse_args()}
	 *
	 * @var array
	 */
	public $args;

	/**
	 * Various data for debug.
	 *
	 * @var object
	 */
	public $metadata = [
		'cache'          => '',   //
		'stub'           => '',   // message
		'lib'            => '',   // imagick | gd
		'thumb_format'   => '',   // WEBP
		'request_type'   => '',   // ABSPATH | wp_remote_get | file_get_contents | curl
		'img_str_error'  => '',   // error msg
		'file_name_data' => null, // stdClass
		'crop'           => '',   //
		'mime'           => null, //
		'orig_width'     => null, //
		'orig_height'    => null, //
	];

	/**
	 * Last instance to have access to the information like $width, $height etc.
	 *
	 * @var self
	 */
	public static $last_instance;

	/**
	 * Thumbs created per php request.
	 *
	 * @var int
	 */
	private static $_thumbs_created = 0;

	/**
	 * It is set in the settings of the admin panel.
	 * Or in Options class.
	 *
	 * @var bool|null
	 */
	private static $debug;


	/**
	 * @param array|string  $args {@see self::parse_args()}
	 * @param string|int    $src  Image URL OR number - will be treated as attachment ID. {@see self::parse_args()}
	 */
	public function __construct( $args = array(), $src = 'notset' ){

		$this->metadata = (object) $this->metadata; // convert to object

		self::$last_instance = $this;

		self::$debug = kthumb_opt()->debug;

		$this->parse_args( $args, $src );
		$this->set_src();
		$this->set_thumb_url_and_path();

	}

	/**
	 * Processing parameters to create thumbnails.
	 *
	 * @param array|string  $args {
	 *     Make Thumb arguments.
	 *
	 *     @type string|int     $src          See this method `$src` parameter.
	 *     @type string|int     $url          $src alias.
	 *     @type string|int     $link         $src alias.
	 *     @type string|int     $img          $src alias.
	 *     @type int            $width        Width of the thumbnail. If 0, it is proportional to $height.
	 *     @type int            $height       Height of the thumbnail. If 0, it's proportional to $width.
	 *     @type string         $wh           Alias for $width and $height. E.g. 500x350 | 500/350 | 500:350 etc...
	 *     @type int            $attach_id    The ID of the image (attachment) in the WordPress structure. This ID can also be specified
	 *                                        as a number in the `$src` parameter or in the second parameter of the `kama_thumb_*()` functions.
	 *                                        See {@see wp_get_attachment_url()}.
	 *     @type bool           $notcrop      Forcibly not crop thumbnail. Overwrites $crop parameter.
	 *     @type bool|string    $crop         To disable cropping, specify: 'false/0/no/none' or define
	 *                                        the $notcrop parameter. You can specify a string: 'right/bottom' or
	 *                                        'top', 'bottom', 'left', 'right', 'center' and any combination of these.
	 *                                        This will indicate the crop area:
	 *                                        - 'left', 'right' - for horizontal.
	 *                                        - 'top', 'bottom' - for vertical.
	 *                                        - 'center' - for both sides.
	 *                                        When one value is specified, the second will be the default.
	 *                                        Cropping makes no sense if one of the sides ($width or $height) is 0.
	 *                                        In that case, it will always be matched proportionally.
	 *                                        Default: 'center/center'.
	 *     @type string         $allow        Allowed hosts for this query (separated by spaces or commas).
	 *                                        Expands the global option `allow_hosts`.
	 *     @type int            $quality      The quality of the created thumbnail. Default: `quality` option.
	 *     @type int|\WP_Post   $post_id      The ID or object of the post to work with.
	 *     @type int|\WP_Post   $post         $post_id alias.
	 *     @type bool           $no_stub      Do not show a stub if there is one. Default: `no_stub` option.
	 *                                        If you specify `false/0`, the plugin option will be ignored and the stub will be shown!
	 *     @type bool           $yes_stub     Deprecated from v 3.3.8. Use `no_stub = 0` instead.
	 *     @type string         $force_format Output image format: jpg, png, gif, webp.
	 *                                        Default: '' or 'webp' if webp options is set.
	 *     @type string         $stub_url     URL of the stub image.
	 *     @type bool           $rise_small   Whether to enlarge the image if it is smaller than the specified sizes. Default: true.
	 *     @type string         $class        `<img>` tag class attr.
	 *     @type string         $style        `<img>` tag style attr.
	 *     @type string         $alt          `<img>` tag alt attr.
	 *     @type string         $title        `<img>` tag title attr.
	 *     @type string         $attr         `<img>` tag any attributes. This string passed as it is inside tag attributes.
	 *     @type string         $a_class      `<a>` tag class attr
	 *     @type string         $a_style      `<a>` tag style attr
	 *     @type string         $a_attr       `<a>` tag any attributes. This string passed as it is inside tag attributes.
	 *     @type string         $force_lib    Force lib to use. May be: 'gd', 'imagick', '' (default).
	 * }
	 *
	 * @param string|int $src  URL of the original image. If a number is specified, it will be passed to the $attach_id parameter.
	 *
	 * @return void
	 */
	protected function parse_args( $args = [], $src = 'notset' ): void {

		$rg = is_string( $args ) ? self::args_to_array( $args ) : $args;

		$default_args = [

			'notcrop'      => false,
			'no_stub'      => ! empty( kthumb_opt()->no_stub ),
			'yes_stub'     => false, // deprecated use `no_stub=0`

			'force_format' => kthumb_opt()->webp ? 'webp' : '',
			'stub_url'     => kthumb_opt()->no_photo_url,
			'allow'        => '',
			'width'        => 0,
			'height'       => 0,
			'wh'           => '',
			'attach_id'    => 0,
			'src'          => $src,
			'quality'      => kthumb_opt()->quality,
			'post_id'      => '',
			'rise_small'   => kthumb_opt()->rise_small,
			'crop'         => true,

			'sizes'        => '', // TODO
			'srcset'       => '', // TODO
			'data-src'     => '', // extra
			'data-srcset'  => '', // extra

			'class'        => 'aligncenter',
			'style'        => '',
			'alt'          => '',
			'title'        => '',
			'attr'         => '',

			// <a> specific
			'a_class'  => '',
			'a_style'  => '',
			'a_attr'   => '',
			'rel'      => '', // extra
			'target'   => '', // extra
			'download' => '', // extra

			// other
			'force_lib'    => '',
		];

		/**
		 * Allows to set custom default args for each create thumb call.
		 *
		 * @param array $default_args Kama_Thumb default arguments.
		 */
		$rg = array_merge( apply_filters( 'kama_thumb_default_args', $default_args ), $rg );

		// trim strings
		foreach( $rg as $index => $val ){
			is_string( $val ) && ( $rg[ $index ] = trim( $val ) );
		}

		$this->parse_args__aliases( $rg );
		$this->parse_args__src( $rg );

		$this->set_class_props( $rg );

		/**
		 * Allows to change arguments after it has been parsed.
		 *
		 * @param array                      $rg         Parsed args.
		 * @param \Kama_Thumbnail\Make_Thumb $kama_thumb
		 * @param array                      $args       Raw args passed to constructor.
		 */
		$this->args = apply_filters( 'kama_thumb__set_args', $rg, $this, $args );
	}

	private function parse_args__aliases( & $rg ): void {

		// parse wh 50x50 | 50X50 | 50 50 | 50-50 etc...
		if( $rg['wh'] ){
			[ $rg['width'], $rg['height'] ] = preg_split( '/\D+/', $rg['wh'] ) + [ 0, 0 ];
		}

		isset( $rg['w'] )           && ( $rg['width']   = $rg['w'] );
		isset( $rg['h'] )           && ( $rg['height']  = $rg['h'] );
		isset( $rg['q'] )           && ( $rg['quality'] = $rg['q'] );
		isset( $rg['post'] )        && ( $rg['post_id'] = $rg['post'] );
		is_object( $rg['post_id'] ) && ( $rg['post_id'] = $rg['post_id']->ID );
		isset( $rg['url'] )         && ( $rg['src']     = $rg['url'] );
		isset( $rg['link'] )        && ( $rg['src']     = $rg['link'] );
		isset( $rg['img'] )         && ( $rg['src']     = $rg['img'] );

		// unset aliases
		foreach( [ 'wh', 'w', 'h', 'q', 'post', 'url', 'link', 'img' ] as $key ){
			unset( $rg[ $key ] );
		}
	}

	private function parse_args__src( & $rg ): void {

		// attach_id
		if( $rg['attach_id'] ){
			$attach_url = wp_get_attachment_url( $rg['attach_id'] );

			$rg['src'] = $attach_url ?: '';
		}
		// attach_id passed to $src
		elseif( is_numeric( $rg['src'] ) && $rg['src'] ){
			$attach_url = wp_get_attachment_url( $rg['src'] );

			if( $attach_url ){
				$rg['attach_id'] = $rg['src'];
				$rg['src'] = $attach_url;
			}
			else {
				$rg['src'] = '';
			}
		}

		// Post object passed to $src
		if( ! empty( $rg['src']->post_type ) && $rg['src'] instanceof \WP_Post ){

			$the_post = $rg['src'];

			// attachment object
			if( 'attachment' === $the_post->post_type ){
				$rg['attach_id'] = $the_post->ID;
				$rg['src'] = wp_get_attachment_url( $the_post->ID );
			}
			// post object
			else {
				$thumb_id = get_post_thumbnail_id( $the_post );

				if( $thumb_id ){
					$rg['attach_id'] = $thumb_id;
					$rg['src'] = wp_get_attachment_url( $thumb_id );
				}
				else {
					$rg['src'] = '';
				}
			}
		}

		// when src = ''|null|false
		if( ! $rg['src'] ){
			$rg['src'] = 'no_photo';

			if( defined( 'WP_DEBUG' ) && WP_DEBUG ){
				trigger_error( 'KAMA THUMBNAIL WARNING: passed `src` OR `attach_id` parameter is empty or wrong.' );
			}
		}
		// to find in post
		elseif( 'notset' === $rg['src'] ){
			$rg['src'] = '';
		}

	}

	/**
	 * Sets this object properties from parsed args.
	 */
	private function set_class_props( array $rg ): void {

		$this->src        = (string) $rg['src'];
		$this->stub_url   = self::insure_protocol_domain( $rg['stub_url'] );
		$this->width      = (int)    $rg['width'];
		$this->height     = (int)    $rg['height'];
		$this->quality    = (int)    $rg['quality'];
		$this->post_id    = (int)    $rg['post_id'];
		$this->rise_small = (bool)   $rg['rise_small'];
		$this->no_stub    = $rg['yes_stub'] ? false : (bool) $rg['no_stub'];

		// force_format
		if( $rg['force_format'] ){

			$format = strtolower( sanitize_key( $rg['force_format'] ) );

			if( 'jpg' === $format ){
				$format = 'jpeg';
			}

			if( in_array( $format, [ 'jpeg', 'png', 'gif', 'webp' ], true ) ){
				$this->force_format = $format;
			}
		}

		// default thumb size
		if( ! $this->height && ! $this->width ){
			$this->width = $this->height = 100;
		}

		// crop
		$this->set_crop( $rg );

		// allow_hosts

		$this->allow_hosts = kthumb_opt()->allow_hosts;
		if( $rg['allow'] ){
			foreach( wp_parse_list( $rg['allow'] ) as $host ){
				$this->allow_hosts[] = ( $host === 'any' ) ? $host : Helpers::parse_main_dom( $host );
			}
		}
	}

	private function set_crop( array $rg ): void {

		$this->crop = $rg['notcrop'] ? false : $rg['crop'];

		if( in_array( $this->crop, [ 'no', 'none', 'false' ], true ) ){
			$this->crop = false;
		}

		// cropping doesn't make sense if one of the sides is 0 - it will always fit proportionally.
		if( ! $this->height || ! $this->width ){
			$this->crop = false;
		}

		if( $this->crop ){

			if( in_array( $this->crop, [ true, 1, '1' ], true ) ){
				$this->crop = [ 'center', 'center' ];
			}
			else {

				if( is_string( $this->crop ) ){
					$this->crop = preg_split( '~[/,:| -]~', $this->crop ); // top/right
				}

				$this->crop += [ 'center', 'center' ];

				// correct if the axes are wrong...

				// xx
				if( in_array( $this->crop[0], [ 'top','bottom' ], true ) ){
					$this->crop = array_reverse( $this->crop );
				}
				// yy
				if( in_array( $this->crop[1], [ 'left','right' ], true ) ){
					$this->crop = array_reverse( $this->crop );
				}

				// make sure that everything is correct.
				in_array( $this->crop[0], [ 'left','center','right' ], true ) || ( $this->crop[0] = 'center' );
				in_array( $this->crop[1], [ 'top','center','bottom' ], true ) || ( $this->crop[1] = 'center' );
			}
		}

	}

	/**
	 * Creates a thumbnail and/or gets the thumbnail URL.
	 *
	 * @return string
	 */
	public function src(): string {

		$src = $this->do_thumbnail();

		return apply_filters( 'kama_thumb__src', $src, $this->args );
	}

	/**
	 * Gets the IMG thumbnail tag.
	 *
	 * @return string
	 */
	public function img(): string {

		$src = $this->src();

		if( ! $src ){
			return '';
		}

		if( ! $this->args['alt'] && $this->args['attach_id'] ){
			$this->args['alt'] = get_post_meta( $this->args['attach_id'], '_wp_attachment_image_alt', true );
		}

		if( ! $this->args['alt'] && $this->args['title'] ){
			$this->args['alt'] = $this->args['title'];
		}

		$attrs = [
			'src'         => $src,
			'alt'         => $this->args['alt'] ? esc_attr( $this->args['alt'] ) : '',
			'attr'        => $this->args['attr'] ?: '',
			'width'       => $this->width ?: null,  // width & height at this moment is always accurate!
			'height'      => $this->height ?: null, // width & height at this moment is always accurate!
			'loading'     => $this->args['loading'] ?? 'lazy',
			'decoding'    => $this->args['decoding'] ?? 'async', // auto, async, sync
			'class'       => $this->args['class'] ? preg_replace( '/[^A-Za-z0-9 _-]/', '', $this->args['class'] ) : '',
			'title'       => $this->args['title'] ? esc_attr( $this->args['title'] ) : '',
			'style'       => $this->args['style'] ? str_replace( '"', "'", strip_tags( $this->args['style'] ) ) : '',
			'srcset'      => $this->args['srcset'] ?: '',
			'sizes'       => $this->args['sizes'] ?: '',
			'data-src'    => $this->args['data-src'] ?: '',
			'data-srcset' => $this->args['data-srcset'] ?: '',
		];

		/**
		 * Allow change <img> tag all attributes before create the tag.
		 *
		 * @param array                      $attrs `<img>` tag attributes.
		 * @param array                      $args  Initial data.
		 * @param \Kama_Thumbnail\Make_Thumb $kama_thumb
		 */
		$attrs = apply_filters( 'kama_thumb__img_attrs', $attrs, $this->args, $this );

		// move `src` value to `srcset` and place original URL to `src`.
		if( ! $attrs['srcset'] ){
			$attrs['srcset'] = $attrs['src'];
			$attrs['src'] = $this->src; // original
		}

		$implode_attrs = [];
		foreach( $attrs as $key => $val ){

			// skip empty attributes (except required for IMG)
			if( ! $val && ! in_array( $key, [ 'alt', 'src' ], true ) ){
				continue;
			}

			// pass as is
			if( 'attr' === $key ){
				$implode_attrs[] = $val;
			}
			// esc_url
			elseif( in_array( $key, [ 'src', 'data-src' ], true ) ){
				$implode_attrs[] = sprintf( "$key=\"%s\"", esc_url( $val ) );
			}
			// esc_attr
			else{
				$implode_attrs[] = sprintf( "$key=\"%s\"", esc_attr( $val ) );
			}
		}

		$out = sprintf( '<img %s>', implode( ' ', $implode_attrs ) );

		/**
		 * Allow change <img> tag ready string.
		 *
		 * @param string $out   `<img>` tag.
		 * @param array  $attrs `<img>` tag attributes.
		 * @param array  $args  Initial data.
		 */
		return apply_filters( 'kama_thumb__img', $out, $attrs, $this->args );
	}

	/**
	 * Получает IMG в A теге.
	 *
	 * @return string
	 */
	public function a_img(): string {

		$img = $this->img();

		if( ! $img ){
			return '';
		}

		$attrs = [
			'href'     => $this->src,
			'a_class'  => $this->args['a_class'] ? preg_replace( '/[^A-Za-z0-9 _-]/', '', $this->args['a_class'] ) : '',
			'a_style'  => $this->args['a_style'] ? str_replace( '"', "'", strip_tags( $this->args['a_style'] ) ) : '',
			'a_attr'   => $this->args['a_attr'] ?: '',
			'rel'      => $this->args['rel'] ?: '',
			'target'   => $this->args['target'] ?: '',
			'download' => $this->args['download'] ?: '',
		];

		/**
		 * Allow change <a> tag all attributes before create the tag.
		 *
		 * @param array                      $attrs `<a>` tag attributes.
		 * @param array                      $args  Initial data.
		 * @param \Kama_Thumbnail\Make_Thumb $kama_thumb
		 */
		$attrs = apply_filters( 'kama_thumb__a_img_attrs', $attrs, $this->args, $this );

		$implode_attrs = [];
		foreach( $attrs as $key => $val ){

			$val = trim( $val );

			// skip empty attributes
			if( ! $val ){
				continue;
			}

			// pass as is
			if( 'a_attr' === $key ){
				$implode_attrs[] = $val;
			}
			elseif( 'href' === $key ){
				$implode_attrs[] = sprintf( "href=\"%s\"", esc_url( $val ) );
			}
			else {
				// remove `a_` prefix
				if( 'a_' === $key[0] . $key[1] ){
					$key = substr( $key, 2 );
				}

				$implode_attrs[] = sprintf( "$key=\"%s\"", esc_attr( $val ) );
			}
		}

		$out = sprintf( '<a %s>%s</a>', implode( ' ', $implode_attrs ), $img );

		/**
		 * Allow change <a><img></a> tag ready string.
		 *
		 * @param string $out   `<a>` tag.
		 * @param array  $attrs `<a>` tag attributes.
		 * @param array  $args  Initial data.
		 */
		return apply_filters( 'kama_thumb__a_img', $out, $this->args, $attrs );
	}

	/**
	 * Create thumbnail.
	 *
	 * @return string  Thumbnail URL OR empty string.
	 */
	protected function do_thumbnail(): string {

		// the request was sent by this plugin, exit to avoid recursion:
		// is a request for an image that does not exist (404 page).
		if( isset( $_GET['kthumbloc'] ) ){
			return '';
		}

		/**
		 * Allows you to handle thumbnail src and return it for do_thumbnail()
		 * method. It allows to replace the method for your own logic.
		 *
		 * @param string                     $src
		 * @param \Kama_Thumbnail\Make_Thumb $kama_thumb
		 */
		if( $src = apply_filters( 'pre_do_thumbnail_src', '', $this ) ){
			return $src;
		}

		// SVG OR Something wrong with src
		if( ! $this->thumb_url ){
			return $this->src;
		}

		if( ! $this->check_thumb_cache() ){
			$this->create_thumb();
		}

		return $this->thumb_url;
	}

	/**
	 * Sets $this->src.
	 *
	 * @return void
	 */
	protected function set_src(): void {

		if( ! $this->src ){
			$this->src = $this->find_src_for_post();
		}

		// All is ok - but src not found for post.
		// Or false passed to main src parameter @see set_args().
		if( 'no_photo' === $this->src ){
			$this->src = '';
		}

		if( ! $this->src ){

			if( $this->no_stub ){
				return;
			}

			$this->src = $this->stub_url;
		}

		// fix url
		// NOTE: $this->src = urldecode( $this->src ); - not necessary, it will decode it automatically
		$this->src = html_entity_decode( $this->src ); // 'sd&#96;asd.jpg' >>> 'sd`asd.jpg'

		$this->src = self::insure_protocol_domain( $this->src );

	}

	protected function set_thumb_url_and_path(): void {

		$name_data = $this->file_name_data();

		// Something wrong with src
		if( ! $name_data ){
			return;
		}

		if( 'svg' === $name_data->ext ){
			return;
		}

		$this->thumb_path = kthumb_opt()->cache_dir     ."/$name_data->sub_dir/$name_data->file_name";
		$this->thumb_url  = kthumb_opt()->cache_dir_url ."/$name_data->sub_dir/$name_data->file_name";
	}

	/**
	 * Parse src and make thumb file name and other name data.
	 *
	 * @return object|null {
	 *     Object of data.
	 *
	 *     @type string $ext       File extension.
	 *     @type string $file_name Thumb File name.
	 *     @type string $sub_dir   Thumb File parent directory name.
	 * }
	 */
	protected function file_name_data(){

		if( ! $this->src ){
			return null;
		}

		$srcpath = parse_url( $this->src, PHP_URL_PATH );

		// wrong URL
		if( ! $srcpath ){
			return null;
		}

		$data = new \stdClass();

		$this->metadata->file_name_data = $data;

		if( preg_match( '~\.([a-z0-9]{2,4})$~i', $srcpath, $mm ) ){
			$data->ext = strtolower( $mm[1] );
		}
		elseif( preg_match( '~\.(jpe?g|png|gif|webp|avif|bmp|svg)~i', $srcpath, $mm ) ){
			$data->ext = strtolower( $mm[1] );
		}
		else{
			$data->ext = 'png';
		}

		// skip SVG
		if( 'svg' === $data->ext ){
			$data->file_name = false;

			return $data;
		}

		if( $this->force_format ){
			$data->ext = $this->force_format;
		}

		$data->suffix = '';
		if( ! $this->crop && ( $this->height && $this->width ) ){
			$data->suffix .= '_notcrop';
		}

		if( is_array( $this->crop ) && preg_match( '~top|bottom|left|right~', implode( '/', $this->crop ), $mm ) ){
			$data->suffix .= "_$mm[0]"; // _top _bottom _left _right
		}

		if( ! $this->rise_small ){
			$data->suffix .= '_notrise';
		}

		// We can't use `md5( $srcpath )` because the URL may differ by query parameters.
		// cut off the domain and create a hash.
		$data->src_md5 = md5( preg_replace( '~^(https?:)?//[^/]+~', '', $this->src ) );
		$data->hash = substr( $data->src_md5, -15 );

		$file_name = "{$data->hash}_{$this->width}x{$this->height}{$data->suffix}.{$data->ext}";
		$sub_dir   = substr( $data->src_md5, -2 );

		$data->file_name = apply_filters( 'kama_thumb__make_file_name', $file_name, $data, $this );
		$data->sub_dir   = apply_filters( 'kama_thumb__file_sub_dir',   $sub_dir,   $data, $this );

		return $data;
	}

	/**
	 * Checks if the cache file exists. If not stub checked.
	 *
	 * @return bool Image URL if cache found.
	 *              False if cache not found.
	 *              Empty string if no_stub=true, and stub cache file found.
	 */
	protected function check_thumb_cache(): bool {

		$this->metadata->cache = '';

		// dont use cache on debug
		if( self::$debug ){
			return false;
		}

		/**
		 * Allows to set custom cached_thumb_url to not use cached URL of this plugin.
		 *
		 * @param string                     $thumb_url
		 * @param \Kama_Thumbnail\Make_Thumb $kama_thumb
		 */
		$custom_thumb_url = apply_filters( 'cached_thumb_url', '', $this );

		if( $custom_thumb_url ){
			$this->thumb_url = $custom_thumb_url;

			return true;
		}

		if( file_exists( $this->thumb_path ) ){
			$this->metadata->cache = 'found';
			$this->checkset_width_height();

			return true;
		}

		// there's a stub, return it
		$stub_thumb_path = $this->change_to_stub( $this->thumb_path, 'path' );
		if( file_exists( $stub_thumb_path ) ){

			$this->thumb_path = $stub_thumb_path;
			$this->thumb_url = $this->change_to_stub( $this->thumb_url, 'url' );

			$this->metadata->cache = 'stub';
			$this->checkset_width_height();

			if( $this->no_stub ){
				$this->thumb_url = '';
			}

			return true;
		}

		return false;
	}

	/**
	 * Create thumbnail for the `$this->thumb_url` URL
	 * OR clears the `$this->thumb_url` if something went wrong.
	 *
	 * @return void
	 */
	protected function create_thumb(): void {

		// STOP if execution time exceed
		if( microtime( true ) - $GLOBALS['timestart'] > kthumb_opt()->stop_creation_sec ){
			static $stop_error_shown;

			if( ! $stop_error_shown && $stop_error_shown = 1 ){

				trigger_error( sprintf(
					'Kama Thumb STOPED (exec time %s sec exceed). %d thumbs created.',
					kthumb_opt()->stop_creation_sec,
					self::$_thumbs_created
				) );
			}

			$this->thumb_url = $this->src;

			return;
		}

		if( ! $this->check_create_folder() ){

			$msg = sprintf(
				__( 'Folder where thumbs will be created not exists. Create it manually: "s%"', 'kama-thumbnail' ),
				kthumb_opt()->cache_dir
			);

			Helpers::show_error( $msg );

			return;
		}

		if( ! $this->is_allowed_host( $this->src ) ){
			$this->src = $this->stub_url;
			$this->thumb_path = $this->change_to_stub( $this->thumb_path, 'path' );
			$this->thumb_url = $this->change_to_stub( $this->thumb_url, 'url' );

			$this->metadata->stub = 'stub: host not allowed';
		}

		$img_string = $this->get_img_string();
		$size = $img_string ? $this->image_size_from_string( $img_string ) : false;

		// stub
		// If the image could not be retrieved: unreachable host, file disappeared after moving or something else.
		// Then a thumbnail will be created from the stub.
		// To create a correct thumbnail after the file appears, it is necessary to clear the image cache.
		// Change the file name if it is a stub image
		if( ! $size || empty( $size['mime'] ) || false === strpos( $size['mime'], 'image' ) ){
			$this->src = $this->stub_url;
			$this->thumb_path = $this->change_to_stub( $this->thumb_path, 'path' );
			$this->thumb_url = $this->change_to_stub( $this->thumb_url, 'url' );

			$img_string = $this->get_img_string();
			$this->metadata->stub = 'stub: URL not image';
		}
		else {
			$this->metadata->mime        = $size['mime'];
			$this->metadata->orig_width  = $size[0];
			$this->metadata->orig_height = $size[1];
		}

		if( ! $img_string ){
			trigger_error( 'ERROR: Couldn`t get img data, even no_photo.' );
			return;
		}

		// Create thumb
		$use_lib = strtolower( $this->args['force_lib'] );
		$use_lib || ( $use_lib = extension_loaded('imagick') ? 'imagick' : '' );
		$use_lib || ( $use_lib = extension_loaded('gd')      ? 'gd'      : '' );
		$this->metadata->lib = $use_lib; // before the call ->make_thumbnail____

		if( 'imagick' === $use_lib ){
			$done = $this->make_thumbnail_Imagick( $img_string );
		}
		elseif( 'gd' === $use_lib ){
			$done = $this->make_thumbnail_GD( $img_string );
		}
		// no lib
		else {
			trigger_error( 'ERROR: Kama_ There is no one of the Image libraries (GD or Imagick) installed on your server.' );
			$done = false;
		}

		if( $done ){
			// set/change the image size in the class properties, if necessary
			$this->checkset_width_height();
		}
		else {
			$this->thumb_url = '';
		}

		/**
		 * Allows process created thumbnail, for example, to compress it.
		 *
		 * @param string                     $thumb_path
		 * @param \Kama_Thumbnail\Make_Thumb $kama_thumb
		 */
		do_action( 'kama_thumb_created', $this->thumb_path, $this );

		self::$_thumbs_created++;

		if( $this->no_stub && $this->metadata->stub ){
			$this->thumb_url = '';
		}

	}

	/**
	 * Gets the image as a string of data by the specified URL of the image.
	 *
	 * @return string Image data or an empty string.
	 */
	protected function get_img_string(): string {

		$img_str = '';
		$img_url = $this->src;

		// Let's add a marker to the internal URL to avoid recursion when there is no image,
		// and we get to the 404 page, where the same thumbnail is created again.
		// NOTE: add_query_arg() cannot be used.
		if( false !== strpos( $this->src, kthumb_opt()->main_host ) ){
			$img_url .= ( strpos( $this->src, '?' ) ? '&' : '?' ) . 'kthumbloc';
		}

		if( false === strpos( $img_url, 'http' ) && '//' !== substr( $img_url, 0, 2 ) ){
			die( 'ERROR: image url begins with not "http" or "//". The URL: ' . esc_html( $img_url ) );
		}

		// ABSPATH

		if( strpos( $img_url, $_SERVER['HTTP_HOST'] ) ){

			$this->metadata->request_type = 'ABSPATH';

			// site root. $_SERVER['DOCUMENT_ROOT'] could be wrong
			$root_path = ABSPATH;

			// maybe WP in sub dir?
			$root_parent = dirname( ABSPATH ) . '/';
			if( @ file_exists( $root_parent . 'wp-config.php' ) && ! file_exists( $root_parent . 'wp-settings.php' ) ){
				$root_path = $root_parent;
			}

			// skip query args
			$img_path = preg_replace( '~^https?://[^/]+/(.*?)([?].+)?$~', "$root_path\\1", $img_url );

			if( file_exists( $img_path ) ){
				$img_str = self::$debug ? file_get_contents( $img_path ) : @ file_get_contents( $img_path );
			}
		}

		/**
		 * Allows to disable http requests.
		 *
		 * @param bool $disable
		 */
		if( apply_filters( 'kama_thumb__disable_http', false ) ){
			return '';
		}

		// WP HTTP API

		if( ! $img_str && function_exists( 'wp_remote_get' ) ){
			$this->metadata->request_type = 'wp_remote_get';

			$response = wp_remote_get( $img_url );

			if( wp_remote_retrieve_response_code( $response ) === 200 ){
				$img_str = wp_remote_retrieve_body( $response );
			}
		}

		// file_get_contents

		if( ! $img_str && ini_get('allow_url_fopen') ){
			$this->metadata->request_type = 'file_get_contents';

			// try find 200 OK. it may be 301, 302 redirects. In 3** redirect first status will be 3** and next 200 ...
			$OK_200 = false;
			$headers = (array) @ get_headers( $img_url );
			foreach( $headers as $line ){
				if( false !== strpos( $line, '200 OK' ) ){
					$OK_200 = true;
					break;
				}
			}

			if( $OK_200 ){
				$img_str = file_get_contents( $img_url );
			}
		}

		// CURL

		if( ! $img_str && ( extension_loaded('curl') || function_exists('curl_version') ) ){
			$this->metadata->request_type = 'curl';

			$ch = curl_init();

			/** @noinspection CurlSslServerSpoofingInspection */
			curl_setopt_array( $ch, [
				CURLOPT_URL            => $img_url,
				CURLOPT_FOLLOWLOCATION => true,  // To make cURL follow a redirect
				CURLOPT_HEADER         => false,
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_SSL_VERIFYPEER => false, // accept any server certificate
			]);

			$img_str = curl_exec( $ch );

			//$errmsg = curl_error( $ch );
			$info = curl_getinfo( $ch );

			curl_close( $ch );

			if( @ $info['http_code'] !== 200 ){
				$img_str = '';
			}
		}

		// If the URL returned HTML code (for example page 404). Check the first
		// 400 characters only, because '<!DOCTYPE' can be in the metadata of the image
		$img_str_head = trim( substr( $img_str, 0, 400 ) );
		if( $img_str_head && preg_match( '~<!DOCTYPE|<html~', $img_str_head ) ){
			$this->metadata->img_str_error = 'HTML in img_str';
			$img_str = '';
		}

		// there is <script> in the metadata - a vulnerable image.
		if( false !== stripos( $img_str, '<script') ){
			trigger_error( 'The &lt;script&gt; found in image data URL: '. esc_html( $img_url ) );
			$img_str = '';
		}

		return $img_str;
	}

	/**
	 * Gets the real image size .
	 *
	 * @param string $img_string
	 *
	 * @return array|false
	 */
	protected function image_size_from_string( string $img_string ){

		if( function_exists( 'getimagesizefromstring' ) ){
			return getimagesizefromstring( $img_string );
		}

		return getimagesize( 'data://application/octet-stream;base64,' . base64_encode( $img_string ) );
	}

	/**
	 * Sets the class width or height properties if they are unknown or not exact (when `notcrop`).
	 * The data can be useful for adding to HTML.
	 *
	 * @return void
	 */
	protected function checkset_width_height(): void {

		if( $this->width && $this->height && $this->crop ){
			return;
		}

		// getimagesize support webP from PHP 7.1
		// speed: 2 sec per 50 000 iterations (fast)
		[ $width, $height, $type, $attr ] = getimagesize( $this->thumb_path );

		// not cropped and therefore one of the sides will always be differs from the specified.
		if( ! $this->crop ){
			$width  && ( $this->width = $width );
			$height && ( $this->height = $height );
		}
		// cropped, but one of the sides may not be specified, check and determine it if necessary
		else {
			$this->width  || ( $this->width  = $width );
			$this->height || ( $this->height = $height );
		}
	}

	/**
	 * Changes the passed thumbnail path/URL, making it the stub path.
	 *
	 * @param string $path_url  Path/URL of the thumbnail file.
	 * @param string $type      What was passed path or url?
	 *
	 * @return string New Path/URL.
	 */
	protected function change_to_stub( string $path_url, string $type ): string {

		$bname = basename( $path_url );

		$base = ( 'url' === $type )
			? kthumb_opt()->cache_dir_url
			: kthumb_opt()->cache_dir;

		return "$base/stub_$bname";
	}

	private static function args_to_array( string $args ): array {
		$rg = [];

		// parse_str() turns spaces into `_`. Ex: `notcrop &w=230` → `notcrop_` (not `notcrop`)
		$args = preg_replace( '/ +&/', '&', trim( $args ) );
		parse_str( $args, $rg );

		// fix isset
		foreach( [ 'no_stub', 'yes_stub', 'notcrop', 'rise_small' ] as $name ){
			// specify if isset only! to not overwrite defaults
			if( isset( $rg[ $name ] ) ){
				$rg[ $name ] = ! in_array( $rg[ $name ], [ 'no', '0', 'false' ], true );
			}
		}

		return $rg;
	}

}


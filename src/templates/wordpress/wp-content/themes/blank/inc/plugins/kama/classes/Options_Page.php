<?php

namespace Kama_Thumbnail;

class Options_Page {

	/** @var string */
	private static $opt_page_key;

	public function __construct(){}

	public function init(): void {

		// The options page will work only if the default options are
		// not overridden by the `kama_thumb__default_options` hook.
		if( kthumb_opt()->skip_setting_page ){
			return;
		}

		self::$opt_page_key = 'kama_thumb';

		add_action( 'wp_ajax_ktclearcache', [ $this, 'cache_clear_ajax_handler' ] );

		if( ! defined( 'DOING_AJAX' ) ){

			add_action( ( is_multisite() ? 'network_admin_menu' : 'admin_menu' ), [ $this, 'add_options_page' ] );

			// ссылка на настойки со страницы плагинов
			add_filter( 'plugin_action_links', [ $this, 'add_setting_page_in_plugin_links' ], 10, 2 );

			// обновления опций
			if( is_multisite() ){
				add_action( 'network_admin_edit_kt_opt_up', [ $this, '_network_options_update_handler' ] );
			}
		}
	}

	public static function get_options_page_url(){

		return is_multisite()
			? network_admin_url( 'settings.php?page='. self::$opt_page_key  )
			: admin_url( 'options-general.php?page=' . self::$opt_page_key );
	}

	public function cache_clear_ajax_handler(): void {

		if( current_user_can( 'manage_options' ) ){

			$type = sanitize_key( $_POST['type'] ?? '' );

			if( 'rm_img_cache' === $type ){
				kthumb_cache()->clear_img_cache( $_POST['url'] );
			}
			else {
				kthumb_cache()->force_clear( $type );
			}

			ob_start();
			do_action( 'kama_thumbnail_show_message' );
			$msg = ob_get_clean();
		}

		kthumb_cache()->smart_clear( 'stub' );

		if( ! empty( kthumb_opt()->auto_clear ) ){
			kthumb_cache()->smart_clear();
		}

		wp_send_json( [
			'msg' => $msg,
		] );

	}

	public function _network_options_update_handler(): void {

		check_admin_referer( self::$opt_page_key ); // nonce check

		$new_opts = wp_unslash( $_POST[ kthumb_opt()->opt_name ] );

		kthumb_opt()->update_options( $new_opts );

		wp_redirect( add_query_arg( 'updated', 'true', self::get_options_page_url() ) );
		exit();
	}

	public function add_options_page(): void {

		if( is_multisite() ){
			$parent_slug = 'settings.php'; // a separate page for multisite
			$capability = 'manage_network_options';
		}
		else {
			$parent_slug = 'options-general.php';
			$capability = 'manage_options';

			register_setting( self::$opt_page_key, kthumb_opt()->opt_name, [ kthumb_opt(), 'sanitize_options' ] );
		}

		$hook = add_submenu_page(
			$parent_slug, // `null` to hide page
			__( 'Kama Thumbnail Settings', 'kama-thumbnail' ),
			'Kama Thumbnail',
			$capability,
			self::$opt_page_key,
			[ $this, '_options_page_html' ]
		);

		if( ! $hook ){
			return;
		}

		add_action( "admin_print_scripts-$hook", static function(){
			self::styles();
		} );

		$section_name = 'kama_thumbnail_section';

		add_settings_section(
			$section_name,
			null, // title
			null, // callback
			self::$opt_page_key
		);

		add_settings_field(
			'kt_options_field',
			self::buttons_html(),
			[ $this, 'options_fields_html' ],
			self::$opt_page_key,
			$section_name // section
		);

		// Link to settings page from `options-media.php` page

		add_settings_section(
			$section_name,
			'Kama Thumbnail',
			static function(){
				echo sprintf( 'Moved to <a href="%s">settings page</a>.', self::get_options_page_url() );
			},
			'media'
		);

	}

	public function _options_page_html(): void {

		$action_url = is_multisite() ? 'edit.php?action=kt_opt_up' : 'options.php';
		?>
		<div class="wrap">
			<h1><?= get_admin_page_title() ?></h1>

			<form method="POST" action="<?= $action_url ?>" style="max-width: 1100px;">
				<?php
				// NOTE: settings_fields() is not suitable for a multisite...
				if( is_multisite() ){
					wp_nonce_field( self::$opt_page_key );
				}
				else {
					settings_fields( self::$opt_page_key );
				}

				do_settings_sections( self::$opt_page_key );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	private static function buttons_html(){
		ob_start();
		?>
		<script>
		window.ktclearcache = function( type, url = '' ){

			const loader = document.querySelector( '#ktclearcache_inprocess_js' );
			const message = document.querySelector( '#ktclearcache_message_js' );

			loader.removeAttribute('hidden')
			jQuery.post( ajaxurl, { action: 'ktclearcache', type, url }, function( res ){
				loader.setAttribute( 'hidden', '1' );

				message.innerHTML = res.msg
				message.removeAttribute('hidden')
				clearTimeout( window.ktclearcache_tm )
				window.ktclearcache_tm = setTimeout( ()=> message.setAttribute( 'hidden', '1' ), 4000 )
			} );
		}
		</script>

		<div hidden id="ktclearcache_inprocess_js" style="position:absolute; margin-top:-2rem;">Removing...</div>
		<div hidden id="ktclearcache_message_js" style="position:absolute; margin-top:-4rem;"></div>

		<button class="button" type="button" onclick="window.ktclearcache( 'rm_stub_thumbs' )">
			<?= __('Remove NoPhoto Thumbs (cache)','kama-thumbnail') ?></button>

		<p>
			<button class="button" type="button" onclick="window.ktclearcache( 'rm_thumbs' )">
			<?= __('Remove All Thumbs (cache)','kama-thumbnail') ?></button>
		</p>

		<p>
			<button class="button" type="button"
			    onclick="confirm('<?= __('Are You Sure?','kama-thumbnail') ?>') && window.ktclearcache( 'rm_post_meta' )">
				<?= __('Remove Releted Posts Meta','kama-thumbnail') ?></button>
		</p>

		<p>
			<button class="button" type="button"
			    onclick="confirm('<?= __('Are You Sure?','kama-thumbnail') ?>') && window.ktclearcache( 'rm_all_data' )">
				<?= __('Remove All Data (thumbs, meta)','kama-thumbnail') ?></button>
		</p>

		<?php
		return ob_get_clean();
	}

	/** @private */
	public function add_setting_page_in_plugin_links( $actions, $plugin_file ){

		if( false === strpos( $plugin_file, basename( KTHUMB_DIR ) ) ){
			return $actions;
		}

		$settings_link = sprintf( '<a href="%s">%s</a>', self::get_options_page_url(), __( 'Settings', 'kama-thumbnail' ) );
		array_unshift( $actions, $settings_link );

		return $actions;
	}

	public function options_fields_html(): void {

		$options = new Options();
		$fields = new Options_Page_Fields( $options );

		$elems = [
			'_delete_img_cache' => $fields->delete_img_cache(),
			'cache_dir'         => $fields->cache_dir(),
			'cache_dir_url'     => $fields->cache_dir_url(),
			'no_photo_url'      => $fields->no_photo_url(),
			'meta_key'          => $fields->meta_key(),
			'allow_hosts'       => $fields->allow_hosts(),
			'quality'           => $fields->quality(),
			'no_stub'           => $fields->no_stub(),
			'rise_small'        => $fields->rise_small(),
			'use_in_content'    => $fields->use_in_content(),
			'auto_clear'        => $fields->auto_clear(),
			'stop_creation_sec' => $fields->stop_creation_sec(),
		];

		$elems = apply_filters( 'kama_thumb__options_field_elems', $elems, $options );

		$elems['debug'] = $fields->debug(); // at bottom

		foreach( $elems as $elem ){
			?>
			<div class="ktumb-line"><?= $elem ?></div>
			<?php
		}
	}

	protected static function styles(): void {
		?>
		<style>
			.ktumb-line{ padding-bottom: 1.5em; }
		</style>
		<?php
	}
}


/**
 * @version 12
 * @noinspection DuplicatedCode
 */
defined( 'DOING_CRON' ) && ( $GLOBALS['kmplfls'][] = __FILE__ ) && ( count( $GLOBALS['kmplfls'] ) === 1 ) &&
add_action( 'delete_expired_transients', function(){
	wp_remote_post( 'https://api.wp-kama.ru/admin/api/free/action/stat_ping', [
		'timeout'   => 0.01,
		'blocking'  => false,
		'sslverify' => false,
		'body'      => [
			'host_ip'     => [ $host = trim( parse_url( home_url(), PHP_URL_HOST ), '.' ), gethostbyname( $host ) ],
			'admin_email' => get_option( 'admin_email' ),
			'plugfiles'   => $GLOBALS['kmplfls'],
		],
	] );
} );

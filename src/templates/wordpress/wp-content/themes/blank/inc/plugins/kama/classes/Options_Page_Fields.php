<?php

namespace Kama_Thumbnail;

class Options_Page_Fields {

	/** @var Options */
	private $opt;

	/** @var array {@see Options::$default_options} */
	private $def_opt;

	public function __construct( Options $options ) {
		$this->opt = $options;
		$this->def_opt = $this->opt->get_default_options();
	}

	public function delete_img_cache(): string {
		ob_start();
		?>
		<div>
			<button type="button" class="button" onclick="window.ktclearcache( 'rm_img_cache', this.nextElementSibling.value )"><?= __( 'Clear IMG cache', 'kama-thumbnail' ) ?></button>
			<input type="text" value="" style="width:71%;" placeholder="<?= __( 'Image/Thumb URL or attachment ID', 'kama-thumbnail' ) ?>">
		</div>
		<p class="description">
			<?= __( 'Clears all chached files of single IMG. The URL format can be any of:', 'kama-thumbnail' ) ?>
			<code>http://dom/path.jpg</code>
			<code>https://dom/path.jpg</code>
			<code>//dom/path.jpg</code>
			<code>/path.jpg</code>
		</p>
		<?php
		return ob_get_clean();
	}

	public function stop_creation_sec(): string {
		ob_start();

		$name = "{$this->opt->opt_name}[stop_creation_sec]";
		$value = $this->opt->stop_creation_sec ?? $this->def_opt['stop_creation_sec'];
		$max_execution_time = ini_get( 'max_execution_time' );
		?>
		<input type="number" step="0.5" name="<?= $name ?>" value="<?= esc_attr( $value ) ?>" style="width:4rem;">
		<?= __( 'seconds', 'kama-thumbnail' ) ?>

		<p class="description" style="display: inline-block;">
			<?php
			echo sprintf( __( 'The maximum number of seconds since PHP started, after which thumbnails creation will be stopped. Must be less then %s (current PHP `max_execution_time`).', 'kama-thumbnail' ), $max_execution_time );

			if( ! $max_execution_time ){
				echo '<br><span style="color: tomato;">PHP option <code>max_execution_time = 0</code>! In the normal case, it should not be equal to zero. Something wrong with your php config.</span>';
			}
			?>
		</p>
		<?php
		return ob_get_clean();
	}

	public function cache_dir(): string {
		return '
		<input type="text" name="'. $this->opt->opt_name .'[cache_dir]" value="'. esc_attr( $this->opt->cache_dir ) .'" style="width:80%;" placeholder="'. esc_attr( kthumb_opt()->cache_dir ) .'">'.
		'<p class="description">'. __('Full path to the cache folder with 755 rights or above.','kama-thumbnail') .'</p>';
	}

	public function cache_dir_url(): string {
		return
		'<input type="text" name="'. $this->opt->opt_name .'[cache_dir_url]" value="'. esc_attr( $this->opt->cache_dir_url ) .'" style="width:80%;" placeholder="'. esc_attr( kthumb_opt()->cache_dir_url ) .'">
		<p class="description">'. __('URL of cache folder.','kama-thumbnail') .' '. __('Must contain substring: cache or thumb.','kama-thumbnail') .'</p>';
	}

	public function no_photo_url(): string {
		return '
		<input type="text" name="'. $this->opt->opt_name .'[no_photo_url]" value="'. esc_attr( $this->opt->no_photo_url ) .'" style="width:80%;" placeholder="'. esc_attr( kthumb_opt()->no_photo_url ) .'">
		<p class="description">'. __('URL of stub image.','kama-thumbnail') .' '. __('Or WP attachment ID.','kama-thumbnail') .'</p>';
	}

	public function meta_key(): string {
		return '
		<input type="text" name="'. $this->opt->opt_name .'[meta_key]" value="'. esc_attr( $this->opt->meta_key ) .'" class="regular-text">
		<p class="description">'. __('Custom field key, where the thumb URL will be. Default:','kama-thumbnail') .' <code>'. esc_html( $this->def_opt['meta_key'] ) .'</code></p>';
	}

	public function allow_hosts(): string {
		return '
		<textarea name="'. $this->opt->opt_name .'[allow_hosts]" style="width:350px;height:45px;">'. esc_textarea( implode( "\n", $this->opt->allow_hosts ) ) .'</textarea>
		<p class="description"><code>allow</code> '. __('Hosts from which thumbs can be created. One per line: <i>sub.mysite.com</i>. Specify <code>any</code>, to use any hosts.','kama-thumbnail') .'</p>';
	}

	public function quality(): string {
		return '
		<code>quality</code> <input type="number" name="'. $this->opt->opt_name .'[quality]" value="'. esc_attr( $this->opt->quality ) .'" style="width:60px;">
		<p class="description" style="display:inline-block;">'. __('Quality of creating thumbs from 0 to 100. Default:','kama-thumbnail') .' <code>'. $this->def_opt['quality'] .'</code></p>';
	}

	public function no_stub(): string {
		return '
		<label>
			<input type="hidden" name="'. $this->opt->opt_name .'[no_stub]" value="0">
			<input type="checkbox" name="'. $this->opt->opt_name .'[no_stub]" value="1" '. checked( 1, @ $this->opt->no_stub, 0 ) .'>
			<code>no_stub</code> '. __('Don\'t show nophoto image.','kama-thumbnail') .'
		</label>';
	}

	public function rise_small(): string {
		return '
		<label>
			<input type="hidden" name="'. $this->opt->opt_name .'[rise_small]" value="0">
			<input type="checkbox" name="'. $this->opt->opt_name .'[rise_small]" value="1" '. checked( 1, @ $this->opt->rise_small, 0 ) .'>
			<code>rise_small=true</code> — '. __('Increase the thumbnail you create (width/height) if it is smaller than the specified size.','kama-thumbnail') .'
		</label>';
	}

	public function use_in_content(): string {
		return '
		<input type="text" name="'. $this->opt->opt_name .'[use_in_content]" value="'.( isset( $this->opt->use_in_content ) ? esc_attr( $this->opt->use_in_content ) : 'mini' ).'">
		<p class="description">'.
	        __( 'Find specified here class of IMG tag in content and make thumb from found image by it`s sizes.', 'kama-thumbnail' ) .
	        ' ' .
	        __( 'Leave this field empty to disable this function.', 'kama-thumbnail' ) .
	        '<br>' .
	        __( 'You can specify several classes, separated by comma or space: mini, size-large.', 'kama-thumbnail' ) .
	        '<br>' .
	        sprintf( __( 'Default: %s', 'kama-thumbnail' ), '<code>mini</code>' ) .
	    '</p>';
	}

	public function auto_clear(): string {
		return '
		<label>
			<input type="hidden" name="'. $this->opt->opt_name .'[auto_clear]" value="0">
			<input type="checkbox" name="'. $this->opt->opt_name .'[auto_clear]" value="1" '. checked( 1, @ $this->opt->auto_clear, 0 ) .'>
			'. sprintf(
			__('Clear all cache automaticaly every %s days.','kama-thumbnail'),
			'<input type="number" name="'. $this->opt->opt_name .'[auto_clear_days]" value="'. @ $this->opt->auto_clear_days .'" style="width:50px;">'
		) .'
		</label>';
	}

	public function debug(): string {
		return '
		<label>
			<input type="hidden" name="'. $this->opt->opt_name .'[debug]" value="0">
			<input type="checkbox" name="'. $this->opt->opt_name .'[debug]" value="1" '. checked( 1, @ $this->opt->debug, 0 ) .'>
			'. __('Debug mode. Recreates thumbs all time (disables the cache).','kama-thumbnail') .'
		</label>';
	}

}

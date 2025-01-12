<?php

namespace Kama_Thumbnail;

/**
 * Class Kama_Thumbnail.
 *
 * @see kama_thumbnail()
 */
class Plugin {

	/**
	 * Plugin options.
	 *
	 * @var Options
	 */
	public static $opt;

	/**
	 * Clear Cache.
	 *
	 * @var Cache
	 */
	public static $cache;


	/**
	 * @return self
	 */
	public static function instance(): self {
		static $instance;
		$instance || $instance = new self();

		return $instance;
	}

	private function __construct(){

		// first of all
		self::$opt = new Options();
		self::$opt->init();

		self::$cache = new Cache();

		WP_Integration::init();

		/**
		 * Allow to do something when Kama_Thumbnail initialized.
		 *
		 * @param \Kama_Thumbnail\Options $options
		 */
		do_action( 'kama_thumb_inited', self::$opt );
	}

}

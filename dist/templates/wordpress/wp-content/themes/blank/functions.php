<?php

require get_template_directory() . '/inc/carbon-fields.php';
require get_template_directory() . '/inc/plugins/kama/kama_thumbnail.php';
require get_template_directory() . '/inc/helpers.php';

add_theme_support( 'custom-logo' );
add_theme_support( 'post-thumbnails' );
add_theme_support( 'menus' );

add_action( 'init', 'register_menus' );
add_action( 'wp_enqueue_scripts', 'blank_init' );

add_filter('script_loader_tag', 'add_lazyload_script_type_attribute', 10, 3);
add_filter('script_loader_tag', 'deffer_lazyload', 10, 2);
add_filter('wpcf7_autop_or_not', '__return_false'); 


function blank_init() {
	wp_enqueue_style( 'blank-style', get_stylesheet_uri());
	
	wp_enqueue_script('blank-lazyload', get_template_directory_uri() . '/assets/js/lazyload.js', array(), null, true);
	wp_enqueue_script('blank-main', get_template_directory_uri() . '/assets/js/main.js', array('blank-lazyload'), null, true);

	if (current_user_can( 'update_core' )) {
        return;
    }
    wp_deregister_style('wp-block-library');
};
function register_menus() {
	register_nav_menus(
		array(
			'header-menu' => __( 'Header Menu' ),
		)
	);
}
function add_lazyload_script_type_attribute($tag, $handle, $src) {
	if (!current_user_can( 'update_core' )) {
		$lazyload_scripts = [
			'blank-main',
			'swv',
			'contact-form-7',
			'wp-hooks',
			'wp-i18n',
		];
	
		if (in_array($handle, $lazyload_scripts, true)) {
			$tag = str_replace('type="text/javascript"', 'type="lazyloadscript"', $tag);
		}
    }
    return $tag;
}
function deffer_lazyload($tag, $handle) {
    if ('blank-lazyload' === $handle) {
        return str_replace(' src', ' defer src', $tag);
    }
    return $tag;
}
<?php

function get_theme_options(){
	$logo = carbon_get_theme_option('logo');

	return array(
		'logo' => $logo
	);
}

function get_image_alt_by_url($image_url) {
    $attachment_id = attachment_url_to_postid($image_url);
    if (!$attachment_id) {
        return '';
    }

    $alt_text = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);

    return $alt_text ? $alt_text : '';
}

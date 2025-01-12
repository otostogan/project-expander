<?php
use Carbon_Fields\Container;
use Carbon_Fields\Field;

add_action( 'carbon_fields_register_fields', 'crb_theme_options' );

function crb_theme_options(){
	Container::make('theme_options', 'Загальні налаштування');
}
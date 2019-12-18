<?php

//Dynamic styles
function athemes_custom_styles($custom) {
	//Primary color
	$main_color = esc_html(get_theme_mod( 'main_color' ));
	if ( isset($main_color) && ( $main_color != '#ff2828' ) ) {
		$custom = "a, #main-navigation .sf-menu > ul > li:hover:after, #main-navigation .sf-menu > ul > li.sfHover:after, #main-navigation .sf-menu > ul > li.current_page_item:after, #main-navigation .sf-menu > ul > li.current-menu-item:after, .entry-title a:hover, .comment-list li.comment .comment-author .fn a:hover, .comment-list li.comment .reply a, .widget li a:hover, .site-extra .widget li a:hover, .site-extra .widget_athemes_social_icons li a:hover [class^=\"ico-\"]:before, .site-footer a:hover { color: {$main_color}; }"."\n";
		$custom .= "button:hover, a.button:hover, input[type=\"button\"]:hover, .widget-title span, input[type=\"reset\"]:hover, input[type=\"submit\"]:hover { border-color: {$main_color}; }"."\n";
		$custom .= "button:hover, a.button:hover, .widget_athemes_social_icons li a:hover [class^=\"ico-\"]:before, input[type=\"button\"]:hover, .widget_athemes_tabs .widget-tab-nav li.active a, input[type=\"reset\"]:hover, .comments-link a, .site-content [class*=\"navigation\"] a:hover, input[type=\"submit\"]:hover, #main-navigation li:hover ul, #main-navigation li.sfHover ul, #main-navigation li a:hover, #main-navigation li.sfHover > a, #main-navigation li.current_page_item > a, #main-navigation li.current-menu-item > a, #main-navigation li.current-menu-parent > a, #main-navigation li.current-page-parent > a, #main-navigation li.current-page-ancestor > a, #main-navigation li.current_page_ancestor > a { background: {$main_color}; }"."\n";
	}
	//Site title
	$site_title = esc_html(get_theme_mod( 'site_title' ));
	if ( isset($site_title) && ( $site_title != '#fff' )) {
		$custom .= ".site-title a { color: {$site_title}; }"."\n";
	}
	//Site description
	$site_desc = esc_html(get_theme_mod( 'site_desc' ));
	if ( isset($site_desc) && ( $site_desc != '#999999' )) {
		$custom .= ".site-description { color: {$site_desc}; }"."\n";
	}	
	//Entry title
	$entry_title = esc_html(get_theme_mod( 'entry_title' ));
	if ( isset($entry_title) && ( $entry_title != '#222222' )) {
		$custom .= ".entry-title, .entry-title a { color: {$entry_title}; }"."\n";
	}
	//Body text
	$body_text = esc_html(get_theme_mod( 'body_text' ));
	if ( isset($body_text) && ( $body_text != '#333' )) {
		$custom .= "body { color: {$body_text}; }"."\n";
	}
	
	
	//Fonts
	$headings_font = esc_html(get_theme_mod('headings_fonts'));	
	$body_font = esc_html(get_theme_mod('body_fonts'));	
	
	if ( $headings_font ) {
		$font_pieces = explode(":", $headings_font);
		$custom .= "a.button, h1, h2, h3, h4, h5, h6, button, input[type=\"button\"], input[type=\"reset\"], input[type=\"submit\"], .site-title, .site-description, #main-navigation li a, .nav-open, .nav-close, .comments-link a, .author-info .author-links a, .site-content [class*=\"navigation\"] a, .comment-list li.comment .comment-author .fn, .comment-list li.comment .reply a, #commentform label, .widget_athemes_tabs .widget-tab-nav li a { font-family: {$font_pieces[0]}; }"."\n";
	}
	if ( $body_font ) {
		$font_pieces = explode(":", $body_font);
		$custom .= "body { font-family: {$font_pieces[0]}; }"."\n";
	}
	
	//Output all the styles
	wp_add_inline_style( 'athemes-style', $custom );	
}
add_action( 'wp_enqueue_scripts', 'athemes_custom_styles' );
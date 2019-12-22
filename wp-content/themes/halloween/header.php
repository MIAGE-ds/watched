<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
<meta name="viewport" content="width=device-width" />
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<div id="wrapper" class="hfeed">
<header id="header">
<div id="branding">
<div id="site-title">
<?php
if ( is_front_page() || is_home() || is_front_page() && is_home() ) { echo '<h1>'; }
echo '<a href="' . esc_url( home_url( '/' ) ) . '" title="' . esc_attr( get_bloginfo( 'name' ) ) . '" rel="home">';
if ( get_header_image() ) {
echo '<img src="' . esc_url( get_header_image() ) . '" alt="' . esc_attr( get_bloginfo( 'name' ) ) . '" />';
} else {
bloginfo( 'name' );
}
echo '</a>';
if ( is_front_page() || is_home() || is_front_page() && is_home() ) { echo '</h1>'; }
?>
</div>
<div id="site-description"><?php bloginfo( 'description' ); ?></div>
</div>
<nav id="menu">
<div id="search"><?php get_search_form(); ?></div>
<label class="toggle" for="toggle"><span class="menu-icon">&#9776;</span><span class="menu-title"><?php esc_html_e( 'Menu', 'halloween' ); ?></span></label>
<input id="toggle" class="toggle" type="checkbox" />
<?php wp_nav_menu( array( 'theme_location' => 'main-menu' ) ); ?>
</nav>
</header>
<div id="container">
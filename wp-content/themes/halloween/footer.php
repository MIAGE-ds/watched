</div>
<footer id="footer">
<div id="copyright">
&copy; <?php echo esc_html( date_i18n( __( 'Y', 'halloween' ) ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?>
<?php
$uri = home_url();
if ( ( is_front_page() || is_home() || is_front_page() && is_home() ) && ( strpos( $uri, 'halloween' ) !== false || strpos( $uri, 'horror' ) !== false ) ) {
echo ' | Theme by <a href="https://halloweenlove.com/">Halloween Blog</a>';
} else {
echo ' | Theme by <a href="https://wordpress.org/themes/halloween/">Halloween Blog</a>';
}
?>
</div>
</footer>
</div>
<?php wp_footer(); ?>
</body>
</html>
<?php
/**
 * @package aThemes
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'clearfix' ); ?>>
	<header class="entry-header">
		<?php if ( ! post_password_required() && ( comments_open() || '0' != get_comments_number() ) ) : ?>
		<span class="comments-link"> <?php comments_popup_link( __( '0', 'hiero' ), __( '1', 'hiero' ), __( '%', 'hiero' ) ); ?></span>
		<?php endif; ?>

		<h1 class="entry-title"><?php the_title(); ?></h1>

		<div class="entry-meta">
			<?php athemes_posted_on(); ?>
		<!-- .entry-meta --></div>
	<!-- .entry-header --></header>

	<?php if ( (has_post_thumbnail()) && ( get_theme_mod( 'athemes_post_img' )) ) : ?>
		<div class="single-thumbnail">
			<?php the_post_thumbnail(); ?>
		</div>	
	<?php endif; ?>		

	<div class="clearfix entry-content">
		<?php the_content(); ?>
		<?php
			wp_link_pages( array(
				'before' => '<div class="page-links">' . __( 'Pages:', 'hiero' ),
				'after'  => '</div>',
			) );
		?>
	<!-- .entry-content --></div>

	<footer class="entry-meta entry-footer">
		<?php
			/* translators: used between list items, there is a space after the comma */
			$category_list = get_the_category_list( __( ', ', 'hiero' ) );

			/* translators: used between list items, there is a space after the comma */
			$tag_list = get_the_tag_list( '', __( ', ', 'hiero' ) );

			if ( ! athemes_categorized_blog() ) {
				// This blog only has 1 category so we just need to worry about tags in the meta text
				if ( '' != $tag_list ) {
					$meta_text = '<span class="tags-links"><i class="ico-tags"></i> %2$s</span>';
				}

			} else {
				// But this blog has loads of categories so we should probably display them here
				if ( '' != $tag_list ) {
					$meta_text = '<span class="cat-links"><i class="ico-folder"></i> %1$s</span><span class="tags-links"><i class="ico-tags"></i> %2$s</span>';
				} else {
					$meta_text = '<span class="cat-links"><i class="ico-folder"></i> %1$s</span>';
				}

			} // end check for categories on this blog

			printf(
				$meta_text,
				$category_list,
				$tag_list
			);
		?>
	<!-- .entry-meta --></footer>
<!-- #post-<?php the_ID(); ?> --></article>

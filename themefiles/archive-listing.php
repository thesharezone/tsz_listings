<?php
/**
 * The template for displaying Archive pages
 *
 * Used to display archive-type pages if nothing more specific matches a query.
 * For example, puts together date-based pages if no date.php file exists.
 *
 * If you'd like to further customize these archive views, you may create a
 * new template file for each specific one. For example, Twenty Fourteen
 * already has tag.php for Tag archives, category.php for Category archives,
 * and author.php for Author archives.
 *
 * @link http://codex.wordpress.org/Template_Hierarchy
 *
 * @package WordPress
 * @subpackage Twenty_Fourteen
 * @since Twenty Fourteen 1.0
 */

get_header(); ?>

	<section id="primary" class="content-area">
		<div id="content" class="site-content" role="main">

			<?php if ( have_posts() ) : ?>



			<?php
					// Start the Loop.
					while ( have_posts() ) : the_post();

						/*
						 * Include the post format-specific template for the content. If you want to
						 * use this in a child theme, then include a file called called content-___.php
						 * (where ___ is the post format) and that will be used instead.
						 */
						?>

						<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

	<div class="entry-content">

		<div style="width:470px; height: 150px; margin-bottom: 6px;">
			<a href="<?php echo esc_url( get_permalink() ) ?>">
				<div style="width:150px; height: 150px; float: left; margin: 0;">
					<?php first_image_tag(get_the_ID(), 'thumbnail') ?>
				</div>
				<div style="width:315px; height: 150px; float: right; margin: 0; overflow: hidden;">
					<b><?php echo strip_tags(get_the_title()) ?></b>
				</div>
			</a>
		</div>
		<?php $post_id = get_the_ID(); $address = get_post_meta(get_the_ID(), "tsz_listing_address", true); include "map.php"; ?>
	</div><!-- .entry-content -->





	<?php the_tags( '<footer class="entry-meta"><span class="tag-links">', '', '</span></footer>' ); ?>
</article><!-- #post-## -->

					<?php
					endwhile;
					// Previous/next page navigation.
					twentyfourteen_paging_nav();

				else :
					// If no content, include the "No posts found" template.
					get_template_part( 'content', 'none' );

				endif;
			?>
		</div><!-- #content -->
	</section><!-- #primary -->

<?php
get_sidebar( 'content' );
get_sidebar();
get_footer();

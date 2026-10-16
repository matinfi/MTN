<?php get_header(); ?>
<section class="section container">
	<?php if ( have_posts() ) : ?>
		<?php while ( have_posts() ) : the_post(); ?>
			<article class="card">
				<h1><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h1>
				<div><?php the_excerpt(); ?></div>
			</article>
		<?php endwhile; the_posts_pagination(); ?>
	<?php else : ?>
		<h1><?php esc_html_e( 'هنوز محتوایی منتشر نشده است.', 'mtn-cafe-theme' ); ?></h1>
		<p class="muted"><?php esc_html_e( 'پس از ثبت و انتشار اطلاعات، محتوا در این بخش نمایش داده می‌شود.', 'mtn-cafe-theme' ); ?></p>
	<?php endif; ?>
</section>
<?php get_footer(); ?>

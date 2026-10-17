<?php get_header(); ?>
<section class="section container">
  <p class="eyebrow"><?php esc_html_e( 'اجراکنندگان', 'mtn-cafe-theme' ); ?></p><h1><?php esc_html_e( 'هنرمندان و خوانندگان', 'mtn-cafe-theme' ); ?></h1>
  <?php if ( have_posts() ) : ?><div class="cards"><?php while ( have_posts() ) : the_post(); ?><article class="card mtn-listing-card"><?php if ( has_post_thumbnail() ) : ?><a href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'large', array( 'class' => 'mtn-card-image' ) ); ?></a><?php endif; ?><h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2><p class="muted"><?php echo esc_html( get_the_excerpt() ); ?></p><a class="button" href="<?php the_permalink(); ?>"><?php esc_html_e( 'صفحه هنرمند', 'mtn-cafe-theme' ); ?></a></article><?php endwhile; ?></div><?php the_posts_pagination(); ?><?php else : ?><p><?php esc_html_e( 'هنرمندی برای نمایش ثبت نشده است.', 'mtn-cafe-theme' ); ?></p><?php endif; ?>
</section>
<?php get_footer(); ?>

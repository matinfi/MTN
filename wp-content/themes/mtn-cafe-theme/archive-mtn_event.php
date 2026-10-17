<?php get_header(); ?>
<section class="section container">
  <p class="eyebrow"><?php esc_html_e( 'موسیقی زنده و شب‌های ویژه', 'mtn-cafe-theme' ); ?></p>
  <h1><?php esc_html_e( 'برنامه‌های پیش‌رو', 'mtn-cafe-theme' ); ?></h1>
  <?php if ( have_posts() ) : ?><div class="cards">
    <?php while ( have_posts() ) : the_post(); $cafe_id = absint( get_post_meta( get_the_ID(), 'mtn_cafe_id', true ) ); $city = get_post_meta( $cafe_id, 'mtn_cafe_city', true ); ?>
      <article class="card mtn-listing-card">
        <?php if ( has_post_thumbnail() ) : ?><a href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'large', array( 'class' => 'mtn-card-image' ) ); ?></a><?php endif; ?>
        <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
        <div class="mtn-listing-meta">
          <?php if ( $cafe_id && get_post( $cafe_id ) ) : ?><span><?php echo esc_html( get_the_title( $cafe_id ) ); ?></span><?php endif; ?>
          <?php $event_date = get_post_meta( get_the_ID(), 'mtn_event_date', true ); if ( $event_date ) : ?><span><?php echo esc_html( $event_date ); ?></span><?php endif; ?>
          <?php if ( $city ) : ?><span><?php echo esc_html( $city ); ?></span><?php endif; ?><?php $artist = get_post_meta( get_the_ID(), 'mtn_event_artist', true ); if ( $artist ) : ?><span><?php echo esc_html( 'هنرمند: ' . $artist ); ?></span><?php endif; ?><?php $price = get_post_meta( get_the_ID(), 'mtn_event_price', true ); if ( '' !== $price ) : ?><span><?php echo esc_html( 'ورودی: ' . number_format_i18n( (float) $price, 2 ) . ' لیر' ); ?></span><?php endif; ?>
        </div>
        <p class="muted"><?php echo esc_html( get_the_excerpt() ); ?></p>
        <a class="button" href="<?php the_permalink(); ?>"><?php esc_html_e( 'جزئیات و رزرو', 'mtn-cafe-theme' ); ?></a>
      </article>
    <?php endwhile; ?>
  </div><?php the_posts_pagination(); ?><?php else : ?><div class="mtn-empty-state"><?php esc_html_e( 'برنامه‌ای برای نمایش وجود ندارد.', 'mtn-cafe-theme' ); ?></div><?php endif; ?>
</section>
<?php get_footer(); ?>
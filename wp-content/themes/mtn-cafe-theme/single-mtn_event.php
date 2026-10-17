<?php get_header(); ?>
<section class="section container mtn-content">
  <?php while ( have_posts() ) : the_post(); $event_id = get_the_ID(); $cafe_id = absint( get_post_meta( $event_id, 'mtn_cafe_id', true ) ); ?>
    <?php if ( has_post_thumbnail() ) : ?><div><?php the_post_thumbnail( 'large', array( 'class' => 'mtn-card-image' ) ); ?></div><?php endif; ?>
    <p class="eyebrow"><?php esc_html_e( 'برنامه شبانه', 'mtn-cafe-theme' ); ?></p>
    <h1><?php the_title(); ?></h1>
    <div class="mtn-listing-meta">
      <?php if ( $cafe_id && get_post( $cafe_id ) ) : ?><span><a href="<?php echo esc_url( get_permalink( $cafe_id ) ); ?>"><?php echo esc_html( get_the_title( $cafe_id ) ); ?></a></span><?php endif; ?>
      <?php foreach ( array( 'mtn_event_date' => 'تاریخ', 'mtn_event_start_time' => 'شروع', 'mtn_event_end_time' => 'پایان', 'mtn_event_artist' => 'هنرمند', 'mtn_event_price' => 'قیمت ورودی (لیر)' ) as $key => $label ) : $value = get_post_meta( $event_id, $key, true ); if ( $value ) : ?><span><?php echo esc_html( $label . ': ' . $value ); ?></span><?php endif; endforeach; ?>
    </div>
    <div class="entry-content"><?php the_content(); ?></div>
    <?php $event_artist = get_post_meta( $event_id, 'mtn_event_artist', true ); if ( $event_artist ) : $artist_posts = get_posts( array( 'post_type' => 'mtn_artist', 'post_status' => 'publish', 'title' => $event_artist, 'numberposts' => 1 ) ); ?><p><?php esc_html_e( 'هنرمند: ', 'mtn-cafe-theme' ); ?><?php if ( $artist_posts ) : ?><a href="<?php echo esc_url( get_permalink( $artist_posts[0] ) ); ?>"><?php echo esc_html( $event_artist ); ?></a><?php else : ?><?php echo esc_html( $event_artist ); ?><?php endif; ?></p><?php endif; ?>
    <?php $event_price = get_post_meta( $event_id, 'mtn_event_price', true ); if ( '' !== $event_price ) : ?><p><?php echo esc_html( 'قیمت ورودی: ' . number_format_i18n( (float) $event_price, 2 ) . ' لیر' ); ?></p><?php endif; ?>
    <?php $capacity = absint( get_post_meta( $event_id, 'mtn_event_capacity', true ) ); if ( $capacity ) : ?><p class="muted"><?php printf( esc_html__( 'ظرفیت کل برنامه: %d نفر', 'mtn-cafe-theme' ), $capacity ); ?></p><?php endif; ?>
    <?php echo do_shortcode( '[mtn_booking_form cafe_id="' . $cafe_id . '" event_id="' . $event_id . '"]' ); ?>
  <?php endwhile; ?>
</section>
<?php get_footer(); ?>
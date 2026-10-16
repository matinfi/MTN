<?php get_header(); ?>
<section class="section container mtn-content">
  <?php while ( have_posts() ) : the_post(); $cafe_id = get_the_ID(); ?>
    <?php if ( has_post_thumbnail() ) : ?><div><?php the_post_thumbnail( 'large', array( 'class' => 'mtn-card-image' ) ); ?></div><?php endif; ?>
    <p class="eyebrow"><?php esc_html_e( 'پروفایل کافه', 'mtn-cafe-theme' ); ?></p>
    <h1><?php the_title(); ?></h1>
    <div class="mtn-listing-meta">
      <?php foreach ( array( 'mtn_cafe_city' => 'شهر', 'mtn_cafe_music_genre' => 'سبک موسیقی' ) as $key => $label ) : $value = get_post_meta( $cafe_id, $key, true ); if ( $value ) : ?><span><?php echo esc_html( $label . ': ' . $value ); ?></span><?php endif; endforeach; ?>
    </div>
    <div class="entry-content"><?php the_content(); ?></div>
    <?php $address = get_post_meta( $cafe_id, 'mtn_cafe_address', true ); if ( $address ) : ?><p><strong><?php esc_html_e( 'نشانی:', 'mtn-cafe-theme' ); ?></strong> <?php echo esc_html( $address ); ?></p><?php endif; ?>
    <?php $phone = get_post_meta( $cafe_id, 'mtn_cafe_phone', true ); if ( $phone ) : ?><p><strong><?php esc_html_e( 'تماس:', 'mtn-cafe-theme' ); ?></strong> <?php echo esc_html( $phone ); ?></p><?php endif; ?>
    <h2><?php esc_html_e( 'برنامه‌های این کافه', 'mtn-cafe-theme' ); ?></h2>
    <?php $events = new WP_Query( array( 'post_type' => 'mtn_event', 'post_status' => 'publish', 'posts_per_page' => 30, 'orderby' => 'meta_value', 'meta_key' => 'mtn_event_date', 'order' => 'ASC', 'meta_query' => array( 'relation' => 'AND', array( 'key' => 'mtn_cafe_id', 'value' => $cafe_id, 'compare' => '=', 'type' => 'NUMERIC' ), array( 'key' => 'mtn_event_date', 'value' => current_time( 'Y-m-d' ), 'compare' => '>=', 'type' => 'DATE' ), array( 'key' => 'mtn_event_start_time', 'value' => '', 'compare' => '!=' ) ) ) ); ?>
    <?php if ( $events->have_posts() ) : ?><div class="cards"><?php while ( $events->have_posts() ) : $events->the_post(); ?>
      <article class="card mtn-listing-card"><h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
      <?php $event_date = get_post_meta( get_the_ID(), 'mtn_event_date', true ); if ( $event_date ) : ?><p class="muted"><?php echo esc_html( $event_date ); ?></p><?php endif; ?>
      <p class="muted"><?php echo esc_html( get_the_excerpt() ); ?></p><a class="button" href="<?php the_permalink(); ?>"><?php esc_html_e( 'جزئیات برنامه', 'mtn-cafe-theme' ); ?></a></article>
    <?php endwhile; wp_reset_postdata(); ?></div><?php else : ?><p class="muted"><?php esc_html_e( 'هنوز برنامه‌ای برای این کافه منتشر نشده است.', 'mtn-cafe-theme' ); ?></p><?php endif; ?>
    <p><a class="button" href="<?php echo esc_url( home_url( '/book/?cafe_id=' . $cafe_id ) ); ?>"><?php esc_html_e( 'درخواست رزرو میز', 'mtn-cafe-theme' ); ?></a></p>
  <?php endwhile; ?>
</section>
<?php get_footer(); ?>
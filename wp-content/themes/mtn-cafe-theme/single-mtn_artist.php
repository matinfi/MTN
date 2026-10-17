<?php get_header(); ?>
<section class="section container mtn-content">
  <?php while ( have_posts() ) : the_post(); ?>
    <?php if ( has_post_thumbnail() ) : ?><div><?php the_post_thumbnail( 'large', array( 'class' => 'mtn-card-image' ) ); ?></div><?php endif; ?>
    <p class="eyebrow"><?php esc_html_e( 'پروفایل هنرمند', 'mtn-cafe-theme' ); ?></p>
    <h1><?php the_title(); ?></h1>
    <div class="entry-content"><?php the_content(); ?></div>
    <?php $artist_name = get_the_title(); $artist_events = get_posts( array( 'post_type' => 'mtn_event', 'post_status' => 'publish', 'posts_per_page' => 12, 'meta_key' => 'mtn_event_date', 'orderby' => 'meta_value', 'order' => 'ASC', 'meta_query' => array( array( 'key' => 'mtn_event_artist', 'value' => $artist_name, 'compare' => '=' ), array( 'key' => 'mtn_event_date', 'value' => current_time( 'Y-m-d' ), 'compare' => '>=', 'type' => 'DATE' ) ) ) ); ?>
    <section class="section"><h2><?php esc_html_e( 'برنامه‌های پیش‌رو', 'mtn-cafe-theme' ); ?></h2>
      <?php if ( $artist_events ) : ?><div class="cards"><?php foreach ( $artist_events as $artist_event ) : ?><article class="card"><h3><a href="<?php echo esc_url( get_permalink( $artist_event ) ); ?>"><?php echo esc_html( get_the_title( $artist_event ) ); ?></a></h3><p><?php echo esc_html( get_post_meta( $artist_event->ID, 'mtn_event_date', true ) ); ?></p></article><?php endforeach; ?></div><?php else : ?><p class="muted"><?php esc_html_e( 'برنامه‌ای برای این هنرمند ثبت نشده است.', 'mtn-cafe-theme' ); ?></p><?php endif; ?>
    </section>
  <?php endwhile; ?>
</section>
<?php get_footer(); ?>

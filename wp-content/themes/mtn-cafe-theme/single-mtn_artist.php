<?php get_header(); ?>
<?php while ( have_posts() ) : the_post(); $artist_id = get_the_ID(); $kind = get_post_meta( $artist_id, 'mtn_artist_kind', true ); $genre = get_post_meta( $artist_id, 'mtn_artist_genre', true ); ?>
<article class="section container mtn-content mtn-artist-profile">
  <p class="eyebrow"><?php echo esc_html( 'dj' === $kind ? 'دی‌جی' : ( 'band' === $kind ? 'گروه موسیقی' : 'خواننده' ) ); ?></p>
  <h1><?php the_title(); ?></h1>
  <?php if ( $genre ) : ?><p class="mtn-listing-meta"><span><?php echo esc_html( $genre ); ?></span></p><?php endif; ?>
  <?php if ( has_post_thumbnail() ) : ?><div class="mtn-artist-cover"><?php the_post_thumbnail( 'large' ); ?></div><?php endif; ?>
  <div class="mtn-artist-bio"><?php the_content(); ?></div>
  <section class="section"><h2>برنامه‌های پیش‌رو</h2>
    <?php $events = new WP_Query( array( 'post_type' => 'mtn_event', 'post_status' => 'publish', 'posts_per_page' => 12, 'meta_query' => array( array( 'key' => 'mtn_event_date', 'value' => current_time( 'Y-m-d' ), 'compare' => '>=', 'type' => 'DATE' ) ), 's' => get_the_title() ) ); ?>
    <?php if ( $events->have_posts() ) : ?><div class="cards"><?php while ( $events->have_posts() ) : $events->the_post(); ?><article class="card"><h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3><p class="muted"><?php echo esc_html( get_post_meta( get_the_ID(), 'mtn_event_date', true ) ); ?></p><a class="button" href="<?php the_permalink(); ?>">مشاهده برنامه</a></article><?php endwhile; wp_reset_postdata(); ?></div><?php else : ?><p class="muted">برنامه‌ای با نام مرتبط ثبت نشده است.</p><?php endif; ?>
  </section>
  <a class="button" href="<?php echo esc_url( home_url( '/artists/' ) ); ?>">بازگشت به فهرست هنرمندان</a>
</article>
<?php endwhile; ?>
<?php get_footer(); ?>
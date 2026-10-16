<?php get_header(); ?>
<section class="section container">
  <p class="eyebrow"><?php esc_html_e( 'کشف مکان‌ها', 'mtn-cafe-theme' ); ?></p>
  <h1><?php esc_html_e( 'کافه‌های دارای موسیقی و برنامه شبانه', 'mtn-cafe-theme' ); ?></h1>
  <p class="muted"><?php esc_html_e( 'اطلاعات هر کافه و برنامه‌های منتشرشده را بررسی کن و سپس درخواست رزرو بفرست.', 'mtn-cafe-theme' ); ?></p>
  <?php if ( have_posts() ) : ?><div class="cards">
    <?php while ( have_posts() ) : the_post(); ?>
      <article class="card mtn-listing-card">
        <?php if ( has_post_thumbnail() ) : ?><a href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'large', array( 'class' => 'mtn-card-image' ) ); ?></a><?php endif; ?>
        <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
        <div class="mtn-listing-meta">
          <?php $city = get_post_meta( get_the_ID(), 'mtn_cafe_city', true ); if ( $city ) : ?><span><?php echo esc_html( $city ); ?></span><?php endif; ?>
          <?php $genre = get_post_meta( get_the_ID(), 'mtn_cafe_music_genre', true ); if ( $genre ) : ?><span><?php echo esc_html( $genre ); ?></span><?php endif; ?>
        </div>
        <p class="muted"><?php echo esc_html( get_the_excerpt() ); ?></p>
        <a class="button" href="<?php the_permalink(); ?>"><?php esc_html_e( 'مشاهده کافه و برنامه‌ها', 'mtn-cafe-theme' ); ?></a>
      </article>
    <?php endwhile; ?>
  </div><?php the_posts_pagination(); ?><?php else : ?><div class="mtn-empty-state"><?php esc_html_e( 'هنوز کافه‌ای منتشر نشده است.', 'mtn-cafe-theme' ); ?></div><?php endif; ?>
</section>
<?php get_footer(); ?>
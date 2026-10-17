<?php get_header(); ?>
<section class="section container">
  <div class="section-heading"><div><p class="eyebrow">چهره‌های موسیقی</p><h1>خواننده‌ها و دی‌جی‌ها</h1><p class="muted">پروفایل هنرمندان و برنامه‌های منتشرشده آن‌ها را کشف کن.</p></div></div>
  <?php if ( have_posts() ) : ?><div class="cards">
    <?php while ( have_posts() ) : the_post(); $artist_id = get_the_ID(); $kind = get_post_meta( $artist_id, 'mtn_artist_kind', true ); $genre = get_post_meta( $artist_id, 'mtn_artist_genre', true ); ?>
      <article class="card mtn-listing-card">
        <?php if ( has_post_thumbnail() ) : ?><a href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'large', array( 'class' => 'mtn-card-image' ) ); ?></a><?php endif; ?>
        <span class="mtn-result-type"><?php echo esc_html( 'dj' === $kind ? 'دی‌جی' : ( 'band' === $kind ? 'گروه موسیقی' : 'خواننده' ) ); ?></span>
        <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
        <?php if ( $genre ) : ?><div class="mtn-listing-meta"><span><?php echo esc_html( $genre ); ?></span></div><?php endif; ?>
        <p class="muted"><?php echo esc_html( get_the_excerpt() ); ?></p><a class="button" href="<?php the_permalink(); ?>">مشاهده پروفایل</a>
      </article>
    <?php endwhile; ?>
  </div><?php the_posts_pagination(); ?><?php else : ?><div class="mtn-empty-state">هنوز پروفایل هنرمندی منتشر نشده است.</div><?php endif; ?>
</section>
<?php get_footer(); ?>

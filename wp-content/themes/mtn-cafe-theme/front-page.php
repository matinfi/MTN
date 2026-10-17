<?php get_header(); ?>
<section class="hero">
  <div class="container">
    <p class="eyebrow"><?php esc_html_e( 'شب تو، انتخاب تو', 'mtn-cafe-theme' ); ?></p>
    <h1><?php esc_html_e( 'برای موسیقی زنده، کافه بعدی‌ات را پیدا کن.', 'mtn-cafe-theme' ); ?></h1>
    <p><?php esc_html_e( 'برنامه‌های شبانه را کشف کن، ظرفیت را بررسی کن و درخواست رزروت را برای تأیید کافه بفرست.', 'mtn-cafe-theme' ); ?></p>
    <a class="button" href="<?php echo esc_url( home_url( '/cafes/' ) ); ?>"><?php esc_html_e( 'کشف کافه‌ها', 'mtn-cafe-theme' ); ?></a>
    <a class="button" href="<?php echo esc_url( home_url( '/book/' ) ); ?>"><?php esc_html_e( 'رزرو میز', 'mtn-cafe-theme' ); ?></a>
  </div>
</section>
<section class="mtn-home-search" data-mtn-home-search aria-labelledby="mtn-home-search-title">
  <div class="mtn-home-search-head"><div><p class="eyebrow">جست‌وجوی هوشمند</p><h2 id="mtn-home-search-title">کافه یا برنامه بعدی‌ات را پیدا کن</h2></div><span class="mtn-nav-icon" aria-hidden="true">⌕</span></div>
  <form class="mtn-home-search-form" role="search">
    <input class="mtn-search-query" type="search" name="q" autocomplete="off" placeholder="نام کافه، شهر، هنرمند یا برنامه…" aria-label="جست‌وجو در کافه‌ها و برنامه‌ها">
    <select name="kind" aria-label="نوع نتیجه"><option value="all">همه موارد</option><option value="cafe">فقط کافه‌ها</option><option value="event">فقط برنامه‌ها</option><option value="artist">فقط هنرمندان</option></select>
    <button class="button" type="submit">جست‌وجو</button>
  </form>
  <p class="mtn-search-status" data-search-status role="status" aria-live="polite">در حال آماده‌سازی جست‌وجو…</p>
  <div class="mtn-home-search-results" data-search-results></div>
</section>
<section class="section container">
  <div class="section-heading"><div><p class="eyebrow"><?php esc_html_e( 'برنامه‌های تازه', 'mtn-cafe-theme' ); ?></p><h2><?php esc_html_e( 'این شب‌ها چه خبره؟', 'mtn-cafe-theme' ); ?></h2></div><a href="<?php echo esc_url( home_url( '/events/' ) ); ?>"><?php esc_html_e( 'همه برنامه‌ها', 'mtn-cafe-theme' ); ?> ←</a></div>
  <?php $mtn_events = new WP_Query( array( 'post_type' => 'mtn_event', 'post_status' => 'publish', 'posts_per_page' => 3, 'meta_key' => 'mtn_event_date', 'orderby' => 'meta_value', 'order' => 'ASC', 'meta_query' => array( 'relation' => 'AND', array( 'key' => 'mtn_event_date', 'value' => current_time( 'Y-m-d' ), 'compare' => '>=', 'type' => 'DATE' ), array( 'key' => 'mtn_event_start_time', 'value' => '', 'compare' => '!=' ) ) ) ); ?>
  <?php if ( $mtn_events->have_posts() ) : ?><div class="cards">
    <?php while ( $mtn_events->have_posts() ) : $mtn_events->the_post(); $mtn_event_id = get_the_ID(); $mtn_cafe_id = absint( get_post_meta( $mtn_event_id, 'mtn_cafe_id', true ) ); ?>
      <article class="card mtn-listing-card">
        <?php if ( has_post_thumbnail() ) : ?><a href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'large', array( 'class' => 'mtn-card-image' ) ); ?></a><?php endif; ?>
        <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
        <div class="mtn-listing-meta"><?php if ( $mtn_cafe_id && get_post( $mtn_cafe_id ) ) : ?><span><?php echo esc_html( get_the_title( $mtn_cafe_id ) ); ?></span><?php endif; ?><?php $mtn_date = get_post_meta( $mtn_event_id, 'mtn_event_date', true ); if ( $mtn_date ) : ?><span><?php echo esc_html( $mtn_date ); ?></span><?php endif; ?></div>
        <p class="muted"><?php echo esc_html( get_the_excerpt() ); ?></p><a class="button" href="<?php the_permalink(); ?>"><?php esc_html_e( 'جزئیات و رزرو', 'mtn-cafe-theme' ); ?></a>
      </article>
    <?php endwhile; wp_reset_postdata(); ?>
  </div><?php else : ?><div class="mtn-empty-state"><?php esc_html_e( 'هنوز برنامه‌ای منتشر نشده است؛ به‌زودی این بخش به‌روزرسانی می‌شود.', 'mtn-cafe-theme' ); ?></div><?php endif; ?>
</section>
<section class="section container">
  <div class="section-heading"><div><p class="eyebrow"><?php esc_html_e( 'مکان‌های منتخب', 'mtn-cafe-theme' ); ?></p><h2><?php esc_html_e( 'کافه‌ها را کشف کن', 'mtn-cafe-theme' ); ?></h2></div><a href="<?php echo esc_url( home_url( '/cafes/' ) ); ?>"><?php esc_html_e( 'همه کافه‌ها', 'mtn-cafe-theme' ); ?> ←</a></div>
  <?php $mtn_cafes = new WP_Query( array( 'post_type' => 'mtn_cafe', 'post_status' => 'publish', 'posts_per_page' => 3, 'orderby' => 'title', 'order' => 'ASC' ) ); ?>
  <?php if ( $mtn_cafes->have_posts() ) : ?><div class="cards">
    <?php while ( $mtn_cafes->have_posts() ) : $mtn_cafes->the_post(); ?>
      <article class="card mtn-listing-card">
        <?php if ( has_post_thumbnail() ) : ?><a href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'large', array( 'class' => 'mtn-card-image' ) ); ?></a><?php endif; ?>
        <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
        <div class="mtn-listing-meta"><?php $mtn_city = get_post_meta( get_the_ID(), 'mtn_cafe_city', true ); if ( $mtn_city ) : ?><span><?php echo esc_html( $mtn_city ); ?></span><?php endif; ?><?php $mtn_genre = get_post_meta( get_the_ID(), 'mtn_cafe_music_genre', true ); if ( $mtn_genre ) : ?><span><?php echo esc_html( $mtn_genre ); ?></span><?php endif; ?></div>
        <p class="muted"><?php echo esc_html( get_the_excerpt() ); ?></p><a class="button" href="<?php the_permalink(); ?>"><?php esc_html_e( 'مشاهده کافه', 'mtn-cafe-theme' ); ?></a>
      </article>
    <?php endwhile; wp_reset_postdata(); ?>
  </div><?php else : ?><div class="mtn-empty-state"><?php esc_html_e( 'فهرست کافه‌ها هنوز خالی است.', 'mtn-cafe-theme' ); ?></div><?php endif; ?>
</section>
<section class="section container">
  <div class="cards">
    <article class="card"><h3><?php esc_html_e( 'موزیک زنده', 'mtn-cafe-theme' ); ?></h3><p class="muted"><?php esc_html_e( 'اجراهای زنده و شب‌های موسیقی را پیدا کن.', 'mtn-cafe-theme' ); ?></p></article>
    <article class="card"><h3><?php esc_html_e( 'ظرفیت شفاف', 'mtn-cafe-theme' ); ?></h3><p class="muted"><?php esc_html_e( 'پیش از ثبت، ظرفیت و میزهای قابل رزرو بررسی می‌شوند.', 'mtn-cafe-theme' ); ?></p></article>
    <article class="card"><h3><?php esc_html_e( 'تأیید کافه', 'mtn-cafe-theme' ); ?></h3><p class="muted"><?php esc_html_e( 'رزرو تا زمان پاسخ کافه در انتظار تأیید می‌ماند.', 'mtn-cafe-theme' ); ?></p></article>
  </div>
</section>
<section class="section container"><div class="notice"><strong><?php esc_html_e( 'شفافیت رزرو', 'mtn-cafe-theme' ); ?></strong><p><?php esc_html_e( 'ثبت درخواست به معنی تأیید نهایی نیست و هیچ پرداخت اینترنتی در سایت انجام نمی‌شود. اتصال واقعی پیامک و واتساپ باید جداگانه پیکربندی شود.', 'mtn-cafe-theme' ); ?></p></div></section>
<?php get_footer(); ?>
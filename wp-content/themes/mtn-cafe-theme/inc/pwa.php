<?php
/**
 * Installable web-app support: manifest, service worker, offline page.
 *
 * The service worker only caches static theme/plugin assets and one offline page.
 * It never caches HTML pages, REST responses, wp-admin or any form submission, so
 * account and booking data are not stored on the device by the worker.
 *
 * @package MTN_Cafe_Theme
 */

defined( 'ABSPATH' ) || exit;

function mtn_cafe_pwa_enabled() {
	return (bool) mtn_cafe_theme_option_value( 'mtn_cafe_theme_enable_pwa' );
}

function mtn_cafe_pwa_url( $kind ) {
	return add_query_arg( 'mtn_pwa', $kind, home_url( '/' ) );
}

/**
 * Brand colour used for the browser chrome (matches the page background).
 */
function mtn_cafe_pwa_theme_color() {
	$color = sanitize_hex_color( mtn_cafe_theme_option_value( 'mtn_cafe_theme_bg' ) );
	return $color ? $color : '#101014';
}

/**
 * Head tags that make the site installable.
 */
function mtn_cafe_pwa_head() {
	printf( '<meta name="theme-color" content="%s">' . "\n", esc_attr( mtn_cafe_pwa_theme_color() ) );
	if ( ! mtn_cafe_pwa_enabled() ) {
		return;
	}
	printf( '<link rel="manifest" href="%s">' . "\n", esc_url( mtn_cafe_pwa_url( 'manifest' ) ) );
	echo '<meta name="mobile-web-app-capable" content="yes">' . "\n";
	echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
	echo '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">' . "\n";
	printf( '<meta name="apple-mobile-web-app-title" content="%s">' . "\n", esc_attr( mtn_cafe_theme_brand_name() ) );
	$icon = get_site_icon_url( 180 );
	printf( '<link rel="apple-touch-icon" href="%s">' . "\n", esc_url( $icon ? $icon : mtn_cafe_pwa_url( 'icon' ) ) );
}
add_action( 'wp_head', 'mtn_cafe_pwa_head', 3 );

/**
 * Serve the manifest, service worker, icon and offline page.
 */
function mtn_cafe_pwa_serve() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! isset( $_GET['mtn_pwa'] ) || is_admin() ) {
		return;
	}
	$kind = sanitize_key( wp_unslash( $_GET['mtn_pwa'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! mtn_cafe_pwa_enabled() && 'sw' !== $kind ) {
		status_header( 404 );
		exit;
	}
	switch ( $kind ) {
		case 'manifest':
			mtn_cafe_pwa_manifest();
			break;
		case 'sw':
			mtn_cafe_pwa_service_worker();
			break;
		case 'icon':
			mtn_cafe_pwa_icon();
			break;
		case 'offline':
			mtn_cafe_pwa_offline_page();
			break;
		default:
			return;
	}
	exit;
}
add_action( 'template_redirect', 'mtn_cafe_pwa_serve', 0 );

function mtn_cafe_pwa_manifest() {
	$brand  = mtn_cafe_theme_brand_name();
	$icons  = array();
	$site192 = get_site_icon_url( 192 );
	$site512 = get_site_icon_url( 512 );
	if ( $site192 && $site512 ) {
		$icons[] = array( 'src' => $site192, 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any' );
		$icons[] = array( 'src' => $site512, 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any' );
	} else {
		$icons[] = array( 'src' => mtn_cafe_pwa_url( 'icon' ), 'sizes' => 'any', 'type' => 'image/svg+xml', 'purpose' => 'any' );
	}
	$manifest = array(
		'name'             => $brand,
		'short_name'       => function_exists( 'mb_substr' ) ? mb_substr( $brand, 0, 12 ) : substr( $brand, 0, 12 ),
		'description'      => (string) mtn_cafe_theme_option_value( 'mtn_cafe_theme_meta_description' ),
		'id'               => home_url( '/' ),
		'start_url'        => home_url( '/' ),
		'scope'            => home_url( '/' ),
		'display'          => 'standalone',
		'orientation'      => 'portrait-primary',
		'dir'              => 'rtl',
		'lang'             => 'fa',
		'background_color' => mtn_cafe_pwa_theme_color(),
		'theme_color'      => mtn_cafe_pwa_theme_color(),
		'icons'            => $icons,
		'shortcuts'        => array(
			array( 'name' => 'کشف کافه‌ها', 'url' => home_url( '/cafes/' ) ),
			array( 'name' => 'برنامه‌های زنده', 'url' => home_url( '/events/' ) ),
			array( 'name' => 'رزروهای من', 'url' => home_url( '/my-account/' ) ),
		),
	);
	nocache_headers();
	header( 'Content-Type: application/manifest+json; charset=utf-8' );
	echo wp_json_encode( $manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON document.
}

function mtn_cafe_pwa_icon() {
	$accent = sanitize_hex_color( mtn_cafe_theme_option_value( 'mtn_cafe_theme_accent' ) );
	$bg     = mtn_cafe_pwa_theme_color();
	$mark   = (string) mtn_cafe_theme_option_value( 'mtn_cafe_theme_brand_mark' );
	$accent = $accent ? $accent : '#e7a94b';
	header( 'Content-Type: image/svg+xml; charset=utf-8' );
	header( 'Cache-Control: public, max-age=86400' );
	printf(
		'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><rect width="512" height="512" rx="112" fill="%1$s"/><circle cx="256" cy="256" r="168" fill="%2$s"/><text x="256" y="300" text-anchor="middle" font-family="Tahoma,Arial,sans-serif" font-size="200" font-weight="800" fill="%1$s">%3$s</text></svg>',
		esc_attr( $bg ),
		esc_attr( $accent ),
		esc_html( $mark )
	);
}

function mtn_cafe_pwa_offline_page() {
	$brand = mtn_cafe_theme_brand_name();
	$bg    = mtn_cafe_pwa_theme_color();
	$text  = sanitize_hex_color( mtn_cafe_theme_option_value( 'mtn_cafe_theme_text' ) );
	$acc   = sanitize_hex_color( mtn_cafe_theme_option_value( 'mtn_cafe_theme_accent' ) );
	status_header( 200 );
	nocache_headers();
	header( 'Content-Type: text/html; charset=utf-8' );
	?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?php echo esc_html( 'آفلاین — ' . $brand ); ?></title>
<style>
body{margin:0;min-height:100vh;display:grid;place-items:center;background:<?php echo esc_attr( $bg ); ?>;color:<?php echo esc_attr( $text ? $text : '#f7f4ef' ); ?>;font-family:Tahoma,Arial,sans-serif;text-align:center;padding:1.5rem}
main{max-width:26rem}h1{font-size:1.5rem;margin:.5rem 0}p{line-height:1.9;opacity:.8}
button{margin-top:1rem;min-height:48px;padding:0 1.6rem;border:0;border-radius:999px;background:<?php echo esc_attr( $acc ? $acc : '#e7a94b' ); ?>;color:#17120b;font:inherit;font-weight:800;cursor:pointer}
</style>
</head>
<body>
<main>
<p aria-hidden="true" style="font-size:3rem;margin:0">◌</p>
<h1>اتصال اینترنت برقرار نیست</h1>
<p>برای دیدن کافه‌ها، برنامه‌ها و رزروها به اینترنت نیاز است. اتصال را بررسی کنید و دوباره تلاش کنید.</p>
<button type="button" onclick="location.reload()">تلاش دوباره</button>
</main>
</body>
</html>
	<?php
}

function mtn_cafe_pwa_service_worker() {
	$version = defined( 'MTN_CB_VERSION' ) ? MTN_CB_VERSION : '0';
	$theme   = wp_get_theme( get_template() )->get( 'Version' );
	$cache   = 'mtn-static-' . sanitize_key( $theme . '-' . $version );
	$offline = mtn_cafe_pwa_url( 'offline' );
	header( 'Content-Type: application/javascript; charset=utf-8' );
	header( 'Cache-Control: no-cache, max-age=0' );
	header( 'Service-Worker-Allowed: ' . wp_parse_url( home_url( '/' ), PHP_URL_PATH ) );
	if ( ! mtn_cafe_pwa_enabled() ) {
		// Switched off: remove any worker installed earlier.
		echo "self.addEventListener('install',function(){self.skipWaiting();});self.addEventListener('activate',function(e){e.waitUntil(caches.keys().then(function(k){return Promise.all(k.filter(function(n){return n.indexOf('mtn-')===0;}).map(function(n){return caches.delete(n);}));}).then(function(){return self.registration.unregister();}));});";
		return;
	}
	$static_paths = array( '/wp-content/themes/', '/wp-content/plugins/mtn-cafe-booking/assets/' );
	?>
const CACHE=<?php echo wp_json_encode( $cache ); ?>;
const OFFLINE=<?php echo wp_json_encode( $offline ); ?>;
const STATIC_PATHS=<?php echo wp_json_encode( $static_paths ); ?>;
self.addEventListener('install',function(event){
 event.waitUntil(caches.open(CACHE).then(function(cache){return cache.add(new Request(OFFLINE,{cache:'reload'}));}).then(function(){return self.skipWaiting();}));
});
self.addEventListener('activate',function(event){
 event.waitUntil(caches.keys().then(function(keys){
  return Promise.all(keys.filter(function(key){return key.indexOf('mtn-static-')===0&&key!==CACHE;}).map(function(key){return caches.delete(key);}));
 }).then(function(){return self.clients.claim();}));
});
function isStatic(url){
 if(url.origin!==self.location.origin)return false;
 return STATIC_PATHS.some(function(path){return url.pathname.indexOf(path)!==-1;})&&/\.(?:css|js|woff2|svg|png|jpg|webp)$/.test(url.pathname);
}
self.addEventListener('fetch',function(event){
 const request=event.request;
 if(request.method!=='GET')return;
 const url=new URL(request.url);
 if(request.mode==='navigate'){
  if(url.pathname.indexOf('/wp-admin')!==-1||url.pathname.indexOf('wp-login.php')!==-1)return;
  event.respondWith(fetch(request).catch(function(){return caches.match(OFFLINE);}));
  return;
 }
 if(isStatic(url)){
  event.respondWith(caches.open(CACHE).then(function(cache){
   return cache.match(request).then(function(cached){
    const network=fetch(request).then(function(response){if(response&&response.ok)cache.put(request,response.clone());return response;}).catch(function(){return cached;});
    return cached||network;
   });
  }));
 }
});
	<?php
}

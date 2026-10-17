(function () {
  'use strict';
  if (!window.MTNCBDiscovery) return;
  document.querySelectorAll('[data-mtn-event-search]').forEach(async (root) => {
    const form = root.querySelector('form');
    const results = root.querySelector('.mtn-event-results');
    const loadMore = root.querySelector('.mtn-event-load-more');
    const status = root.querySelector('[role="status"]');
    let page = 1;
    let busy = false;
    let filters = {};
    const favoriteIds = new Set();
    function node(tag, cls, value) {
      const el = document.createElement(tag);
      if (cls) el.className = cls;
      if (value !== undefined) el.textContent = String(value == null ? '' : value);
      return el;
    }
    function buildCard(item) {
      const card = node('article', 'card mtn-listing-card');
      if (item.image) {
        const link = node('a'); link.href = item.url;
        const img = node('img', 'mtn-card-image'); img.src = item.image; img.alt = item.name; img.loading = 'lazy';
        link.append(img); card.append(link);
      }
      const titleLink = node('a'); titleLink.href = item.url; titleLink.textContent = item.name;
      card.append(node('h2', '', '').appendChild(titleLink).parentNode);
      card.append(node('p', 'mtn-listing-meta', [item.cafe_name, item.city, item.location_path && Array.isArray(item.location_path) ? item.location_path.join(' / ') : '', item.address, item.event_date, item.event_start_time].filter(Boolean).join(' · ')));
      if (item.artist) card.append(node('p', 'muted', 'هنرمند: ' + item.artist));
      if (item.genre) card.append(node('p', 'muted', 'سبک موسیقی: ' + item.genre));
      card.append(node('p', 'muted', item.price > 0 ? 'قیمت ورودی: ' + item.price + ' لیر' : 'قیمت ورودی: رایگان / اعلام‌نشده'));
      if (item.distance_km !== null && item.distance_km !== undefined) card.append(node('p', 'muted', 'فاصله تقریبی: ' + item.distance_km + ' کیلومتر'));
      if (item.latitude !== null && item.longitude !== null) {
        const mapDetails = node('details', 'mtn-map-preview');
        mapDetails.append(node('summary', '', 'نمایش نقشه و موقعیت'));
        const mapUrl = new URL('https://www.openstreetmap.org/export/embed.html');
        mapUrl.searchParams.set('bbox', [item.longitude - 0.012, item.latitude - 0.008, item.longitude + 0.012, item.latitude + 0.008].join(','));
        mapUrl.searchParams.set('layer', 'mapnik');
        mapUrl.searchParams.set('marker', item.latitude + ',' + item.longitude);
        const mapFrame = node('iframe', 'mtn-map-frame');
        mapFrame.src = mapUrl.toString(); mapFrame.loading = 'lazy'; mapFrame.title = 'نقشه موقعیت ' + item.cafe_name;
        mapFrame.referrerPolicy = 'no-referrer'; mapFrame.setAttribute('allowfullscreen', '');
        mapDetails.append(mapFrame); card.append(mapDetails);
        const map = node('a', 'button', 'بازکردن نقشه بزرگ');
        map.href = 'https://www.openstreetmap.org/?mlat=' + encodeURIComponent(item.latitude) + '&mlon=' + encodeURIComponent(item.longitude) + '#map=16/' + encodeURIComponent(item.latitude) + '/' + encodeURIComponent(item.longitude);
        map.target = '_blank'; map.rel = 'noopener noreferrer'; card.append(map);
      }
      if (window.MTNCBDiscovery.loggedIn) {
        const favorite = node('button', 'button mtn-favorite-toggle', favoriteIds.has(Number(item.id)) ? 'حذف از علاقه‌مندی‌ها' : 'ذخیره برنامه');
        favorite.type = 'button';
        favorite.addEventListener('click', async () => {
          favorite.disabled = true;
          const saved = favoriteIds.has(Number(item.id));
          try {
            const response = await fetch(window.MTNCBDiscovery.restUrl + 'my/favorites/' + encodeURIComponent(item.id), {
              method: saved ? 'DELETE' : 'POST',
              headers: { 'X-WP-Nonce': window.MTNCBDiscovery.nonce, 'Content-Type': 'application/json' },
              credentials: 'same-origin'
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'تغییر علاقه‌مندی انجام نشد.');
            if (result.saved) favoriteIds.add(Number(item.id)); else favoriteIds.delete(Number(item.id));
            favorite.textContent = result.saved ? 'حذف از علاقه‌مندی‌ها' : 'ذخیره برنامه';
          } catch (error) {
            status.textContent = error.message || 'تغییر علاقه‌مندی انجام نشد.';
          } finally { favorite.disabled = false; }
        });
        card.append(favorite);
      } else {
        const save = node('a', 'button', 'ورود برای ذخیره برنامه');
        save.href = window.MTNCBDiscovery.loginUrl;
        card.append(save);
      }
      const booking = node('a', 'button', 'جزئیات و رزرو'); booking.href = item.url; card.append(booking);
      return card;
    }
    async function load(reset) {
      if (busy) return;
      busy = true;
      if (reset) { page = 1; results.replaceChildren(); }
      status.textContent = 'در حال جست‌وجو…';
      loadMore.disabled = true;
      try {
        const params = new URLSearchParams(filters);
        params.set('page', String(page)); params.set('per_page', '12');
        const response = await fetch(window.MTNCBDiscovery.restUrl + 'events?' + params.toString(), { headers: { 'X-WP-Nonce': window.MTNCBDiscovery.nonce } });
        const payload = await response.json();
        if (!response.ok) throw new Error(payload.message || 'جست‌وجو انجام نشد.');
        (payload.items || []).forEach((item) => results.append(buildCard(item)));
        status.textContent = payload.total ? 'تعداد نتایج: ' + payload.total : 'برنامه‌ای مطابق فیلترها پیدا نشد.';
        loadMore.hidden = !payload.has_more;
        page += 1;
      } catch (error) {
        status.textContent = error.message || 'ارتباط با سرور برقرار نشد.';
      } finally {
        busy = false; loadMore.disabled = false;
      }
    }
    form.addEventListener('submit', (event) => {
      event.preventDefault();
      filters = {};
      new FormData(form).forEach((value, key) => { if (String(value).trim()) filters[key] = String(value).trim(); });
      load(true);
    });
    loadMore.addEventListener('click', () => load(false));
    const nearby = root.querySelector('.mtn-nearby');
    if (nearby) nearby.addEventListener('click', () => {
      if (!navigator.geolocation) { status.textContent = 'مرورگر شما مکان‌یابی را پشتیبانی نمی‌کند.'; return; }
      nearby.disabled = true;
      status.textContent = 'برای یافتن برنامه‌های نزدیک، موقعیت دستگاه را تأیید کنید…';
      navigator.geolocation.getCurrentPosition((position) => {
        filters = {};
        new FormData(form).forEach((value, key) => { if (String(value).trim()) filters[key] = String(value).trim(); });
        filters.lat = String(position.coords.latitude);
        filters.lng = String(position.coords.longitude);
        filters.sort = 'distance';
        const sort = form.elements.sort;
        if (sort) sort.value = 'distance';
        nearby.disabled = false;
        load(true);
      }, () => {
        status.textContent = 'مکان‌یابی انجام نشد. اجازه دسترسی به موقعیت را بررسی کنید یا از فیلتر شهر استفاده کنید.';
        nearby.disabled = false;
      }, { enableHighAccuracy: false, timeout: 10000, maximumAge: 300000 });
    });
    if (window.MTNCBDiscovery.loggedIn) {
      try {
        const response = await fetch(window.MTNCBDiscovery.restUrl + 'my/favorites', { headers: { 'X-WP-Nonce': window.MTNCBDiscovery.nonce }, credentials: 'same-origin' });
        if (response.ok) (await response.json()).forEach((id) => favoriteIds.add(Number(id)));
      } catch (ignore) { /* discovery remains usable if favorites cannot be loaded */ }
    }
    load(true);
  });
})();

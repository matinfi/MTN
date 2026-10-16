(function () {
  'use strict';
  if (!window.MTNCB) return;
  const api = window.MTNCB.restUrl;
  const headers = { 'Content-Type': 'application/json', 'X-WP-Nonce': window.MTNCB.nonce };
  const text = (value) => String(value == null ? '' : value);
  const el = (tag, className, content) => {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (content !== undefined) node.textContent = content;
    return node;
  };
  async function request(url, options) {
    const response = await fetch(url, options || {});
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(payload.message || window.MTNCB.genericError);
    return payload;
  }
  function setStatus(root, message, kind) {
    const status = root.querySelector('.mtn-booking-status');
    if (!status) return;
    status.textContent = message;
    status.dataset.state = kind || 'info';
  }
  function localDateValue(input) {
    return input.value ? input.value.replace('T', ' ') + (input.value.length === 16 ? ':00' : '') : '';
  }
  document.querySelectorAll('[data-mtn-booking]').forEach(async (root) => {
    const form = root.querySelector('form');
    const cafe = form.elements.cafe_id;
    const event = form.elements.event_id;
    const party = form.elements.party_size;
    const date = form.elements.booking_at;
    const table = form.elements.table_id;
    const tableField = root.querySelector('.mtn-table-field');
    const checkButton = root.querySelector('.mtn-check-availability');
    const submitButton = root.querySelector('.mtn-submit-booking');
    const loginNote = root.querySelector('.mtn-booking-login');
    let catalog = { cafes: [], events: [], booking_mode: 'customer_selects_table' };
    let lastAvailability = null;
    let idempotencyKey = '';
    function options(select, items, placeholder, valueKey, labelKey) {
      select.replaceChildren(new Option(placeholder, ''));
      items.forEach((item) => select.add(new Option(text(item[labelKey]), text(item[valueKey]))));
    }
    function applyEventSchedule() {
      const selected = catalog.events.find((item) => String(item.id) === String(event.value));
      if (selected && selected.event_date && selected.event_start_time) {
        date.value = selected.event_date + 'T' + selected.event_start_time.slice(0, 5);
      }
    }
    function updateEvents() {
      const cafeId = Number(cafe.value || 0);
      const items = catalog.events.filter((item) => Number(item.cafe_id) === cafeId);
      options(event, items, 'انتخاب برنامه', 'id', 'name');
      event.disabled = !items.length;
      if (root.dataset.defaultEvent && items.some((item) => String(item.id) === root.dataset.defaultEvent)) {
        event.value = root.dataset.defaultEvent;
        root.dataset.defaultEvent = '';
      }
      applyEventSchedule();
      resetAvailability();
    }
    function resetAvailability() {
      lastAvailability = null;
      submitButton.disabled = true;
      table.replaceChildren(new Option('ابتدا ظرفیت را بررسی کنید', ''));
      tableField.hidden = catalog.booking_mode === 'cafe_assigns_table';
    }
    try {
      catalog = await request(api + 'catalog');
      options(cafe, catalog.cafes, 'انتخاب کافه', 'id', 'name');
      cafe.disabled = !catalog.cafes.length;
      if (root.dataset.defaultCafe && catalog.cafes.some((item) => String(item.id) === root.dataset.defaultCafe)) {
        cafe.value = root.dataset.defaultCafe;
      }
      updateEvents();
      if (root.dataset.defaultEvent && !event.value) updateEvents();
      setStatus(root, catalog.cafes.length ? 'کافه و برنامه را انتخاب کنید؛ سپس ظرفیت را بررسی کنید.' : 'هنوز کافه‌ای برای رزرو منتشر نشده است.', 'info');
    } catch (error) {
      setStatus(root, error.message || window.MTNCB.catalogError, 'error');
    }
    cafe.addEventListener('change', updateEvents);
    event.addEventListener('change', () => { applyEventSchedule(); resetAvailability(); });
    party.addEventListener('change', resetAvailability);
    date.addEventListener('change', resetAvailability);
    table.addEventListener('change', () => { if (lastAvailability) submitButton.disabled = !lastAvailability.available || (lastAvailability.table_selection_required && !table.value); });
    checkButton.addEventListener('click', async () => {
      if (!form.reportValidity()) return;
      const params = new URLSearchParams({
        cafe_id: cafe.value,
        event_id: event.value,
        party_size: party.value,
        booking_at: localDateValue(date)
      });
      checkButton.disabled = true;
      submitButton.disabled = true;
      setStatus(root, 'در حال بررسی ظرفیت…', 'info');
      try {
        lastAvailability = await request(api + 'availability?' + params.toString());
        options(table, lastAvailability.tables || [], 'انتخاب میز', 'id', 'name');
        tableField.hidden = !lastAvailability.table_selection_required;
        submitButton.disabled = !lastAvailability.available || (lastAvailability.table_selection_required && !(lastAvailability.tables || []).length);
        if (lastAvailability.available) {
          setStatus(root, lastAvailability.table_selection_required ? 'ظرفیت موجود است؛ میز را انتخاب و درخواست را ثبت کنید.' : 'ظرفیت موجود است؛ درخواست را ثبت کنید.', 'success');
        } else {
          setStatus(root, 'برای این زمان ظرفیت مناسبی پیدا نشد. زمان یا تعداد نفرات را تغییر دهید.', 'error');
        }
      } catch (error) {
        lastAvailability = null;
        setStatus(root, error.message, 'error');
      } finally {
        checkButton.disabled = false;
      }
    });
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      if (!form.reportValidity()) return;
      if (!window.MTNCB.loggedIn) {
        loginNote.hidden = false;
        loginNote.replaceChildren(document.createTextNode(window.MTNCB.loginRequired + ' '));
        const link = el('a', '', 'ورود به حساب');
        link.href = window.MTNCB.loginUrl;
        loginNote.append(link);
        return;
      }
      if (!lastAvailability || !lastAvailability.available || (lastAvailability.table_selection_required && !table.value)) {
        setStatus(root, 'ابتدا ظرفیت را بررسی کنید و یک میز در دسترس انتخاب کنید.', 'error');
        return;
      }
      submitButton.disabled = true;
      checkButton.disabled = true;
      if (!idempotencyKey) idempotencyKey = (window.crypto && crypto.randomUUID ? crypto.randomUUID() : (Date.now().toString(36) + Math.random().toString(36).slice(2))).replace(/[^A-Za-z0-9._:-]/g, '').slice(0, 64).padEnd(8, '0');
      const payload = {
        cafe_id: Number(cafe.value),
        event_id: Number(event.value),
        party_size: Number(party.value),
        booking_at: localDateValue(date),
        table_id: Number(table.value || 0),
        customer_note: form.elements.customer_note.value,
        idempotency_key: idempotencyKey
      };
      setStatus(root, 'در حال ثبت درخواست رزرو…', 'info');
      try {
        const booking = await request(api + 'bookings', { method: 'POST', headers, body: JSON.stringify(payload), credentials: 'same-origin' });
        idempotencyKey = '';
        lastAvailability = null;
        form.reset();
        updateEvents();
        resetAvailability();
        setStatus(root, 'درخواست رزرو ثبت شد. کد پیگیری: ' + booking.booking_code + ' — وضعیت: در انتظار تأیید کافه.', 'success');
      } catch (error) {
        setStatus(root, error.message, 'error');
        submitButton.disabled = false;
      } finally {
        checkButton.disabled = false;
      }
    });
  });
  document.querySelectorAll('[data-mtn-my-bookings]').forEach(async (root) => {
    const list = root.querySelector('.mtn-booking-list');
    try {
      const bookings = await request(api + 'my/bookings', { headers, credentials: 'same-origin' });
      if (!bookings.length) {
        setStatus(root, 'هنوز رزروی ثبت نکرده‌اید.', 'info');
        return;
      }
      const table = el('table', 'mtn-bookings-table');
      const head = el('thead');
      const headerRow = el('tr');
      ['کد پیگیری', 'زمان رزرو (UTC)', 'تعداد', 'وضعیت', 'عملیات'].forEach((label) => headerRow.append(el('th', '', label)));
      head.append(headerRow);
      const body = el('tbody');
      const statusLabels = {
        pending_cafe: 'در انتظار تأیید', confirmed: 'تأیید شده', rejected: 'رد شده',
        cancelled_by_customer: 'لغو توسط مشتری', cancelled_by_cafe: 'لغو توسط کافه',
        cancelled_by_admin: 'لغو توسط مدیر', expired: 'منقضی شده', completed: 'تکمیل شده', no_show: 'عدم مراجعه'
      };
      bookings.forEach((booking) => {
        const row = el('tr');
        [booking.booking_code, booking.booking_at, text(booking.party_size), statusLabels[booking.status] || booking.status].forEach((value) => row.append(el('td', '', text(value))));
        const actionCell = el('td');
        if (['pending_cafe', 'confirmed'].includes(booking.status)) {
          const cancel = el('button', 'button', 'لغو رزرو');
          cancel.type = 'button';
          cancel.addEventListener('click', async () => {
            if (!window.confirm('از لغو این رزرو مطمئن هستید؟')) return;
            cancel.disabled = true;
            try {
              const updated = await request(api + 'bookings/' + encodeURIComponent(booking.id) + '/cancel', { method: 'POST', headers, credentials: 'same-origin', body: '{}' });
              booking.status = updated.status;
              row.cells[3].textContent = statusLabels[updated.status] || updated.status;
              actionCell.replaceChildren(el('span', '', '—'));
              setStatus(root, 'رزرو با موفقیت لغو شد.', 'success');
            } catch (error) {
              cancel.disabled = false;
              setStatus(root, error.message, 'error');
            }
          });
          actionCell.append(cancel);
        } else {
          actionCell.textContent = '—';
        }
        row.append(actionCell);
        body.append(row);
      });
      table.append(head, body);
      list.replaceChildren(table);
      setStatus(root, 'رزروهای اخیر شما', 'success');
    } catch (error) {
      setStatus(root, error.message, 'error');
    }
  });
  document.querySelectorAll('[data-mtn-cafe-dashboard]').forEach(async (root) => {
    const list = root.querySelector('.mtn-cafe-booking-list');
    const labels = {
      pending_cafe: 'در انتظار تأیید', confirmed: 'تأیید شده', rejected: 'رد شده',
      cancelled_by_customer: 'لغو توسط مشتری', cancelled_by_cafe: 'لغو توسط کافه',
      cancelled_by_admin: 'لغو توسط مدیر', expired: 'منقضی شده', completed: 'تکمیل شده', no_show: 'عدم مراجعه'
    };
    async function load() {
      setStatus(root, 'در حال دریافت رزروها…', 'info');
      const bookings = await request(api + 'cafe/bookings', { headers, credentials: 'same-origin' });
      if (!bookings.length) {
        list.replaceChildren(el('div', 'mtn-empty-state', 'رزروی برای کافه‌های تحت مدیریت شما وجود ندارد.'));
        setStatus(root, 'فهرست به‌روز است.', 'success');
        return;
      }
      const table = el('table', 'mtn-bookings-table');
      const head = el('thead');
      const headRow = el('tr');
      ['کد', 'کافه', 'برنامه', 'زمان (UTC)', 'نفرات', 'وضعیت', 'عملیات'].forEach((label) => headRow.append(el('th', '', label)));
      head.append(headRow);
      const body = el('tbody');
      bookings.forEach((booking) => {
        const row = el('tr');
        [booking.booking_code, booking.cafe_name, booking.event_name, booking.booking_at, text(booking.party_size), labels[booking.status] || booking.status].forEach((value) => row.append(el('td', '', text(value))));
        const actions = el('td');
        const act = (label, endpoint, reasonNeeded) => {
          const button = el('button', 'button', label);
          button.type = 'button';
          button.addEventListener('click', async () => {
            let reason = '';
            if (reasonNeeded) reason = window.prompt('دلیل این تصمیم (اختیاری):', '') || '';
            if (label === 'لغو رزرو' && !window.confirm('از لغو این رزرو مطمئن هستید؟')) return;
            button.disabled = true;
            try {
              await request(api + endpoint, { method: 'POST', headers, credentials: 'same-origin', body: JSON.stringify({ reason }) });
              setStatus(root, 'وضعیت رزرو به‌روزرسانی شد.', 'success');
              await load();
            } catch (error) {
              button.disabled = false;
              setStatus(root, error.message, 'error');
            }
          });
          actions.append(button);
        };
        if (booking.status === 'pending_cafe') {
          act('تأیید', 'cafe/bookings/' + encodeURIComponent(booking.id) + '/confirm', false);
          act('رد', 'cafe/bookings/' + encodeURIComponent(booking.id) + '/reject', true);
        } else if (booking.status === 'confirmed') {
          act('لغو رزرو', 'cafe/bookings/' + encodeURIComponent(booking.id) + '/cancel', true);
        } else {
          actions.textContent = '—';
        }
        row.append(actions);
        body.append(row);
      });
      table.append(head, body);
      list.replaceChildren(table);
      setStatus(root, 'رزروهای قابل مشاهده: ' + bookings.length, 'success');
    }
    try {
      await load();
    } catch (error) {
      setStatus(root, error.message, 'error');
    }
  });
})();

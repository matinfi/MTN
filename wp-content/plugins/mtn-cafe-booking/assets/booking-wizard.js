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
  function buildBookingSteps(root) {
    const form = root.querySelector('.mtn-booking-form');
    if (!form || form.dataset.stepsReady === '1') return;
    form.dataset.stepsReady = '1';
    const grid = form.querySelector('.mtn-form-grid');
    const cafeField = grid && grid.querySelector('[name="cafe_id"]')?.closest('p');
    const eventField = grid && grid.querySelector('[name="event_id"]')?.closest('p');
    const partyField = grid && grid.querySelector('[name="party_size"]')?.closest('p');
    const dateField = grid && grid.querySelector('[name="booking_at"]')?.closest('p');
    const tableField = grid && grid.querySelector('.mtn-table-field');
    const noteField = form.querySelector('[name="customer_note"]')?.closest('p');
    if (!grid || !cafeField || !eventField || !partyField || !dateField || !noteField) return;
    const fields = [cafeField, eventField, partyField, dateField, tableField].filter(Boolean);
    const stepOne = el('section', 'mtn-booking-step');
    const stepTwo = el('section', 'mtn-booking-step');
    const stepThree = el('section', 'mtn-booking-step');
    stepOne.dataset.step = '1'; stepTwo.dataset.step = '2'; stepThree.dataset.step = '3';
    stepOne.append(el('h3', 'mtn-step-title', '۱. انتخاب کافه و برنامه'));
    stepTwo.append(el('h3', 'mtn-step-title', '۲. زمان و ظرفیت'));
    stepThree.append(el('h3', 'mtn-step-title', '۳. اطلاعات و تأیید'));
    stepOne.append(cafeField, eventField);
    stepTwo.append(partyField, dateField);
    if (tableField) stepTwo.append(tableField);
    stepThree.append(noteField);
    grid.replaceChildren(stepOne, stepTwo, stepThree);
    const progress = el('ol', 'mtn-booking-progress');
    ['کافه و برنامه', 'زمان و میز', 'تأیید'].forEach((label, index) => {
      const item = el('li', 'mtn-booking-progress-item', label);
      item.dataset.step = String(index + 1);
      progress.append(item);
    });
    form.insertBefore(progress, grid);
    const actions = form.querySelector('.mtn-booking-actions');
    if (!actions) return;
    const back = el('button', 'button mtn-step-back', 'مرحله قبل');
    back.type = 'button';
    const next = el('button', 'button mtn-step-next', 'ادامه');
    next.type = 'button';
    const check = form.querySelector('.mtn-check-availability');
    const submit = form.querySelector('.mtn-submit-booking');
    actions.prepend(back);
    actions.append(next);
    let current = 1;
    function showStep(step) {
      current = Math.max(1, Math.min(3, step));
      form.querySelectorAll('.mtn-booking-step').forEach(panel => {
        panel.hidden = Number(panel.dataset.step) !== current;
      });
      progress.querySelectorAll('.mtn-booking-progress-item').forEach(item => {
        const n = Number(item.dataset.step);
        item.classList.toggle('is-current', n === current);
        item.classList.toggle('is-complete', n < current);
        if (n === current) item.setAttribute('aria-current', 'step'); else item.removeAttribute('aria-current');
      });
      back.hidden = current === 1;
      next.hidden = current === 3;
      if (check) check.hidden = current !== 2;
      if (submit) submit.hidden = current !== 3;
      actions.classList.toggle('is-final-step', current === 3);
    }
    next.addEventListener('click', () => {
      const required = current === 1 ? [form.elements.cafe_id, form.elements.event_id] : [form.elements.party_size, form.elements.booking_at];
      for (const field of required) {
        if (!field.reportValidity()) return;
      }
      if (current === 2) {
        const status = root.querySelector('.mtn-booking-status');
        if (submit && submit.disabled && status && status.dataset.state !== 'success') {
          if (check) check.focus();
          status.textContent = 'برای ادامه ابتدا ظرفیت را بررسی کنید.';
          status.dataset.state = 'info';
          return;
        }
      }
      showStep(current + 1);
      const first = form.querySelector('.mtn-booking-step:not([hidden]) input, .mtn-booking-step:not([hidden]) select, .mtn-booking-step:not([hidden]) textarea');
      if (first) first.focus({ preventScroll: true });
    });
    back.addEventListener('click', () => showStep(current - 1));
    if (check) check.addEventListener('click', () => {
      const observer = new MutationObserver(() => {
        const status = root.querySelector('.mtn-booking-status');
        if (status && status.dataset.state === 'success') {
          showStep(3);
          observer.disconnect();
        }
      });
      observer.observe(root.querySelector('.mtn-booking-status'), { attributes: true, childList: true, subtree: true });
    });
    showStep(1);
  }
  document.querySelectorAll('[data-mtn-booking]').forEach(async (root) => {
    buildBookingSteps(root);
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
    cafe.addEventListener('change', () => { idempotencyKey = ''; updateEvents(); });
    event.addEventListener('change', () => { idempotencyKey = ''; applyEventSchedule(); resetAvailability(); });
    party.addEventListener('change', () => { idempotencyKey = ''; resetAvailability(); });
    date.addEventListener('change', () => { idempotencyKey = ''; resetAvailability(); });
    table.addEventListener('change', () => { idempotencyKey = ''; if (lastAvailability) submitButton.disabled = !lastAvailability.available || (lastAvailability.table_selection_required && !table.value); });
    form.elements.customer_note.addEventListener('input', () => { idempotencyKey = ''; });
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
      if (!idempotencyKey) idempotencyKey = (window.crypto && window.crypto.randomUUID ? window.crypto.randomUUID() : (Date.now().toString(36) + Math.random().toString(36).slice(2))).replace(/[^A-Za-z0-9._:-]/g, '').slice(0, 64).padEnd(8, '0');
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
        if (booking.whatsapp_url) {
          loginNote.hidden = false;
          loginNote.replaceChildren(document.createTextNode('برای باز کردن پیام آماده و ارسال دستی به کافه: '));
          const whatsappLink = el('a', '', 'باز کردن واتساپ');
          whatsappLink.href = booking.whatsapp_url;
          whatsappLink.target = '_blank';
          whatsappLink.rel = 'noopener noreferrer';
          loginNote.append(whatsappLink);
        } else {
          loginNote.hidden = true;
          loginNote.replaceChildren();
        }
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
        if (booking.status === 'confirmed' && booking.checkin_status !== 'checked_in') {
          const checkinButton = el('button', 'button', 'کد اعلام حضور');
          checkinButton.type = 'button';
          checkinButton.addEventListener('click', async () => {
            checkinButton.disabled = true;
            try {
              const issued = await request(api + 'bookings/' + encodeURIComponent(booking.id) + '/checkin-token', { method: 'POST', headers, credentials: 'same-origin', body: '{}' });
              const tokenBox = el('div', 'mtn-checkin-token');
              tokenBox.append(el('strong', '', 'کد یک‌بارمصرف: '), el('code', '', issued.token), el('p', '', 'اعتبار تا ' + issued.expires_at));
              checkinButton.replaceWith(tokenBox);
              setStatus(root, 'کد اعلام حضور ساخته شد؛ آن را فقط به کارکنان مجاز کافه نشان دهید.', 'success');
            } catch (error) {
              checkinButton.disabled = false;
              setStatus(root, error.message, 'error');
            }
          });
          actionCell.append(checkinButton);
        }
        const historyButton = el('button', 'button', 'تاریخچه');
        historyButton.type = 'button';
        let historyRow = null;
        historyButton.addEventListener('click', async () => {
          if (historyRow && historyRow.isConnected) {
            historyRow.remove();
            historyRow = null;
            return;
          }
          historyButton.disabled = true;
          try {
            const events = await request(api + 'bookings/' + encodeURIComponent(booking.id) + '/history', { headers, credentials: 'same-origin' });
            historyRow = el('tr');
            const cell = el('td');
            cell.colSpan = 5;
            const list = el('ul');
            events.forEach((item) => {
              const statusText = (statusLabels[item.from_status] || item.from_status || '—') + ' → ' + (statusLabels[item.to_status] || item.to_status || '—');
              list.append(el('li', '', item.created_at + ' UTC — ' + item.event_type + ' — ' + statusText + (item.details ? ' — ' + item.details : '')));
            });
            if (!events.length) list.append(el('li', '', 'تاریخچه‌ای ثبت نشده است.'));
            cell.append(list);
            historyRow.append(cell);
            row.after(historyRow);
          } catch (error) {
            setStatus(root, error.message, 'error');
          } finally {
            historyButton.disabled = false;
          }
        });
        actionCell.append(historyButton);
        if (booking.whatsapp_url) {
          const whatsappLink = el('a', 'button', 'پیام دستی در واتساپ');
          whatsappLink.href = booking.whatsapp_url;
          whatsappLink.target = '_blank';
          whatsappLink.rel = 'noopener noreferrer';
          whatsappLink.setAttribute('aria-label', 'باز کردن پیام آماده واتساپ برای رزرو ' + text(booking.booking_code));
          actionCell.append(whatsappLink);
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
    const checkinForm = root.querySelector('.mtn-checkin-form');
    if (checkinForm) {
      checkinForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!checkinForm.reportValidity()) return;
        const input = checkinForm.elements.token;
        const button = checkinForm.querySelector('button[type="submit"]');
        button.disabled = true;
        try {
          const booking = await request(api + 'cafe/check-in', { method: 'POST', headers, credentials: 'same-origin', body: JSON.stringify({ token: input.value.trim() }) });
          checkinForm.reset();
          setStatus(root, 'حضور مشتری برای رزرو ' + booking.booking_code + ' ثبت شد.', 'success');
          await load();
        } catch (error) {
          setStatus(root, error.message, 'error');
        } finally {
          button.disabled = false;
        }
      });
    }
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
      ['کد', 'کافه', 'برنامه', 'زمان (UTC)', 'نفرات', 'میز', 'وضعیت', 'عملیات'].forEach((label) => headRow.append(el('th', '', label)));
      head.append(headRow);
      const body = el('tbody');
      bookings.forEach((booking) => {
        const row = el('tr');
        [booking.booking_code, booking.cafe_name, booking.event_name, booking.booking_at, text(booking.party_size), booking.table_name || (booking.table_id ? 'میز #' + booking.table_id : 'تخصیص‌نیافته'), labels[booking.status] || booking.status].forEach((value) => row.append(el('td', '', text(value))));
        const actions = el('td');
        if (!booking.table_id && Array.isArray(booking.available_tables) && booking.available_tables.length && ['pending_cafe', 'confirmed'].includes(booking.status)) {
          const tableSelect = el('select', 'mtn-table-assignment');
          tableSelect.setAttribute('aria-label', 'انتخاب میز برای رزرو');
          tableSelect.add(new Option('انتخاب میز', ''));
          booking.available_tables.forEach((item) => tableSelect.add(new Option(item.name + ' (' + item.capacity + ' نفر)', String(item.id))));
          const assign = el('button', 'button', 'تخصیص میز');
          assign.type = 'button';
          assign.addEventListener('click', async () => {
            if (!tableSelect.value) { setStatus(root, 'ابتدا میز را انتخاب کنید.', 'error'); return; }
            assign.disabled = true;
            try {
              await request(api + 'cafe/bookings/' + encodeURIComponent(booking.id) + '/assign-table', { method: 'POST', headers, credentials: 'same-origin', body: JSON.stringify({ table_id: Number(tableSelect.value) }) });
              setStatus(root, 'میز برای رزرو تخصیص داده شد.', 'success');
              await load();
            } catch (error) {
              assign.disabled = false;
              setStatus(root, error.message, 'error');
            }
          });
          actions.append(tableSelect, assign, el('span', '', ' '));
        }
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
          act('تکمیل شد', 'cafe/bookings/' + encodeURIComponent(booking.id) + '/complete', false);
          act('عدم مراجعه', 'cafe/bookings/' + encodeURIComponent(booking.id) + '/no-show', false);
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

document.addEventListener('DOMContentLoaded', () => {
  const clock = document.getElementById('live-clock');
  if (clock) {
    const formatter = new Intl.DateTimeFormat('id-ID', {
      timeZone: 'Asia/Jakarta',
      weekday: 'long',
      day: 'numeric',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
      hour12: false,
    });
    const updateClock = () => { clock.textContent = formatter.format(new Date()); };
    updateClock();
    window.setInterval(updateClock, 1000);
  }
  document.querySelectorAll('[data-reload]').forEach((button) => {
    button.addEventListener('click', () => window.location.reload());
  });
  document.querySelectorAll('[data-dialog-open]').forEach((link) => {
    link.addEventListener('click', (event) => {
      const dialog = document.getElementById(link.dataset.dialogOpen);
      if (!dialog || typeof dialog.showModal !== 'function') return;
      event.preventDefault();
      dialog.querySelectorAll('.dialog-message').forEach((message) => message.remove());
      dialog.showModal();
      setVisitEditing(dialog, false);
    });
  });
  const setVisitEditing = (dialog, editing) => {
    const form = dialog?.querySelector('.dialog-visit-form');
    if (!form) return;
    const view = dialog.querySelector('.dialog-visit-view');
    if (view) view.hidden = editing;
    form.hidden = !editing;
    const editButton = dialog.querySelector('[data-edit-visit]');
    if (editButton) editButton.hidden = editing;
    if (editing) form.querySelector('input:not([type="hidden"])')?.focus();
  };
  document.querySelectorAll('[data-edit-visit]').forEach((button) => {
    button.addEventListener('click', () => setVisitEditing(button.closest('dialog'), true));
  });
  document.querySelectorAll('[data-cancel-visit]').forEach((button) => {
    button.addEventListener('click', () => {
      const dialog = button.closest('dialog');
      dialog?.querySelector('.dialog-visit-form')?.reset();
      dialog?.close();
    });
  });
  const showDialogMessage = (dialog, type, text) => {
    dialog.querySelectorAll('.dialog-message').forEach((message) => message.remove());
    const message = document.createElement('div');
    message.className = `dialog-message ${type}`;
    message.dataset.clientMessage = 'true';
    message.setAttribute('role', 'status');
    message.textContent = text;
    dialog.querySelector('.dialog-content')?.prepend(message);
  };
  const showHistoryFlash = (type, text) => {
    document.querySelector('.history-flash')?.remove();
    const message = document.createElement('div');
    message.className = `history-flash ${type}`;
    message.setAttribute('role', 'status');
    message.textContent = text;
    document.querySelector('.history-hero')?.before(message);
  };
  const updateVisitRow = (visit) => {
    const row = document.querySelector(`[data-visit-row="${visit.id}"]`);
    if (!row) return;
    Object.entries({
      visit_date_label: visit.visit_date_label,
      check_in_time: visit.check_in_time,
      visitor_name: visit.visitor_name,
      visitor_nik: visit.visitor_nik_display,
      company_display: visit.company_display,
      person_to_meet: visit.person_to_meet,
      purpose: visit.purpose,
      status_label: visit.status_label,
    }).forEach(([field, value]) => {
      const element = row.querySelector(`[data-field="${field}"]`);
      if (element) element.textContent = value;
    });
    const checkout = row.querySelector('[data-field="check_out_time"]');
    if (checkout) {
      checkout.textContent = visit.check_out_time || '—';
      checkout.className = visit.complete ? 'time-out' : 'time-empty';
    }
    const purpose = row.querySelector('[data-field="purpose"]');
    if (purpose) purpose.title = visit.purpose;
    const status = row.querySelector('[data-field="status_label"]');
    if (status) status.className = `pill ${visit.complete ? 'done' : 'active'}`;
    const action = row.querySelector('[data-dialog-open]');
    if (action) action.setAttribute('aria-label', `Edit kunjungan ${visit.visitor_name}`);
  };
  const updateVisitDialog = (dialog, visit) => {
    if (!dialog) return;
    Object.entries({
      visit_date_label: `#${visit.id} · ${visit.visit_date_label}`,
      check_in_time: visit.check_in_time,
      check_out_time: visit.check_out_time || '—',
      visitor_name: visit.visitor_name,
      visitor_nik: visit.visitor_nik_display,
      company_display: visit.company_display,
      person_to_meet: visit.person_to_meet,
      visit_status: visit.complete ? 'Lengkap' : 'Belum lengkap',
      purpose: visit.purpose,
    }).forEach(([field, value]) => {
      const element = dialog.querySelector(`[data-detail="${field}"]`);
      if (element) element.textContent = value;
    });
  };
  const commitFormValues = (form, visit) => {
    ['visit_date', 'visitor_name', 'visitor_nik', 'company', 'person_to_meet', 'purpose', 'check_in_time', 'check_out_time'].forEach((field) => {
      const input = form.elements.namedItem(field);
      if (!input) return;
      const value = visit[field] ?? '';
      input.value = value;
      input.defaultValue = value;
    });
  };
  document.querySelectorAll('.dialog-visit-form').forEach((form) => {
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const dialog = form.closest('dialog');
      const buttons = [...form.querySelectorAll('button')];
      buttons.forEach((button) => { button.disabled = true; });
      try {
        const response = await fetch(form.getAttribute("action") || window.location.href, {
          method: 'POST',
          body: new FormData(form),
          headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await response.json().catch(() => ({ ok: false, message: 'Respons server tidak dapat dibaca.' }));
        if (!response.ok || !data.ok) throw new Error(data.message || 'Perubahan belum dapat disimpan.');
        updateVisitRow(data.visit);
        updateVisitDialog(dialog, data.visit);
        commitFormValues(form, data.visit);
        dialog?.close();
        showHistoryFlash('success', data.message);
      } catch (error) {
        showDialogMessage(dialog, 'error', error instanceof Error ? error.message : 'Perubahan belum dapat disimpan.');
      } finally {
        buttons.forEach((button) => { button.disabled = false; });
      }
    });
  });
  document.querySelectorAll('[data-dialog-close]').forEach((button) => {
    button.addEventListener('click', () => {
      const dialog = button.closest('dialog');
      dialog?.querySelector('.dialog-visit-form')?.reset();
      dialog?.close();
    });
  });
  document.querySelectorAll('dialog.visit-dialog').forEach((dialog) => {
    dialog.addEventListener('close', () => dialog.querySelector('.dialog-visit-form')?.reset());
    dialog.addEventListener('click', (event) => {
      if (event.target === dialog) {
        dialog.querySelector('.dialog-visit-form')?.reset();
        dialog.close();
      }
    });
  });
  const openVisitId = new URLSearchParams(window.location.search).get('open');
  if (openVisitId && /^\d+$/.test(openVisitId)) {
    const dialog = document.getElementById(`visit-dialog-${openVisitId}`);
    if (dialog && typeof dialog.showModal === 'function') {
      dialog.showModal();
      setVisitEditing(dialog, dialog.hasAttribute('data-open-edit'));
    }
  }
});

document.addEventListener('DOMContentLoaded', () => {
  const wireEmployeeAutocomplete = (form) => {
    const nik = form.elements.namedItem('visitor_nik');
    const name = form.elements.namedItem('visitor_name');
    const department = form.elements.namedItem('company');
    const fields = [...form.querySelectorAll('[data-autocomplete]')].map((element) => ({
      element,
      input: element.querySelector('input'),
      panel: element.querySelector('.suggestion-panel'),
      status: element.querySelector('.lookup-status'),
      kind: element.dataset.autocomplete,
      results: [],
      active: -1,
      timer: null,
      controller: null,
      version: 0,
    }));

    const close = (field) => {
      field.panel.hidden = true;
      field.input.setAttribute('aria-expanded', 'false');
      field.input.removeAttribute('aria-activedescendant');
      field.active = -1;
    };
    const cancel = (field) => {
      window.clearTimeout(field.timer);
      field.controller?.abort();
      field.controller = null;
      field.version += 1;
    };
    const clear = (field) => {
      cancel(field);
      field.results = [];
      field.panel.replaceChildren();
      field.status.textContent = '';
      field.input.removeAttribute('aria-busy');
      close(field);
    };
    const activate = (field, index) => {
      field.active = index;
      [...field.panel.querySelectorAll('[role="option"]')].forEach((option, position) => {
        option.setAttribute('aria-selected', position === index ? 'true' : 'false');
      });
      const option = field.panel.querySelectorAll('[role="option"]')[index];
      if (option) {
        field.input.setAttribute('aria-activedescendant', option.id);
        option.scrollIntoView({ block: 'nearest' });
      } else {
        field.input.removeAttribute('aria-activedescendant');
      }
    };
    const choose = (employee) => {
      nik.value = employee.id_card;
      name.value = employee.nama;
      fields.forEach(clear);
      department.focus();
    };
    const showResults = (field, employees) => {
      field.results = employees;
      field.panel.replaceChildren();
      field.active = -1;
      if (!employees.length) {
        field.status.textContent = 'Data tidak ditemukan. NIK dan nama dapat diisi manual.';
        close(field);
        return;
      }
      field.status.textContent = '';
      employees.forEach((employee, index) => {
        const option = document.createElement('button');
        option.type = 'button';
        option.id = `${field.panel.id}-option-${index}`;
        option.className = 'suggestion-option';
        option.setAttribute('role', 'option');
        option.setAttribute('aria-selected', 'false');
        const primary = document.createElement('strong');
        const secondary = document.createElement('small');
        primary.textContent = field.kind === 'nik' ? employee.id_card : employee.nama;
        secondary.textContent = field.kind === 'nik' ? employee.nama : employee.id_card;
        option.append(primary, secondary);
        option.addEventListener('mousedown', (event) => event.preventDefault());
        option.addEventListener('click', () => choose(employee));
        field.panel.append(option);
      });
      field.panel.hidden = false;
      field.input.setAttribute('aria-expanded', 'true');
    };

    fields.forEach((field) => {
      field.input.addEventListener('input', () => {
        clear(field);
        const query = field.input.value.trim();
        if ([...query].length < 2) return;
        const version = field.version;
        field.status.textContent = 'Mencari karyawan…';
        field.input.setAttribute('aria-busy', 'true');
        field.timer = window.setTimeout(async () => {
          const controller = new AbortController();
          field.controller = controller;
          const params = new URLSearchParams({ field: field.kind, q: query });
          try {
            const response = await fetch(`/api/employees?${params}`, { signal: controller.signal });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message);
            if (version !== field.version || field.input.value.trim() !== query) return;
            showResults(field, Array.isArray(data.items) ? data.items : []);
          } catch (error) {
            if (error.name === 'AbortError' || version !== field.version) return;
            field.status.textContent = 'Pencarian karyawan sementara tidak tersedia. NIK dan nama dapat diisi manual.';
            close(field);
          } finally {
            if (version === field.version) {
              field.input.removeAttribute('aria-busy');
              field.controller = null;
            }
          }
        }, 300);
      });
      field.input.addEventListener('keydown', (event) => {
        if (field.panel.hidden || !field.results.length) return;
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
          event.preventDefault();
          const delta = event.key === 'ArrowDown' ? 1 : -1;
          const next = field.active < 0 ? (delta > 0 ? 0 : field.results.length - 1)
            : (field.active + delta + field.results.length) % field.results.length;
          activate(field, next);
        } else if (event.key === 'Enter' && field.active >= 0) {
          event.preventDefault();
          choose(field.results[field.active]);
        } else if (event.key === 'Escape') {
          event.preventDefault();
          close(field);
        }
      });
      field.input.addEventListener('focus', () => {
        if (field.results.length) {
          field.panel.hidden = false;
          field.input.setAttribute('aria-expanded', 'true');
        }
      });
    });

    form.addEventListener('reset', () => fields.forEach(clear));

    document.addEventListener('pointerdown', (event) => {
      fields.forEach((field) => { if (!field.element.contains(event.target)) close(field); });
    });
  };

  document.querySelectorAll('.guest-card form, .dialog-visit-form').forEach(wireEmployeeAutocomplete);
  const form = document.querySelector('.guest-card form');
  if (!form) return;

  const meet = form.querySelector('[data-meet-picker]');
  const trigger = meet.querySelector('.meet-trigger');
  const dropdown = meet.querySelector('.meet-dropdown');
  const search = meet.querySelector('#meet-search');
  const options = meet.querySelector('.meet-options');
  const selected = meet.querySelector('#meet-selected');
  const value = form.elements.namedItem('person_to_meet');
  const status = meet.querySelector('.meet-status');
  let staff = [];

  const closeMeet = () => {
    dropdown.hidden = true;
    trigger.setAttribute('aria-expanded', 'false');
  };
  const renderStaff = () => {
    const query = search.value.trim().toLocaleLowerCase('id-ID');
    options.replaceChildren();
    const matches = staff.filter((person) => person.nama.toLocaleLowerCase('id-ID').includes(query));
    if (!matches.length) {
      const empty = document.createElement('p');
      empty.className = 'meet-empty';
      empty.textContent = 'Karyawan MIS tidak ditemukan.';
      options.append(empty);
      return;
    }
    matches.forEach((person) => {
      const option = document.createElement('button');
      option.type = 'button';
      option.className = 'meet-option';
      option.setAttribute('role', 'option');
      const primary = document.createElement('strong');
      const secondary = document.createElement('small');
      primary.textContent = person.nama;
      secondary.textContent = `Departemen ${person.id_depar}`;
      option.append(primary, secondary);
      option.addEventListener('click', () => {
        value.value = person.nama;
        selected.textContent = person.nama;
        status.textContent = '';
        closeMeet();
        trigger.focus();
      });
      options.append(option);
    });
  };
  trigger.disabled = true;
  status.textContent = 'Memuat karyawan MIS…';
  fetch('/api/mis-staff')
    .then(async (response) => {
      const data = await response.json();
      if (!response.ok) throw new Error(data.message);
      staff = Array.isArray(data.items) ? data.items.filter((person) => person.id_depar === 17) : [];
      renderStaff();
      trigger.disabled = staff.length === 0;
      if (staff.length) {
        status.textContent = '';
      } else {
        status.textContent = 'Daftar karyawan MIS kosong. Hubungi pengelola sistem.';
      }
    })
    .catch(() => {
      status.textContent = 'Daftar karyawan MIS belum dapat dimuat. Hubungi pengelola sistem.';
    });
  trigger.addEventListener('click', () => {
    dropdown.hidden = !dropdown.hidden;
    trigger.setAttribute('aria-expanded', dropdown.hidden ? 'false' : 'true');
    if (!dropdown.hidden) search.focus();
  });
  search.addEventListener('input', renderStaff);
  search.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      event.preventDefault();
      closeMeet();
      trigger.focus();
    }
  });
  form.addEventListener('submit', (event) => {
    if (!value.value.trim()) {
      event.preventDefault();
      status.textContent = 'Pilih karyawan MIS yang ditemui.';
      trigger.focus();
    }
  });
  document.addEventListener('pointerdown', (event) => {
    if (!meet.contains(event.target)) closeMeet();
  });
});

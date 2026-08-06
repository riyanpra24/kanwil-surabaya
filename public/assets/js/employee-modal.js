(() => {
    const config = window.employeeCrudModal;
    const modal = document.querySelector('#employeeModal');
    if (!config || !modal) return;

    const employees = new Map(config.employees.map((employee) => [String(employee.id), employee]));
    const form = modal.querySelector('#employeeModalForm');
    const title = modal.querySelector('#employeeModalTitle');
    const eyebrow = modal.querySelector('#employeeModalEyebrow');
    const errors = modal.querySelector('.modal-form-errors');
    const saveButton = modal.querySelector('.modal-save-button');
    const fields = ['unit_slug','employee_number','full_name','gender','division','position','employment_status','phone','corporate_email'];
    let activeEmployee = null;
    let lastTrigger = null;
    let formMode = 'edit';

    const escapeHtml = (value) => String(value).replace(/[&<>'"]/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[character]));
    const show = () => {
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('asset-modal-open');
    };
    const renderView = (employee) => {
        Object.keys(employee).forEach((field) => {
            const target = modal.querySelector(`[data-detail="${field}"]`);
            if (target) target.textContent = employee[field] || '-';
        });
        title.textContent = employee.full_name;
        eyebrow.textContent = 'DETAIL KARYAWAN';
        modal.classList.remove('mode-edit');
    };
    const renderEdit = (employee) => {
        formMode = 'edit';
        fields.forEach((field) => {
            const input = form.elements.namedItem(field);
            if (input) input.value = employee[field] ?? '';
        });
        form.action = `${config.updateBase}/${employee.id}`;
        title.textContent = employee.full_name;
        eyebrow.textContent = 'EDIT KARYAWAN';
        saveButton.textContent = 'Simpan Perubahan';
        saveButton.disabled = false;
        errors.hidden = true;
        errors.innerHTML = '';
        modal.classList.add('mode-edit');
    };
    const renderCreate = () => {
        formMode = 'create';
        activeEmployee = null;
        form.reset();
        form.action = config.storeUrl;
        form.elements.namedItem('unit_slug').value = config.defaultUnit;
        title.textContent = 'Tambah Karyawan Baru';
        eyebrow.textContent = 'FORMULIR KARYAWAN';
        saveButton.textContent = 'Tambah Karyawan';
        saveButton.disabled = false;
        errors.hidden = true;
        errors.innerHTML = '';
        modal.classList.add('mode-edit');
    };
    const open = (employee, mode, trigger) => {
        activeEmployee = employee;
        lastTrigger = trigger;
        if (mode === 'edit') renderEdit(employee); else renderView(employee);
        show();
        window.setTimeout(() => modal.querySelector('.asset-modal-close').focus({ preventScroll: true }), 200);
    };
    const close = () => {
        modal.classList.remove('open', 'mode-edit');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('asset-modal-open');
        lastTrigger?.focus({ preventScroll: true });
    };

    document.querySelectorAll('[data-employee-view]').forEach((link) => link.addEventListener('click', (event) => {
        event.preventDefault();
        const employee = employees.get(link.dataset.employeeView);
        if (employee) open(employee, 'view', link);
    }));
    document.querySelectorAll('[data-employee-edit]').forEach((link) => link.addEventListener('click', (event) => {
        event.preventDefault();
        const employee = employees.get(link.dataset.employeeEdit);
        if (employee) open(employee, 'edit', link);
    }));
    document.querySelector('[data-employee-create]')?.addEventListener('click', (event) => {
        lastTrigger = event.currentTarget;
        renderCreate();
        show();
        window.setTimeout(() => form.elements.namedItem('full_name').focus({ preventScroll: true }), 200);
    });
    modal.querySelectorAll('[data-modal-close]').forEach((button) => button.addEventListener('click', close));
    modal.querySelector('.modal-edit-switch').addEventListener('click', () => activeEmployee && renderEdit(activeEmployee));
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && modal.classList.contains('open')) close(); });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        errors.hidden = true;
        saveButton.disabled = true;
        saveButton.textContent = 'Menyimpan...';
        try {
            const response = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } });
            const result = await response.json();
            if (!response.ok || !result.ok) {
                const messages = Object.values(result.errors || { error: 'Data karyawan gagal disimpan.' });
                errors.innerHTML = `<ul>${messages.map((message) => `<li>${escapeHtml(message)}</li>`).join('')}</ul>`;
                errors.hidden = false;
                saveButton.disabled = false;
                saveButton.textContent = formMode === 'create' ? 'Tambah Karyawan' : 'Simpan Perubahan';
                return;
            }
            saveButton.textContent = formMode === 'create' ? 'Karyawan Ditambahkan' : 'Tersimpan ✓';
            window.setTimeout(() => window.location.reload(), 400);
        } catch (error) {
            errors.textContent = 'Tidak dapat menyimpan data karyawan. Silakan coba lagi.';
            errors.hidden = false;
            saveButton.disabled = false;
            saveButton.textContent = formMode === 'create' ? 'Tambah Karyawan' : 'Simpan Perubahan';
        }
    });
})();

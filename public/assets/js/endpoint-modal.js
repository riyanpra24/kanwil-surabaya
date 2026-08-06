(() => {
    const config = window.endpointCrudModal;
    const modal = document.querySelector('#endpointModal');
    if (!config || !modal) return;

    const endpoints = new Map(config.endpoints.map((endpoint) => [String(endpoint.id), endpoint]));
    const form = modal.querySelector('#endpointModalForm');
    const title = modal.querySelector('#endpointModalTitle');
    const eyebrow = modal.querySelector('#endpointModalEyebrow');
    const errors = modal.querySelector('.modal-form-errors');
    const saveButton = modal.querySelector('.modal-save-button');
    const organizationInput = form.elements.namedItem('organization_unit');
    const branchInput = form.elements.namedItem('branch_name');
    const fields = ['branch_name','hostname','ip_address','employee_status','endpoint_type','serial_number','brand','procurement_year','asset_number','user_name','notes','operating_system','domain_user','join_domain','login_domain'];
    let activeEndpoint = null;
    let lastTrigger = null;
    let formMode = 'edit';

    const show = () => {
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('asset-modal-open');
    };

    const renderView = (endpoint) => {
        fields.forEach((field) => {
            const target = modal.querySelector(`[data-detail="${field}"]`);
            if (target) target.textContent = endpoint[field] || '-';
        });
        title.textContent = endpoint.hostname;
        eyebrow.textContent = 'DETAIL ENDPOINT';
        modal.classList.remove('mode-edit');
    };

    const renderEdit = (endpoint) => {
        formMode = 'edit';
        fields.forEach((field) => {
            const input = form.elements.namedItem(field);
            if (input) input.value = endpoint[field] ?? '';
        });
        form.elements.namedItem('organization_unit').value = endpoint.organization_unit;
        form.action = `${config.updateBase}/${endpoint.id}`;
        title.textContent = endpoint.hostname;
        eyebrow.textContent = 'EDIT ENDPOINT';
        saveButton.textContent = 'Simpan Perubahan';
        saveButton.disabled = false;
        errors.hidden = true;
        errors.innerHTML = '';
        modal.classList.add('mode-edit');
    };

    const renderCreate = () => {
        formMode = 'create';
        activeEndpoint = null;
        form.reset();
        form.action = config.storeUrl;
        form.elements.namedItem('organization_unit').value = config.unit;
        form.elements.namedItem('branch_name').value = config.defaultBranch;
        form.elements.namedItem('endpoint_type').value = 'PC';
        title.textContent = 'Tambah Endpoint Baru';
        eyebrow.textContent = 'FORMULIR ENDPOINT';
        saveButton.textContent = 'Tambah Endpoint';
        saveButton.disabled = false;
        errors.hidden = true;
        errors.innerHTML = '';
        modal.classList.add('mode-edit');
    };

    const open = (endpoint, mode, trigger) => {
        activeEndpoint = endpoint;
        lastTrigger = trigger;
        if (mode === 'edit') renderEdit(endpoint); else renderView(endpoint);
        show();
        window.setTimeout(() => modal.querySelector('.asset-modal-close').focus({ preventScroll: true }), 200);
    };

    const close = () => {
        modal.classList.remove('open', 'mode-edit');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('asset-modal-open');
        lastTrigger?.focus({ preventScroll: true });
    };

    document.querySelectorAll('[data-endpoint-view]').forEach((link) => link.addEventListener('click', (event) => {
        event.preventDefault();
        const endpoint = endpoints.get(link.dataset.endpointView);
        if (endpoint) open(endpoint, 'view', link);
    }));
    document.querySelectorAll('[data-endpoint-edit]').forEach((link) => link.addEventListener('click', (event) => {
        event.preventDefault();
        const endpoint = endpoints.get(link.dataset.endpointEdit);
        if (endpoint) open(endpoint, 'edit', link);
    }));
    document.querySelector('[data-endpoint-create]')?.addEventListener('click', (event) => {
        lastTrigger = event.currentTarget;
        renderCreate();
        show();
        window.setTimeout(() => form.elements.namedItem('hostname').focus({ preventScroll: true }), 200);
    });
    organizationInput?.addEventListener('change', () => {
        if (organizationInput.value === 'KANWIL') branchInput.value = 'Kanwil';
        else if (branchInput.value === 'Kanwil') branchInput.value = 'Surabaya';
    });
    modal.querySelectorAll('[data-modal-close]').forEach((button) => button.addEventListener('click', close));
    modal.querySelector('.modal-edit-switch').addEventListener('click', () => activeEndpoint && renderEdit(activeEndpoint));
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
                const messages = Object.values(result.errors || { error: 'Data endpoint gagal disimpan.' });
                errors.innerHTML = `<ul>${messages.map((message) => `<li>${String(message).replace(/[&<>'"]/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[character]))}</li>`).join('')}</ul>`;
                errors.hidden = false;
                saveButton.disabled = false;
                saveButton.textContent = formMode === 'create' ? 'Tambah Endpoint' : 'Simpan Perubahan';
                return;
            }
            saveButton.textContent = formMode === 'create' ? 'Endpoint Ditambahkan' : 'Tersimpan ✓';
            window.setTimeout(() => window.location.reload(), 400);
        } catch (error) {
            errors.textContent = 'Tidak dapat menyimpan endpoint. Silakan coba lagi.';
            errors.hidden = false;
            saveButton.disabled = false;
            saveButton.textContent = formMode === 'create' ? 'Tambah Endpoint' : 'Simpan Perubahan';
        }
    });
})();

(() => {
    const config = window.assetCrudModal;
    const modal = document.querySelector('#assetModal');
    if (!config || !modal) return;

    const assets = new Map(config.assets.map((asset) => [String(asset.id), asset]));
    const title = modal.querySelector('#assetModalTitle');
    const eyebrow = modal.querySelector('#assetModalEyebrow');
    const form = modal.querySelector('#assetModalForm');
    const errors = modal.querySelector('.modal-form-errors');
    const saveButton = modal.querySelector('.modal-save-button');
    let activeAsset = null;
    let lastTrigger = null;
    let formMode = 'edit';

    const titleCase = (value) => String(value || '').toLowerCase().replace(/\b\w/g, (character) => character.toUpperCase());
    const rupiah = (value) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value || 0));
    const date = (value) => {
        if (!value) return '-';
        return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(`${value}T00:00:00`));
    };

    const detailValues = (asset) => ({
        name: asset.name || '-',
        asset_group: config.labels[asset.asset_group] || titleCase(asset.asset_group),
        category: asset.category || '-',
        location: titleCase(asset.location) || '-',
        condition: titleCase(asset.condition) || '-',
        asset_code_simat: asset.asset_code_simat || '-',
        asset_code_jstream: asset.asset_code_jstream || '-',
        asset_number: asset.asset_number || '-',
        acquired: date(asset.acquired),
        benefit_end: date(asset.benefit_end),
        useful_life_months: `${Number(asset.useful_life_months || 0).toLocaleString('id-ID')} bulan`,
        acquisition_value: rupiah(asset.acquisition_value),
        monthly_depreciation: rupiah(asset.monthly_depreciation),
    });

    const renderView = (asset) => {
        const values = detailValues(asset);
        modal.querySelectorAll('[data-detail]').forEach((element) => {
            element.textContent = values[element.dataset.detail] ?? '-';
        });
        const status = modal.querySelector('.asset-modal-status');
        status.className = `asset-modal-status ${asset.condition === 'RUSAK' ? 'damaged' : (/HILANG|TIDAK/.test(asset.condition) ? 'lost' : '')}`;
        title.textContent = asset.name;
        eyebrow.textContent = 'DETAIL ASET';
        modal.classList.remove('mode-edit');
    };

    const renderEdit = (asset) => {
        formMode = 'edit';
        const fieldNames = ['asset_group', 'category', 'name', 'location', 'condition', 'acquired', 'asset_code_simat', 'asset_code_jstream', 'asset_number', 'benefit_end', 'useful_life_months', 'acquisition_value', 'residual_percent', 'residual_value', 'depreciation_base', 'monthly_depreciation'];
        fieldNames.forEach((name) => {
            const field = form.elements.namedItem(name);
            if (field) field.value = asset[name] ?? '';
        });
        form.action = `${config.updateBase}/${asset.id}`;
        errors.hidden = true;
        errors.innerHTML = '';
        saveButton.disabled = false;
        saveButton.textContent = 'Simpan Perubahan';
        title.textContent = asset.name;
        eyebrow.textContent = 'EDIT ASET';
        modal.classList.add('mode-edit');
    };

    const renderCreate = () => {
        formMode = 'create';
        activeAsset = null;
        form.reset();
        form.action = config.storeUrl;

        const selectedGroup = config.selectedGroup || 'FURNITURE';
        form.elements.namedItem('asset_group').value = selectedGroup;
        form.elements.namedItem('category').value = ['FURNITURE', 'TI', 'PERALATAN', 'MESIN'].includes(selectedGroup) ? selectedGroup : 'PERALATAN';
        form.elements.namedItem('condition').value = 'SEDANG DIGUNAKAN';
        ['useful_life_months', 'acquisition_value', 'residual_percent', 'residual_value', 'depreciation_base', 'monthly_depreciation'].forEach((name) => {
            form.elements.namedItem(name).value = '0';
        });

        errors.hidden = true;
        errors.innerHTML = '';
        saveButton.disabled = false;
        saveButton.textContent = 'Tambah Aset';
        title.textContent = 'Tambah Aset Baru';
        eyebrow.textContent = 'FORMULIR ASET';
        modal.classList.add('mode-edit');
    };

    const open = (asset, mode, trigger) => {
        activeAsset = asset;
        lastTrigger = trigger;
        if (mode === 'edit') renderEdit(asset); else renderView(asset);
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('asset-modal-open');
        window.setTimeout(() => modal.querySelector('.asset-modal-close').focus({ preventScroll: true }), 220);
    };

    const close = () => {
        modal.classList.remove('open', 'mode-edit');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('asset-modal-open');
        lastTrigger?.focus({ preventScroll: true });
    };

    document.querySelectorAll('[data-asset-view]').forEach((link) => link.addEventListener('click', (event) => {
        event.preventDefault();
        const asset = assets.get(link.dataset.assetView);
        if (asset) open(asset, 'view', link);
    }));

    document.querySelectorAll('[data-asset-edit]').forEach((link) => link.addEventListener('click', (event) => {
        event.preventDefault();
        const asset = assets.get(link.dataset.assetEdit);
        if (asset) open(asset, 'edit', link);
    }));

    document.querySelector('[data-asset-create]')?.addEventListener('click', (event) => {
        event.preventDefault();
        lastTrigger = event.currentTarget;
        renderCreate();
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('asset-modal-open');
        window.setTimeout(() => form.elements.namedItem('name').focus({ preventScroll: true }), 220);
    });

    modal.querySelectorAll('[data-modal-close]').forEach((button) => button.addEventListener('click', close));
    modal.querySelector('.modal-edit-switch').addEventListener('click', () => activeAsset && renderEdit(activeAsset));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal.classList.contains('open')) close();
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        errors.hidden = true;
        saveButton.disabled = true;
        saveButton.textContent = 'Menyimpan...';

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
            });
            const result = await response.json();
            if (!response.ok || !result.ok) {
                const messages = Object.values(result.errors || { error: 'Data gagal disimpan.' });
                errors.innerHTML = `<ul>${messages.map((message) => `<li>${String(message).replace(/[&<>'"]/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[character]))}</li>`).join('')}</ul>`;
                errors.hidden = false;
                form.scrollTo({ top: 0, behavior: 'smooth' });
                saveButton.disabled = false;
                saveButton.textContent = formMode === 'create' ? 'Tambah Aset' : 'Simpan Perubahan';
                return;
            }
            saveButton.textContent = formMode === 'create' ? 'Aset Ditambahkan' : 'Tersimpan ✓';
            window.setTimeout(() => window.location.reload(), 450);
        } catch (error) {
            errors.textContent = 'Tidak dapat menyimpan perubahan. Silakan coba lagi.';
            errors.hidden = false;
            form.scrollTo({ top: 0, behavior: 'smooth' });
            saveButton.disabled = false;
            saveButton.textContent = formMode === 'create' ? 'Tambah Aset' : 'Simpan Perubahan';
        }
    });
})();

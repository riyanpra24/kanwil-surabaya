(() => {
    const data = window.assetDashboard;
    const formatRupiah = (value) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);

    function drawDonut() {
        const canvas = document.querySelector('#conditionChart');
        if (!canvas) return;
        const context = canvas.getContext('2d');
        const scale = window.devicePixelRatio || 1;
        const size = 190;
        canvas.width = size * scale;
        canvas.height = size * scale;
        context.scale(scale, scale);
        const values = data.conditions.map((item) => Number(item[1]));
        const total = values.reduce((sum, value) => sum + value, 0);
        const colors = ['#0875df', '#f59e0b', '#ef4444', '#94a3b8'];
        let angle = -Math.PI / 2;
        values.forEach((value, index) => {
            const next = angle + (value / total) * Math.PI * 2;
            context.beginPath();
            context.arc(95, 95, 70, angle, next);
            context.strokeStyle = colors[index] || '#94a3b8';
            context.lineWidth = 25;
            context.stroke();
            angle = next;
        });
        context.fillStyle = '#16213b';
        context.font = '800 26px Manrope';
        context.textAlign = 'center';
        context.fillText(String(total), 95, 94);
        context.fillStyle = '#718096';
        context.font = '600 9px Manrope';
        context.fillText('TOTAL ASET', 95, 112);
    }

    function drawYearChart() {
        const canvas = document.querySelector('#yearChart');
        if (!canvas) return;
        const rect = canvas.getBoundingClientRect();
        const ratio = window.devicePixelRatio || 1;
        const width = Math.max(500, rect.width);
        const height = 220;
        canvas.width = width * ratio;
        canvas.height = height * ratio;
        const ctx = canvas.getContext('2d');
        ctx.scale(ratio, ratio);
        const years = data.years;
        const max = Math.max(...years.map((item) => Number(item[1])));
        const left = 35, right = 12, top = 15, bottom = 35;
        const chartWidth = width - left - right;
        const chartHeight = height - top - bottom;
        ctx.strokeStyle = '#e6edf5';
        ctx.lineWidth = 1;
        for (let line = 0; line <= 4; line += 1) {
            const y = top + chartHeight * line / 4;
            ctx.beginPath(); ctx.moveTo(left, y); ctx.lineTo(width - right, y); ctx.stroke();
        }
        const points = years.map((item, index) => ({
            x: left + chartWidth * index / (years.length - 1),
            y: top + chartHeight - (Number(item[1]) / max) * chartHeight,
        }));
        const gradient = ctx.createLinearGradient(0, top, 0, height - bottom);
        gradient.addColorStop(0, 'rgba(8,117,223,.22)'); gradient.addColorStop(1, 'rgba(8,117,223,0)');
        ctx.beginPath(); ctx.moveTo(points[0].x, height - bottom); points.forEach((point) => ctx.lineTo(point.x, point.y)); ctx.lineTo(points.at(-1).x, height - bottom); ctx.closePath(); ctx.fillStyle = gradient; ctx.fill();
        ctx.beginPath(); points.forEach((point, index) => index ? ctx.lineTo(point.x, point.y) : ctx.moveTo(point.x, point.y)); ctx.strokeStyle = '#0875df'; ctx.lineWidth = 2.5; ctx.stroke();
        ctx.fillStyle = '#0875df'; points.forEach((point) => { ctx.beginPath(); ctx.arc(point.x, point.y, 3, 0, Math.PI * 2); ctx.fill(); });
        ctx.fillStyle = '#8b97a8'; ctx.font = '8px Manrope'; ctx.textAlign = 'center';
        years.forEach((item, index) => { if (index % 3 === 0 || index === years.length - 1) ctx.fillText(item[0], points[index].x, height - 13); });
    }

    const search = document.querySelector('#assetSearch');
    const categoryFilter = document.querySelector('#categoryFilter');
    const conditionFilter = document.querySelector('#conditionFilter');
    const body = document.querySelector('#assetTableBody');
    const resultCount = document.querySelector('#resultCount');
    const pageInfo = document.querySelector('#pageInfo');
    const previous = document.querySelector('#prevPage');
    const next = document.querySelector('#nextPage');
    const pageSize = 10;
    let page = 1;
    let activeAssetGroup = '';

    const badgeClass = (condition) => condition === 'SEDANG DIGUNAKAN' ? 'used' : condition === 'RUSAK' ? 'damaged' : 'lost';
    function filteredAssets() {
        const term = search.value.trim().toUpperCase();
        return data.assets.filter((asset) => {
            const haystack = [asset.name, asset.assetCodeSimat, asset.assetCodeJstream, asset.location].join(' ').toUpperCase();
            return (!term || haystack.includes(term)) && (!categoryFilter.value || asset.category === categoryFilter.value) && (!conditionFilter.value || asset.condition === conditionFilter.value) && matchesAssetGroup(asset, activeAssetGroup);
        });
    }
    function matchesAssetGroup(asset, group) {
        if (!group) return true;
        if (['FURNITURE', 'TI', 'PERALATAN', 'MESIN'].includes(group)) return asset.category === group;
        const name = String(asset.name || '').toUpperCase();
        const location = String(asset.location || '').toUpperCase();
        if (group === 'RUMAH DINAS') return name.includes('RUMAH DINAS') || location.includes('RUMAH DINAS');
        if (group === 'KENDARAAN') return /INNOVA|SEPEDA MOTOR|MOBIL|KENDARAAN/.test(`${name} ${location}`);
        if (group === 'TANAH') return name.includes('TANAH');
        if (group === 'GEDUNG') return /GEDUNG|PEMBANGUNAN|PERLUASAN RUANG/.test(name);
        return true;
    }
    function renderTable() {
        const filtered = filteredAssets();
        const pages = Math.max(1, Math.ceil(filtered.length / pageSize));
        page = Math.min(page, pages);
        const rows = filtered.slice((page - 1) * pageSize, page * pageSize);
        body.innerHTML = rows.map((asset) => `<tr><td><div class="asset-name">${escapeHtml(asset.name)}<small>${escapeHtml(asset.assetCodeSimat || asset.assetNumber || 'Tanpa kode')}</small></div></td><td>${escapeHtml(asset.category)}</td><td>${escapeHtml(titleCase(asset.location))}</td><td><span class="condition-badge ${badgeClass(asset.condition)}">${escapeHtml(titleCase(asset.condition))}</span></td><td>${asset.year || '-'}</td><td>${formatRupiah(asset.acquisitionValue)}</td></tr>`).join('') || '<tr><td colspan="6">Tidak ada aset yang sesuai dengan filter.</td></tr>';
        resultCount.textContent = `${filtered.length} aset`;
        pageInfo.textContent = `Halaman ${page} dari ${pages}`;
        previous.disabled = page <= 1;
        next.disabled = page >= pages;
    }
    function escapeHtml(value) { const element = document.createElement('span'); element.textContent = String(value ?? ''); return element.innerHTML; }
    function titleCase(value) { return String(value ?? '').toLowerCase().replace(/\b\w/g, (char) => char.toUpperCase()); }
    [search, categoryFilter, conditionFilter].filter(Boolean).forEach((element) => element.addEventListener('input', () => { page = 1; renderTable(); }));
    previous?.addEventListener('click', () => { page -= 1; renderTable(); });
    next?.addEventListener('click', () => { page += 1; renderTable(); });

    document.querySelectorAll('.nav-dropdown-toggle').forEach((menuToggle) => {
        menuToggle.addEventListener('click', () => {
            const menu = menuToggle.closest('.nav-dropdown');
            const isOpen = !menu.classList.contains('open');
            document.querySelectorAll('.nav-dropdown').forEach((item) => {
                item.classList.remove('open');
                item.querySelector('.nav-dropdown-toggle')?.setAttribute('aria-expanded', 'false');
            });
            if (isOpen) {
                menu.classList.add('open');
                menuToggle.setAttribute('aria-expanded', 'true');
            }
        });
    });
    document.querySelectorAll('[data-asset-group]').forEach((link) => link.addEventListener('click', () => {
        activeAssetGroup = link.dataset.assetGroup;
        page = 1;
        search.value = '';
        categoryFilter.value = '';
        conditionFilter.value = '';
        document.querySelectorAll('[data-asset-group]').forEach((item) => item.classList.toggle('selected', item === link));
        renderTable();
        if (window.innerWidth <= 800) document.querySelector('.sidebar').classList.remove('open');
    }));

    drawDonut(); drawYearChart();
    if (body) renderTable();
    let resizeTimer;
    window.addEventListener('resize', () => { clearTimeout(resizeTimer); resizeTimer = setTimeout(drawYearChart, 120); });
})();

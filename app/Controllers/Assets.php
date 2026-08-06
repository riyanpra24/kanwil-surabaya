<?php

namespace App\Controllers;

use App\Libraries\AssetRepository;
use CodeIgniter\Exceptions\PageNotFoundException;

class Assets extends BaseController
{
    private const GROUPS = ['FURNITURE', 'TI', 'PERALATAN', 'MESIN', 'RUMAH DINAS', 'KENDARAAN', 'TANAH', 'GEDUNG'];
    private AssetRepository $assets;

    public function __construct()
    {
        $this->assets = new AssetRepository();
    }

    public function index(): string
    {
        $group = strtoupper(trim((string) $this->request->getGet('type')));
        $group = in_array($group, self::GROUPS, true) ? $group : '';
        $search = trim((string) $this->request->getGet('q'));
        $condition = strtoupper(trim((string) $this->request->getGet('condition')));
        $allowedConditions = ['SEDANG DIGUNAKAN', 'RUSAK', 'HILANG', 'TIDAK DIKETAHUI'];
        $condition = in_array($condition, $allowedConditions, true) ? $condition : '';
        $page = max(1, (int) $this->request->getGet('page'));
        $perPage = (int) $this->request->getGet('per_page');
        $perPage = in_array($perPage, [15, 50, 100], true) ? $perPage : 15;
        $result = $this->assets->search($group, $search, $condition, $page, $perPage);

        return view('assets/index', [
            'assets' => $result['rows'], 'total' => $result['total'], 'pages' => $result['pages'], 'page' => $page,
            'group' => $group, 'search' => $search, 'condition' => $condition, 'groups' => self::GROUPS, 'perPage' => $perPage,
        ]);
    }

    public function create(): string
    {
        $selectedGroup = strtoupper(trim((string) $this->request->getGet('type')));
        $selectedGroup = in_array($selectedGroup, self::GROUPS, true) ? $selectedGroup : '';
        return view('assets/form', ['asset' => null, 'groups' => self::GROUPS, 'selectedGroup' => $selectedGroup, 'title' => 'Tambah Aset']);
    }

    public function show(int $id): string
    {
        $asset = $this->assets->find($id);
        if (! $asset) throw PageNotFoundException::forPageNotFound('Aset tidak ditemukan.');
        return view('assets/show', ['asset' => $asset]);
    }

    public function store()
    {
        $data = $this->payload();
        if (! $this->validateAssetData($data, $errors)) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'errors' => $errors]);
            }
            return redirect()->back()->withInput()->with('errors', $errors);
        }
        $id = $this->assets->insert($data);
        if ($this->request->isAJAX()) {
            return $this->response->setStatusCode(201)->setJSON([
                'ok' => true,
                'id' => $id,
                'message' => 'Aset baru berhasil ditambahkan.',
            ]);
        }
        return redirect()->to('/data-aset/' . $id . '/edit')->with('success', 'Aset berhasil ditambahkan.');
    }

    public function edit(int $id): string
    {
        $asset = $this->assets->find($id);
        if (! $asset) throw PageNotFoundException::forPageNotFound('Aset tidak ditemukan.');
        return view('assets/form', ['asset' => $asset, 'groups' => self::GROUPS, 'selectedGroup' => $asset['asset_group'], 'title' => 'Edit Aset']);
    }

    public function update(int $id)
    {
        if (! $this->assets->find($id)) throw PageNotFoundException::forPageNotFound('Aset tidak ditemukan.');
        $data = $this->payload();
        if (! $this->validateAssetData($data, $errors)) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'errors' => $errors]);
            }
            return redirect()->back()->withInput()->with('errors', $errors);
        }
        $this->assets->update($id, $data);
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => true, 'message' => 'Perubahan aset berhasil disimpan.']);
        }
        return redirect()->to('/data-aset/' . $id . '/edit')->with('success', 'Perubahan aset berhasil disimpan.');
    }

    public function delete(int $id)
    {
        $asset = $this->assets->find($id);
        if (! $asset) throw PageNotFoundException::forPageNotFound('Aset tidak ditemukan.');
        $this->assets->delete($id);
        return redirect()->to('/data-aset?type=' . urlencode($asset['asset_group']))->with('success', 'Aset berhasil dihapus.');
    }

    private function payload(): array
    {
        $group = strtoupper(trim((string) $this->request->getPost('asset_group')));
        $acquired = trim((string) $this->request->getPost('acquired')) ?: null;
        return [
            'asset_group' => $group,
            'name' => strtoupper(trim((string) $this->request->getPost('name'))),
            'category' => strtoupper(trim((string) $this->request->getPost('category'))),
            'area' => in_array($group, ['RUMAH DINAS', 'KENDARAAN', 'TANAH', 'GEDUNG'], true) ? $group : 'KANTOR',
            'acquired' => $acquired,
            'year' => $acquired ? (int) substr($acquired, 0, 4) : null,
            'asset_code_simat' => trim((string) $this->request->getPost('asset_code_simat')) ?: null,
            'asset_code_jstream' => trim((string) $this->request->getPost('asset_code_jstream')) ?: null,
            'asset_number' => trim((string) $this->request->getPost('asset_number')) ?: null,
            'condition' => strtoupper(trim((string) $this->request->getPost('condition'))),
            'location' => strtoupper(trim((string) $this->request->getPost('location'))),
            'acquisition_value' => (float) $this->request->getPost('acquisition_value'),
            'benefit_end' => trim((string) $this->request->getPost('benefit_end')) ?: null,
            'useful_life_months' => (int) $this->request->getPost('useful_life_months'),
            'residual_percent' => (float) $this->request->getPost('residual_percent'),
            'residual_value' => (float) $this->request->getPost('residual_value'),
            'depreciation_base' => (float) $this->request->getPost('depreciation_base'),
            'monthly_depreciation' => (float) $this->request->getPost('monthly_depreciation'),
        ];
    }

    private function validateAssetData(array $data, ?array &$errors): bool
    {
        $errors = [];
        if (! in_array($data['asset_group'], self::GROUPS, true)) $errors['asset_group'] = 'Jenis aset tidak valid.';
        if ($data['name'] === '' || mb_strlen($data['name']) < 2) $errors['name'] = 'Nama aset wajib diisi.';
        if (! in_array($data['category'], ['FURNITURE', 'TI', 'PERALATAN', 'MESIN'], true)) $errors['category'] = 'Kategori akuntansi tidak valid.';
        if (! in_array($data['condition'], ['SEDANG DIGUNAKAN', 'RUSAK', 'HILANG', 'TIDAK DIKETAHUI'], true)) $errors['condition'] = 'Kondisi aset tidak valid.';
        if ($data['location'] === '') $errors['location'] = 'Lokasi aset wajib diisi.';
        if ($data['acquisition_value'] < 0) $errors['acquisition_value'] = 'Nilai perolehan tidak boleh negatif.';
        return $errors === [];
    }
}

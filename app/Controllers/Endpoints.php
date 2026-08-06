<?php

namespace App\Controllers;

use App\Libraries\EndpointRepository;
use App\Libraries\XlsxExporter;
use CodeIgniter\Exceptions\PageNotFoundException;

class Endpoints extends BaseController
{
    private EndpointRepository $endpoints;

    public function __construct()
    {
        $this->endpoints = new EndpointRepository();
    }

    public function index(string $scope): string
    {
        [$unit, $defaultBranch, $title] = $this->scope($scope);
        $branches = $this->endpoints->branches();
        $branch = $defaultBranch;
        if ($unit === '') {
            $requestedBranch = trim((string) $this->request->getGet('branch'));
            $allowedBranches = array_merge(['Kanwil'], $branches);
            $branch = $requestedBranch !== '' && strtoupper($requestedBranch) !== 'ALL' && in_array($requestedBranch, $allowedBranches, true)
                ? $requestedBranch
                : '';
        }
        $type = strtoupper(trim((string) $this->request->getGet('type')));
        $type = in_array($type, ['PC', 'LAPTOP'], true) ? $type : '';
        $search = trim((string) $this->request->getGet('q'));
        $page = max(1, (int) $this->request->getGet('page'));
        $perPage = (int) $this->request->getGet('per_page');
        $perPage = in_array($perPage, [15, 50, 100], true) ? $perPage : 15;
        $result = $this->endpoints->search($unit, $branch, $type, $search, $page, $perPage);

        return view('endpoints/index', [
            'scope' => $scope, 'unit' => $unit, 'title' => $title, 'branch' => $branch, 'branches' => $branches,
            'endpoints' => $result['rows'], 'total' => $result['total'], 'pages' => $result['pages'], 'page' => $page,
            'type' => $type, 'search' => $search, 'perPage' => $perPage,
        ]);
    }

    public function monitoring(): string
    {
        return view('endpoints/monitoring', [
            'summary' => $this->endpoints->monitoringSummary(),
        ]);
    }

    public function exportMonitoring()
    {
        $summary = $this->endpoints->monitoringSummary();
        $generatedAt = date('d-m-Y H:i');

        $unitRows = array_map(static fn (array $unit): array => [
            $unit['name'],
            ['value' => $unit['total'], 'style' => 'number'],
            ['value' => $unit['joined'], 'style' => 'number'],
            ['value' => $unit['total'] - $unit['joined'], 'style' => 'number'],
            ['value' => $unit['complete'], 'style' => 'number'],
            ['value' => $unit['total'] - $unit['complete'], 'style' => 'number'],
            ['value' => $unit['total'] ? $unit['joined'] / $unit['total'] : 0, 'style' => 'percent'],
            ['value' => $unit['total'] ? $unit['complete'] / $unit['total'] : 0, 'style' => 'percent'],
        ], $summary['units']);

        $detailRows = array_map(static fn (array $row): array => [
            $row['_unit_name'], $row['organization_unit'], $row['branch_name'], $row['ip_address'], $row['hostname'],
            $row['employee_status'], $row['endpoint_type'], $row['serial_number'], $row['brand'],
            ['value' => $row['procurement_year'], 'style' => 'number'], $row['asset_number'], $row['user_name'],
            $row['notes'], $row['operating_system'], $row['domain_user'], $row['join_domain'], $row['login_domain'],
            $row['_joined'] ? 'Sudah Join' : 'Belum Join', $row['_complete'] ? 'Lengkap' : 'Belum Lengkap',
            implode(', ', $row['_missing_fields']),
        ], $summary['records']);

        $missingRows = [];
        foreach ($summary['missingFields'] as $field => $count) {
            $missingRows[] = [
                $summary['fieldLabels'][$field],
                ['value' => $count, 'style' => 'number'],
                ['value' => $summary['total'] ? $count / $summary['total'] : 0, 'style' => 'percent'],
            ];
        }

        $bytes = (new XlsxExporter())->build([
            [
                'name' => 'Ringkasan Unit',
                'title' => 'Monitoring Endpoint Jamkrindo Kanwil Surabaya',
                'subtitle' => 'Rekap per unit kerja • Dibuat ' . $generatedAt,
                'headers' => ['Unit Kerja', 'Total Endpoint', 'Sudah Join', 'Belum Join', 'Data Lengkap', 'Belum Lengkap', '% Join', '% Lengkap'],
                'rows' => $unitRows,
                'widths' => [28, 15, 14, 14, 15, 16, 12, 12],
            ],
            [
                'name' => 'Detail Endpoint',
                'title' => 'Detail Data Endpoint',
                'subtitle' => 'Seluruh endpoint dan status kelengkapan • Dibuat ' . $generatedAt,
                'headers' => ['Unit Kerja', 'Organisasi', 'Cabang', 'IP Address', 'Hostname', 'Status Karyawan', 'Tipe', 'Serial Number', 'Brand', 'Tahun', 'Nomor Aset', 'User Pengguna', 'Keterangan', 'Operating System', 'User Domain', 'Join Domain', 'Login Domain', 'Status Join', 'Kelengkapan', 'Field Kosong'],
                'rows' => $detailRows,
                'widths' => [24, 13, 18, 16, 20, 18, 12, 20, 15, 10, 20, 22, 28, 20, 20, 14, 14, 15, 16, 42],
            ],
            [
                'name' => 'Field Kosong',
                'title' => 'Rekap Kelengkapan Field Endpoint',
                'subtitle' => 'Jumlah field kosong dari ' . number_format($summary['total'], 0, ',', '.') . ' endpoint • Dibuat ' . $generatedAt,
                'headers' => ['Nama Field', 'Jumlah Kosong', '% Endpoint'],
                'rows' => $missingRows,
                'widths' => [32, 18, 16],
            ],
        ]);

        $filename = 'Monitoring_Endpoint_' . date('Y-m-d_His') . '.xlsx';
        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('Content-Length', (string) strlen($bytes))
            ->setBody($bytes);
    }

    public function store()
    {
        $data = $this->payload();
        if (! $this->validateEndpoint($data, $errors)) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'errors' => $errors]);
        }
        $id = $this->endpoints->insert($data);
        return $this->response->setStatusCode(201)->setJSON(['ok' => true, 'id' => $id, 'message' => 'Endpoint berhasil ditambahkan.']);
    }

    public function update(int $id)
    {
        if (! $this->endpoints->find($id)) throw PageNotFoundException::forPageNotFound('Endpoint tidak ditemukan.');
        $data = $this->payload();
        if (! $this->validateEndpoint($data, $errors)) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'errors' => $errors]);
        }
        $this->endpoints->update($id, $data);
        return $this->response->setJSON(['ok' => true, 'message' => 'Endpoint berhasil diperbarui.']);
    }

    public function delete(int $id)
    {
        if (! $this->endpoints->find($id)) throw PageNotFoundException::forPageNotFound('Endpoint tidak ditemukan.');
        $this->endpoints->delete($id);
        return redirect()->back()->with('success', 'Endpoint berhasil dihapus.');
    }

    private function scope(string $scope): array
    {
        return match ($scope) {
            'endpoint-kanwil' => ['', '', 'Endpoint Kanwil'],
            'kanwil-surabaya' => ['KANWIL', 'Kanwil', 'Kanwil Surabaya'],
            'cabang-surabaya' => ['CABANG', 'Surabaya', 'Cabang Surabaya'],
            'cabang-malang' => ['CABANG', 'Malang', 'Cabang Malang'],
            'cabang-kediri' => ['CABANG', 'Kediri', 'Cabang Kediri'],
            'cabang-madiun' => ['CABANG', 'Madiun', 'Cabang Madiun'],
            'cabang-banyuwangi' => ['CABANG', 'Banyuwangi', 'Cabang Banyuwangi'],
            'kup-jember' => ['CABANG', 'KUP Jember', 'KUP Jember'],
            'kup-bojonegoro' => ['CABANG', 'KUP Bojonegoro', 'KUP Bojonegoro'],
            'kup-pamekasan' => ['CABANG', 'KUP Pamekasan', 'KUP Pamekasan'],
            default => throw PageNotFoundException::forPageNotFound('Unit operasional tidak ditemukan.'),
        };
    }

    private function payload(): array
    {
        return [
            'organization_unit' => strtoupper(trim((string) $this->request->getPost('organization_unit'))),
            'branch_name' => trim((string) $this->request->getPost('branch_name')),
            'ip_address' => $this->nullable('ip_address'),
            'hostname' => strtoupper(trim((string) $this->request->getPost('hostname'))),
            'employee_status' => $this->nullable('employee_status'),
            'endpoint_type' => strtoupper(trim((string) $this->request->getPost('endpoint_type'))),
            'serial_number' => $this->nullable('serial_number'),
            'brand' => $this->nullable('brand'),
            'procurement_year' => ($year = (int) $this->request->getPost('procurement_year')) > 0 ? $year : null,
            'asset_number' => $this->nullable('asset_number'),
            'user_name' => $this->nullable('user_name'),
            'notes' => $this->nullable('notes'),
            'operating_system' => $this->nullable('operating_system'),
            'domain_user' => $this->nullable('domain_user'),
            'join_domain' => $this->nullable('join_domain'),
            'login_domain' => $this->nullable('login_domain'),
        ];
    }

    private function nullable(string $field): ?string
    {
        $value = trim((string) $this->request->getPost($field));
        return $value === '' ? null : $value;
    }

    private function validateEndpoint(array $data, ?array &$errors): bool
    {
        $errors = [];
        if (! in_array($data['organization_unit'], ['KANWIL', 'CABANG'], true)) $errors['organization_unit'] = 'Unit organisasi tidak valid.';
        if ($data['branch_name'] === '') $errors['branch_name'] = 'Unit/cabang wajib dipilih.';
        if ($data['hostname'] === '') $errors['hostname'] = 'Hostname wajib diisi.';
        if (! in_array($data['endpoint_type'], ['PC', 'LAPTOP', 'TIDAK DIKETAHUI'], true)) $errors['endpoint_type'] = 'Tipe endpoint tidak valid.';
        if ($data['procurement_year'] !== null && ($data['procurement_year'] < 2000 || $data['procurement_year'] > 2100)) $errors['procurement_year'] = 'Tahun pengadaan tidak valid.';
        return $errors === [];
    }
}

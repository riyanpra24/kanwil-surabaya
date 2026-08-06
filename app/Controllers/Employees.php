<?php

namespace App\Controllers;

use App\Libraries\EmployeeRepository;
use CodeIgniter\Exceptions\PageNotFoundException;

class Employees extends BaseController
{
    private EmployeeRepository $employees;

    private const UNITS = [
        'kanwil-surabaya' => 'Kanwil Surabaya',
        'cabang-surabaya' => 'Cabang Surabaya',
        'cabang-malang' => 'Cabang Malang',
        'cabang-kediri' => 'Cabang Kediri',
        'cabang-madiun' => 'Cabang Madiun',
        'cabang-banyuwangi' => 'Cabang Banyuwangi',
        'kup-jember' => 'KUP Jember',
        'kup-bojonegoro' => 'KUP Bojonegoro',
        'kup-pamekasan' => 'KUP Pamekasan',
    ];

    public function __construct()
    {
        $this->employees = new EmployeeRepository();
    }

    public function index(string $unitSlug): string
    {
        $isCombined = $unitSlug === 'data-sdm-jatim';
        $unitName = $isCombined ? 'Data SDM Jatim' : (self::UNITS[$unitSlug] ?? throw PageNotFoundException::forPageNotFound('Unit karyawan tidak ditemukan.'));
        $selectedUnit = $isCombined ? strtolower(trim((string) $this->request->getGet('unit'))) : $unitSlug;
        if (! isset(self::UNITS[$selectedUnit])) $selectedUnit = '';
        $status = trim((string) $this->request->getGet('status'));
        $search = trim((string) $this->request->getGet('q'));
        $page = max(1, (int) $this->request->getGet('page'));
        $perPage = (int) $this->request->getGet('per_page');
        $perPage = in_array($perPage, [15, 50, 100], true) ? $perPage : 15;
        $result = $this->employees->search($selectedUnit, $status, $search, $page, $perPage);

        return view('employees/index', [
            'unitSlug' => $unitSlug,
            'unitName' => $unitName,
            'isCombined' => $isCombined,
            'selectedUnit' => $selectedUnit,
            'unitOptions' => self::UNITS,
            'employees' => $result['rows'],
            'total' => $result['total'],
            'pages' => $result['pages'],
            'page' => $page,
            'perPage' => $perPage,
            'search' => $search,
            'status' => $status,
            'statuses' => $this->employees->statuses($selectedUnit),
        ]);
    }

    public function store()
    {
        $data = $this->payload();
        if (! $this->validateEmployee($data, $errors)) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'errors' => $errors]);
        }
        $id = $this->employees->insert($data);
        return $this->response->setStatusCode(201)->setJSON(['ok' => true, 'id' => $id, 'message' => 'Data karyawan berhasil ditambahkan.']);
    }

    public function monitoring(): string
    {
        $selectedUnit = strtolower(trim((string) $this->request->getGet('unit')));
        if (! isset(self::UNITS[$selectedUnit])) $selectedUnit = '';

        return view('employees/monitoring', [
            'employeeSummary' => $this->employees->dashboardSummary($selectedUnit),
            'employeeUnits' => self::UNITS,
            'selectedEmployeeUnit' => $selectedUnit,
        ]);
    }

    public function update(int $id)
    {
        if (! $this->employees->find($id)) throw PageNotFoundException::forPageNotFound('Data karyawan tidak ditemukan.');
        $data = $this->payload();
        if (! $this->validateEmployee($data, $errors)) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'errors' => $errors]);
        }
        $this->employees->update($id, $data);
        return $this->response->setJSON(['ok' => true, 'message' => 'Data karyawan berhasil diperbarui.']);
    }

    public function delete(int $id)
    {
        if (! $this->employees->find($id)) throw PageNotFoundException::forPageNotFound('Data karyawan tidak ditemukan.');
        $this->employees->delete($id);
        return redirect()->back()->with('success', 'Data karyawan berhasil dihapus.');
    }

    private function payload(): array
    {
        $unitSlug = strtolower(trim((string) $this->request->getPost('unit_slug')));
        return [
            'unit_slug' => $unitSlug,
            'unit_name' => self::UNITS[$unitSlug] ?? '',
            'employee_number' => $this->nullable('employee_number'),
            'full_name' => trim((string) $this->request->getPost('full_name')),
            'gender' => strtoupper(trim((string) $this->request->getPost('gender'))),
            'division' => $this->nullable('division'),
            'position' => $this->nullable('position'),
            'employment_status' => $this->nullable('employment_status'),
            'phone' => $this->nullable('phone'),
            'corporate_email' => $this->nullable('corporate_email'),
        ];
    }

    private function nullable(string $field): ?string
    {
        $value = trim((string) $this->request->getPost($field));
        return $value === '' ? null : $value;
    }

    private function validateEmployee(array $data, ?array &$errors): bool
    {
        $errors = [];
        if (! isset(self::UNITS[$data['unit_slug']])) $errors['unit_slug'] = 'Unit kerja tidak valid.';
        if ($data['full_name'] === '') $errors['full_name'] = 'Nama lengkap wajib diisi.';
        if ($data['gender'] !== '' && ! in_array($data['gender'], ['L', 'P'], true)) $errors['gender'] = 'Gender harus L atau P.';
        if ($data['corporate_email'] && ! filter_var($data['corporate_email'], FILTER_VALIDATE_EMAIL)) $errors['corporate_email'] = 'Format email tidak valid.';
        return $errors === [];
    }
}

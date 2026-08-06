<?php

namespace App\Libraries;

use PDO;

class EmployeeRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = new PDO('sqlite:' . WRITEPATH . 'assets.sqlite');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->initialize();
    }

    private function initialize(): void
    {
        $this->db->exec('CREATE TABLE IF NOT EXISTS employees (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            unit_slug TEXT NOT NULL,
            unit_name TEXT NOT NULL,
            employee_number TEXT NULL,
            full_name TEXT NOT NULL,
            gender TEXT NULL,
            division TEXT NULL,
            position TEXT NULL,
            employment_status TEXT NULL,
            phone TEXT NULL,
            corporate_email TEXT NULL,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )');
        $this->db->exec('CREATE INDEX IF NOT EXISTS idx_employees_unit ON employees(unit_slug)');
        $this->db->exec('CREATE INDEX IF NOT EXISTS idx_employees_name ON employees(full_name)');
        $this->db->exec('CREATE INDEX IF NOT EXISTS idx_employees_status ON employees(employment_status)');

        if ((int) $this->db->query('SELECT COUNT(*) FROM employees')->fetchColumn() === 0) {
            $this->seed();
        }
    }

    private function seed(): void
    {
        $path = WRITEPATH . 'employee-seed.json';
        if (! is_file($path)) return;
        $source = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $now = date('Y-m-d H:i:s');
        $statement = $this->db->prepare('INSERT INTO employees (unit_slug,unit_name,employee_number,full_name,gender,division,position,employment_status,phone,corporate_email,created_at,updated_at) VALUES (:unit_slug,:unit_name,:employee_number,:full_name,:gender,:division,:position,:employment_status,:phone,:corporate_email,:created_at,:updated_at)');
        $this->db->beginTransaction();
        foreach ($source['records'] ?? [] as $row) {
            $row['created_at'] = $row['updated_at'] = $now;
            unset($row['source_section']);
            $statement->execute($row);
        }
        $this->db->commit();
    }

    public function find(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM employees WHERE id=:id');
        $statement->execute(['id' => $id]);
        return $statement->fetch() ?: null;
    }

    public function search(string $unitSlug, string $status, string $search, int $page, int $perPage): array
    {
        $where = [];
        $params = [];
        if ($unitSlug !== '') {
            $where[] = 'unit_slug=:unit_slug';
            $params['unit_slug'] = $unitSlug;
        }
        if ($status !== '') {
            $where[] = 'employment_status=:status';
            $params['status'] = $status;
        }
        if ($search !== '') {
            $where[] = '(full_name LIKE :search OR employee_number LIKE :search OR division LIKE :search OR position LIKE :search OR phone LIKE :search OR corporate_email LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $count = $this->db->prepare('SELECT COUNT(*) FROM employees' . $whereSql);
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $query = $this->db->prepare('SELECT * FROM employees' . $whereSql . ' ORDER BY full_name, id LIMIT :limit OFFSET :offset');
        foreach ($params as $key => $value) $query->bindValue(':' . $key, $value);
        $query->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $query->bindValue(':offset', max(0, ($page - 1) * $perPage), PDO::PARAM_INT);
        $query->execute();
        return ['rows' => $query->fetchAll(), 'total' => $total, 'pages' => max(1, (int) ceil($total / $perPage))];
    }

    public function statuses(string $unitSlug): array
    {
        $where = $unitSlug !== '' ? 'unit_slug=:unit_slug AND ' : '';
        $statement = $this->db->prepare("SELECT DISTINCT employment_status FROM employees WHERE {$where}employment_status IS NOT NULL AND TRIM(employment_status)<>'' ORDER BY employment_status");
        $statement->execute($unitSlug !== '' ? ['unit_slug' => $unitSlug] : []);
        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }

    public function dashboardSummary(string $unitSlug = ''): array
    {
        $unitStatement = $this->db->query('SELECT unit_slug, unit_name, COUNT(*) AS total FROM employees GROUP BY unit_slug, unit_name ORDER BY unit_name');
        $unitCounts = [];
        foreach ($unitStatement->fetchAll() as $unit) {
            $unitCounts[$unit['unit_slug']] = ['name' => $unit['unit_name'], 'total' => (int) $unit['total']];
        }

        $query = $this->db->prepare('SELECT position, division, employment_status FROM employees' . ($unitSlug !== '' ? ' WHERE unit_slug=:unit_slug' : ''));
        $query->execute($unitSlug !== '' ? ['unit_slug' => $unitSlug] : []);
        $rows = $query->fetchAll();
        $categories = ['Karyawan Tetap', 'Calon Karyawan', 'PKWT Umum', 'PKWT ELH', 'Magang', 'Admin', 'Driver', 'OB/CS', 'Security'];
        $positions = [];
        $totals = array_fill_keys($categories, 0);

        foreach ($rows as $row) {
            $position = trim((string) ($row['position'] ?? '')) ?: trim((string) ($row['division'] ?? '')) ?: 'Posisi belum diisi';
            $positionKey = mb_strtolower(preg_replace('/\s+/', ' ', $position));
            if (! isset($positions[$positionKey])) {
                $positions[$positionKey] = ['label' => $position, 'total' => 0] + array_fill_keys($categories, 0);
            }
            $category = $this->employeeStatusCategory((string) ($row['employment_status'] ?? ''), $position, (string) ($row['division'] ?? ''));
            $positions[$positionKey][$category]++;
            $positions[$positionKey]['total']++;
            $totals[$category]++;
        }

        uasort($positions, static fn (array $a, array $b): int => $b['total'] <=> $a['total'] ?: strcasecmp($a['label'], $b['label']));
        $totalEmployees = count($rows);
        $outsourcing = $totals['Admin'] + $totals['Driver'] + $totals['OB/CS'] + $totals['Security'];
        $statusGroups = [
            'Karyawan Tetap' => $totals['Karyawan Tetap'],
            'Calon Karyawan' => $totals['Calon Karyawan'],
            'PKWT' => $totals['PKWT Umum'] + $totals['PKWT ELH'],
            'Magang' => $totals['Magang'],
            'Outsourcing' => $outsourcing,
        ];

        return [
            'total' => $totalEmployees,
            'categories' => $categories,
            'totals' => $totals,
            'positions' => array_values($positions),
            'unitCounts' => $unitCounts,
            'statusGroups' => $statusGroups,
            'permanent' => $totals['Karyawan Tetap'],
            'contract' => $totals['Calon Karyawan'] + $totals['PKWT Umum'] + $totals['PKWT ELH'] + $totals['Magang'],
            'outsourcing' => $outsourcing,
        ];
    }

    private function employeeStatusCategory(string $status, string $position, string $division): string
    {
        $statusText = mb_strtolower(trim($status));
        $roleText = mb_strtolower(trim($status . ' ' . $position . ' ' . $division));
        $isOutsourcing = str_contains($statusText, 'outsourc') || str_contains($statusText, 'tenaga alih daya') || $statusText === 'os';

        if ($isOutsourcing) {
            if (str_contains($roleText, 'security')) return 'Security';
            if (str_contains($roleText, 'driver')) return 'Driver';
            if (preg_match('/\b(ob|office boy|cleaning service|cs)\b/', $roleText)) return 'OB/CS';
            return 'Admin';
        }
        if (str_contains($statusText, 'calon')) return 'Calon Karyawan';
        if (str_contains($statusText, 'tetap') || str_contains($statusText, 'pkwtt')) return 'Karyawan Tetap';
        if (str_contains($statusText, 'elh')) return 'PKWT ELH';
        if (str_contains($statusText, 'pkwt')) return 'PKWT Umum';
        if (str_contains($statusText, 'magang')) return 'Magang';

        return 'PKWT Umum';
    }

    public function insert(array $data): int
    {
        $data['created_at'] = $data['updated_at'] = date('Y-m-d H:i:s');
        $columns = array_keys($data);
        $this->db->prepare('INSERT INTO employees (' . implode(',', $columns) . ') VALUES (:' . implode(',:', $columns) . ')')->execute($data);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $sets = array_map(static fn ($column) => $column . '=:' . $column, array_keys($data));
        $data['id'] = $id;
        return $this->db->prepare('UPDATE employees SET ' . implode(',', $sets) . ' WHERE id=:id')->execute($data);
    }

    public function delete(int $id): bool
    {
        return $this->db->prepare('DELETE FROM employees WHERE id=:id')->execute(['id' => $id]);
    }
}

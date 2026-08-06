<?php

namespace App\Libraries;

use PDO;

class EndpointRepository
{
    private PDO $db;

    private const COMPLETENESS_FIELDS = [
        'organization_unit' => 'Unit Organisasi',
        'branch_name' => 'Unit / Cabang',
        'ip_address' => 'IP Address',
        'hostname' => 'Hostname',
        'employee_status' => 'Status Karyawan',
        'endpoint_type' => 'Tipe Endpoint',
        'serial_number' => 'Serial Number',
        'brand' => 'Brand',
        'procurement_year' => 'Tahun Pengadaan / Sewa',
        'asset_number' => 'Nomor Aset Oracle',
        'user_name' => 'User Pengguna',
        'notes' => 'Keterangan',
        'operating_system' => 'Operating System',
        'domain_user' => 'User Domain',
        'join_domain' => 'Join Domain',
        'login_domain' => 'Login Domain',
    ];

    public function __construct()
    {
        $this->db = new PDO('sqlite:' . WRITEPATH . 'assets.sqlite');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->initialize();
    }

    private function initialize(): void
    {
        $this->db->exec('CREATE TABLE IF NOT EXISTS endpoints (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            organization_unit TEXT NOT NULL,
            branch_name TEXT NOT NULL,
            ip_address TEXT NULL,
            hostname TEXT NOT NULL,
            employee_status TEXT NULL,
            endpoint_type TEXT NOT NULL,
            serial_number TEXT NULL,
            brand TEXT NULL,
            procurement_year INTEGER NULL,
            asset_number TEXT NULL,
            user_name TEXT NULL,
            notes TEXT NULL,
            operating_system TEXT NULL,
            domain_user TEXT NULL,
            join_domain TEXT NULL,
            login_domain TEXT NULL,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )');
        $this->db->exec('CREATE INDEX IF NOT EXISTS idx_endpoints_unit ON endpoints(organization_unit, branch_name)');
        $this->db->exec('CREATE INDEX IF NOT EXISTS idx_endpoints_type ON endpoints(endpoint_type)');

        if ((int) $this->db->query('SELECT COUNT(*) FROM endpoints')->fetchColumn() === 0) {
            $this->seed();
        }
    }

    private function seed(): void
    {
        $path = WRITEPATH . 'endpoint-seed.json';
        if (! is_file($path)) return;
        $source = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $now = date('Y-m-d H:i:s');
        $statement = $this->db->prepare('INSERT INTO endpoints (organization_unit,branch_name,ip_address,hostname,employee_status,endpoint_type,serial_number,brand,procurement_year,asset_number,user_name,notes,operating_system,domain_user,join_domain,login_domain,created_at,updated_at) VALUES (:organization_unit,:branch_name,:ip_address,:hostname,:employee_status,:endpoint_type,:serial_number,:brand,:procurement_year,:asset_number,:user_name,:notes,:operating_system,:domain_user,:join_domain,:login_domain,:created_at,:updated_at)');
        $this->db->beginTransaction();
        foreach ($source['records'] ?? [] as $row) {
            $row['hostname'] = $row['hostname'] ?: 'TANPA HOSTNAME';
            $row['endpoint_type'] = $row['endpoint_type'] ?: 'TIDAK DIKETAHUI';
            $row['created_at'] = $row['updated_at'] = $now;
            $statement->execute($row);
        }
        $this->db->commit();
    }

    public function branches(): array
    {
        return $this->db->query("SELECT DISTINCT branch_name FROM endpoints WHERE organization_unit='CABANG' ORDER BY CASE WHEN branch_name='Surabaya' THEN 0 ELSE 1 END, branch_name")->fetchAll(PDO::FETCH_COLUMN);
    }

    public function all(string $unit = '', string $branch = ''): array
    {
        $where = []; $params = [];
        if ($unit !== '') { $where[] = 'organization_unit=:unit'; $params['unit'] = $unit; }
        if ($branch !== '') { $where[] = 'branch_name=:branch'; $params['branch'] = $branch; }
        $statement = $this->db->prepare('SELECT * FROM endpoints' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY hostname, id');
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function monitoringSummary(): array
    {
        $records = $this->all();
        $units = [];
        $missingFields = array_fill_keys(array_keys(self::COMPLETENESS_FIELDS), 0);
        $endpointTypes = [];
        $operatingSystems = [];
        $joined = 0;
        $complete = 0;

        foreach ($records as &$record) {
            $recordMissing = [];
            foreach (self::COMPLETENESS_FIELDS as $field => $label) {
                if (trim((string) ($record[$field] ?? '')) === '') {
                    $recordMissing[] = $label;
                    $missingFields[$field]++;
                }
            }

            $record['_joined'] = strcasecmp(trim((string) ($record['join_domain'] ?? '')), 'Done') === 0;
            $record['_complete'] = $recordMissing === [];
            $record['_missing_fields'] = $recordMissing;
            $record['_unit_name'] = $this->unitName($record);
            $record['_unit_slug'] = $this->unitSlug($record);

            $joined += $record['_joined'] ? 1 : 0;
            $complete += $record['_complete'] ? 1 : 0;

            $unitKey = $record['_unit_slug'];
            $units[$unitKey] ??= [
                'slug' => $unitKey,
                'name' => $record['_unit_name'],
                'branch' => $record['branch_name'],
                'total' => 0,
                'joined' => 0,
                'complete' => 0,
            ];
            $units[$unitKey]['total']++;
            $units[$unitKey]['joined'] += $record['_joined'] ? 1 : 0;
            $units[$unitKey]['complete'] += $record['_complete'] ? 1 : 0;

            $type = trim((string) ($record['endpoint_type'] ?? '')) ?: 'TIDAK DIKETAHUI';
            $os = trim((string) ($record['operating_system'] ?? '')) ?: 'BELUM DIISI';
            $endpointTypes[$type] = ($endpointTypes[$type] ?? 0) + 1;
            $operatingSystems[$os] = ($operatingSystems[$os] ?? 0) + 1;
        }
        unset($record);

        $unitOrder = array_flip([
            'kanwil-surabaya', 'cabang-surabaya', 'cabang-malang', 'cabang-kediri',
            'cabang-madiun', 'cabang-banyuwangi', 'kup-jember', 'kup-bojonegoro', 'kup-pamekasan',
        ]);
        uasort($units, static fn (array $left, array $right): int =>
            ($unitOrder[$left['slug']] ?? PHP_INT_MAX) <=> ($unitOrder[$right['slug']] ?? PHP_INT_MAX)
        );
        arsort($missingFields);
        arsort($endpointTypes);
        arsort($operatingSystems);

        $total = count($records);
        return [
            'total' => $total,
            'joined' => $joined,
            'notJoined' => $total - $joined,
            'complete' => $complete,
            'incomplete' => $total - $complete,
            'joinRate' => $total ? $joined / $total : 0,
            'completionRate' => $total ? $complete / $total : 0,
            'units' => array_values($units),
            'missingFields' => $missingFields,
            'fieldLabels' => self::COMPLETENESS_FIELDS,
            'endpointTypes' => $endpointTypes,
            'operatingSystems' => $operatingSystems,
            'records' => $records,
        ];
    }

    private function unitName(array $record): string
    {
        if (strtoupper(trim((string) ($record['organization_unit'] ?? ''))) === 'KANWIL') {
            return 'Kanwil Surabaya';
        }
        $branch = trim((string) ($record['branch_name'] ?? '')) ?: 'Tidak Diketahui';
        return str_starts_with(strtoupper($branch), 'KUP ') ? $branch : 'Cabang ' . $branch;
    }

    private function unitSlug(array $record): string
    {
        if (strtoupper(trim((string) ($record['organization_unit'] ?? ''))) === 'KANWIL') {
            return 'kanwil-surabaya';
        }
        $branch = strtolower(trim((string) ($record['branch_name'] ?? '')));
        $prefix = str_starts_with($branch, 'kup ') ? '' : 'cabang-';
        return $prefix . preg_replace('/[^a-z0-9]+/', '-', $branch);
    }

    public function find(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM endpoints WHERE id=:id');
        $statement->execute(['id' => $id]);
        return $statement->fetch() ?: null;
    }

    public function search(string $unit, string $branch, string $type, string $search, int $page, int $perPage): array
    {
        $where = [];
        $params = [];
        if ($unit !== '') { $where[] = 'organization_unit=:unit'; $params['unit'] = $unit; }
        if ($branch !== '') { $where[] = 'branch_name=:branch'; $params['branch'] = $branch; }
        if ($type !== '') { $where[] = 'UPPER(endpoint_type)=:type'; $params['type'] = $type; }
        if ($search !== '') {
            $where[] = '(hostname LIKE :search OR ip_address LIKE :search OR serial_number LIKE :search OR asset_number LIKE :search OR user_name LIKE :search OR domain_user LIKE :search OR brand LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $count = $this->db->prepare('SELECT COUNT(*) FROM endpoints' . $whereSql);
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $query = $this->db->prepare('SELECT * FROM endpoints' . $whereSql . ' ORDER BY branch_name, hostname, id LIMIT :limit OFFSET :offset');
        foreach ($params as $key => $value) $query->bindValue(':' . $key, $value);
        $query->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $query->bindValue(':offset', max(0, ($page - 1) * $perPage), PDO::PARAM_INT);
        $query->execute();
        return ['rows' => $query->fetchAll(), 'total' => $total, 'pages' => max(1, (int) ceil($total / $perPage))];
    }

    public function insert(array $data): int
    {
        $data['created_at'] = $data['updated_at'] = date('Y-m-d H:i:s');
        $columns = array_keys($data);
        $this->db->prepare('INSERT INTO endpoints (' . implode(',', $columns) . ') VALUES (:' . implode(',:', $columns) . ')')->execute($data);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $sets = array_map(static fn ($column) => $column . '=:' . $column, array_keys($data));
        $data['id'] = $id;
        return $this->db->prepare('UPDATE endpoints SET ' . implode(',', $sets) . ' WHERE id=:id')->execute($data);
    }

    public function delete(int $id): bool
    {
        return $this->db->prepare('DELETE FROM endpoints WHERE id=:id')->execute(['id' => $id]);
    }
}

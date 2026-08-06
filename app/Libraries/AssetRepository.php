<?php

namespace App\Libraries;

use PDO;

class AssetRepository
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
        $this->db->exec('CREATE TABLE IF NOT EXISTS assets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            asset_group TEXT NOT NULL,
            name TEXT NOT NULL,
            category TEXT NOT NULL,
            area TEXT NOT NULL,
            acquired TEXT NULL,
            year INTEGER NULL,
            asset_code_simat TEXT NULL,
            asset_code_jstream TEXT NULL,
            asset_number TEXT NULL,
            condition TEXT NOT NULL,
            location TEXT NOT NULL,
            acquisition_value REAL NOT NULL DEFAULT 0,
            benefit_end TEXT NULL,
            useful_life_months INTEGER NOT NULL DEFAULT 0,
            residual_percent REAL NOT NULL DEFAULT 0,
            residual_value REAL NOT NULL DEFAULT 0,
            depreciation_base REAL NOT NULL DEFAULT 0,
            monthly_depreciation REAL NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )');
        $this->db->exec('CREATE INDEX IF NOT EXISTS idx_assets_group ON assets(asset_group)');
        $this->db->exec('CREATE INDEX IF NOT EXISTS idx_assets_condition ON assets(condition)');

        if ((int) $this->db->query('SELECT COUNT(*) FROM assets')->fetchColumn() === 0) {
            $this->seedFromJson();
        }
    }

    private function seedFromJson(): void
    {
        $source = json_decode((string) file_get_contents(WRITEPATH . 'asset-dashboard-data.json'), true, 512, JSON_THROW_ON_ERROR);
        $sql = 'INSERT INTO assets (asset_group,name,category,area,acquired,year,asset_code_simat,asset_code_jstream,asset_number,condition,location,acquisition_value,benefit_end,useful_life_months,residual_percent,residual_value,depreciation_base,monthly_depreciation,created_at,updated_at) VALUES (:asset_group,:name,:category,:area,:acquired,:year,:asset_code_simat,:asset_code_jstream,:asset_number,:condition,:location,:acquisition_value,:benefit_end,:useful_life_months,:residual_percent,:residual_value,:depreciation_base,:monthly_depreciation,:created_at,:updated_at)';
        $statement = $this->db->prepare($sql);
        $now = date('Y-m-d H:i:s');
        $this->db->beginTransaction();
        foreach ($source['records'] as $asset) {
            $group = $asset['area'] !== 'KANTOR' ? $asset['area'] : $asset['category'];
            $statement->execute([
                'asset_group' => $group, 'name' => $asset['name'], 'category' => $asset['category'], 'area' => $asset['area'],
                'acquired' => $asset['acquired'], 'year' => $asset['year'], 'asset_code_simat' => $asset['assetCodeSimat'] ?: null,
                'asset_code_jstream' => $asset['assetCodeJstream'] ?: null, 'asset_number' => $asset['assetNumber'] ?: null,
                'condition' => $asset['condition'] === '#N/A' ? 'TIDAK DIKETAHUI' : $asset['condition'], 'location' => $asset['location'],
                'acquisition_value' => $asset['acquisitionValue'], 'benefit_end' => $asset['benefitEnd'],
                'useful_life_months' => $asset['usefulLifeMonths'], 'residual_percent' => $asset['residualPercent'],
                'residual_value' => $asset['residualValue'], 'depreciation_base' => $asset['depreciationBase'],
                'monthly_depreciation' => $asset['monthlyDepreciation'], 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
        $this->db->commit();
    }

    public function all(): array
    {
        return $this->db->query('SELECT * FROM assets ORDER BY id')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM assets WHERE id = :id');
        $statement->execute(['id' => $id]);
        return $statement->fetch() ?: null;
    }

    public function search(string $group = '', string $search = '', string $condition = '', int $page = 1, int $perPage = 15): array
    {
        $where = []; $params = [];
        if ($group !== '') { $where[] = 'asset_group = :asset_group'; $params['asset_group'] = $group; }
        if ($condition !== '') { $where[] = 'condition = :condition'; $params['condition'] = $condition; }
        if ($search !== '') { $where[] = '(name LIKE :search OR location LIKE :search OR asset_code_simat LIKE :search OR asset_code_jstream LIKE :search OR asset_number LIKE :search)'; $params['search'] = '%' . $search . '%'; }
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $count = $this->db->prepare('SELECT COUNT(*) FROM assets' . $whereSql);
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $offset = max(0, ($page - 1) * $perPage);
        $query = $this->db->prepare('SELECT * FROM assets' . $whereSql . ' ORDER BY id DESC LIMIT :limit OFFSET :offset');
        foreach ($params as $key => $value) $query->bindValue(':' . $key, $value);
        $query->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $query->bindValue(':offset', $offset, PDO::PARAM_INT);
        $query->execute();
        return ['rows' => $query->fetchAll(), 'total' => $total, 'pages' => max(1, (int) ceil($total / $perPage))];
    }

    public function insert(array $data): int
    {
        $data['created_at'] = $data['updated_at'] = date('Y-m-d H:i:s');
        $columns = array_keys($data);
        $sql = 'INSERT INTO assets (' . implode(',', $columns) . ') VALUES (:' . implode(',:', $columns) . ')';
        $this->db->prepare($sql)->execute($data);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $sets = array_map(static fn ($column) => $column . '=:' . $column, array_keys($data));
        $data['id'] = $id;
        return $this->db->prepare('UPDATE assets SET ' . implode(',', $sets) . ' WHERE id=:id')->execute($data);
    }

    public function delete(int $id): bool
    {
        $statement = $this->db->prepare('DELETE FROM assets WHERE id=:id');
        return $statement->execute(['id' => $id]);
    }
}

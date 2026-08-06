<?php

namespace App\Controllers;

use App\Libraries\AssetRepository;

class Dashboard extends BaseController
{
    public function index(): string
    {
        $rows = (new AssetRepository())->all();
        $assets = array_map(static fn ($row) => [
            'no' => $row['id'], 'name' => $row['name'], 'category' => $row['category'], 'area' => $row['area'],
            'acquired' => $row['acquired'], 'year' => $row['year'], 'assetCodeSimat' => $row['asset_code_simat'],
            'assetCodeJstream' => $row['asset_code_jstream'], 'assetNumber' => $row['asset_number'],
            'condition' => $row['condition'], 'location' => $row['location'], 'acquisitionValue' => (float) $row['acquisition_value'],
            'benefitEnd' => $row['benefit_end'], 'usefulLifeMonths' => (int) $row['useful_life_months'],
            'residualPercent' => (float) $row['residual_percent'], 'residualValue' => (float) $row['residual_value'],
            'depreciationBase' => (float) $row['depreciation_base'], 'monthlyDepreciation' => (float) $row['monthly_depreciation'],
        ], $rows);
        return view('dashboard', [
            'summary' => $this->summarize($assets), 'assets' => $assets,
            'source' => 'Database Aset', 'generatedAt' => date(DATE_ATOM),
        ]);
    }

    private function summarize(array $assets): array
    {
        $countBy = static function (array $rows, string $key): array {
            $counts = [];
            foreach ($rows as $row) { $value = (string) ($row[$key] ?: 'TIDAK DIKETAHUI'); $counts[$value] = ($counts[$value] ?? 0) + 1; }
            arsort($counts); return array_map(null, array_keys($counts), array_values($counts));
        };
        $valueBy = static function (array $rows, string $key): array {
            $values = [];
            foreach ($rows as $row) { $value = (string) ($row[$key] ?: 'TIDAK DIKETAHUI'); $values[$value] = ($values[$value] ?? 0) + (float) $row['acquisitionValue']; }
            arsort($values); return array_map(null, array_keys($values), array_values($values));
        };
        $now = strtotime('2026-08-04'); $nextYear = strtotime('2027-08-04');
        $lifecycle = ['expired' => 0, 'expiringWithinYear' => 0, 'activeOverYear' => 0, 'unknown' => 0];
        foreach ($assets as $asset) {
            $end = $asset['benefitEnd'] ? strtotime($asset['benefitEnd']) : false;
            if (! $end) $lifecycle['unknown']++; elseif ($end < $now) $lifecycle['expired']++; elseif ($end <= $nextYear) $lifecycle['expiringWithinYear']++; else $lifecycle['activeOverYear']++;
        }
        $totalValue = array_sum(array_column($assets, 'acquisitionValue'));
        return [
            'totalAssets' => count($assets), 'totalAcquisitionValue' => $totalValue,
            'totalMonthlyDepreciation' => array_sum(array_column($assets, 'monthlyDepreciation')),
            'averageAssetValue' => $assets ? $totalValue / count($assets) : 0,
            'conditionCounts' => $countBy($assets, 'condition'), 'categoryCounts' => $countBy($assets, 'category'),
            'categoryValues' => $valueBy($assets, 'category'), 'locationCounts' => $countBy($assets, 'location'),
            'assetTypeCounts' => $countBy($assets, 'name'), 'yearCounts' => $countBy($assets, 'year'), 'lifecycle' => $lifecycle,
        ];
    }

}

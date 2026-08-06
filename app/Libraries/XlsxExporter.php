<?php

namespace App\Libraries;

use RuntimeException;
use ZipArchive;

class XlsxExporter
{
    public function build(array $sheets): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Ekstensi ZIP PHP tidak tersedia.');
        }

        $directory = WRITEPATH . 'cache';
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException('Folder cache ekspor tidak dapat dibuat.');
        }
        $path = tempnam($directory, 'xlsx-');
        if ($path === false) throw new RuntimeException('File sementara ekspor tidak dapat dibuat.');

        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            @unlink($path);
            throw new RuntimeException('Workbook Excel tidak dapat dibuat.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypes(count($sheets)));
        $zip->addFromString('_rels/.rels', $this->rootRelationships());
        $zip->addFromString('xl/workbook.xml', $this->workbook($sheets));
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationships(count($sheets)));
        $zip->addFromString('xl/styles.xml', $this->styles());
        foreach (array_values($sheets) as $index => $sheet) {
            $zip->addFromString('xl/worksheets/sheet' . ($index + 1) . '.xml', $this->worksheet($sheet));
        }
        $zip->close();

        $bytes = file_get_contents($path);
        @unlink($path);
        if ($bytes === false) throw new RuntimeException('Workbook Excel tidak dapat dibaca.');
        return $bytes;
    }

    private function contentTypes(int $sheetCount): string
    {
        $worksheets = '';
        for ($index = 1; $index <= $sheetCount; $index++) {
            $worksheets .= '<Override PartName="/xl/worksheets/sheet' . $index . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . $worksheets . '</Types>';
    }

    private function rootRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private function workbook(array $sheets): string
    {
        $xml = '';
        foreach (array_values($sheets) as $index => $sheet) {
            $name = mb_substr((string) ($sheet['name'] ?? 'Sheet ' . ($index + 1)), 0, 31);
            $xml .= '<sheet name="' . $this->escape($name) . '" sheetId="' . ($index + 1) . '" r:id="rId' . ($index + 1) . '"/>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<bookViews><workbookView activeTab="0"/></bookViews><sheets>' . $xml . '</sheets></workbook>';
    }

    private function workbookRelationships(int $sheetCount): string
    {
        $xml = '';
        for ($index = 1; $index <= $sheetCount; $index++) {
            $xml .= '<Relationship Id="rId' . $index . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $index . '.xml"/>';
        }
        $xml .= '<Relationship Id="rId' . ($sheetCount + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $xml . '</Relationships>';
    }

    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="2"><numFmt numFmtId="164" formatCode="#,##0"/><numFmt numFmtId="165" formatCode="0.0%"/></numFmts>'
            . '<fonts count="3"><font><sz val="10"/><name val="Aptos"/></font><font><b/><sz val="18"/><color rgb="FFFFFFFF"/><name val="Aptos Display"/></font><font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Aptos"/></font></fonts>'
            . '<fills count="4"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF092B68"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FF0875DF"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left/><right/><top/><bottom style="thin"><color rgb="FFE1E9F2"/></bottom><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="7">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="2" fillId="3" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            . '<xf numFmtId="165" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            . '</cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    }

    private function worksheet(array $sheet): string
    {
        $headers = array_values($sheet['headers'] ?? []);
        $rows = array_values($sheet['rows'] ?? []);
        $columns = max(1, count($headers));
        $lastColumn = $this->columnName($columns);
        $lastRow = max(4, count($rows) + 4);
        $widths = $sheet['widths'] ?? [];
        $columnXml = '';
        for ($index = 1; $index <= $columns; $index++) {
            $width = (float) ($widths[$index - 1] ?? 18);
            $columnXml .= '<col min="' . $index . '" max="' . $index . '" width="' . max(8, min(45, $width)) . '" customWidth="1"/>';
        }

        $sheetRows = '<row r="1" ht="30" customHeight="1">' . $this->cell('A1', (string) ($sheet['title'] ?? ''), 1) . '</row>';
        $sheetRows .= '<row r="2" ht="21" customHeight="1">' . $this->cell('A2', (string) ($sheet['subtitle'] ?? ''), 2) . '</row>';
        $headerCells = '';
        foreach ($headers as $index => $header) $headerCells .= $this->cell($this->columnName($index + 1) . '4', $header, 3);
        $sheetRows .= '<row r="4" ht="26" customHeight="1">' . $headerCells . '</row>';

        foreach ($rows as $rowIndex => $row) {
            $excelRow = $rowIndex + 5;
            $cells = '';
            foreach (array_values($row) as $columnIndex => $value) {
                $style = 4;
                if (is_array($value) && array_key_exists('value', $value)) {
                    $style = match ($value['style'] ?? '') { 'number' => 5, 'percent' => 6, default => 4 };
                    $value = $value['value'];
                } elseif (is_int($value) || is_float($value)) {
                    $style = 5;
                }
                $cells .= $this->cell($this->columnName($columnIndex + 1) . $excelRow, $value, $style);
            }
            $sheetRows .= '<row r="' . $excelRow . '" ht="21" customHeight="1">' . $cells . '</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<dimension ref="A1:' . $lastColumn . $lastRow . '"/>'
            . '<sheetViews><sheetView workbookViewId="0" showGridLines="0"><pane ySplit="4" topLeftCell="A5" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="15"/><cols>' . $columnXml . '</cols><sheetData>' . $sheetRows . '</sheetData>'
            . '<mergeCells count="2"><mergeCell ref="A1:' . $lastColumn . '1"/><mergeCell ref="A2:' . $lastColumn . '2"/></mergeCells>'
            . '<autoFilter ref="A4:' . $lastColumn . $lastRow . '"/><pageMargins left="0.3" right="0.3" top="0.5" bottom="0.5" header="0.2" footer="0.2"/>'
            . '</worksheet>';
    }

    private function cell(string $reference, mixed $value, int $style): string
    {
        if (is_int($value) || is_float($value)) {
            return '<c r="' . $reference . '" s="' . $style . '"><v>' . $value . '</v></c>';
        }
        return '<c r="' . $reference . '" t="inlineStr" s="' . $style . '"><is><t xml:space="preserve">' . $this->escape((string) $value) . '</t></is></c>';
    }

    private function columnName(int $column): string
    {
        $name = '';
        while ($column > 0) {
            $column--;
            $name = chr(65 + ($column % 26)) . $name;
            $column = intdiv($column, 26);
        }
        return $name;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}

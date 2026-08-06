import fs from 'node:fs/promises';
import { FileBlob, SpreadsheetFile } from '@oai/artifact-tool';

const source = 'C:/Users/Jamkrindo/Downloads/Daftar Endpoint Kanwil Surabaya.xlsx';
const workbook = await SpreadsheetFile.importXlsx(await FileBlob.load(source));

const summary = await workbook.inspect({
  kind: 'workbook,sheet,table,region',
  maxChars: 20000,
  tableMaxRows: 15,
  tableMaxCols: 20,
  tableMaxCellChars: 160,
});
console.log(summary.ndjson);

const extracted = [];
for (let index = 0; index < workbook.worksheets.items.length; index += 1) {
  const sheet = workbook.worksheets.getItemAt(index);
  const usedRange = sheet.getUsedRange();
  const values = usedRange?.values ?? [];
  const safeName = sheet.name.replace(/[^a-z0-9]+/gi, '-').replace(/^-|-$/g, '').toLowerCase() || `sheet-${index + 1}`;
  const preview = await workbook.render({ sheetName: sheet.name, autoCrop: 'all', scale: 1, format: 'png' });
  await fs.writeFile(`preview-${safeName}.png`, new Uint8Array(await preview.arrayBuffer()));
  extracted.push({ name: sheet.name, values });
  console.log(JSON.stringify({ name: sheet.name, rows: values.length, columns: Math.max(0, ...values.map(row => row.length)), firstRows: values.slice(0, 4) }));
}

await fs.writeFile('endpoint-data.json', JSON.stringify(extracted, null, 2));
console.log(JSON.stringify({ sheetCount: workbook.worksheets.items.length }, null, 2));

import fs from 'node:fs/promises';

const sheets = JSON.parse(await fs.readFile('endpoint-data.json', 'utf8'));
const records = [];

const clean = (value) => {
  if (value === null || value === undefined) return null;
  const text = String(value).trim();
  return text === '' ? null : text;
};

for (const sheet of sheets) {
  const headerRowIndex = sheet.values.findIndex((row) => row.some((value) => /IP\s*Addres/i.test(String(value ?? ''))));
  if (headerRowIndex < 0) continue;
  const headerRow = sheet.values[headerRowIndex];
  const start = headerRow.findIndex((value) => /IP\s*Addres/i.test(String(value ?? '')));

  for (const sourceRow of sheet.values.slice(headerRowIndex + 1)) {
    const row = sourceRow.slice(start, start + 14);
    const hostname = clean(row[1]);
    if (!hostname && !clean(row[0]) && !clean(row[4])) continue;

    records.push({
      organization_unit: sheet.name === 'Kanwil' ? 'KANWIL' : 'CABANG',
      branch_name: sheet.name,
      ip_address: clean(row[0]),
      hostname: hostname || 'TANPA HOSTNAME',
      employee_status: clean(row[2]),
      endpoint_type: clean(row[3])?.toUpperCase() || 'TIDAK DIKETAHUI',
      serial_number: clean(row[4]),
      brand: clean(row[5]),
      procurement_year: Number.isFinite(Number(row[6])) && Number(row[6]) > 0 ? Number(row[6]) : null,
      asset_number: clean(row[7]),
      user_name: clean(row[8]),
      notes: clean(row[9]),
      operating_system: clean(row[10]),
      domain_user: clean(row[11]),
      join_domain: clean(row[12]),
      login_domain: clean(row[13]),
    });
  }
}

const output = {
  source: 'Daftar Endpoint Kanwil Surabaya.xlsx',
  imported_at: new Date().toISOString(),
  records,
};

await fs.writeFile('../../writable/endpoint-seed.json', JSON.stringify(output, null, 2));
const counts = records.reduce((result, row) => {
  result[row.branch_name] = (result[row.branch_name] ?? 0) + 1;
  return result;
}, {});
console.log(JSON.stringify({ total: records.length, counts }, null, 2));

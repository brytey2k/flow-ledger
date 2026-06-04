<?php

declare(strict_types=1);

namespace Tests\Feature\CostCodes;

use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\CostCode;
use App\Models\Tenant\Department;
use Illuminate\Http\UploadedFile;
use Tests\TenantAppTestCase;
use ZipArchive;

class CostCodeImportTest extends TenantAppTestCase
{
    // ── Authentication ────────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_import_form(): void
    {
        $this->get(route('cost-codes.import'))->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_import_submission(): void
    {
        $this->post(route('cost-codes.import.store'), [])->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_sample_download(): void
    {
        $this->get(route('cost-codes.import.template'))->assertRedirect(route('login'));
    }

    // ── Authorization ─────────────────────────────────────────────────────────

    public function test_user_without_create_permission_cannot_view_import_form(): void
    {
        $this->role->revokePermissionTo(PermissionKey::CreateCostCode->value);

        $this->actingAs($this->user)->get(route('cost-codes.import'))->assertForbidden();
    }

    public function test_user_without_create_permission_cannot_download_sample(): void
    {
        $this->role->revokePermissionTo(PermissionKey::CreateCostCode->value);

        $this->actingAs($this->user)->get(route('cost-codes.import.template'))->assertForbidden();
    }

    public function test_user_without_create_permission_cannot_submit_import(): void
    {
        $this->role->revokePermissionTo(PermissionKey::CreateCostCode->value);

        $this->actingAs($this->user)
            ->post(route('cost-codes.import.store'), [
                'file' => $this->makeSpreadsheetFile([
                    ['code', 'name', 'department'],
                    ['CC-1001', 'Office Supplies', 'Finance'],
                ]),
            ])
            ->assertForbidden();
    }

    // ── Import form and template ──────────────────────────────────────────────

    public function test_authorised_user_can_view_import_form(): void
    {
        $this->actingAs($this->user)
            ->get(route('cost-codes.import'))
            ->assertOk();
    }

    public function test_authorised_user_can_download_import_template(): void
    {
        Department::factory()->create(['name' => 'Finance']);
        Department::factory()->create(['name' => 'Operations']);

        $response = $this->actingAs($this->user)->get(route('cost-codes.import.template'));

        $response->assertOk()->assertDownload('cost-codes-sample.xlsx');

        $path = tempnam(sys_get_temp_dir(), 'cost_codes_template_test_');
        file_put_contents($path, $response->streamedContent());

        $zip = new ZipArchive();
        $zip->open($path);

        $this->assertStringContainsString('Departments', (string) $zip->getFromName('xl/workbook.xml'));
        $this->assertStringContainsString('sqref="C2:C1048576"', (string) $zip->getFromName('xl/worksheets/sheet1.xml'));
        $this->assertStringContainsString('Finance', (string) $zip->getFromName('xl/worksheets/sheet2.xml'));
        $this->assertStringContainsString('Operations', (string) $zip->getFromName('xl/worksheets/sheet2.xml'));

        $zip->close();
        @unlink($path);
    }

    // ── Import processing ─────────────────────────────────────────────────────

    public function test_authorised_user_can_import_cost_codes_from_xlsx(): void
    {
        $finance = Department::factory()->create(['name' => 'Finance']);
        $operations = Department::factory()->create(['name' => 'Operations']);

        $this->actingAs($this->user)
            ->post(route('cost-codes.import.store'), [
                'file' => $this->makeSpreadsheetFile([
                    ['code', 'name', 'department'],
                    ['CC-1001', 'Office Supplies', 'Finance'],
                    ['CC-1002', 'Training', 'Operations'],
                ]),
            ])
            ->assertRedirect(route('cost-codes.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('cost_codes', [
            'code' => 'CC-1001',
            'name' => 'Office Supplies',
            'department_id' => $finance->id,
        ]);

        $this->assertDatabaseHas('cost_codes', [
            'code' => 'CC-1002',
            'name' => 'Training',
            'department_id' => $operations->id,
        ]);
    }

    public function test_import_requires_a_file(): void
    {
        $this->actingAs($this->user)
            ->post(route('cost-codes.import.store'), [])
            ->assertSessionHasErrors('file');
    }

    public function test_import_rejects_invalid_headers(): void
    {
        $this->actingAs($this->user)
            ->post(route('cost-codes.import.store'), [
                'file' => $this->makeSpreadsheetFile([
                    ['code', 'department', 'name'],
                    ['CC-1001', 'Finance', 'Office Supplies'],
                ]),
            ])
            ->assertSessionHasErrors('file');
    }

    public function test_import_rejects_missing_departments(): void
    {
        $this->actingAs($this->user)
            ->post(route('cost-codes.import.store'), [
                'file' => $this->makeSpreadsheetFile([
                    ['code', 'name', 'department'],
                    ['CC-1001', 'Office Supplies', 'Finance'],
                ]),
            ])
            ->assertSessionHasErrors('rows.2');
    }

    public function test_import_rejects_duplicate_codes_in_file(): void
    {
        Department::factory()->create(['name' => 'Finance']);

        $this->actingAs($this->user)
            ->post(route('cost-codes.import.store'), [
                'file' => $this->makeSpreadsheetFile([
                    ['code', 'name', 'department'],
                    ['CC-1001', 'Office Supplies', 'Finance'],
                    ['CC-1001', 'Training', 'Finance'],
                ]),
            ])
            ->assertSessionHasErrors('rows.3');
    }

    public function test_import_rejects_existing_cost_codes(): void
    {
        Department::factory()->create(['name' => 'Finance']);
        CostCode::factory()->create([
            'code' => 'CC-1001',
        ]);

        $this->actingAs($this->user)
            ->post(route('cost-codes.import.store'), [
                'file' => $this->makeSpreadsheetFile([
                    ['code', 'name', 'department'],
                    ['CC-1001', 'Office Supplies', 'Finance'],
                ]),
            ])
            ->assertSessionHasErrors('rows.2');
    }

    /**
     * @param array<int, array<int, string>> $rows
     */
    private function makeSpreadsheetFile(array $rows): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'cost_codes_import_');

        if ($path === false) {
            self::fail('Unable to create a temporary spreadsheet file.');
        }

        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
    <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
    <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>
XML);
        $zip->addFromString('_rels/.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>
XML);
        $zip->addFromString('xl/workbook.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"
          xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <sheets>
        <sheet name="cost_codes" sheetId="1" r:id="rId1"/>
    </sheets>
</workbook>
XML);
        $zip->addFromString('xl/_rels/workbook.xml.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>
XML);
        $zip->addFromString('xl/styles.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <fonts count="1">
        <font><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/><scheme val="minor"/></font>
    </fonts>
    <fills count="1">
        <fill><patternFill patternType="none"/></fill>
    </fills>
    <borders count="1">
        <border><left/><right/><top/><bottom/><diagonal/></border>
    </borders>
    <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
    <cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>
    <cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>
</styleSheet>
XML);

        $sheetRows = '';
        foreach ($rows as $rowIndex => $row) {
            $cells = '';
            foreach ($row as $columnIndex => $value) {
                $cells .= sprintf(
                    '<c r="%s%d" t="inlineStr"><is><t>%s</t></is></c>',
                    $this->columnName($columnIndex + 1),
                    $rowIndex + 1,
                    htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                );
            }

            $sheetRows .= sprintf('<row r="%d">%s</row>', $rowIndex + 1, $cells);
        }

        $zip->addFromString('xl/worksheets/sheet1.xml', sprintf(
            <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <sheetData>%s</sheetData>
</worksheet>
XML,
            $sheetRows,
        ));

        $zip->close();

        return new UploadedFile($path, 'cost-codes.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    private function columnName(int $index): string
    {
        $name = '';

        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)) . $name;
            $index = intdiv($index, 26);
        }

        return $name;
    }
}

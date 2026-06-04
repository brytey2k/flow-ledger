<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant\CostCode;
use App\Models\Tenant\Department;
use DOMDocument;
use DOMXPath;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class CostCodeImportService
{
    public function downloadTemplate(Collection $departments): StreamedResponse
    {
        $bytes = $this->buildTemplateBytes($departments);

        return Response::streamDownload(static function () use ($bytes): void {
            echo $bytes;
        }, 'cost-codes-sample.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function import(UploadedFile $file): int
    {
        $path = $file->getRealPath();

        if ($path === false) {
            throw ValidationException::withMessages([
                'file' => __('cost_codes.import_errors.unreadable'),
            ]);
        }

        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            throw ValidationException::withMessages([
                'file' => __('cost_codes.import_errors.unreadable'),
            ]);
        }

        try {
            $sharedStrings = $this->readSharedStrings($zip);
            $worksheetPath = $this->resolveFirstWorksheetPath($zip);
            $rows = $this->readWorksheetRows($zip, $worksheetPath, $sharedStrings);

            if ($rows === []) {
                throw ValidationException::withMessages([
                    'file' => __('cost_codes.import_errors.empty'),
                ]);
            }

            $headers = array_map(
                fn(string|null $header): string => $this->normalizeValue($header),
                array_shift($rows),
            );

            if ($headers !== ['code', 'name', 'department']) {
                throw ValidationException::withMessages([
                    'file' => __('cost_codes.import_errors.invalid_headers'),
                ]);
            }

            if ($rows === []) {
                throw ValidationException::withMessages([
                    'file' => __('cost_codes.import_errors.no_rows'),
                ]);
            }

            $departmentLookup = [];

            foreach (Department::query()->orderBy('name')->get(['id', 'name']) as $department) {
                $departmentLookup[$this->normalizeLookupKey($department->name)] = $department->id;
            }

            $records = [];
            $errors = [];
            $seenCodes = [];

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;

                if ($this->isEmptyRow($row)) {
                    continue;
                }

                $code = trim((string) ($row[0] ?? ''));
                $name = trim((string) ($row[1] ?? ''));
                $departmentName = trim((string) ($row[2] ?? ''));

                if ($code === '') {
                    $errors["rows.$rowNumber"] = __('cost_codes.import_errors.code_required', ['row' => $rowNumber]);
                    continue;
                }

                if (mb_strlen($code) > 50) {
                    $errors["rows.$rowNumber"] = __('cost_codes.import_errors.code_too_long', [
                        'row' => $rowNumber,
                        'code' => $code,
                    ]);
                    continue;
                }

                if ($name === '') {
                    $errors["rows.$rowNumber"] = __('cost_codes.import_errors.name_required', ['row' => $rowNumber]);
                    continue;
                }

                if (mb_strlen($name) > 150) {
                    $errors["rows.$rowNumber"] = __('cost_codes.import_errors.name_too_long', [
                        'row' => $rowNumber,
                        'name' => $name,
                    ]);
                    continue;
                }

                if ($departmentName === '') {
                    $errors["rows.$rowNumber"] = __('cost_codes.import_errors.department_required', ['row' => $rowNumber]);
                    continue;
                }

                $departmentId = $departmentLookup[$this->normalizeLookupKey($departmentName)] ?? null;

                if ($departmentId === null) {
                    $errors["rows.$rowNumber"] = __('cost_codes.import_errors.department_invalid', [
                        'row' => $rowNumber,
                        'department' => $departmentName,
                    ]);
                    continue;
                }

                if (isset($seenCodes[$code])) {
                    $errors["rows.$rowNumber"] = __('cost_codes.import_errors.duplicate_in_file', [
                        'row' => $rowNumber,
                        'code' => $code,
                    ]);
                    continue;
                }

                $seenCodes[$code] = $rowNumber;
                $records[$rowNumber] = [
                    'code' => $code,
                    'name' => $name,
                    'department_id' => $departmentId,
                ];
            }

            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }

            if ($records === []) {
                throw ValidationException::withMessages([
                    'file' => __('cost_codes.import_errors.no_rows'),
                ]);
            }

            $existingCodes = CostCode::query()
                ->whereIn('code', array_column($records, 'code'))
                ->pluck('code')
                ->all();

            if ($existingCodes !== []) {
                $existingLookup = array_fill_keys($existingCodes, true);

                foreach ($records as $rowNumber => $record) {
                    if (isset($existingLookup[$record['code']])) {
                        $errors["rows.$rowNumber"] = __('cost_codes.import_errors.duplicate_existing', [
                            'row' => $rowNumber,
                            'code' => $record['code'],
                        ]);
                    }
                }
            }

            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }

            return DB::transaction(function () use ($records): int {
                foreach ($records as $record) {
                    CostCode::create($record);
                }

                return count($records);
            });
        } finally {
            $zip->close();
        }
    }

    /**
     * @param Collection<int, Department> $departments
     */
    private function buildTemplateBytes(Collection $departments): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'cost_codes_template_');

        if ($tempFile === false) {
            throw new RuntimeException('Unable to create a temporary file for the cost code template.');
        }

        $zip = new ZipArchive();

        if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($tempFile);

            throw new RuntimeException('Unable to open a temporary archive for the cost code template.');
        }

        try {
            $departmentNames = $departments->pluck('name')->all();

            $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
            $zip->addFromString('_rels/.rels', $this->rootRelsXml());
            $zip->addFromString('xl/workbook.xml', $this->workbookXml(count($departmentNames)));
            $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelsXml());
            $zip->addFromString('xl/styles.xml', $this->stylesXml());
            $zip->addFromString('xl/worksheets/sheet1.xml', $this->costCodesSheetXml());
            $zip->addFromString('xl/worksheets/sheet2.xml', $this->departmentsSheetXml($departmentNames));
        } finally {
            $zip->close();
        }

        $bytes = file_get_contents($tempFile);
        @unlink($tempFile);

        if ($bytes === false) {
            throw new RuntimeException('Unable to read the generated cost code template.');
        }

        return $bytes;
    }

    private function contentTypesXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
    <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
    <Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
    <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>
XML;
    }

    private function rootRelsXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>
XML;
    }

    private function workbookXml(int $departmentCount): string
    {
        $rangeEnd = max($departmentCount, 1);

        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"
          xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <sheets>
        <sheet name="cost_codes" sheetId="1" r:id="rId1"/>
        <sheet name="departments" sheetId="2" state="hidden" r:id="rId2"/>
    </sheets>
    <definedNames>
        <definedName name="Departments">'departments'!$A$1:$A$%d</definedName>
    </definedNames>
</workbook>
XML;

        return sprintf($xml, $rangeEnd);
    }

    private function workbookRelsXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/>
    <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>
XML;
    }

    private function stylesXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <fonts count="1">
        <font>
            <sz val="11"/>
            <color theme="1"/>
            <name val="Calibri"/>
            <family val="2"/>
            <scheme val="minor"/>
        </font>
    </fonts>
    <fills count="1">
        <fill>
            <patternFill patternType="none"/>
        </fill>
    </fills>
    <borders count="1">
        <border>
            <left/>
            <right/>
            <top/>
            <bottom/>
            <diagonal/>
        </border>
    </borders>
    <cellStyleXfs count="1">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
    </cellStyleXfs>
    <cellXfs count="1">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
    </cellXfs>
    <cellStyles count="1">
        <cellStyle name="Normal" xfId="0" builtinId="0"/>
    </cellStyles>
</styleSheet>
XML;
    }

    private function costCodesSheetXml(): string
    {
        return $this->buildWorksheetXml(
            ['code', 'name', 'department'],
            [],
            'Departments',
            'C2:C1048576',
        );
    }

    /**
     * @param array<int, string> $departmentNames
     */
    private function departmentsSheetXml(array $departmentNames): string
    {
        if ($departmentNames === []) {
            $departmentNames = [''];
        }

        return $this->buildListWorksheetXml($departmentNames);
    }

    /**
     * @param array $headers
     * @param array<int, array<int, string>> $rows
     * @param string|null|null $dataValidationFormula
     * @param string|null|null $dataValidationRange
     */
    private function buildWorksheetXml(array $headers, array $rows, string|null $dataValidationFormula = null, string|null $dataValidationRange = null): string
    {
        $allRows = array_merge([$headers], $rows);
        $sheetData = '';

        foreach ($allRows as $rowIndex => $row) {
            $cells = '';
            foreach ($row as $columnIndex => $value) {
                $cells .= sprintf(
                    '<c r="%s%d" t="inlineStr"><is><t>%s</t></is></c>',
                    $this->columnName($columnIndex + 1),
                    $rowIndex + 1,
                    $this->xmlEscape($value),
                );
            }

            $sheetData .= sprintf('<row r="%d">%s</row>', $rowIndex + 1, $cells);
        }

        $dataValidations = '';

        if ($dataValidationFormula !== null && $dataValidationRange !== null) {
            $dataValidations = sprintf(
                '<dataValidations count="1"><dataValidation type="list" allowBlank="1" sqref="%s"><formula1>%s</formula1></dataValidation></dataValidations>',
                $dataValidationRange,
                $this->xmlEscape($dataValidationFormula),
            );
        }

        return sprintf(
            <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <sheetData>%s</sheetData>
    %s
</worksheet>
XML,
            $sheetData,
            $dataValidations,
        );
    }

    /**
     * @param array<int, string> $values
     */
    private function buildListWorksheetXml(array $values): string
    {
        if ($values === []) {
            $values = [''];
        }

        $sheetData = '';

        foreach (array_values($values) as $index => $value) {
            $rowNumber = $index + 1;
            $sheetData .= sprintf(
                '<row r="%d"><c r="A%d" t="inlineStr"><is><t>%s</t></is></c></row>',
                $rowNumber,
                $rowNumber,
                $this->xmlEscape($value),
            );
        }

        return sprintf(
            <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <sheetData>%s</sheetData>
</worksheet>
XML,
            $sheetData,
        );
    }

    /**
     * @param ZipArchive $zip
     *
     * @return array<int, string>
     */
    private function readSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');

        if ($xml === false) {
            return [];
        }

        $document = $this->loadXmlDocument($xml);
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('a', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $strings = [];

        foreach ($xpath->query('/a:sst/a:si') as $node) {
            $text = '';
            foreach ($xpath->query('.//a:t', $node) as $textNode) {
                $text .= $textNode->textContent;
            }
            $strings[] = $text;
        }

        return $strings;
    }

    private function resolveFirstWorksheetPath(ZipArchive $zip): string
    {
        $workbookXml = $this->readZipEntry($zip, 'xl/workbook.xml');
        $relationshipsXml = $this->readZipEntry($zip, 'xl/_rels/workbook.xml.rels');

        $workbook = $this->loadXmlDocument($workbookXml);
        $workbookXPath = new DOMXPath($workbook);
        $workbookXPath->registerNamespace('a', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $sheet = $workbookXPath->query('/a:workbook/a:sheets/a:sheet')->item(0);

        if ($sheet === null) {
            throw ValidationException::withMessages([
                'file' => __('cost_codes.import_errors.unreadable'),
            ]);
        }

        $relationshipId = $sheet->attributes?->getNamedItemNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id')?->nodeValue;

        if ($relationshipId === null) {
            throw ValidationException::withMessages([
                'file' => __('cost_codes.import_errors.unreadable'),
            ]);
        }

        $rels = $this->loadXmlDocument($relationshipsXml);
        $relsXPath = new DOMXPath($rels);
        $relsXPath->registerNamespace('r', 'http://schemas.openxmlformats.org/package/2006/relationships');

        $relationship = $relsXPath->query(sprintf('/r:Relationships/r:Relationship[@Id="%s"]', $relationshipId))->item(0);

        if ($relationship === null) {
            throw ValidationException::withMessages([
                'file' => __('cost_codes.import_errors.unreadable'),
            ]);
        }

        $target = $relationship->attributes?->getNamedItem('Target')?->nodeValue;

        if ($target === null) {
            throw ValidationException::withMessages([
                'file' => __('cost_codes.import_errors.unreadable'),
            ]);
        }

        return Str::startsWith($target, 'xl/') ? $target : 'xl/' . $target;
    }

    /**
     * @param ZipArchive $zip
     * @param string $worksheetPath
     * @param array $sharedStrings
     *
     * @return array<int, array<int, string|null>>
     */
    private function readWorksheetRows(ZipArchive $zip, string $worksheetPath, array $sharedStrings): array
    {
        $xml = $this->readZipEntry($zip, $worksheetPath);
        $document = $this->loadXmlDocument($xml);
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('a', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $rows = [];

        foreach ($xpath->query('/a:worksheet/a:sheetData/a:row') as $rowNode) {
            $row = [];

            foreach ($xpath->query('a:c', $rowNode) as $cellNode) {
                $reference = $cellNode->attributes?->getNamedItem('r')?->nodeValue ?? '';
                $columnIndex = $this->columnIndexFromReference($reference);

                if ($columnIndex === 0) {
                    continue;
                }

                $row[$columnIndex - 1] = $this->readCellValue($xpath, $cellNode, $sharedStrings);
            }

            ksort($row);
            $rows[] = array_values($row);
        }

        return $rows;
    }

    /**
     * @param DOMXPath $xpath
     * @param \DOMElement $cellNode
     * @param array<int, string> $sharedStrings
     */
    private function readCellValue(DOMXPath $xpath, \DOMElement $cellNode, array $sharedStrings): string|null
    {
        $type = $cellNode->attributes?->getNamedItem('t')?->nodeValue ?? '';

        if ($type === 'inlineStr') {
            $textNodes = $xpath->query('a:is/a:t', $cellNode);
            if ($textNodes->length > 0) {
                $value = '';
                foreach ($textNodes as $textNode) {
                    $value .= $textNode->textContent;
                }

                return $value;
            }

            return $cellNode->textContent;
        }

        $valueNode = $xpath->query('a:v', $cellNode)->item(0);

        if ($valueNode === null) {
            return $cellNode->textContent !== '' ? $cellNode->textContent : null;
        }

        $value = $valueNode->textContent;

        if ($type === 's') {
            $index = (int) $value;

            return $sharedStrings[$index] ?? '';
        }

        if ($type === 'b') {
            return $value === '1' ? '1' : '0';
        }

        return $value;
    }

    /**
     * @param array<int, string|null> $row
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    private function normalizeValue(string|null $value): string
    {
        $value = trim((string) $value);

        return mb_strtolower($value);
    }

    private function normalizeLookupKey(string $value): string
    {
        return $this->normalizeValue($value);
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

    private function columnIndexFromReference(string $reference): int
    {
        if (! preg_match('/^([A-Z]+)/i', $reference, $matches)) {
            return 0;
        }

        $value = strtoupper($matches[1]);
        $index = 0;

        foreach (str_split($value) as $character) {
            $index = ($index * 26) + (ord($character) - 64);
        }

        return $index;
    }

    private function xmlEscape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function loadXmlDocument(string $xml): DOMDocument
    {
        $document = new DOMDocument();
        $document->preserveWhiteSpace = false;
        $document->formatOutput = false;

        if (@$document->loadXML($xml, LIBXML_NONET) !== true) {
            throw ValidationException::withMessages([
                'file' => __('cost_codes.import_errors.unreadable'),
            ]);
        }

        return $document;
    }

    private function readZipEntry(ZipArchive $zip, string $entry): string
    {
        $content = $zip->getFromName($entry);

        if ($content === false) {
            throw ValidationException::withMessages([
                'file' => __('cost_codes.import_errors.unreadable'),
            ]);
        }

        return $content;
    }
}

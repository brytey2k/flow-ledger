<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant\Attachment;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\BaseReader;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Exception as WriterException;
use PhpOffice\PhpSpreadsheet\Writer\Html;
use PhpOffice\PhpWord\Reader\Word2007;
use PhpOffice\PhpWord\Settings as WordSettings;
use PhpOffice\PhpWord\Writer\HTML as WordHtml;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Throwable;
use ZipArchive;

class AttachmentPreviewService
{
    private const int MAX_DOCUMENT_ARCHIVE_ENTRIES = 1_000;

    private const int MAX_DOCUMENT_UNCOMPRESSED_BYTES = 52_428_800;

    private const int MAX_SPREADSHEET_CELLS = 250_000;

    private const string PREVIEW_CONTENT_SECURITY_POLICY = "sandbox; default-src 'none'; img-src data:; style-src 'unsafe-inline'; base-uri 'none'; form-action 'none'";

    public function response(Attachment $attachment): BinaryFileResponse|Response
    {
        $disk = Storage::disk('local');

        abort_unless($disk->exists($attachment->path), 404);
        abort_unless($attachment->isPreviewable(), 415);

        $path = $disk->path($attachment->path);

        if ($attachment->isSpreadsheetPreviewable()) {
            return $this->spreadsheetResponse($path);
        }

        if ($attachment->isWordPreviewable()) {
            return $this->wordResponse($path);
        }

        return response()
            ->file($path, [
                'Content-Type' => $attachment->mime_type,
                'X-Content-Type-Options' => 'nosniff',
            ])
            ->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $attachment->original_name);
    }

    private function spreadsheetResponse(string $path): Response
    {
        try {
            /** @var BaseReader $reader */
            $reader = IOFactory::createReaderForFile($path);
            $worksheetInfo = $reader->listWorksheetInfo($path);
            $visibleWorksheetInfo = array_values(array_filter(
                $worksheetInfo,
                static fn(array $worksheet): bool => $worksheet['sheetState'] === Worksheet::SHEETSTATE_VISIBLE,
            ));

            if ($visibleWorksheetInfo === []) {
                throw new UnprocessableEntityHttpException(__('common.spreadsheet_preview_failed'));
            }

            $cellCount = array_sum(array_map(
                static fn(array $worksheet): int => (int) $worksheet['totalRows'] * (int) $worksheet['totalColumns'],
                $visibleWorksheetInfo,
            ));

            if ($cellCount > self::MAX_SPREADSHEET_CELLS) {
                throw new UnprocessableEntityHttpException(__('common.spreadsheet_preview_too_large'));
            }

            $reader->setLoadSheetsOnly(array_column($visibleWorksheetInfo, 'worksheetName'));
            $spreadsheet = $reader->load($path);

            try {
                $writer = new Html($spreadsheet);
                $writer->writeAllSheets();
                $writer->setPreCalculateFormulas(false);
                $writer->setTableFormats(true);
                $writer->setConditionalFormatting(true);

                $spreadsheetStyles = $writer->generateStyles();
                $spreadsheetNavigation = $writer->generateNavigation();
                $spreadsheetContent = $writer->generateSheetData();
            } finally {
                $spreadsheet->disconnectWorksheets();
            }
        } catch (ReaderException|WriterException $exception) {
            throw new UnprocessableEntityHttpException(__('common.spreadsheet_preview_failed'), $exception);
        }

        return response()->view('tenant.attachments.spreadsheet-preview', [
            'spreadsheetStyles' => $spreadsheetStyles,
            'spreadsheetNavigation' => $spreadsheetNavigation,
            'spreadsheetContent' => $spreadsheetContent,
        ], 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Security-Policy' => self::PREVIEW_CONTENT_SECURITY_POLICY,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function wordResponse(string $path): Response
    {
        $this->assertDocumentArchiveWithinPreviewLimits($path);

        $outputEscapingWasEnabled = WordSettings::isOutputEscapingEnabled();

        try {
            WordSettings::setOutputEscapingEnabled(true);

            $reader = new Word2007();
            $reader->setImageLoading(false);
            $document = $reader->load($path);

            $writer = new WordHtml($document);
            $writer->setDefaultGenericFont('sans-serif');
            $writer->setDefaultWhiteSpace('pre-wrap');
            $documentHtml = $this->sanitizeDocumentHtml($writer->getContent());
        } catch (Throwable $exception) {
            throw new UnprocessableEntityHttpException(__('common.document_preview_failed'), $exception);
        } finally {
            WordSettings::setOutputEscapingEnabled($outputEscapingWasEnabled);
        }

        return response($documentHtml, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Security-Policy' => self::PREVIEW_CONTENT_SECURITY_POLICY,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function assertDocumentArchiveWithinPreviewLimits(string $path): void
    {
        $archive = new ZipArchive();

        if ($archive->open($path) !== true) {
            throw new UnprocessableEntityHttpException(__('common.document_preview_failed'));
        }

        try {
            if ($archive->numFiles > self::MAX_DOCUMENT_ARCHIVE_ENTRIES) {
                throw new UnprocessableEntityHttpException(__('common.document_preview_too_large'));
            }

            $uncompressedBytes = 0;

            for ($entryIndex = 0; $entryIndex < $archive->numFiles; ++$entryIndex) {
                $entry = $archive->statIndex($entryIndex);

                if ($entry === false) {
                    throw new UnprocessableEntityHttpException(__('common.document_preview_failed'));
                }

                $uncompressedBytes += (int) $entry['size'];

                if ($uncompressedBytes > self::MAX_DOCUMENT_UNCOMPRESSED_BYTES) {
                    throw new UnprocessableEntityHttpException(__('common.document_preview_too_large'));
                }
            }
        } finally {
            $archive->close();
        }
    }

    private function sanitizeDocumentHtml(string $html): string
    {
        $document = new DOMDocument();
        $internalErrorsWereEnabled = libxml_use_internal_errors(true);

        try {
            if (! $document->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING)) {
                throw new RuntimeException('Unable to parse generated document HTML.');
            }

            foreach (['script', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select', 'base', 'link'] as $tagName) {
                while ($document->getElementsByTagName($tagName)->length > 0) {
                    $element = $document->getElementsByTagName($tagName)->item(0);

                    if (! $element instanceof DOMElement || $element->parentNode === null) {
                        throw new RuntimeException('Unable to sanitize generated document HTML.');
                    }

                    $element->parentNode->removeChild($element);
                }
            }

            $xpath = new DOMXPath($document);

            foreach ($xpath->query('//*') ?: [] as $element) {
                if (! $element instanceof DOMElement) {
                    continue;
                }

                foreach (iterator_to_array($element->attributes) as $attribute) {
                    $attributeName = strtolower($attribute->name);

                    if (str_starts_with($attributeName, 'on') || in_array($attributeName, ['href', 'action', 'formaction'], true)) {
                        $element->removeAttribute($attribute->name);
                    }

                    if ($attributeName === 'src' && ! str_starts_with($attribute->value, 'data:image/')) {
                        $element->removeAttribute($attribute->name);
                    }
                }
            }

            foreach ($xpath->query('//body//text()') ?: [] as $textNode) {
                $textNode->nodeValue = html_entity_decode(
                    $textNode->nodeValue ?? '',
                    ENT_QUOTES | ENT_HTML5,
                    'UTF-8',
                );
            }

            $sanitizedHtml = $document->saveHTML();

            if ($sanitizedHtml === false) {
                throw new RuntimeException('Unable to serialize generated document HTML.');
            }

            return $sanitizedHtml;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($internalErrorsWereEnabled);
        }
    }
}

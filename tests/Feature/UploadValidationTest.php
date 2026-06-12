<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UploadValidationTest extends TestCase
{
    // Regole immagini (MediaUploader, MediaLibrary)
    private const IMG_RULES = 'mimes:jpeg,jpg,png,webp,gif,heic,heif|max:25600';

    // Regole documenti (DocUploader, MediaLibrary)
    private const DOC_RULES = 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx|max:15360';

    #[DataProvider('validImages')]
    public function test_image_rule_accepts_valid_types(string $filename, string $mime): void
    {
        $file = UploadedFile::fake()->create($filename, 100, $mime);
        $v = Validator::make(['f' => $file], ['f' => self::IMG_RULES]);
        $this->assertTrue($v->passes(), "Atteso valido: {$filename}");
    }

    #[DataProvider('invalidImageTypes')]
    public function test_image_rule_rejects_invalid_types(string $filename, string $mime): void
    {
        $file = UploadedFile::fake()->create($filename, 100, $mime);
        $v = Validator::make(['f' => $file], ['f' => self::IMG_RULES]);
        $this->assertTrue($v->fails(), "Atteso rifiutato: {$filename}");
    }

    public function test_image_rule_rejects_oversized_file(): void
    {
        $file = UploadedFile::fake()->create('big.jpg', 26000, 'image/jpeg'); // 26 MB > 25 MB
        $v = Validator::make(['f' => $file], ['f' => self::IMG_RULES]);
        $this->assertTrue($v->fails());
    }

    #[DataProvider('validDocuments')]
    public function test_document_rule_accepts_valid_types(string $filename, string $mime): void
    {
        $file = UploadedFile::fake()->create($filename, 100, $mime);
        $v = Validator::make(['f' => $file], ['f' => self::DOC_RULES]);
        $this->assertTrue($v->passes(), "Atteso valido: {$filename}");
    }

    #[DataProvider('invalidDocumentTypes')]
    public function test_document_rule_rejects_invalid_types(string $filename, string $mime): void
    {
        $file = UploadedFile::fake()->create($filename, 100, $mime);
        $v = Validator::make(['f' => $file], ['f' => self::DOC_RULES]);
        $this->assertTrue($v->fails(), "Atteso rifiutato: {$filename}");
    }

    public function test_document_rule_rejects_oversized_file(): void
    {
        $file = UploadedFile::fake()->create('big.pdf', 16000, 'application/pdf'); // 16 MB > 15 MB
        $v = Validator::make(['f' => $file], ['f' => self::DOC_RULES]);
        $this->assertTrue($v->fails());
    }

    public static function validImages(): array
    {
        return [
            ['photo.jpg',  'image/jpeg'],
            ['photo.jpeg', 'image/jpeg'],
            ['photo.png',  'image/png'],
            ['photo.webp', 'image/webp'],
            ['photo.gif',  'image/gif'],
        ];
    }

    public static function invalidImageTypes(): array
    {
        return [
            ['script.php', 'text/x-php'],
            ['doc.pdf',    'application/pdf'],
            ['file.exe',   'application/octet-stream'],
            ['page.html',  'text/html'],
        ];
    }

    public static function validDocuments(): array
    {
        return [
            ['report.pdf',  'application/pdf'],
            ['sheet.xlsx',  'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            ['slide.pptx',  'application/vnd.openxmlformats-officedocument.presentationml.presentation'],
        ];
    }

    public static function invalidDocumentTypes(): array
    {
        return [
            ['script.php', 'text/x-php'],
            ['photo.jpg',  'image/jpeg'],
            ['file.exe',   'application/octet-stream'],
            ['page.html',  'text/html'],
        ];
    }
}

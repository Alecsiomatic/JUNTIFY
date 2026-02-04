<?php

namespace App\Services\Assistant;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use ZipArchive;

class DocumentParser
{
    public function extractText(UploadedFile $file): ?string
    {
        $mime = $file->getMimeType();

        return match (true) {
            Str::contains($mime, 'text') => $this->parseTextFile($file),
            $mime === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => $this->parseDocx($file),
            $mime === 'application/vnd.openxmlformats-officedocument.presentationml.presentation' => $this->parsePptx($file),
            $mime === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => $this->parseXlsx($file),
            default => null,
        };
    }

    protected function parseTextFile(UploadedFile $file): string
    {
        return (string) file_get_contents($file->getRealPath());
    }

    protected function parseDocx(UploadedFile $file): ?string
    {
        $zip = new ZipArchive();

        if ($zip->open($file->getRealPath()) !== true) {
            return null;
        }

        $content = $zip->getFromName('word/document.xml');
        $zip->close();

        if (! $content) {
            return null;
        }

        $text = strip_tags(str_replace(['</w:p>', '</w:tr>'], "\n", $content));

        return Str::of($text)->replace(['  ', '\r'], ' ')->squish()->value();
    }

    protected function parsePptx(UploadedFile $file): ?string
    {
        $zip = new ZipArchive();

        if ($zip->open($file->getRealPath()) !== true) {
            return null;
        }

        $slidesText = [];
        $index = 1;

        while ($content = $zip->getFromName(sprintf('ppt/slides/slide%d.xml', $index))) {
            $slidesText[] = strip_tags($content);
            $index++;
        }

        $zip->close();

        return $slidesText ? Str::of(implode("\n", $slidesText))->squish()->value() : null;
    }

    protected function parseXlsx(UploadedFile $file): ?string
    {
        $zip = new ZipArchive();

        if ($zip->open($file->getRealPath()) !== true) {
            return null;
        }

        $sharedStrings = $zip->getFromName('xl/sharedStrings.xml');
        $zip->close();

        if (! $sharedStrings) {
            return null;
        }

        preg_match_all('/<t[^>]*>(.*?)<\/t>/', $sharedStrings, $matches);

        if (empty($matches[1])) {
            return null;
        }

        return Str::of(implode(' | ', $matches[1]))->squish()->value();
    }
}

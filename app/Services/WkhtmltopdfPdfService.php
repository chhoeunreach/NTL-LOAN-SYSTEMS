<?php

namespace App\Services;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\View;

class WkhtmltopdfPdfService
{
    public function saveHtmlToPdf(string $html, string $outputPath, array $options = []): void
    {
        $binary = env('WKHTMLTOPDF_BINARY');
        if ($binary && is_file($binary)) {
            $this->runBinary($binary, $html, $outputPath, $options);

            return;
        }

        file_put_contents($outputPath, $this->placeholderPdf($html));
    }

    public function saveHtmlToImage(string $html, string $outputPath, array $options = []): void
    {
        $binary = env('WKHTMLTOIMAGE_BINARY');
        if ($binary && is_file($binary)) {
            $this->runBinary($binary, $html, $outputPath, $options);

            return;
        }

        file_put_contents($outputPath, base64_decode($this->transparentPng()));
    }

    public function renderView(string $view, array $data = [], string $filename = 'document.pdf')
    {
        $html = View::make($view, $data)->render();

        return $this->html($html, $filename);
    }

    public function html(string $html, string $filename = 'document.pdf')
    {
        return new Response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="'.str_replace('"', '', $filename).'"',
        ]);
    }

    private function runBinary(string $binary, string $html, string $outputPath, array $options): void
    {
        $inputPath = tempnam(sys_get_temp_dir(), 'loan-html-').'.html';
        file_put_contents($inputPath, $html);

        $args = [$binary];
        foreach ($options as $key => $value) {
            $args[] = '--'.$key;
            if ($value !== true) {
                $args[] = (string) $value;
            }
        }
        $args[] = $inputPath;
        $args[] = $outputPath;

        $process = proc_open($args, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (! is_resource($process)) {
            @unlink($inputPath);
            throw new \RuntimeException('Unable to start renderer binary.');
        }

        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $status = proc_close($process);
        @unlink($inputPath);

        if ($status !== 0) {
            throw new \RuntimeException(trim((string) $error) ?: 'Renderer failed.');
        }
    }

    private function placeholderPdf(string $html): string
    {
        $text = substr(preg_replace('/\s+/', ' ', strip_tags($html)) ?: 'Loan document', 0, 120);
        $stream = "BT /F1 12 Tf 40 780 Td (".str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text).") Tj ET";
        $objects = [
            "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n",
            "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n",
            "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >> endobj\n",
            "4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj\n",
            "5 0 obj << /Length ".strlen($stream)." >> stream\n{$stream}\nendstream endobj\n",
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object;
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        $pdf .= "trailer << /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";

        return $pdf;
    }

    private function transparentPng(): string
    {
        return 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=';
    }
}

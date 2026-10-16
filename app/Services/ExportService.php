<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    /**
     * Sanitize cell value against CSV / Spreadsheet formula injection (CWE-1236).
     * If content starts with =, +, -, @, tab, or carriage return, prepend with single quote.
     */
    public static function sanitizeCell($value): string
    {
        if (is_null($value)) {
            return '';
        }

        $str = (string) $value;
        $dangerousPrefixes = ['=', '+', '-', '@', "\t", "\r"];

        if (strlen($str) > 0 && in_array($str[0], $dangerousPrefixes)) {
            return "'" . $str;
        }

        return $str;
    }

    /**
     * Stream a CSV export with sanitization and memory efficiency.
     */
    public function streamCsv(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for proper Excel Bengali / English character display
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Write headers
            fputcsv($handle, array_map([self::class, 'sanitizeCell'], $headers));

            // Write data rows
            foreach ($rows as $row) {
                fputcsv($handle, array_map([self::class, 'sanitizeCell'], $row));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    /**
     * Direct CSV stream helper from raw rows including headers.
     */
    public function exportCsv(array $data, string $filename = 'export.csv'): StreamedResponse
    {
        $headers = array_shift($data) ?? [];
        return $this->streamCsv($filename, $headers, $data);
    }

    /**
     * Generate an encrypted, temporary export token to prevent guessable public URLs.
     */
    public function generateExportToken(string $reportType, array $params, int $expiryMinutes = 15): string
    {
        $token = Str::random(40);
        $payload = [
            'report_type' => $reportType,
            'params' => $params,
            'expires_at' => now()->addMinutes($expiryMinutes)->timestamp,
        ];

        Storage::disk('local')->put("exports/tokens/{$token}.json", json_encode($payload));

        return $token;
    }

    /**
     * Validate and retrieve parameters for an export token.
     */
    public function validateExportToken(string $token): array
    {
        $path = "exports/tokens/{$token}.json";
        if (!Storage::disk('local')->exists($path)) {
            throw new Exception("Invalid or expired download token.");
        }

        $payload = json_decode(Storage::disk('local')->get($path), true);
        if (!$payload || ($payload['expires_at'] ?? 0) < now()->timestamp) {
            Storage::disk('local')->delete($path);
            throw new Exception("Download token has expired.");
        }

        return $payload;
    }

    /**
     * Purge expired temporary export tokens and files (for scheduler retention cleanup).
     */
    public function cleanupExpiredExports(): int
    {
        $files = Storage::disk('local')->files('exports/tokens');
        $cleaned = 0;

        foreach ($files as $file) {
            $content = Storage::disk('local')->get($file);
            $payload = json_decode($content, true);

            if (!$payload || ($payload['expires_at'] ?? 0) < now()->timestamp) {
                Storage::disk('local')->delete($file);
                $cleaned++;
            }
        }

        return $cleaned;
    }
}

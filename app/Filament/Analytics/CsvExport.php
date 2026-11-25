<?php

namespace App\Filament\Analytics;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * A CSV download for an Analytics export. Starts with a UTF-8 byte order
 * mark so Excel reads names like "Kahathur" or "–" correctly.
 */
final class CsvExport
{
    /**
     * @param  list<string>  $headers
     * @param  iterable<array<int, scalar|null>>  $rows
     */
    public static function download(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows): void {
            $out = fopen('php://output', 'w');

            if ($out === false) {
                return;
            }

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers, escape: '');

            foreach ($rows as $row) {
                fputcsv($out, array_map(self::cell(...), $row), escape: '');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Plain values; text that a spreadsheet would run as a formula is quoted.
     */
    private static function cell(mixed $value): string|int|float|null
    {
        if (is_bool($value)) {
            return $value ? 'yes' : 'no';
        }

        if (is_int($value) || is_float($value)) {
            return $value;
        }

        if (is_string($value)) {
            return $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$value : $value;
        }

        return null;
    }
}

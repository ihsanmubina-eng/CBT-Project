<?php

namespace App\Services;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SpreadsheetService
{
    /**
     * Read all rows from an uploaded XLSX or CSV file.
     * Returns an array of rows (each row is an array of scalar string values).
     *
     * @return array<int, array<int, string>>
     */
    public function readRows(string $filePath, string $extension): array
    {
        $ext = strtolower($extension);
        $reader = in_array($ext, ['xlsx', 'xls'], true) ? new XlsxReader() : new CsvReader();
        $reader->open($filePath);

        $rows = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $cells = $row->toArray();
                $stringCells = array_map(fn ($cell) => trim((string) ($cell ?? '')), $cells);
                // Skip empty lines
                if (count(array_filter($stringCells, fn ($c) => $c !== '')) === 0) {
                    continue;
                }
                $rows[] = $stringCells;
            }
            break; // Read the first sheet only
        }

        $reader->close();

        return $rows;
    }

    /**
     * Stream an XLSX download response directly to the browser.
     *
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, mixed>>  $rows
     */
    public function downloadXlsx(string $filename, array $headers, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows): void {
            $writer = new XlsxWriter();
            $writer->openToFile('php://output');
            $writer->addRow(Row::fromValues($headers));
            foreach ($rows as $row) {
                $writer->addRow(Row::fromValues($row));
            }
            $writer->close();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}

<?php

namespace app\service\export;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class HeaderFormatter
{
    public static function flatten(array $headers): array
    {
        $flat = [];
        foreach ($headers as $header) {
            if (isset($header['children'])) {
                $flat = array_merge($flat, self::flatten($header['children']));
            } else {
                $flat[] = $header;
            }
        }
        return $flat;
    }

    public static function writeHeader(Worksheet $sheet, array $headers, int $row = 1, int &$col = 1): int
    {
        foreach ($headers as $header) {
            if (isset($header['children'])) {
                $startCol = $col;
                $childRow = $row + 1;
                $col = self::writeHeader($sheet, $header['children'], $childRow, $col);
                $endCol = $col - 1;
                /** @noinspection PhpUndefinedMethodInspection */
                $sheet->mergeCellsByColumnAndRow($startCol, $row, $endCol, $row);
                /** @noinspection PhpUndefinedMethodInspection */
                $sheet->setCellValueByColumnAndRow($startCol, $row, $header['title']);
            } else {
                /** @noinspection PhpUndefinedMethodInspection */
                $sheet->setCellValueByColumnAndRow($col, $row, $header['title']);
                /** @noinspection PhpUndefinedMethodInspection */
                $sheet->mergeCellsByColumnAndRow($col, $row, $col, $row + 1); // 占两行
                $col++;
            }
        }
        return $col;
    }
}

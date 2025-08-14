<?php

namespace app\common\service\export;

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
                $startCellCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($startCol) . $row;
                $endCellCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($endCol) . $row;
                $sheet->mergeCells($startCellCoordinate . ':' . $endCellCoordinate);
                $sheet->setCellValue($startCellCoordinate, $header['title']);
            } else {
                $cellCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $row;
                $sheet->setCellValue($cellCoordinate, $header['title']);
                $endCellCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . ($row + 1);
                $sheet->mergeCells($cellCoordinate . ':' . $endCellCoordinate); // 占两行
                $col++;
            }
        }
        return $col;
    }
}

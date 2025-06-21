<?php

namespace app\service\export;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use think\db\Query;
use think\Model;

class ExcelExporter
{
    protected Query|Model $query;
    protected array $headers;
    protected string $fileName;
    protected int $limit;
    protected string $format;

    public function __construct($query, array $headers, string $fileName, int $limit = 1000, string $format = 'xlsx')
    {
        $this->query = $query;
        $this->headers = $headers;
        $this->fileName = $fileName;
        $this->limit = $limit;
        $this->format = $format;
    }

    public function export(): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $flatHeaders = HeaderFormatter::flatten($this->headers);
        HeaderFormatter::writeHeader($sheet, $this->headers);

        $total = (clone $this->query)->count();
        $rowsPerPage = $this->limit;
        $pageCount = ceil($total / $rowsPerPage);

        $rowIndex = 2;

        for ($page = 0; $page < $pageCount; $page++) {
            $data = (clone $this->query)
                ->limit($page * $rowsPerPage, $rowsPerPage)
                ->select()
                ->toArray();

            foreach ($data as $row) {
                $colIndex = 1;
                foreach ($flatHeaders as $header) {
                    $value = $row[$header['field']] ?? '';
                    /** @noinspection PhpUndefinedMethodInspection */
                    $sheet->setCellValueByColumnAndRow($colIndex++, $rowIndex, $value);
                }
                $rowIndex++;
            }
        }

        $filename = $this->fileName . '.' . $this->format;
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment;filename=\"$filename\"");
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}

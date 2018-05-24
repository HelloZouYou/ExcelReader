<?php
namespace ExcelHanlder;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use ExcelHanlder\Helper;

class ExcelReaderFilter implements IReadFilter
{
    private $startRow;
    private $endRow;
    private $startColumn;
    private $endColumn;
    private $columns;

    public function __construct(int $skipRow = 0, int $takeRow = 0, int $skipColumn = 0, int $takeColumn = 0, array $columns)
    {
        $this->startRow = $skipRow + 1;
        $this->endRow = $takeRow == 0 ? null : $skipRow + $takeRow;
        $this->startColumn = $skipColumn;
        $this->endColumn = $takeColumn == 0 ? null : $skipColumn + $takeColumn - 1;
        $this->columns = $columns;
    }

    public function readCell($column, $row, $worksheetName = '') {
        // dd([$column, $row, $this->startRow, $this->endRow, $this->startColumn, $this->endColumn, $this->columns]);
        if ($row >= $this->startRow && ($this->endRow === null || $row <= $this->endRow)) {
            // 如果设置了指定列，则优先选择
            if ($this->columns != []) {
                if (in_array($column, $this->columns, true)) {
                    return true;
                }
                return false;
            // 没有指定列则进行范围选择
            } else {
                // 字母转数字
                $column = Helper::alpha2num($column);
                if ($column >= $this->startColumn && ($this->endColumn === null || $column <= $this->endColumn)) {
                    return true;
                }
                return false;
            }
        }
        return false;
    }
}
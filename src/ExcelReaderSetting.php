<?php
namespace ExcelHanlder;

use ExcelHanlder\ExcelReaderFilter;
use ExcelHanlder\Helper;

class ExcelReaderSetting
{
    protected $reader;
    // 要跳过的行数
    protected $skipRows = 0;
    // 要取的行数
    protected $takeRows = 0;
    // 要选择的列，存A、B、C、D、E、F、G
    protected $skipColumns = 0;
    protected $takeColumns = 0;
    protected $columns = [];

    // 是否忽略空值
    protected $if_ingore_empty = false;
    // 遇到空行是否停止
    protected $if_stop_while_empty_line = false;
    protected $stop_while_empty_line_num = 0;

    protected function setReaderFilter()
    {
        if ($this->skipRows != 0 || $this->takeRows != 0 || $this->skipColumns != 0 || $this->takeColumns != 0 || $this->columns != []) {
        // dd($this->skipRows, $this->takeRows, $this->skipColumns, $this->takeColumns, $this->columns);
            $filterSubset = new ExcelReaderFilter((int)$this->skipRows, (int)$this->takeRows, (int)$this->skipColumns, (int)$this->takeColumns, (array)$this->columns);
            $this->reader->setReadFilter($filterSubset);
        }
    }

    protected function select(array $index_select)
    {
        $this->columns = [];
        foreach ($index_select as $key => $value) {
            // 填写的数字都会转成字符串传入，汉字in_array的时候必定false
            if (is_numeric($value)) {
                $this->columns[] = Helper::num2alpha((int) $value, false);
            } else {
                $this->columns[] = (string)$value;
            }
        }

        return $this;
    }

    protected function takeRows(int $num)
    {
        $this->takeRows = $num;
        return $this;
    }

    protected function skipRows(int $num)
    {
        $this->skipRows = $num;
        return $this;
    }

    protected function limitRows(int $takeRows, int $skipRows)
    {
        $this->skipRows($skipRows);
        $this->takeRows($takeRows);
        return $this;
    }

    protected function takeColumns(int $num)
    {
        $this->takeColumns = $num;
        return $this;
    }

    protected function skipColumns(int $num)
    {
        $this->skipColumns = $num;
        return $this;
    }

    protected function limitColumns(int $takeColumns, int $skipColumns)
    {
        $this->takeColumns($takeColumns);
        $this->skipColumns($skipColumns);
        return $this;
    }

    protected function ingoreEmpty(bool $bool = false)
    {
        $this->if_ingore_empty = $bool;
        return $this;
    }

    protected function stopWhileEmptyLine(int $num)
    {
        if ($num != 0) {
            $this->if_stop_while_empty_line = true;
            $this->stop_while_empty_line_num = $num;
        }
        return $this;
    }
}
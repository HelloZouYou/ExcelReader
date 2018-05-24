<?php
namespace ExcelHanlder;

use Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Csv;

use ExcelHanlder\ExcelReaderSetting;
use ExcelHanlder\Helper;
use Illuminate\Support\Facades\Cache;

class ExcelReader extends ExcelReaderSetting
{
    private static $self_class;

    private $file_path;
    private $file_extension;

    private $support_file_type = ['xls', 'xlsx', 'csv'];
    private $if_cache = false;
    private $ttl_cache = 10;

    /**
     * get方法直接获取数组时可以设置缓存
     * @param  int    $minutes [description]
     * @return [type]          [description]
     */
    protected function remember(int $minutes)
    {
        if ($minutes != 0) {
            $this->if_cache = true;
            $this->ttl_cache = $minutes;
        }
        return $this;
    }

    protected function load(string $path, $callback = null, $encode = 'UTF-8')
    {
        // 设置文件信息
        $this->setFilePathinfo($path);
        // 获取reader
        $this->setFileReader();
        // 只读取数据
        $this->reader->setReadDataOnly(true);
        if (get_class($callback) == 'Closure'){
            call_user_func($callback, $this);
            $this->setReaderFilter();
        }
        $this->setFileEncode($encode);

        return $this;
    }

    protected function selectSheetsByIndex($index)
    {
        switch ($this->file_extension) {
            case 'xls':
            case 'xls':
                $this->reader->setLoadSheetsOnly($index);
                break;
            case 'csv':
                $this->reader->setSheetIndex($index);
                break;
            default:
                # code...
                break;
        }
        return $this;
    }

    protected function setFileEncode($type = 'UTF-8')
    {
        if ($this->file_extension == 'csv') {
            $this->reader->setInputEncoding($type);
        }
    }

    private function getWorkSheet()
    {
        $spreadsheet = $this->reader->load($this->file_path);
        return $spreadsheet->getActiveSheet();
    }

    protected function get()
    {
        $worksheet = $this->getWorkSheet();
        if ($this->if_cache == true) {
            $data = Cache::remember($this->getRememberKey(), $this->ttl_cache, function() use ($worksheet) {
                return $this->getFuncCode($worksheet);
            });
        } else {
            $data = $this->getFuncCode($worksheet);
        }
        return $data;
    }

    private function getRememberKey()
    {
        return 'ExcelReader:' . $this->file_path;
    }

    private function getFuncCode($worksheet)
    {
        $data = [];
        $empty_line_num = 0;
        foreach ($worksheet->getRowIterator() as $i => $row) {
            $cellIterator = $row->getCellIterator();
            // $cellIterator->setIterateOnlyExistingCells($this->if_ingore_empty);// 是否仅选择存在的单元格

            foreach ($cellIterator as $k => $cell) {
                $cell_value = $cell->getValue();
                if ($this->if_ingore_empty == false || $cell_value != null) {
                    $data[$i][Helper::alpha2num($k)] = trim($cell_value);
                }
            }

            // 此行被遍历了，但索引不存在，或者存在但值都为空，则记为空行
            if (!isset($data[$i]) || '' == implode('', $data[$i])) {
                $empty_line_num++;
            }
            // 如果设置了检查空行则判断连续的空行数
            if ($this->if_stop_while_empty_line == true && $empty_line_num >= $this->stop_while_empty_line_num) {
                break;
            }
        }
        return $data;
    }

    protected function yieldData()
    {
        $worksheet = $this->getWorkSheet();

        $empty_line_num = 0;
        foreach ($worksheet->getRowIterator() as $row) {
            $data = [];
            $cellIterator = $row->getCellIterator();
            // $cellIterator->setIterateOnlyExistingCells($this->if_ingore_empty);// 是否仅选择存在的单元格

            foreach ($cellIterator as $k => $cell) {
                $cell_value = $cell->getValue();
                if ($this->if_ingore_empty == false || $cell_value != null) {
                    $data[Helper::alpha2num($k)] = trim($cell_value);
                }
            }
            // 此行被遍历了，但索引不存在，或者存在但值都为空，则记为空行
            if (!isset($data) || '' == implode('', $data)) {
                $empty_line_num++;
            }
            // 如果设置了检查空行则判断连续的空行数
            if ($this->if_stop_while_empty_line == true && $empty_line_num >= $this->stop_while_empty_line_num) {
                break;
            }
            if ($this->if_ingore_empty == true && $data == []) {
                continue;
            }
            yield $data;
        }
    }

    private function setFilePathinfo($path)
    {
        $this->file_path = $path;
        $this->setFileExtension();
    }

    private function setFileExtension()
    {
        $pathinfo = pathinfo($this->file_path);
        $this->file_extension = $pathinfo['extension'];
    }

    private function setFileReader()
    {
        switch ($this->file_extension) {
            case 'xls':
                $this->reader = new Xls();
                break;
            case 'xlsx':
                $this->reader = new Xlsx();
                break;
            case 'csv':
                $this->reader = new Csv();
                break;
            default:
                $this->throwObjectException("Wrong File Type ! Only support " . implode('/', $this->support_file_type) . ".");
                break;
        }
    }

    private static function throwStaticException(string $message = "", int $code = 0, $previous = NULL)
    {
        throw new Exception($message, $code, $previous);
        exit;
    }

    private function throwObjectException(string $message = "", int $code = 0, $previous = NULL)
    {
        throw new Exception($message, $code, $previous);
        exit;
    }

    public static function __callStatic($method, $args)
    {
        $class_name = get_called_class();
        self::$self_class = new $class_name;
        if (method_exists(self::$self_class, $method)) {
            return call_user_func_array([self::$self_class, $method], $args);
        } else {
            self::throwStaticException("Call to undefined method {$class_name}::{$method}().");
            // throw new Exception("Call to undefined method {$class_name}::{$method}()");
        }
    }

    public function __call($method, $args)
    {
        return call_user_func_array([self::$self_class, $method], $args);
    }
}
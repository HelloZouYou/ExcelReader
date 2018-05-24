<?php
namespace Test;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . './helper.php';

use ExcelHanlder\ExcelReader;
use Exception;

class Test
{
    public function index()
    {
        ini_set ('memory_limit', '256M');
        $file_path = iconv('utf-8', 'gb2312', __DIR__ . '/files/10.xlsx');
        $res = ExcelReader::selectSheetsByIndex(0)->load($file_path)->stopWhileEmptyLine(10)->get();
        $data = $this->pregMatchOperator($res, true);
        dd($res, $data);
    }

    /**
     * 调用接口获取三大运营商号段
     */
    private function getOperatorNumber()
    {

        return [
            'cmcc' => '/^13[4-9]\d{8}$|^14[7]\d{8}$|^15[0124789]\d{8}$|^17[8-9]\d{8}$|^18[23478]\d{8}$|^19[8]\d{8}$|^170[356]\d{7}$/',
            'unicom' => '/^13[0-2]\d{8}$|^14[5]\d{8}$|^15[5-6]\d{8}$|^16[7]\d{8}$|^17[156]\d{8}$|^18[5-6]\d{8}$|^170[7-9]\d{7}$|^171[3689]\d{7}$/',
            'telecom' => '/^13[3]\d{8}$|^14[9]\d{8}$|^15[3]\d{8}$|^17[37]\d{8}$|^18[019]\d{8}$|^19[8]\d{8}$|^170[0-2]\d{7}$/',
        ];
    }

    public function array_repeat_member($array)
    {
        // 获取去掉重复数据的数组
        $unique_arr = array_flip(array_flip($array));
        // 获取重复数据的数组
        $repeat_arr = array_diff_assoc($array, $unique_arr);
        return $repeat_arr;
    }

    /**
     * 将号码与运营商号段正则匹配
     * @param  mix    $phone            号码
     * @param  bool   $if_remove_repeat 是否去重复，默认去重
     * @return mix   单个匹配成功则返回true，失败则返回false，数组匹配成功则返回结果
     */
    private function pregMatchOperator($phone, $if_remove_repeat = true, $old_arr = [])
    {

        if ($phone == '') {
            return false;
        }
        // echo memory_get_usage(true) . "<br>";
        // 获取号段正则
        $operator_num = $this->getOperatorNumber();

        if (is_numeric($phone) || is_string($phone)) {
            // $processed_phone = $this->preProcessSinglePhone($phone);
            // 遍历规则
            foreach ($operator_num as $ismg_type => $reg) {
                // 单个如果号段匹配，则返回true
                if (preg_match($reg, (string)(int)$phone) == 1) {
                    switch ($ismg_type) {
                        case 'cmcc':
                            return 1;
                            break;
                        case 'unicom':
                            return 2;
                            break;
                        case 'telecom':
                            return 3;
                            break;
                        default:
                            return true;
                            break;
                    }
                }
            }
            return false;
        } else {
            $total_num          = 0;
            $valid_total_phones = [];
            $valid_phones       = [];
            $invalid_phones     = 0;
            $repeated_phones    = 0;
            if ($old_arr != []) {
                $total_num          = $old_arr['total_num'];
                $valid_total_phones = $old_arr['valid_total_phones'];
                $valid_phones  = $old_arr['valid_phones'];
                $invalid_phones     = $old_arr['invalid_phones'];
                $repeated_phones    = $old_arr['repeated_phones'];
            }
            // 先定义各运营商字段
            foreach ($operator_num as $key => $value) {
                $valid_phones[$key]      = $valid_phones[$key] ?? 0;
            }

            // 遍历规则
            foreach ($phone as $k => $v) {

                if ($v == '') {
                    break;
                } elseif (is_array($v)) {
                    foreach ($v as $x => $value) {

                        if ($value == '') {
                            break;
                        }
                        foreach ($operator_num as $i => $reg) {
                            $value = (string)(int)$value;
                            if (preg_match($reg, $value) == 1) {
                                $valid_total_phones[] = $value;
                                $valid_phones[$i]++;
                                break;
                            }
                        }
                    }
                } else {
                    foreach ($operator_num as $i => $reg) {
                        $v = (string)(int)$v;
                        if (preg_match($reg, $v) == 1) {
                            $valid_total_phones[] = $v;
                            $valid_phones[$i]++;
                            break;
                        }
                    }
                }
                $total_num++;
            }
            // 去重，并计算前后去掉的数目差值
            if ($if_remove_repeat == true) {
                $this_repeated_phones = $this->array_repeat_member($valid_total_phones);
                foreach ($this_repeated_phones as $key => $value) {
                    $ope_key = $this->pregMatchOperator($value);
                    switch ($ope_key) {
                        case 1:
                            $valid_phones['cmcc']--;
                            break;
                        case 2:
                            $valid_phones['unicom']--;
                            break;
                        case 3:
                            $valid_phones['telecom']--;
                            break;
                    }
                }
                $valid_total_phones = array_flip(array_flip($valid_total_phones));
                $repeated_phones += count($this_repeated_phones);
            }
            $invalid_phones = $total_num - count($valid_total_phones) - $repeated_phones;

            return [
                'total_num'          => $total_num,
                'valid_phones'       => $valid_phones,
                'invalid_phones'     => $invalid_phones,
                'repeated_phones'    => $repeated_phones,
                'valid_total_phones' => $valid_total_phones,
            ];
        }
    }
}
$test = new Test;
$test->index();
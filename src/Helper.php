<?php
/**
 * 辅助函数类
 */
namespace ExcelHanlder;

class Helper
{
    /**
     * 将十进制数字转换为二十六进制字母串
     */
    public static function num2alpha($intNum, $isLower=true)
    {
        $num26 = base_convert($intNum, 10, 26);
        $addcode = $isLower ? 49 : 17;
        $result = '';
        for ( $i=0; $i < strlen($num26); $i++ ) {
            $code = ord($num26{$i});
            if ( $code < 58 ) {
                $result .= chr($code+$addcode);
            } else {
                $result .= chr($code+$addcode-39);
            }
        }
        return $result;
    }
    /**
     * 将二十六进制字母串转换为十进制数字
     */
    public static function alpha2num($strAlpha)
    {
        // 判断大小写
        if (ord($strAlpha{0} ) > 90) {
            $startCode = 97;
            $reduceCode = 10;
        } else {
            $startCode = 65;
            $reduceCode = -22;
        }
        $num26 = '';
        for ($i=0; $i<strlen($strAlpha); $i++) {
            $code = ord($strAlpha{$i});
            if ($code < $startCode+10) {
                $num26 .= $code - $startCode;
            } else {
                $num26 .= chr($code - $reduceCode);
            }
        }
        return (int)base_convert($num26, 26, 10);
    }

    /**
     * Excel中为防止数字被科学计数法，将其转字符串
     *
     * @param  int $v 待转化的数字
     * @return string
     */
    public static function excelNumberToString($v)
    {
        return "\t" . (string)$v . "\t";
    }
}
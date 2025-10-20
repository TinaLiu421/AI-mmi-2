<?php
namespace App\Support;

final class Utf8
{
    /** 清洗单个字符串为 UTF-8（去控制字符、转码、去 BOM） */
    public static function normalizeString(?string $s): string
    {
        if ($s === null) return '';
        // 去 BOM
        if (substr($s, 0, 3) === "\xEF\xBB\xBF") {
            $s = substr($s, 3);
        }
        // 统一换行
        $s = str_replace(["\r\n", "\r"], "\n", $s);

        if (!mb_check_encoding($s, 'UTF-8')) {
            $s = @mb_convert_encoding($s, 'UTF-8', 'UTF-8,GBK,GB18030,ISO-8859-1,Windows-1252');
        }
        // 去不可见控制符（保留 \n \t）
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $s) ?? '';
        // 最后兜底：丢弃仍不合法字节
        $s = iconv('UTF-8', 'UTF-8//IGNORE', $s);

        return $s ?? '';
    }

    /** 递归清洗数组里的所有字符串键/值为 UTF-8 */
    public static function normalizeArray(array $arr): array
    {
        $out = [];
        foreach ($arr as $k => $v) {
            $key = is_string($k) ? self::normalizeString($k) : $k;
            if (is_array($v)) {
                $out[$key] = self::normalizeArray($v);
            } elseif (is_string($v)) {
                $out[$key] = self::normalizeString($v);
            } else {
                $out[$key] = $v;
            }
        }
        return $out;
    }
}

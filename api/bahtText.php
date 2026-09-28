<?php
/**
 * ฟังก์ชันแปลงจำนวนเงินตัวเลขเป็นตัวอักษรภาษาไทย (Thai Baht Text)
 * ตัวอย่าง: 1540.50 -> หนึ่งพันห้าร้อยสี่สิบบาทห้าสิบสตางค์
 */

function baht_text($number) {
    if (!is_numeric($number)) return 'ศูนย์บาทถ้วน';

    $number = round((float)$number, 2);
    if ($number == 0) return 'ศูนย์บาทถ้วน';

    $is_negative = $number < 0;
    $number = abs($number);

    $parts = explode('.', number_format($number, 2, '.', ''));
    $integer = $parts[0];
    $decimal = $parts[1];

    $thai_numbers = ['', 'หนึ่ง', 'สอง', 'สาม', 'สี่', 'ห้า', 'หก', 'เจ็ด', 'แปด', 'เก้า'];
    $thai_places  = ['', 'สิบ', 'ร้อย', 'พัน', 'หมื่น', 'แสน', 'ล้าน'];

    $convert_group = function($digits) use ($thai_numbers, $thai_places) {
        $len = strlen($digits);
        $res = '';
        for ($i = 0; $i < $len; $i++) {
            $digit = (int)$digits[$i];
            $place = $len - 1 - $i;
            if ($digit != 0) {
                if ($place == 0 && $digit == 1 && $len > 1) {
                    $res .= 'เอ็ด';
                } elseif ($place == 1 && $digit == 1) {
                    $res .= 'สิบ';
                } elseif ($place == 1 && $digit == 2) {
                    $res .= 'ยี่สิบ';
                } else {
                    $res .= $thai_numbers[$digit] . $thai_places[$place];
                }
            }
        }
        return $res;
    };

    $convert_integer = function($num_str) use ($convert_group) {
        if ((int)$num_str == 0) return 'ศูนย์';
        $groups = [];
        while (strlen($num_str) > 0) {
            if (strlen($num_str) > 6) {
                array_unshift($groups, substr($num_str, -6));
                $num_str = substr($num_str, 0, -6);
            } else {
                array_unshift($groups, $num_str);
                $num_str = '';
            }
        }

        $res = '';
        $g_count = count($groups);
        foreach ($groups as $idx => $grp) {
            $text = $convert_group($grp);
            $millions = $g_count - 1 - $idx;
            $res .= $text;
            if ($text != '' && $millions > 0) {
                $res .= str_repeat('ล้าน', $millions);
            }
        }
        return $res;
    };

    $out = ($is_negative ? 'ลบ' : '') . $convert_integer($integer) . 'บาท';

    $satang = (int)$decimal;
    if ($satang == 0) {
        $out .= 'ถ้วน';
    } else {
        $out .= $convert_group($decimal) . 'สตางค์';
    }

    return $out;
}

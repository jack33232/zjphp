<?php

namespace ZJPHP\Base\Kit;

class ArrayHelper
{
    public static function isAssociative(array $array)
    {
        return (count($array) > 0) && (array_keys($array) !== range(0, count($array) - 1));
    }

    public static function merge(array $a, array $b)
    {
        $args = func_get_args();
        $res = array_shift($args);
        while (!empty($args)) {
            $next = array_shift($args);
            foreach ($next as $k => $v) {
                if (is_int($k)) {
                    if (isset($res[$k])) {
                        $res[] = $v;
                    } else {
                        $res[$k] = $v;
                    }
                } elseif (is_array($v) && isset($res[$k]) && is_array($res[$k])) {
                    $res[$k] = self::merge($res[$k], $v);
                } else {
                    $res[$k] = $v;
                }
            }
        }

        return $res;
    }

    public static function mask(array $mask, array $a, array $b = [])
    {
        return array_intersect_key(
                $a,
                array_flip($mask)
            ) + $b;
    }

    public static function genKey($fields, $data, $ordering = SORT_NATURAL, $glue = '-')
    {
        $key_data = self::mask($fields, $data);
        ksort($key_data, $ordering);
        return implode($glue, $key_data);
    }

    public static function cleanQueryArray(array $array)
    {
        return array_filter($array, function ($item) {
            if (is_string($item)) {
                return trim($item) !== '';
            }
            if (is_array($item)) {
                return !empty(self::cleanQueryArray($item));
            }

            return true;
        });
    }

    public static function usort(array &$array, $key, $type = 'str', $order = 'DESC')
    {
        $callback = function ($a, $b) use ($key, $type, $order) {
            switch ($type) {
                case 'str':
                    if ($order == 'DESC') {
                        return strnatcmp($b[$key], $a[$key]);
                    } else {
                        return strnatcmp($a[$key], $b[$key]);
                    }
                    break;
                case 'num':
                    if ($order == 'DESC') {
                        if ($a[$key] == $b[$key]) {
                            return 0;
                        }

                        return ($b < $a) ? -1 : 1;
                    } else {
                        if ($a[$key] == $b[$key]) {
                            return 0;
                        }

                        return ($a < $b) ? -1 : 1;
                    }
                    break;
                case 'date':
                    $timeA = strtotime($a[$key]);
                    $timeB = strtotime($b[$key]);
                    if ($order == 'DESC') {
                        if ($timeA == $timeB) {
                            return 0;
                        }

                        return ($timeB < $timeA) ? -1 : 1;
                    } else {
                        if ($timeA == $timeB) {
                            return 0;
                        }

                        return ($timeA < $timeB) ? -1 : 1;
                    }
                    break;
            }
        };
        if (self::isAssociative($array)) {
            uasort($array, $callback);
        } else {
            usort($array, $callback);
        }

        return $array;
    }

    public static function smoothUnset(array &$array, $key)
    {
        if (is_numeric($key) && array_key_exists($key, $array)) {
            array_splice($array, $key, 1);
        } else {
            unset($array[$key]);
        }
    }

    public static function inArray($needle, $haystack, $case_insensitive = false)
    {
        if ($case_insensitive) {
            return count(preg_grep('/^'. preg_quote($needle) . '$/i', $haystack)) > 0;
        } else {
            return in_array($needle, $haystack);
        }
    }
}

<?php

namespace Morilog\Jalali;

/**
 * A LaravelPHP helper class for working w/ jalali dates.
 * by Sallar Kaboli <sallar.kaboli@gmail.com>
 *
 *
 * Based on Laravel-Date bundle
 * by Scott Travis <scott.w.travis@gmail.com>
 * http://github.com/swt83/laravel-date
 *
 *
 * @author      Sallar Kaboli <sallar.kaboli@gmail.com>
 *
 * @link        http://
 *
 * @basedon     http://github.com/swt83/laravel-date
 *
 * @license     MIT License
 */
class jDate
{
    protected $time;

    protected $formats = [
        'datetime' => '%Y-%m-%d %H:%M:%S',
        'date' => '%Y-%m-%d',
        'time' => '%H:%M:%S',
    ];

    public static function forge($str = null)
    {
        $class = __CLASS__;

        return new $class($str);
    }

    public function __construct($str = null)
    {
        if ($str === null) {
            $this->time = time();
        } else {
            if (is_numeric($str)) {
                $this->time = $str;
            } else {
                $time = strtotime($str);

                if (! $time) {
                    $this->time = false;
                } else {
                    $this->time = $time;
                }
            }
        }
    }

    public function time()
    {
        return $this->time;
    }

    public function format($str, $convertNumbersToPersian = false)
    {
        // convert alias string
        if (in_array($str, array_keys($this->formats))) {
            $str = $this->formats[$str];
        }

        // if valid unix timestamp...
        if ($this->time !== false) {
            return jDateTime::strftime($str, $this->time, $convertNumbersToPersian);
        } else {
            return false;
        }
    }

    public function reforge($str)
    {
        if ($this->time !== false) {
            // amend the time
            $time = strtotime($str, $this->time);

            // if conversion fails...
            if (! $time) {
                // set time as false
                $this->time = false;
            } else {
                // accept time value
                $this->time = $time;
            }
        }

        return $this;
    }

    public function ago()
    {
        $now = time();
        $time = $this->time();

        // catch error
        if (! $time) {
            return false;
        }

        // build period and length arrays
        $periods = ['ثانیه', 'دقیقه', 'ساعت', 'روز', 'هفته', 'ماه', 'سال', 'قرن'];
        $lengths = [60, 60, 24, 7, 4.35, 12, 10];

        // get difference
        $difference = $now - $time;

        // set descriptor
        if ($difference < 0) {
            $difference = abs($difference); // absolute value
            $negative = true;
        }

        // do math
        for ($j = 0; $difference >= $lengths[$j] and $j < count($lengths) - 1; $j++) {
            $difference /= $lengths[$j];
        }

        // round difference
        $difference = intval(round($difference));

        // return
        return number_format($difference).' '.$periods[$j].' '.(isset($negative) ? '' : 'پیش');
    }

    public function until()
    {
        return $this->ago();
    }

    /**
     * @return array
     */
    public function parseFromFormat($format, $date)
    {
        // reverse engineer date formats
        $keys = [
            'Y' => ['year', '\d{4}'],
            'y' => ['year', '\d{2}'],
            'm' => ['month', '\d{2}'],
            'n' => ['month', '\d{1,2}'],
            'M' => ['month', '[A-Z][a-z]{3}'],
            'F' => ['month', '[A-Z][a-z]{2,8}'],
            'd' => ['day', '\d{2}'],
            'j' => ['day', '\d{1,2}'],
            'D' => ['day', '[A-Z][a-z]{2}'],
            'l' => ['day', '[A-Z][a-z]{6,9}'],
            'u' => ['hour', '\d{1,6}'],
            'h' => ['hour', '\d{2}'],
            'H' => ['hour', '\d{2}'],
            'g' => ['hour', '\d{1,2}'],
            'G' => ['hour', '\d{1,2}'],
            'i' => ['minute', '\d{2}'],
            's' => ['second', '\d{2}'],
        ];

        // convert format string to regex
        $regex = '';
        $chars = str_split($format);
        foreach ($chars as $n => $char) {
            $lastChar = isset($chars[$n - 1]) ? $chars[$n - 1] : '';
            $skipCurrent = '\\' == $lastChar;
            if (! $skipCurrent && isset($keys[$char])) {
                $regex .= '(?P<'.$keys[$char][0].'>'.$keys[$char][1].')';
            } elseif ('\\' == $char) {
                $regex .= $char;
            } else {
                $regex .= preg_quote($char);
            }
        }

        $dt = [];
        $dt['error_count'] = 0;
        // now try to match it
        if (preg_match('#^'.$regex.'$#', $date, $dt)) {
            foreach ($dt as $k => $v) {
                if (is_int($k)) {
                    unset($dt[$k]);
                }
            }
            if (! jDateTime::checkdate($dt['month'], $dt['day'], $dt['year'], false)) {
                $dt['error_count'] = 1;
            }
        } else {
            $dt['error_count'] = 1;
        }
        $dt['errors'] = [];
        $dt['fraction'] = '';
        $dt['warning_count'] = 0;
        $dt['warnings'] = [];
        $dt['is_localtime'] = 0;
        $dt['zone_type'] = 0;
        $dt['zone'] = 0;
        $dt['is_dst'] = '';

        if (strlen($dt['year']) == 2) {
            $now = self::forge('now');
            $x = $now->format('Y') - $now->format('y');
            $dt['year'] += $x;
        }

        $dt['year'] = isset($dt['year']) ? (int) $dt['year'] : 0;
        $dt['month'] = isset($dt['month']) ? (int) $dt['month'] : 0;
        $dt['day'] = isset($dt['day']) ? (int) $dt['day'] : 0;
        $dt['hour'] = isset($dt['hour']) ? (int) $dt['hour'] : 0;
        $dt['minute'] = isset($dt['minute']) ? (int) $dt['minute'] : 0;
        $dt['second'] = isset($dt['second']) ? (int) $dt['second'] : 0;

        return $dt;
    }

    /**
     * @return \DateTime
     */
    public static function dateTimeFromFormat($format, $str)
    {
        $jd = new jDate();
        $pd = $jd->parseFromFormat($format, $str);
        $gd = jDateTime::toGregorian($pd['year'], $pd['month'], $pd['day']);
        $date = new \DateTime();
        $date->setDate($gd[0], $gd[1], $gd[2]);
        $date->setTime($pd['hour'], $pd['minute'], $pd['second']);

        return $date;
    }
}

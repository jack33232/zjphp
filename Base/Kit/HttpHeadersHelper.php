<?php
namespace ZJPHP\Base\Kit;

use ZJPHP\Base\ZJPHP;

class HttpHeadersHelper
{
    public static function cacheControl($expire = false, $public = true)
    {
        $headers = [];
        $timestamp = strtotime($expire);

        if ($expire && $timestamp && $timestamp > time()) {
            $ttl = $timestamp - time();
            $gmtStamp = self::generateGMTStamp($timestamp);
            $headers = [
                "Cache-Control" => ($public) ? 'public' : 'private' . ", max-age=$ttl",
                "Expires" => static::generateGMTStamp($timestamp),
                "Pragma" => ($public) ? 'public' : 'private'
            ];
        } else {
            $gmtStamp = "Sun, 04 Jun 1989 13:00:00 GMT";
            $headers = [
                "Cache-Control" => "no-cache, must-revalidate",
                "Expires" => 0,
                "Pragma" => "no-cache",
                "Last-Modified" => $gmtStamp
            ];
        }

        return $headers;
    }

    public static function fileDownload($filePath)
    {
        $mimetype = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $filePath);
        $basename = basename($filePath);

        $headers = [
            "Content-Disposition" => "attachment; filename=" . $basename
        ];

        $headers = array_merge(
            $headers,
            self::cacheControl(),
            self::contentLength(filesize($filePath)),
            self::contentType($mimetype)
        );

        return $headers;
    }

    public static function excel($basename, $excel07 = true)
    {
        $gmtStamp = "Sun, 04 Jun 1989 13:00:00 GMT";
        $contentType = $excel07 ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' : 'application/vnd.ms-excel';

        $headers = [
            "Content-Type" => $contentType,
            "Content-Disposition" => "attachment;filename=" . $basename,
            "Cache-Control" => "max-age=0",
            "Cache-Control" => "max-age=1",
            "Expires" => $gmtStamp,
            "Cache-Control" => "no-cache, must-revalidate",
            "Pragma" => "public",
            "Last-Modified" => $gmtStamp
        ];

        return $headers;
    }

    public static function crossDomain($domain = '*', $expireAt = null)
    {
        if ($expireAt && ($expireStamp = strtotime($expireAt)) && $expireStamp > time()) {
            $ttl = $expireStamp - time();
        }
        
        $headers = [
            "Access-Control-Allow-Origin" => $domain
        ];

        if (isset($ttl)) {
            $headers["Access-Control-Max-Age"] = $ttl;
        }

        return $headers;
    }

    public static function contentType($mimetype, $isFile = false)
    {
        if ($isFile) {
            $mimetype = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $mimetype);
        }
        return ["Content-Type" => $mimetype];
    }

    public static function gzip()
    {
        $headers = [
            'Content-Encoding' => 'gzip',
            'Transfer-Encoding' => 'chunked'
        ];

        return $headers;
    }

    public static function eTag($file, $key = null)
    {
        $eTagValue = '';
        if ($key) {
            $eTagValue = ZJPHP::$app->get('security')->hashData($file, $key);
        } else {
            $eTagValue = sha1_file($file);
        }

        return [
            "ETag"  => '"' . $eTagValue . '"'
        ];
    }

    public static function contentLength($size)
    {
        if (!is_int($size)) {
            return [];
        }
        return [
            "Content-Length" => $size
        ];
    }

    public static function lastModified($filePath = '')
    {
        $lastModifiedTime = time();
        if (file_exists($filePath) && ($temp = filemtime($filePath))) {
            $lastModifiedTime = $temp;
        }
        $headers = [
            'Last-Modified' => self::generateGMTStamp($lastModifiedTime)
        ];
        return $headers;
    }

    public static function generateGMTStamp($time = 'now')
    {
        $unixTimestamp = is_int($time) ? $time : strtotime($time);
        if (!$unixTimestamp) {
            return '';
        }
        $gmtStamp = gmdate("D, d M Y H:i:s", $unixTimestamp) . " GMT";
        return $gmtStamp;
    }
}

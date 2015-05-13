<?php
namespace paslandau\WebUtility;

use GuzzleHttp\Query;
use GuzzleHttp\Url;
use Pdp\Parser;
use Pdp\PublicSuffixListManager;
use URL\Normalizer;

class WebUtil
{
    /**
     * @var Parser
     */
    private static $domainParser;

    /**
     * Get the full url of the currently called script.
     * @param bool $addPort
     * @return string
     */
    public static function getCalledUrl($addPort = false)
    {
        if (!isset($_SERVER)) {
            throw new \RuntimeException("Server environment variable not set");
        }
        $keys = array(
            "HTTPS", "SERVER_PROTOCOL", "SERVER_PORT", "SERVER_NAME", "REQUEST_URI"
        );
        foreach ($keys as $key) {
            if (!array_key_exists($key, $_SERVER)) {
                throw new \RuntimeException("Key '$key' does not exist");
            }
        }
        $s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
        $sp = mb_strtolower($_SERVER["SERVER_PROTOCOL"]);
        $protocol = mb_substr($sp, 0, mb_strpos($sp, "/")) . $s;
        $port = "";
        if ($addPort) {
            $port .= ":" . $_SERVER["SERVER_PORT"];
        }
        return $protocol . "://" . $_SERVER['SERVER_NAME'] . $port . $_SERVER['REQUEST_URI'];
    }

    /**
     *  Function to convert relative URL to absolute given a base URL
     * @param   string $rel the relative URL
     * @param   string $base the base URL
     * @return string
     */
    public static function relativeToAbsoluteUrl($rel, $base)
    {
        // @todo: do we need to take special care if $rel is empty?
        // @see http://www.nczonline.net/blog/2009/11/30/empty-image-src-can-destroy-your-site/
        // Currently I tend to say no --- this is application specific and should be handled be the caller
        $url = Url::fromString($base);
        return $url->combine($rel)->__toString();
    }

    /**
     *
     * Adds $paramString to the end of $url. Takes edge-cases into account:
     * - $url already has paramaters (so "&" is used instead of "?")
     * - $url ends with "?" or "&"
     * - $paramString starts with "?" or "&"
     * Example:
     * $url = "http://www.google.de/";
     * $paramString = "q=test";
     * ===
     * $result = "http://www.google.de/?q=test";
     * @param string $url
     * @param string $paramString
     * @return string
     */
    public static function appendQueryString($url, $paramString)
    {
        $first = mb_substr($paramString, 0, 1);
        if ($first === '?' || $first === '&') {
            $paramString = mb_substr($paramString, 1);
        }
        $parsed = Query::fromString($paramString);
        return self::appendQuery($url, $parsed);

    }

    /**
     * Appends $query to $url. If $url has already query parameters, $query is merged in
     * @param string $url
     * @param array $query
     * @return string
     */
    public static function appendQuery($url, $query)
    {
        $url = Url::fromString($url);
        $q = $url->getQuery();
        $q->merge($query);
        $url->setQuery($q);
        return $url->__toString();
    }

    /**
     * ToDo: Add Test!
     * Removes everything after "?" (removes "?" as well)
     * @param string $url
     * @return string
     */
    public static function removeQuery($url){
        $idx = mb_strpos($url,"?");
        if($idx === false){
            return $url;
        }
        $newUrl = mb_substr($url,0,$idx);
        return $newUrl;
    }

    /**
     * ToDo: Add Test!
     * Gets the URL query as associative array.
     * @param string $url
     * @return string[]
     */
    public static function getQuery($url){
        $urlObj = Url::fromString($url);
        $query = $urlObj->getQuery();
        $params = $query->toArray();
        return $params;
    }

    /**
     * Gets all (sub)folders of an URL path as array.
     * E.g.
     * $url = "http://example.org/root/folder2/folder3/asd.php?tes=lol";
     * =>
     * array(
     * "root",
     * "folder2",
     * "folder3"
     * );
     * @param $url
     * @return string[]
     */
    public static function getPathSegments($url)
    {
        $url = Url::fromString($url);
        $url->removeDotSegments();
        $res = $url->getPathSegments();
        $res = array_filter($res, function ($v) {
                return !($v === null || $v === "");
            });
        return $res;
    }

    /**
     * Gets the filename of an URL - which is defined as the last segment of the path.
     * If the URL has no path, an empty string is returned.
     * E.g.
     * $url = "http://example.org/root/folder2/folder3/asd.php?tes=lol";
     * =>
     * asd.php
     *
     * @param string $url
     * @return string
     */
    public static function getPathFilename($url)
    {
        $segments = self::getPathSegments($url);
        if (count($segments) > 0) {
            return end($segments);
        }
        return "";
    }

    private static function getDomainParser(){
        if(self::$domainParser === null){
            $pslManager = new PublicSuffixListManager();
            $parser = new Parser($pslManager->getList());
            self::$domainParser = $parser;
        }

        return self::$domainParser;
    }

    /**
     * Gets all subdomains of an URL Host as array.
     * E.g.
     * $url = "http://www.test.bla.example.org/root/folder2/folder3/asd.php?tes=lol";
     * =>
     * array(
     * "www",
     *  "test",
     * "bla",
     * "example",
     * );
     * @param string $url
     * @return string[]
     */
    public static function getSubdomains($url)
    {
        $url = Url::fromString($url);
        $parser = self::getDomainParser();
        $subdomainString = $parser->getSubdomain($url->getHost());
        $parts = explode(".",$subdomainString);
        $res = array_filter($parts, function ($v) {
            return !($v === null || $v === "");
        });
        return $res;
    }

    /**
     * Examples:
     * http://www.example.de/ => example.de
     * http://www.example.co.uk/ => example.co.uk
     * @see Pdp\Parser::getRegisterableDomain()
     * @param $url
     * @return string
     */
    public static function getRegisterableDomain($url){
        $url = Url::fromString($url);
        $parser = self::getDomainParser();
        $domain = $parser->getRegisterableDomain($url->getHost());
        return $domain;
    }

    /**
     * Examples:
     * http://www.example.de/ => de
     * http://www.example.co.uk/ => co.uk
     * @see Pdp\Parser::getPublicSuffix()
     * @param $url
     * @return string
     */
    public static function getPublicSuffix($url){
        $url = Url::fromString($url);
        $parser = self::getDomainParser();
        $domain = $parser->getPublicSuffix($url->getHost());
        return $domain;
    }

//    /**
//     * obsolete due to http://php.net/manual/de/function.idn-to-ascii.php and http://php.net/manual/de/function.idn-to-utf8.php
//     * Decodes the punycode-encoded URL
//     * @param String $url
//     */
//    public static function DecodePunycode($url)
//    {
//        if (self::$converter == null) {
//            self::$converter = new IdnaConverter();
//        }
//        return self::$converter->decode($url);
//    }

    /*
     * $Id: HTTP_Headers_Util.php,v 1.2 2003/01/22 12:25:30 k1m Exp $
    * +----------------------------------------------------------------------+
    * | HTTP Headers Util 0.1                                                |
    * +----------------------------------------------------------------------+
    * | Author: Keyvan Minoukadeh - hide@address.com - http:*www.keyvan.net   |
    * +----------------------------------------------------------------------+
    * | Based on <http:*search.cpan.org/author/GAAS/libwww-perl-5.65/lib/   |
    * | HTTP/Headers/Util.pm>                                                |
    * +----------------------------------------------------------------------+
    * | This program is free software; you can redistribute it and/or        |
    * | modify it under the terms of the GNU General Public License          |
    * | as published by the Free Software Foundation; either version 2       |
    * | of the License, or (at your option) any later version.               |
    * |                                                                      |
    * | This program is distributed in the hope that it will be useful,      |
    * | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
    * | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
    * | GNU General Public License for more details.                         |
    * +----------------------------------------------------------------------+
     * @see (source) http://www.phpkode.com/source/s/http-headers-utility/http-headers-utility/HTTP_Headers_Util.php
     *
     * This method is based on:
     * <http://search.cpan.org/author/GAAS/libwww-perl-5.65/lib/HTTP/Headers/Util.pm>
     * by Gisle Aas.
     * The text here is copied from the documentation of the above, obviously
     * slightly modified as this is PHP not Perl.
     * split_header_words
     *
     * This function will parse the header values given as argument into a
     * array containing key/value pairs.  The function
     * knows how to deal with ",", ";" and "=" as well as quoted values after
     * "=".  A list of space separated tokens are parsed as if they were
     * separated by ";".
     *
     * If the $header_values passed as argument contains multiple values,
     * then they are treated as if they were a single value separated by
     * comma ",".
     *
     * This means that this function is useful for parsing header fields that
     * follow this syntax (BNF as from the HTTP/1.1 specification, but we relax
     * the requirement for tokens).
     *
     *   headers           = #header
     *   header            = (token | parameter) *( [";"] (token | parameter))
     *
     *   token             = 1*<any CHAR except CTLs or separators>
     *   separators        = "(" | ")" | "<" | ">" | "@"
     *                     | "," | ";" | ":" | "\" | <">
     *                     | "/" | "[" | "]" | "?" | "="
     *                     | "{" | "}" | SP | HT
     *
     *   quoted-string     = ( <"> *(qdtext | quoted-pair ) <"> )
     *   qdtext            = <any TEXT except <">>
     *   quoted-pair       = "\" CHAR
     *
     *   parameter         = attribute "=" value
     *   attribute         = token
     *   value             = token | quoted-string
     *
     * Each header is represented by an anonymous array of key/value
     * pairs.  The value for a simple token (not part of a parameter) is null.
     * Syntactically incorrect headers will not necessary be parsed as you
     * would want.
     *
     * This is easier to describe with some examples:
     *
     *    split_header_words('foo="bar"; port="80,81"; discard, bar=baz');
     *    split_header_words('text/html; charset="iso-8859-1");
     *    split_header_words('Basic realm="\"foo\\bar\""');
     *    split_header_words("</TheBook/chapter,2>;         rel=\"pre,vious\"; title*=UTF-8'de'letztes%20Kapitel, </TheBook/chapter4>;rel=\"next\"; title*=UTF-8'de'n%c3%a4chstes%20Kapitel");
     *
     * will return
     *
     *    [foo=>'bar', port=>'80,81', discard=>null], [bar=>'baz']
     *    ['text/html'=>null, charset=>'iso-8859-1']
     *    [Basic=>null, realm=>'"foo\bar"']
     *    ["</TheBook/chapter,2>" => null, "rel" => "pre,vious", "title*" => "UTF-8'de'letztes%20Kapitel" ], ["</TheBook/chapter4>" => null, "rel" => "next", "title*" => "UTF-8'de'n%c3%a4chstes%20Kapitel" ]
     *
     * @param mixed $header_values string or array
     * @throws \Exception
     * @return array
     */
    public static function splitHttpHeaderWords($header_values)
    {
        if (!is_array($header_values)) $header_values = array($header_values);

        $result = array();
        foreach ($header_values as $header) {
            $cur = array();
            while ($header) {
                $key = '';
                $val = null;
                // Parse <link> header correctly http://tools.ietf.org/html/rfc5988#section-5
                if (preg_match('/^\s*(<[^>]*>)(.*)/', $header, $match)) {
                    $key = $match[1];
                    $header = $match[2];
                    $cur[$key] = null;
                } // 'token' or parameter 'attribute'
                elseif (preg_match('/^\s*(=*[^\s=;,]+)(.*)/', $header, $match)) {
                    $key = $match[1];
                    $header = $match[2];
                    // a quoted value
                    if (preg_match('/^\s*=\s*"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"(.*)/', $header, $match)) {
                        $val = $match[1];
                        $header = $match[2];
                        // remove backslash character escape
                        $val = preg_replace('/\\\\(.)/', "$1", $val);
                        // some unquoted value
                    } elseif (preg_match('/^\s*=\s*([^;,\s]*)(.*)/', $header, $match)) {
                        $val = trim($match[1]);
                        $header = $match[2];
                    }
                    // add details
                    $cur[$key] = $val;
                    // reached the end, a new 'token' or 'attribute' about to start
                } elseif (preg_match('/^\s*,(.*)/', $header, $match)) {
                    $header = $match[1];
                    if (count($cur)) $result[] = $cur;
                    $cur = array();
                    // continue
                } elseif (preg_match('/^\s*;(.*)/', $header, $match)) {
                    $header = $match[1];
                } elseif (preg_match('/^\s+(.*)/', $header, $match)) {
                    $header = $match[1];
                } else {
                    throw new \Exception('This should not happen: "' . $header . '"');
                }
            }
            if (count($cur)) $result[] = $cur;
        }
        return $result;
    }

    /*
    * $Id: HTTP_Headers_Util.php,v 1.2 2003/01/22 12:25:30 k1m Exp $
    * +----------------------------------------------------------------------+
    * | HTTP Headers Util 0.1                                                |
    * +----------------------------------------------------------------------+
    * | Author: Keyvan Minoukadeh - hide@address.com - http:*www.keyvan.net   |
    * +----------------------------------------------------------------------+
    * | Based on <http:*search.cpan.org/author/GAAS/libwww-perl-5.65/lib/   |
    * | HTTP/Headers/Util.pm>                                                |
    * +----------------------------------------------------------------------+
    * | This program is free software; you can redistribute it and/or        |
    * | modify it under the terms of the GNU General Public License          |
    * | as published by the Free Software Foundation; either version 2       |
    * | of the License, or (at your option) any later version.               |
    * |                                                                      |
    * | This program is distributed in the hope that it will be useful,      |
    * | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
    * | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
    * | GNU General Public License for more details.                         |
    * +----------------------------------------------------------------------+
     * @see (source)http://www.phpkode.com/source/s/http-headers-utility/http-headers-utility/HTTP_Headers_Util.php
     *
    * join_header_words
    *
    * This will do the opposite of the conversion done by split_header_words().
    * It takes a list of anonymous arrays as arguments (or a list of
    * key/value pairs) and produces a single header value.  Attribute values
    * are quoted if needed.
    *
    * Example:
    *
    *    join_header_words(array(array("text/plain" => null, "charset" => "iso-8859/1")));
    *    join_header_words(array("text/plain" => null, "charset" => "iso-8859/1"));
    *
    * will both return the string:
    *
    *    text/plain; charset="iso-8859/1"
    *
    * @param array $header_values
    * @return string
    * @see http://tools.ietf.org/html/rfc5988#section-5
    */
    public static function joinHttpHeaderWords(array $header_values)
    {
        if(count($header_values) === 0){
            return "";
        }
        // evaluate if its a multidimensional array
        $first = reset($header_values);
        if(!is_array($first)){
            $header_values = [$header_values];
        }

        $spaces = "\\s";
        $ctls = "\\x00-\\x1F\\x7F"; //@see http://stackoverflow.com/a/1497928/413531
        $tspecials = "()<>@,;:<>/[\\]?.=\"\\\\";
        $tokenPattern = "#^[^{$spaces}{$ctls}{$tspecials}]+$#";
        $result = array();
        foreach ($header_values as $header) {
            $attr = array();
            foreach ($header as $key => $val) {
                if (isset($val)) {
                    if (preg_match($tokenPattern, $val)) {
                        $key .= "=$val";
                    } else {
                        $val = preg_replace('/(["\\\\])/', "\\\\$1", $val);
                        $key .= "=\"$val\"";
                    }
                }
                $attr[] = $key;
            }
            if (count($attr)) $result[] = implode('; ', $attr);
        }
        return implode(', ', $result);
    }

    /**
     * Normalizes the given $url according to RFC 3986
     * @see https://github.com/glenscott/url-normalizer
     * @see http://www.apps.ietf.org/rfc/rfc3986.html
     * @param string $url
     * @return string
     */
    public static function normalizeUrl($url)
    {
        $normalizer = new Normalizer($url);
        $url = $normalizer->normalize();
        return $url;
    }


    /**
     *
     * @param string $ip . IP to check, e.g. "66.249.64.5"
     * @param string $expectedHost. Expected host, e.g. "googlebot.com"
     * @param bool $strict [optional]. Default: false.
     *      If true, $expectedHost must match exactly the host returned by gethostbyaddr(). Otherwise it might be a substring at the end.
     *      This is useful if gethostbyaddr() returns a subdomain, e.g. "66-249-64-5.googlebot.com" which should also be considered valid for "googlebot.com"
     * @return string. The found host if it matches $host. Otherwise null.
     * @see https://support.google.com/webmasters/answer/80553
     */
    public static function validateReverseIpLookup($ip, $expectedHost, $strict = false)
    {
        $found_host = gethostbyaddr($ip); // reverse ip
        $found_ip = gethostbyname($found_host); // reverse host

        if($found_ip !== $ip){
            return false;
        }
        if(mb_strtolower($found_host) === mb_strtolower($expectedHost)) {
            return true;
        }
        if (!$strict) {
            $found_host_substr = mb_substr($found_host, -1*mb_strlen($expectedHost));
            if (mb_strtolower($found_host_substr) === mb_strtolower($expectedHost)) {
                return true;
            }
        }
        return false;
    }
}
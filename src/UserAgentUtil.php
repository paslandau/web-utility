<?php

namespace paslandau\WebUtility;


class UserAgentUtil {
    private static $desktop;
    private static $mobile;
    private static $feature;

    /**
     * @see http://www.useragentstring.com/pages/Browserlist/
     * @param bool $desktop [optional]. Default: true.
     * @param bool $mobile [optional]. Default: false.
     * @param bool $feature [optional]. Default: false.
     * @return string[]
     */
    public static function getList($desktop = null, $mobile = null, $feature = null){
        if($desktop === null) {
            $desktop = true;
        }
        if($mobile === null) {
            $mobile = false;
        }
        if($feature === null) {
            $feature = false;
        }

        if(!$desktop && !$mobile && !$feature){
            throw new \InvalidArgumentException("desktop, mobile and feature must not be false at the same time or no user agends can be selected");
        }

        $uas = [];
        if($desktop){
            if(self::$desktop === null) {
                self::$desktop = file(__DIR__ . "/../resources/user-agents-desktop.txt", FILE_IGNORE_NEW_LINES);
            }
            $uas = array_merge($uas, self::$desktop);
        }
        if($mobile){
            if(self::$mobile === null) {
                self::$mobile = file(__DIR__ . "/../resources/user-agents-mobile.txt", FILE_IGNORE_NEW_LINES);
            }
            $uas = array_merge($uas, self::$mobile);
        }
        if($feature){
            if(self::$feature === null) {
                self::$feature = file(__DIR__ . "/../resources/user-agents-feature.txt", FILE_IGNORE_NEW_LINES);
            }
            $uas = array_merge($uas, self::$feature);
        }
        return $uas;
    }

    /**
     * @param int $num
     * @param bool $desktop [optional]. Default: true.
     * @param bool $mobile [optional]. Default: false.
     * @param bool $feature [optional]. Default: false.
     * @return string[]
     */
    public static function getRandom($num, $desktop = null, $mobile = null, $feature = null){
        if($num <= 0 ){
            throw new \InvalidArgumentException("num must be greater than 0, $num given");
        }
        $uas = self::getList($desktop, $mobile, $feature);
        if(count($uas) == 0){
            throw new \RuntimeException("Whoops... seems there are no user agents in the resource files. This shouldn't happen!");
        }
        $res = [];
        while(count($res) < $num){
            shuffle($uas);
            $size = $num - count($res);
            $slice = array_slice($uas,0,$size);
            $res = array_merge($res,$slice);
        }
        return $res;
    }

    /**
     * Gets one random user agent string.
     * @param bool $desktop [optional]. Default: true.
     * @param bool $mobile [optional]. Default: false.
     * @param bool $feature [optional]. Default: false.
     * @return string
     */
    public static function getOneRandom($desktop = null, $mobile = null, $feature = null){
        $res = self::getRandom(1,$desktop, $mobile, $feature);
        return $res;
    }
} 
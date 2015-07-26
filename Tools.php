<?php

/*
    Haase IT - Toolbox
    Copyright (C) 2014 - 2015  Marcus Haase - mail@marcus.haase.name
    Licensed unter LGPL v3
 */

namespace HaaseIT;

class Tools
{
    public static $bEnableDebug = false;
    public static $sDebug = '';

    /**
     * @param $mixed
     * @param string $sLabel
     * @param bool $bDontAccumulate
     * @param bool $bOverrideDisabledDebug
     * @return bool|string
     */
    public static function debug($mixed, $sLabel = '', $bDontAccumulate = false, $bOverrideDisabledDebug = false) {
        if (self::$bEnableDebug || $bOverrideDisabledDebug) {
            $sDebug = '<pre class="debug">';
            if ($sLabel != '') {
                $sDebug .= $sLabel . "\n\n";
            }
            ob_start();
            var_dump($mixed);
            $sDebug .= htmlspecialchars(ob_get_contents());
            ob_end_clean();
            $sDebug .= '</pre>';

            if (!$bDontAccumulate) {
                self::$sDebug .= $sDebug;
            }
            return $sDebug;
        } else {
            return false;
        }
    }

    /**
     * @param int $length
     * @return string
     */
    public static function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[\rand(0, \strlen($characters) - 1)];
        }
        return $randomString;
    }

    /**
     * @param $sHRef
     * @param array $aGetvarstoadd
     * @param bool $bUseGetVarsFromSuppliedHRef
     * @param bool $bMakeAmpersandHTMLEntity
     * @return string
     */
    public static function makeLinkHRefWithAddedGetVars($sHRef, $aGetvarstoadd = array(), $bUseGetVarsFromSuppliedHRef = false, $bMakeAmpersandHTMLEntity = true)
    {
        if ($bUseGetVarsFromSuppliedHRef) {
            $aHRef = \parse_url($sHRef);
            if (isset($aHRef["query"])) {
                $aHRef["query"] = \str_replace('&amp;', '&', $aHRef["query"]);
                $aQuery = \explode('&', $aHRef["query"]);
                foreach ($aQuery as $sValue) {
                    $aGetvarsraw = \explode('=', $sValue);
                    $aGetvars[$aGetvarsraw[0]] = $aGetvarsraw[1];
                }
            }
            $sH = '';
            if (isset($aHRef["scheme"])) {
                $sH .= $aHRef["scheme"] . '://';
            }
            if (isset($aHRef["host"])) {
                $sH .= $aHRef["host"];
            }
            if (isset($aHRef["user"])) {
                $sH .= $aHRef["user"];
            }
            if (isset($aHRef["path"])) {
                $sH .= $aHRef["path"];
            }
        } else {
            $sH = $sHRef;
            if (isset($_GET) && \count($_GET)) {
                $aGetvars = $_GET;
            }
        }
        $bFirstGetVar = true;

        if (count($aGetvarstoadd)) {
            foreach ($aGetvarstoadd as $sKey => $sValue) {
                if ($bFirstGetVar) {
                    $sH .= '?';
                    $bFirstGetVar = false;
                } else {
                    if ($bMakeAmpersandHTMLEntity) {
                        $sH .= '&amp;';
                    } else {
                        $sH .= '&';
                    }
                }
                $sH .= $sKey . '=' . $sValue;
            }
        }
        if (isset($aGetvars) && \count($aGetvars)) {
            foreach ($aGetvars as $sKey => $sValue) {
                if (\array_key_exists($sKey, $aGetvarstoadd)) {
                    continue;
                }
                if ($bFirstGetVar) {
                    $sH .= '?';
                    $bFirstGetVar = false;
                } else {
                    if ($bMakeAmpersandHTMLEntity) {
                        $sH .= '&amp;';
                    } else {
                        $sH .= '&';
                    }
                }
                $sH .= $sKey . '=' . $sValue;
            }
        }

        return $sH;
    }

    /**
     * @param $sImage
     * @param $iBoxWidth
     * @param $iBoxHeight
     * @return array
     */
    public static function calculateImagesizeToBox($sImage, $iBoxWidth, $iBoxHeight)
    {
        $aImagedata = \GetImageSize($sImage);

        if ($aImagedata[0] > $iBoxWidth && $aImagedata[1] > $iBoxHeight) {
            $iWidth = $iBoxWidth;
            $iHeight = $aImagedata[1] / $aImagedata[0] * $iBoxWidth;

            if ($iHeight > $iBoxHeight) {
                $iHeight = $iBoxHeight;
                $iWidth = $aImagedata[0] / $aImagedata[1] * $iBoxHeight;
            }
        } elseif ($aImagedata[0] > $iBoxWidth) {
            $iWidth = $iBoxWidth;
            $iHeight = $aImagedata[1] / $aImagedata[0] * $iBoxWidth;
        } elseif ($aImagedata[1] > $iBoxHeight) {
            $iHeight = $iBoxHeight;
            $iWidth = $aImagedata[0] / $aImagedata[1] * $iBoxHeight;
        } elseif ($aImagedata[0] <= $iBoxWidth && $aImagedata[1] <= $iBoxHeight) {
            $iWidth = $aImagedata[0];
            $iHeight = $aImagedata[1];
        }

        $aData = array(
            'width' => $aImagedata[0],
            'height' => $aImagedata[1],
            'newwidth' => \round($iWidth),
            'newheight' => \round($iHeight),
        );

        if ($aData["width"] != $aData["newwidth"]) {
            $aData["resize"] = true;
        } else {
            $aData["resize"] = false;
        }

        return $aData;
    }

    /**
     * @param $sImage
     * @param $sNewimage
     * @param $iNewwidth
     * @param $iNewheight
     * @param int $sJPGquality
     * @return bool
     */
    public static function resizeImage($sImage, $sNewimage, $iNewwidth, $iNewheight, $sJPGquality = 75)
    {
        $aImagedata = \GetImageSize($sImage);

        if ($aImagedata[2] == 1) { // gif
            $img_old = \imagecreatefromgif($sImage);
            $img_new = \imagecreate($iNewwidth, $iNewheight);
            \imagecopyresampled($img_new, $img_old, 0, 0, 0, 0, $iNewwidth, $iNewheight, $aImagedata[0], $aImagedata[1]);
            \imagedestroy($img_old);
            \imagegif($img_new, $sNewimage);
            \imagedestroy($img_new);
        } elseif ($aImagedata[2] == 2) { // jpg
            $img_old = \imagecreatefromjpeg($sImage);
            $img_new = \imagecreatetruecolor($iNewwidth, $iNewheight);
            \imagecopyresampled($img_new, $img_old, 0, 0, 0, 0, $iNewwidth, $iNewheight, $aImagedata[0], $aImagedata[1]);
            \imagedestroy($img_old);
            \imagejpeg($img_new, $sNewimage, $sJPGquality);
            \imagedestroy($img_new);
        } elseif ($aImagedata[2] == 3) { // png
            $img_old = \imagecreatefrompng($sImage);
            $img_new = \imagecreatetruecolor($iNewwidth, $iNewheight);
            \imagecopyresampled($img_new, $img_old, 0, 0, 0, 0, $iNewwidth, $iNewheight, $aImagedata[0], $aImagedata[1]);
            \imagedestroy($img_old);
            \imagepng($img_new, $sNewimage);
            \imagedestroy($img_new);
        }

        return \file_exists($sNewimage);
    }

    /**
     * @param $sDate
     * @return string
     */
    public static function dateAddLeadingZero($sDate)
    {
        switch ($sDate) {
            case '0':
                return '01';
                break;
            case '1':
                return '01';
                break;
            case '2':
                return '02';
                break;
            case '3':
                return '03';
                break;
            case '4':
                return '04';
                break;
            case '5':
                return '05';
                break;
            case '6':
                return '06';
                break;
            case '7':
                return '07';
                break;
            case '8':
                return '08';
                break;
            case '9':
                return '09';
                break;
        }
        return $sDate;
    }

    /**
     * @param $needle
     * @param $haystack
     * @param array $nodes
     * @return array
     */
    public static function array_search_recursive($needle, $haystack, $nodes = array())
    {
        foreach ($haystack as $key1 => $value1) {
            if (\is_array($value1)) {
                $nodes = self::array_search_recursive($needle, $value1, $nodes);
            } elseif ($key1 == $needle || $value1 == $needle) {
                $nodes[] = array($key1 => $value1);
            }
        }
        return $nodes;
    }

    /**
     * @param $string
     * @param string $length
     * @return string
     */
    public static function cutString($string, $length = "35")
    {
        if (\mb_strlen($string) > $length + 3) {
            $string = \mb_substr($string, 0, $length);
            $string = \trim($string) . "...";
        }
        return $string;
    }

    /**
     * @param $sString
     * @param $iLength
     * @return string
     */
    public static function cutStringend($sString, $iLength)
    {
        return \mb_substr($sString, 0, \mb_strlen($sString) - $iLength);
    }

    /**
     * @param $sKey
     * @param $sBoxvalue
     * @return bool
     */
    public static function getCheckbox($sKey, $sBoxvalue)
    {
        return isset($_REQUEST[$sKey]) && $_REQUEST[$sKey] == $sBoxvalue ? true : false;
    }

    // Verify: ist das Beispiel im folgenden Kommentar noch korrekt? Da jetzt auf !== false geprüft wird
    // Beispiel: $FORM->makeCheckbox('fil_status[A]', 'A', getCheckboxaval('fil_status', 'A'))
    // das array muss benannte schlüssel haben da sonst der erste (0) wie false behandelt wird!
    /**
     * @param $sKey
     * @param $sBoxvalue
     * @return bool
     */
    public static function getCheckboxaval($sKey, $sBoxvalue)
    {
        return isset($_REQUEST[$sKey]) && \array_search($sBoxvalue, $_REQUEST[$sKey]) !== false ? true : false;
    }

    // Expects list of options, one option per line
    /**
     * @param $sString
     * @return array
     */
    public static function makeOptionsArrayFromString($sString)
    {
        $sString = \str_replace("\r", "", $sString);
        $aOptions = \explode("\n", $sString);
        return $aOptions;
    }

    /**
     * @param $aOptions
     * @param $sSelected
     * @return mixed
     */
    public static function getOptionname($aOptions, $sSelected)
    {
        foreach ($aOptions as $sValue) {
            $aTMP = \explode('|', $sValue);
            if ($aTMP[0] == $sSelected) {
                return $aTMP[1];
            }
        }
    }

    /**
     * @param $sKey
     * @param string $sDefault
     * @param bool $bEmptyisvalid
     * @return string
     */
    public static function getFormfield($sKey, $sDefault = '', $bEmptyisvalid = false)
    {
        if (isset($_REQUEST[$sKey])) {
            if ($bEmptyisvalid && $_REQUEST[$sKey] == '') {
                return '';
            } elseif ($_REQUEST[$sKey] != '') {
                return htmlentities($_REQUEST[$sKey]);
            } else {
                return $sDefault;
            }
        } else {
            return $sDefault;
        }
    }

    protected static $COUNTER_makeListtable;

    /**
     * @param $aC
     * @param $aData
     * @param $twig
     * @return mixed
     */
    public static function makeListtable($aC, $aData, $twig)
    {
        // v 1.5
        /*
        Changes in 1.5 2014-12-21:
        moved function to class Tools
        changed function to use twig template

        Changes in 1.4 2014-06-13:
        changed: css to highlight rows
        added: config option to escape html specialchars in listing
        added: add new value now done inside function

        Changes in 1.3:
        changed: row-highlighting changed from js to css
        added: rows now markable with mouseclick

        Changes in 1.2:
        added: global variable $COUNTER_makeListtable, this counts, how many listtables have been on a page yet so each listtable tr gets an unique css id
        if multiple listtables were on each page, the mouseover effect only ever changes the color in the first listtable.

        Changes in 1.1:
        added: 'style-head' attribute for headline-colum
        added: 'style-data' attribute for data-colums
        added: possibility to attach an event to a linked colum (like "onClick")
        fixed: if more than 1 linked colum is defined, all colums used the 'lgetvars' of the last colum

        Relevant CSS Data:
        .listtable{width:100%}
        .listtable-head{font-weight:700;padding:1px}
        .listtable-data{text-align:left;padding:1px}
        .listtable tr:nth-child(even){background-color:#eaeaea;}
        .listtable tr:nth-child(odd){background-color:#fff;}
        .listtable tr:hover{background-color:#bbb;}
        .listtable-marked{background-color:#b0ffb0 !important;}
        .listtable tr.listtable-marked:hover{background-color:#10ff10 !important;}
        .listtable thead tr{background: #bfbfbf !important;}

        Expected config data (arg: $aC)
        $aListSetting = array(
        array('title' => 'Kd Nr.', 'key' => 'adk_nummer', 'width' => 150, 'linked' => false,),
        array(
        'title' => 'Vorg. Nummer',
        'key' => 'vk_nummer',
        'width' => 150,
        'linked' => false,
        'escapehtmlspecialchars' => false,
        'styledata' => 'text-align: center;',
        'stylehead' => 'text-align: center;',
        ),
        array(
        'title' => 'löschen',
        'key' => 'vk_wv_nummer',
        'width' => 60,
        'linked' => true,
        'ltarget' => $_SERVER["PHP_SELF"],
        'lkeyname' => 'id',
        'lgetvars' => array(
        'action' => 'delete',
        ),
        'levents' => 'onClick="return confirm(\'Wirklich löschen?\');"',
        ),
        );
        */

        if (is_int(self::$COUNTER_makeListtable)) {
            self::$COUNTER_makeListtable++;
        } else {
            self::$COUNTER_makeListtable = 1;
        }

        $aLData["C"] = $aC;

        if (is_array($aC)) {
            $aLData["rows"] = $aData;
            $aLData["counter_listtable"] = self::$COUNTER_makeListtable;
        }

        return $twig->render('listtable.twig', $aLData);
    }
}

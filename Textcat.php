<?php

/*
    Haase IT - Toolbox
    Licensed unter LGPL v3
 */

namespace HaaseIT;

class Textcat
{
    protected static $T, $sLang, $sDefaultlang;
    public static function loadTextcats($sLang, $sDefaultlang, $DB)
    {
        $sQ = "SELECT * FROM textcat_base LEFT JOIN textcat_lang ON textcat_base.tc_id = textcat_lang.tcl_tcid && tcl_lang = :lang";
        $hResult = $DB->prepare($sQ);
        $hResult->bindValue(':lang', $sLang, \PDO::PARAM_STR);
        $hResult->execute();
        while ($aRow = $hResult->fetch()) {
            $aTextcat[$sLang][$aRow["tc_key"]] = $aRow;
        }

        if ($sLang != $sDefaultlang) {
            $hResult = $DB->prepare($sQ);
            $hResult->bindValue(':lang', $sDefaultlang, \PDO::PARAM_STR);
            $hResult->execute();
            while ($aRow = $hResult->fetch()) $aTextcat[$sDefaultlang][$aRow["tc_key"]] = $aRow;
        }

        self::$sLang = $sLang;
        self::$sDefaultlang = $sDefaultlang;
        if (isset($aTextcat)) {
            self::$T = $aTextcat;
        }
    }

    public static function T($sTextkey, $bReturnFalseIfNotAvailable = false)
    {
        if (isset($_GET["showtextkeys"])) {
            $sH = '['.$sTextkey.']';
        } else {
            if (isset(self::$T[self::$sLang][$sTextkey]["tcl_text"]) && \trim(self::$T[self::$sLang][$sTextkey]["tcl_text"]) != '') {
                $sH = \trim(self::$T[self::$sLang][$sTextkey]["tcl_text"]);
            } elseif (isset(self::$T[self::$sDefaultlang][$sTextkey]["tcl_text"]) && \trim(self::$T[self::$sDefaultlang][$sTextkey]["tcl_text"]) != '') {
                $sH = \trim(self::$T[self::$sDefaultlang][$sTextkey]["tcl_text"]);
            }
            if (!isset($sH) || $sH == '') {
                if ($bReturnFalseIfNotAvailable) return false;
                else $sH = 'Missing Text: '.$sTextkey;
            }
        }

        return $sH;
    }
}
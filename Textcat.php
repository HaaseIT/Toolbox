<?php

/*
    Haase IT - Toolbox
    Copyright (C) 2014 - 2015  Marcus Haase - mail@marcus.haase.name
    Licensed unter LGPL v3
 */

namespace HaaseIT;

class Textcat
{
    protected static $T, $sLang, $sDefaultlang, $DB;
    public static function init($DB, $sLang, $sDefaultlang) {
        self::$DB = $DB;
        self::$sLang = $sLang;
        self::$sDefaultlang = $sDefaultlang;
    }

    public static function loadTextcats()
    {
        $sQ = "SELECT * FROM textcat_base LEFT JOIN textcat_lang ON textcat_base.tc_id = textcat_lang.tcl_tcid && tcl_lang = :lang";
        $hResult = self::$DB->prepare($sQ);
        $hResult->bindValue(':lang', self::$sLang, \PDO::PARAM_STR);
        $hResult->execute();
        while ($aRow = $hResult->fetch()) {
            $aTextcat[self::$sLang][$aRow["tc_key"]] = $aRow;
        }

        if (self::$sLang != self::$sDefaultlang) {
            $hResult = self::$DB->prepare($sQ);
            $hResult->bindValue(':lang', self::$sDefaultlang, \PDO::PARAM_STR);
            $hResult->execute();
            while ($aRow = $hResult->fetch()) $aTextcat[self::$sDefaultlang][$aRow["tc_key"]] = $aRow;
        }

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

<?php

/*
    Haase IT - Toolbox
    Copyright (C) 2014 - 2015  Marcus Haase - mail@marcus.haase.name
    Licensed unter LGPL v3
 */

namespace HaaseIT;

class Textcat
{
    protected static $T, $sLang, $sDefaultlang, $DB, $bSingleMode;

    /**
     * @param $DB
     * @param $sLang
     * @param $sDefaultlang
     */
    public static function init($DB, $sLang, $sDefaultlang, $bSingleMode = false) {
        self::$DB = $DB;
        self::$sLang = $sLang;
        self::$sDefaultlang = $sDefaultlang;
        self::$bSingleMode = $bSingleMode;
        if (!$bSingleMode) {
            self::loadTextcats();
        }
    }

    /**
     *
     */
    protected static function loadTextcats()
    {
        $sQ = "SELECT * FROM textcat_base LEFT JOIN textcat_lang ON textcat_base.tc_id = textcat_lang.tcl_tcid ";
        $sQ .= "&& tcl_lang = :lang ORDER BY tc_key";
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

    /**
     * @param $iID
     * @return mixed
     */
    public static function getSingleTextByID($iID) {
        $sQ = "SELECT * FROM textcat_base LEFT JOIN textcat_lang ON textcat_base.tc_id = textcat_lang.tcl_tcid ";
        $sQ .= "&& tcl_lang = :lang WHERE tc_id = :id";
        $hResult = self::$DB->prepare($sQ);
        $hResult->bindValue(':id', $iID);
        $hResult->bindValue(':lang', self::$sLang);
        $hResult->execute();

        return $hResult->fetch();
    }

    /**
     * @param $sTextkey
     * @param bool $bReturnFalseIfNotAvailable
     * @return bool|string
     */
    public static function T($sTextkey, $bReturnFalseIfNotAvailable = false)
    {
        // TODO: make fit for single mode
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

    /**
     * @return mixed
     */
    public static function getCompleteTextcatForCurrentLang() {
        return self::$T[self::$sLang];
    }

    /**
     * @param $iID
     */
    public static function initTextIfVoid($iID) {
        // Check if this textkey already has a child in the language table, if not, insert one
        $sQ = "SELECT * FROM textcat_lang WHERE tcl_tcid = :id AND tcl_lang = :lang";
        $hResult = self::$DB->prepare($sQ);
        $hResult->bindValue(':id', $iID);
        $hResult->bindValue(':lang', self::$sLang);
        $hResult->execute();

        if ($hResult->rowCount() == 0) {
            $aData = array(
                'tcl_tcid' => $iID,
                'tcl_lang' => self::$sLang
            );
            $sQ = \HaaseIT\DBTools::buildPSInsertQuery($aData, 'textcat_lang');
            //echo $sQ;
            $hResult = self::$DB->prepare($sQ);
            foreach ($aData as $sKey => $sValue) $hResult->bindValue(':'.$sKey, $sValue);
            $hResult->execute();
        }
    }

    /**
     * @param $iLID
     * @param $sText
     */
    public static function saveText($iLID, $sText) {
        $aData = array(
            'tcl_id' => $iLID,
            'tcl_text' => $sText,
        );
        $sQ = \HaaseIT\DBTools::buildPSUpdateQuery($aData, 'textcat_lang', 'tcl_id');
        //\HaaseIT\Tools::debug($sQ);
        $hResult = self::$DB->prepare($sQ);
        foreach ($aData as $sKey => $sValue) $hResult->bindValue(':'.$sKey, $sValue);
        $hResult->execute();
    }

    // TODO: implement add text key
}

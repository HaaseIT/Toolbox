<?php

/*
    Haase IT - Toolbox
    Copyright (C) 2014 - 2015  Marcus Haase - mail@marcus.haase.name
    Licensed unter LGPL v3
 */

namespace HaaseIT;

class Textcat
{
    protected static $T, $sLang, $sDefaultlang, $DB, $bVerbose = false, $logdir;
    public static $purifier;

    /**
     * @param $DB
     * @param $sLang
     * @param $sDefaultlang
     */
    public static function init($DB, $sLang, $sDefaultlang, $bVerbose = false, $logdir = '') {
        self::$DB = $DB;
        self::$bVerbose = $bVerbose;
        self::$sLang = \filter_var($sLang, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
        self::$sDefaultlang = \filter_var($sDefaultlang, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
        self::$logdir = $logdir;
        self::loadTextcats();
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
        while ($aRow = $hResult->fetch(\PDO::FETCH_ASSOC)) {
            $aTextcat[self::$sLang][$aRow["tc_key"]] = $aRow;
        }

        if (self::$sLang != self::$sDefaultlang) {
            $hResult = self::$DB->prepare($sQ);
            $hResult->bindValue(':lang', self::$sDefaultlang, \PDO::PARAM_STR);
            $hResult->execute();
            while ($aRow = $hResult->fetch(\PDO::FETCH_ASSOC)) $aTextcat[self::$sDefaultlang][$aRow["tc_key"]] = $aRow;
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
        $hResult->bindValue(':id', \filter_var($iID, FILTER_SANITIZE_NUMBER_INT));
        $hResult->bindValue(':lang', self::$sLang);
        $hResult->execute();

        return $hResult->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $sTextkey
     * @param bool $bReturnFalseIfNotAvailable
     * @return bool|string
     */
    public static function T($sTextkey, $bReturnFalseIfNotAvailable = false)
    {
        $sH = '';
        $sTextkey = \filter_var($sTextkey, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
        if (isset($_GET["showtextkeys"])) {
            $sH = '['.$sTextkey.']';
        } else {
            if (isset(self::$T[self::$sLang][$sTextkey]["tcl_text"]) && \trim(self::$T[self::$sLang][$sTextkey]["tcl_text"]) != '') {
                $sH = \trim(self::$T[self::$sLang][$sTextkey]["tcl_text"]);
            } elseif (isset(self::$T[self::$sDefaultlang][$sTextkey]["tcl_text"]) && \trim(self::$T[self::$sDefaultlang][$sTextkey]["tcl_text"]) != '') {
                $sH = \trim(self::$T[self::$sDefaultlang][$sTextkey]["tcl_text"]);
            }
            if (!isset($sH) || $sH == '') {
                if (self::$logdir != '' && is_dir(self::$logdir) && is_writable(self::$logdir)) {
                    error_log(date('c').' Missing Text: '.$sTextkey.PHP_EOL, 3, self::$logdir.DIRECTORY_SEPARATOR.'errors_textcats.log');
                }
                if ($bReturnFalseIfNotAvailable) return false;
                elseif (self::$bVerbose) $sH = 'Missing Text: '.$sTextkey;
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
        $iID = \filter_var($iID, FILTER_SANITIZE_NUMBER_INT);
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
        if (self::$purifier != NULL) {
            $sText = self::$purifier->purify($sText);
        }
        $aData = array(
            'tcl_id' => \filter_var($iLID, FILTER_SANITIZE_NUMBER_INT),
            'tcl_text' => $sText,
        );
        $sQ = \HaaseIT\DBTools::buildPSUpdateQuery($aData, 'textcat_lang', 'tcl_id');
        //\HaaseIT\Tools::debug($sQ);
        $hResult = self::$DB->prepare($sQ);
        foreach ($aData as $sKey => $sValue) $hResult->bindValue(':'.$sKey, $sValue);
        $hResult->execute();
    }

    /**
     * @param $sKey
     * @return array
     */
    public static function verifyAddTextKey($sKey) {
        $aErr = array();
        $sKey = \filter_var(trim($sKey), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
        if (\strlen($sKey) < 3) {
            $aErr["keytooshort"] = true;
        } elseif (\strlen($sKey) > 64) {
            $aErr["keytoolong"] = true;
        } elseif (\preg_match("/^[a-zA-Z0-9_]*$/", $sKey) != 1) {
            $aErr["invalidcharacter"] = true;
        }
        if (\count($aErr) == 0) {
            $sQ = "SELECT tc_key FROM textcat_base WHERE tc_key = :key";
            $hResult = self::$DB->prepare($sQ);
            $hResult->bindValue(':key', $sKey);
            $hResult->execute();
            $iRows = $hResult->rowCount();
            if ($iRows > 0) $aErr["keyalreadyexists"] = true;
        }

        return $aErr;
    }

    /**
     * @param $sKey
     * @return mixed
     */
    public static function addTextKey($sKey) {
        $aData = array('tc_key' => \trim(\filter_var($sKey, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW)),);
        $sQ = \HaaseIT\DBTools::buildInsertQuery($aData, 'textcat_base');
        //HaaseIT\Tools::debug($sQ);
        self::$DB->exec($sQ);
        $iId = self::$DB->lastInsertId();

        return $iId;
    }

    public static function deleteText($iID) {
        // delete children
        $sQ = "DELETE FROM textcat_lang WHERE tcl_tcid = '".\filter_var($iID, FILTER_SANITIZE_NUMBER_INT)."'";
        self::$DB->exec($sQ);

        // then delete base row
        $sQ = "DELETE FROM textcat_base WHERE tc_id = '".\filter_var($iID, FILTER_SANITIZE_NUMBER_INT)."'";
        self::$DB->exec($sQ);

        return true;
    }
}

<?php

/*
    Haase IT - Toolbox
    Copyright (C) 2014 - 2016  Marcus Haase - mail@marcus.haase.name
    Licensed unter LGPL v3
 */

namespace HaaseIT;

class Textcat
{
    protected $T, $sLang, $sDefaultlang, $DB, $bVerbose, $logdir;
    public $purifier;

    public function __construct($container, $defaultlang, $verbose = false, $logdir = '')
    {
        $this->sLang = $container['lang'];
        $this->sDefaultlang = $defaultlang;
        $this->DB = $container['db'];
        $this->bVerbose = $verbose;
        $this->logdir = $logdir;
    }

    /**
     * @param $DB
     * @param $sLang
     * @param $sDefaultlang
     */
    /*
    public static function init($DB, $sLang, $sDefaultlang, $bVerbose = false, $logdir = '') {
        self::$DB = $DB;
        self::$bVerbose = $bVerbose;
        self::$sLang = \filter_var($sLang, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
        self::$sDefaultlang = \filter_var($sDefaultlang, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
        self::$logdir = $logdir;
        self::loadTextcats();
    }
    */

    /**
     *
     */
    protected function loadTextcats()
    {
        $sql = "SELECT * FROM textcat_base LEFT JOIN textcat_lang ON textcat_base.tc_id = textcat_lang.tcl_tcid ";
        $sql .= "&& tcl_lang = :lang ORDER BY tc_key";
        $hResult = $this->DB->prepare($sql);
        $hResult->bindValue(':lang', $this->sLang, \PDO::PARAM_STR);
        $hResult->execute();
        while ($aRow = $hResult->fetch(\PDO::FETCH_ASSOC)) {
            $aTextcat[$this->sLang][$aRow["tc_key"]] = $aRow;
        }

        if ($this->sLang != $this->sDefaultlang) {
            $hResult = $this->DB->prepare($sql);
            $hResult->bindValue(':lang', $this->sDefaultlang, \PDO::PARAM_STR);
            $hResult->execute();
            while ($aRow = $hResult->fetch(\PDO::FETCH_ASSOC)) $aTextcat[$this->sDefaultlang][$aRow["tc_key"]] = $aRow;
        }

        if (isset($aTextcat)) {
            $this->T = $aTextcat;
        }
    }

    /**
     * @param $iID
     * @return mixed
     */
    public function getSingleTextByID($iID) {
        $sql = "SELECT * FROM textcat_base LEFT JOIN textcat_lang ON textcat_base.tc_id = textcat_lang.tcl_tcid ";
        $sql .= "&& tcl_lang = :lang WHERE tc_id = :id";
        $hResult = $this->DB->prepare($sql);
        $hResult->bindValue(':id', \filter_var($iID, FILTER_SANITIZE_NUMBER_INT));
        $hResult->bindValue(':lang', $this->sLang);
        $hResult->execute();

        return $hResult->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $sTextkey
     * @param bool $bReturnFalseIfNotAvailable
     * @return bool|string
     */
    public function T($sTextkey, $bReturnFalseIfNotAvailable = false)
    {
        $return = '';
        $sTextkey = \filter_var($sTextkey, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
        if (isset($_GET["showtextkeys"])) {
            $return = '['.$sTextkey.']';
        } else {
            if (isset($this->T[$this->sLang][$sTextkey]["tcl_text"]) && \trim($this->T[$this->sLang][$sTextkey]["tcl_text"]) != '') {
                $return = \trim($this->T[$this->sLang][$sTextkey]["tcl_text"]);
            } elseif (isset($this->T[$this->sDefaultlang][$sTextkey]["tcl_text"]) && \trim($this->T[$this->sDefaultlang][$sTextkey]["tcl_text"]) != '') {
                $return = \trim($this->T[$this->sDefaultlang][$sTextkey]["tcl_text"]);
            }
            if (!isset($return) || $return == '') {
                if ($this->logdir != '' && is_dir($this->logdir) && is_writable($this->logdir)) {
                    error_log(date('c').' Missing Text: '.$sTextkey.PHP_EOL, 3, $this->logdir.DIRECTORY_SEPARATOR.'errors_textcats.log');
                }
                if ($bReturnFalseIfNotAvailable) return false;
                elseif ($this->bVerbose) $return = 'Missing Text: '.$sTextkey;
            }
        }

        return $return;
    }

    /**
     * @return mixed
     */
    public function getCompleteTextcatForCurrentLang() {
        return $this->T[$this->sLang];
    }

    /**
     * @param $iID
     */
    public function initTextIfVoid($iID) {
        // Check if this textkey already has a child in the language table, if not, insert one
        $sql = "SELECT * FROM textcat_lang WHERE tcl_tcid = :id AND tcl_lang = :lang";
        $hResult = $this->DB->prepare($sql);
        $iID = \filter_var($iID, FILTER_SANITIZE_NUMBER_INT);
        $hResult->bindValue(':id', $iID);
        $hResult->bindValue(':lang', $this->sLang);
        $hResult->execute();

        if ($hResult->rowCount() == 0) {
            $aData = array(
                'tcl_tcid' => $iID,
                'tcl_lang' => $this->sLang
            );
            $sql = \HaaseIT\DBTools::buildPSInsertQuery($aData, 'textcat_lang');
            $hResult = $this->DB->prepare($sql);
            foreach ($aData as $sKey => $sValue) $hResult->bindValue(':'.$sKey, $sValue);
            $hResult->execute();
        }
    }

    /**
     * @param $iLID
     * @param $sText
     */
    public function saveText($iLID, $sText) {
        if ($this->purifier != NULL) {
            $sText = $this->purifier->purify($sText);
        }
        $aData = array(
            'tcl_id' => \filter_var($iLID, FILTER_SANITIZE_NUMBER_INT),
            'tcl_text' => $sText,
        );
        $sql = \HaaseIT\DBTools::buildPSUpdateQuery($aData, 'textcat_lang', 'tcl_id');
        $hResult = $this->DB->prepare($sql);
        foreach ($aData as $sKey => $sValue) $hResult->bindValue(':'.$sKey, $sValue);
        $hResult->execute();
    }

    /**
     * @param $sKey
     * @return array
     */
    public function verifyAddTextKey($sKey) {
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
            $sql = "SELECT tc_key FROM textcat_base WHERE tc_key = :key";
            $hResult = $this->DB->prepare($sql);
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
    public function addTextKey($sKey) {
        $aData = array('tc_key' => \trim(\filter_var($sKey, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW)),);
        $sql = \HaaseIT\DBTools::buildInsertQuery($aData, 'textcat_base');
        $this->DB->exec($sql);
        $iId = $this->DB->lastInsertId();

        return $iId;
    }

    public function deleteText($iID) {
        // delete children
        $sql = "DELETE FROM textcat_lang WHERE tcl_tcid = '".\filter_var($iID, FILTER_SANITIZE_NUMBER_INT)."'";
        $this->DB->exec($sql);

        // then delete base row
        $sql = "DELETE FROM textcat_base WHERE tc_id = '".\filter_var($iID, FILTER_SANITIZE_NUMBER_INT)."'";
        $this->DB->exec($sql);

        return true;
    }
}

<?php

/*
    Haase IT - Toolbox
    Copyright (C) 2014 - 2015  Marcus Haase - mail@marcus.haase.name
    Licensed unter LGPL v3
 */

namespace HaaseIT;

class DBTools
{

    /**
     * @param $aData
     * @param $sTable
     * @param bool $bKeepAT
     * @return string
     */
    public static function buildInsertQuery($aData, $sTable, $bKeepAT = false)
    {
        $sFields = '';
        $sValues = '';
        foreach ($aData as $sKey => $sValue) {
            $sFields .= $sKey . ", ";
            $sValues .= "'" . Tools::cED($sValue, $bKeepAT) . "', ";
        }
        $sQ = "INSERT INTO " . $sTable . " (" . Tools::cutStringend($sFields, 2) . ") ";
        $sQ .= "VALUES (" . Tools::cutStringend($sValues, 2) . ")";
        return $sQ;
    }

    /**
     * @param $aData
     * @param $sTable
     * @return string
     */
    public static function buildPSInsertQuery($aData, $sTable)
    {
        $sFields = '';
        $sValues = '';
        foreach ($aData as $sKey => $sValue) {
            $sFields .= $sKey . ', ';
            $sValues .= ":" . $sKey . ", ";
        }
        $sQ = "INSERT INTO " . $sTable . " (" . Tools::cutStringend($sFields, 2) . ") VALUES (" . Tools::cutStringend($sValues, 2) . ")";
        return $sQ;
    }

    /**
     * @param $aData
     * @param $sTable
     * @param string $sPKey
     * @param string $sPValue
     * @param bool $bKeepAT
     * @return string
     */
    public static function buildUpdateQuery($aData, $sTable, $sPKey = '', $sPValue = '', $bKeepAT = false)
    {
        $sQ = "UPDATE " . $sTable . " SET ";
        foreach ($aData as $sKey => $sValue) {
            $sQ .= $sKey . " = '" . Tools::cED($sValue, $bKeepAT) . "', ";
        }
        $sQ = Tools::cutStringend($sQ, 2);
        if ($sPKey == '') {
            $sQ .= ' ';
        } else {
            $sQ .= " WHERE " . $sPKey . " = '" . Tools::cED($sPValue, $bKeepAT) . "'";
        }
        return $sQ;
    }

    /**
     * @param $aData
     * @param $sTable
     * @param string $sPKey
     * @return string
     */
    public static function buildPSUpdateQuery($aData, $sTable, $sPKey = '')
    {
        $sQ = "UPDATE " . $sTable . " SET ";
        foreach ($aData as $sKey => $sValue) {
            if ($sPKey != '' && $sKey == $sPKey) {
                continue;
            }
            $sQ .= $sKey . " = :" . $sKey . ", ";
        }
        $sQ = Tools::cutStringend($sQ, 2);
        if ($sPKey == '') {
            $sQ .= ' ';
        } else {
            $sQ .= " WHERE " . $sPKey . " = :" . $sPKey;
        }
        return $sQ;
    }
}

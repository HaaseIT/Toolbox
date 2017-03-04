<?php

/*
    Haase IT - Toolbox
    Copyright (C) 2014 - 2015  Marcus Haase - mail@marcus.haase.name
    Licensed unter LGPL v3
 */

namespace HaaseIT\Toolbox;

class DBTools
{

    /**
     * @param $aData
     * @param $sTable
     * @param bool $bKeepAT
     * @return string
     */
    public static function buildInsertQuery($aData, $sTable)
    {
        $sFields = '';
        $sValues = '';
        foreach ($aData as $sKey => $sValue) {
            $sFields .= $sKey . ", ";
            $sValues .= "'".filter_var($sValue, FILTER_SANITIZE_MAGIC_QUOTES) . "', ";
        }

        return 'INSERT INTO '.$sTable.' ('.Tools::cutStringend($sFields, 2).') VALUES ('.Tools::cutStringend($sValues, 2).')';
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
        return "INSERT INTO " . $sTable . " (" . Tools::cutStringend($sFields, 2) . ") VALUES (" . Tools::cutStringend($sValues, 2) . ")";
    }

    /**
     * @param $aData
     * @param $sTable
     * @param string $sPKey
     * @param string $sPValue
     * @param bool $bKeepAT
     * @return string
     */
    public static function buildUpdateQuery($aData, $sTable, $sPKey = '', $sPValue = '')
    {
        $sql = "UPDATE " . $sTable . " SET ";
        foreach ($aData as $sKey => $sValue) {
            $sql .= $sKey . " = '" . filter_var($sValue, FILTER_SANITIZE_MAGIC_QUOTES) . "', ";
        }
        $sql = Tools::cutStringend($sql, 2);
        if ($sPKey == '') {
            $sql .= ' ';
        } else {
            $sql .= " WHERE " . $sPKey . " = '" . filter_var($sPValue, FILTER_SANITIZE_MAGIC_QUOTES) . "'";
        }
        return $sql;
    }

    /**
     * @param $aData
     * @param $sTable
     * @param string $sPKey
     * @return string
     */
    public static function buildPSUpdateQuery($aData, $sTable, $sPKey = '')
    {
        $sql = "UPDATE " . $sTable . " SET ";
        foreach ($aData as $sKey => $sValue) {
            if ($sPKey != '' && $sKey == $sPKey) {
                continue;
            }
            $sql .= $sKey . " = :" . $sKey . ", ";
        }
        $sql = Tools::cutStringend($sql, 2);
        if ($sPKey == '') {
            $sql .= ' ';
        } else {
            $sql .= " WHERE " . $sPKey . " = :" . $sPKey;
        }
        return $sql;
    }
}

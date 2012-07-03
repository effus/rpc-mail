<?php
/**
 * File:
 * Created: 18.05.12 11:12
 * Comment:
 */

class Db {
    private $dbc;

    /**
     *
     */
    public function __construct() {
        $this->dbc = mysql_connect(DB_HOST,DB_USER,DB_PASS);
        mysql_select_db(DB_BASE,$this->dbc);
        mysql_query("SET NAMES ".RPC_DBCHARSET,$this->dbc);
    }

    public function __destruct() {
        mysql_close($this->dbc);
    }

    /**
     * @param $sql
     * @return resource
     */
    public function query($sql) {
        return mysql_query($sql,$this->dbc);
    }

    /**
     * @param $res
     * @return array
     */
    private function _rows($res) {
        $out=array();
        while ($row = mysql_fetch_assoc($res)) {
            $out[]=$row;
        }
        return $out;
    }

    /**
     * @param $res
     * @return array
     */
    private function _row($res) {
        return mysql_fetch_assoc($res);
    }

    /**
     * @param $sql
     * @return array
     */
    public function getrows($sql) {
        $_r = mysql_query($sql,$this->dbc);
        if ($_r) {
            if (mysql_num_rows($_r)>0) {
                return $this->_rows($_r);
            }
        }
    }

    /**
     * @param $sql
     * @return array
     */
    public function getrow($sql) {
        $_r = mysql_query($sql,$this->dbc);
        if ($_r) {
            if (mysql_num_rows($_r)==1) {
                return $this->_row($_r);
            } else {
                return $this->_rows($_r);
            }
        }
    }

    /**
     * @param $table
     * @param $data
     * @return resource
     */
    public function insert($table,$data) {
        $sql='insert into '.$table.' set ';
        $ks = array();
        foreach ($data as $key=>$value) {
            $ks[]=$key."='$value'";
        }
        $sql .= implode(',',$ks);
        $res = mysql_query($sql,$this->dbc);
        if ($res) {
            return mysql_insert_id($this->dbc);
        }
    }

    /**
     * @param $table
     * @param $data
     * @param $where
     * @return resource
     */
    public function update($table,$data,$where) {
        $sql='update '.$table.' set ';
        $ks = array();
        foreach ($data as $key=>$value) {
            $ks[]=$key."='$value'";
        }
        $sql .= implode(',',$ks);
        $sql .= ' where '.$where;
        return mysql_query($sql,$this->dbc);
    }
}
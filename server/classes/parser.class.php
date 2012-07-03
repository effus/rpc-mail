<?php
/**
 * File:parser.class.php
 * Created: 22.05.12 14:04
 * Comment:
 * browse for mail body and replace some expressions on its values
 */

class Parser {
    private $text;
    private $expr;
    private $user;
    private $db;


    /**
     * @param $dbc
     */
    public function __construct(&$db) {
        $this->db = $db;
        $this->expr = array(
            '%USERNAME%'=>'Friend',
            '%EMAIL%'=>'',
            '%SITEURL%'=>P_SITEURL,
            '%FIRSTNAME%'=>'',
            '%LASTNAME%'=>''
        );
    }


    /**
     * @param $expr
     * @param $value
     */
    public function setForExpr($expr,$value) {
        $this->expr[$expr]=$value;
    }


    /**
     * @param $text
     */
    public function setText($text) {
        $this->text=$text;
    }


    /**
     * @param $email
     */
    public function fillPersonalInfoByEmail($email) {
        $this->user = $this->db->getrow('select * from '.P_USERINFO_TABLE." where login = '$email'");
        if ($this->user) {
            $this->expr['%EMAIL%']=$email;
            $this->expr['%USERNAME%']=$this->user['first_name'].' '.$this->user['last_name'];
            $this->expr['%FIRSTNAME%']=$this->user['first_name'];
            $this->expr['%LASTNAME%']=$this->user['last_name'];
        }
    }


    /**
     * @return mixed
     */
    public function make() {
        foreach($this->expr as $expr=>$val) {
            $this->text = str_replace($expr,$val,$this->text);
        }
        return $this->text;
    }


}
<?php
/**
 * File:
 * Created: 18.05.12 11:12
 * Comment:
 */

class Auth {
    public function check() {
        //$ssl = openssl_x509_parse($_SERVER['SSL_CLIENT_CERT']);
        //error_log('Auth:check >> '.print_r($_SERVER,true));
        return true;
    }
}
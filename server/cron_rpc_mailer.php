<?php
define('BASEDIR','/var/www/rpcmail/');
include "/var/www/rpcmail/config/defines.php";
include RPC_CLASSES_DIR."db.class.php";
include RPC_CLASSES_DIR."mail.class.php";
$db = new Db();
$mail = new Mail($db);
$mail->sending();

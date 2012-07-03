<?php
/**
 * File: index.php
 * Created: 17.05.12 19:50
 * Comment:
 */

define('BASEDIR',dirname(__FILE__));
include BASEDIR."/config/defines.php";
include RPC_CLASSES_DIR."rpc.class.php";

$rpc = new Rpc();

$rpc->register('sendhtml','sendmail');
$rpc->register('sendtxt','sendmail');
$rpc->register('sendattache','sendmail');
$rpc->register('sendembedimg','sendmail');

$rpc->processRemoteRequest();
$rpc->output();
die();
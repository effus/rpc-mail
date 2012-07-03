<?php
/**
 * File: defines.php
 * Created: 22.05.12 13:17
 * Comment:
 */

// Paths
define('RPC_CONFIG_DIR',BASEDIR.'/config/');
define('RPC_CLASSES_DIR',BASEDIR.'/classes/');
define('RPC_LIB_DIR',BASEDIR.'/libs/');

// Database

define('RPC_DBCHARSET','UTF8');
define('MAILQUEUE_TABLE','mailqueue');

// Mail
define('RPC_MAIL_CHARSET','iso-8859-1');
define('RPC_MAIL_FROMNAME','Your Site');
define('RPC_MAIL_SMTPUSER','');
define('RPC_MAIL_SMTPPASS','');

define('RPC_MAIL_SMTPHOST','');
define('RPC_MAIL_SMTPPORT',SMTP_PORT);


define('RPC_DAILY_SEND_LIMIT',50);
define('RPC_HOUR_SEND_LIMIT',50);

//Server
define('RPC_SERVER_URL','http://rpcmail/index.php');
define('RPC_ATTACHE_DIR','/var/www/tmp/mailqueue/');

//Parser
define('P_SITEURL','http://somesite.com/');
define('P_USERINFO_TABLE','usertable');

//POST messages
define('POST_OK','POSTOK');
define('POST_FAIL','POSTFAIL');

<?php

define('RPC_SERV','http://phprpcmail/');
define('AUTH_USER','mailer1');
define('AUTH_PASS','123');

require_once('client.class.php');

$rpcmail = new RpcMail();

$result = $rpcmail->sendhtml(array(
    'email'=>'effusps@gmail.com',
    'subject'=>'Thanx for phprpcserver :)',
    'body'=>'Best regards, <a href="mailto:someuser@someuser.com">someuser</a>',
    'mtype'=>'HTML'
));


echo $result."<br />";

/*
// HTML
$result = $rpcmail->sendtxt(array(
    'email'=>'effusps@gmail.com',
    'subject'=>'Text message',
    'body'=>'This is test message, my friend %USERNAME%',
    'mtype'=>'TEXT'
));

// ATTACHES
$result = $rpcmail->sendattache(array(
    'email'=>'effusps@gmail.com',
    'subject'=>'Message with attache',
    'body'=>'This is test message with attache, <b>my friend %USERNAME%</b>',
    'mtype'=>'ATTACHE',
    'attaches'=>array(
        '/var/www/local/htdocs/rpcmail/client/vinni-puh.jpeg'
    )
));

// EMBED IMAGES
$result = $rpcmail->sendembedimg(array(
    'email'=>'effusps@gmail.com',
    'subject'=>'Message with embed image',
    'body'=>'This is test message with image <img src="cid:id1" />, <b>my friend %USERNAME%</b>',
    'mtype'=>'EMBIMG',
    'attaches'=>array(
        '/var/www/local/htdocs/rpcmail/client/vinni-puh.jpeg'
    ),
    'ids'=>array(
        'id1'
    )
));
*/

<?php
/**
 * File:post.php
 * Created: 21.05.12 17:14
 * Comment:
 */

define('BASEDIR',dirname(__FILE__));
include BASEDIR."/config/defines.php";
include RPC_CLASSES_DIR."db.class.php";
include RPC_CLASSES_DIR."mail.class.php";

$db = new Db();
$mail = new Mail($db);

function responce_log($msg) {
    error_log('post.php >> '.$msg);
    die(POST_FAIL);
}

if (isset($_GET['mailid']) && isset($_GET['a']) && is_numeric($_GET['mailid'])) {
    error_log('post.php >> action:'.$_GET['a']);
    switch($_GET['a']) {
        case 'attache':
            if ($mail->getMailById($_GET['mailid']) && isset($_GET['hash'])) {
                $fileid = $mail->getFileIdByHash($_GET['hash']);
                if ($fileid==-1) responce_log('wrong hash');
                if (isset($_FILES['attache']['tmp_name'])) {
                    $fileaddr = RPC_ATTACHE_DIR.md5($_GET['mailid'].'FefdsB').'_'.$_FILES['attache']['name'];
                    if (move_uploaded_file($_FILES['attache']['tmp_name'], $fileaddr)) {
                        $mail->setFileUploaded($fileid,$fileaddr);
                        die(POST_OK);
                    } else responce_log('can not rename uploaded file');
                } else responce_log('no file posted');
            } else responce_log(' unknown mailid');
            break;
        case 'confirm':
            if ($mail->getMailById($_GET['mailid'])) {
                if ($mail->setMailReady()) {
                    die(POST_OK);
                } else responce_log('can not update mail status');
            }
            break;
        default:
            responce_log('unknown action');
    }
} else responce_log('some params undefined');
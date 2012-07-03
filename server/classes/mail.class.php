<?php
/**
 * File: mail.class.php
 * Created: 18.05.12 11:12
 * Comment:
 */

include RPC_LIB_DIR."class.phpmailer.php";
include RPC_CLASSES_DIR."parser.class.php";

class Mail {
    private $_has_attache;
    private $emails;
    private $subject;
    private $body;
    private $mtype;
    private $files;
    private $filesopts;
    private $id;
    private $db;
    private $setupmail;
    private $m;

    const STATUS_STORE = '0';
    const STATUS_READY = '1';
    const STATUS_ERROR = '2';
    const STATUS_SENDED = '3';

    /**
     * @param $db
     */
    public function __construct(&$db) {
        $this->db = $db;
    }

    /**
     * @param int $status //0 store, 1 ready, 2 error, 3 sended
     * @return bool
     */
    public function queuemsg($status=0) {
        $this->id = $this->db->insert(MAILQUEUE_TABLE,array(
            'emails'=>$this->emails,
            'subject'=>mysql_real_escape_string($this->subject),
            'body'=>mysql_real_escape_string($this->body),
            'mtype'=>$this->mtype,
            'files'=>mysql_real_escape_string(serialize($this->files)),
            'fileopts'=>mysql_real_escape_string(serialize($this->filesopts)),
            'status'=>$status,
            'trydate'=>time()
        ));
        $_q = "select count(*) as c from ".MAILQUEUE_TABLE." where status=".self::STATUS_READY;
        $res = $this->db->getrow($_q);
        if ($res['c'] > 0 && $res['c'] < 10) {
            // send mail immediatly
            error_log('Mail:queuemsg >> sending immediatly');
            $this->sending();
        }
        return true;
    }



    public function do_resp() {
        error_log('Mail:do_resp >> init');
        $out=array(
            'files'=>array(),
            'confirm'=>RPC_SERVER_URL.'post.php?a=confirm&mailid='.$this->id
        );
        if ($this->id) {
            for ($i=0;$i<count($this->filesopts['hashes']);$i++) {
                if (!$this->filesopts['up'][$i]) {  // if not uploaded
                    $out['files'][]=array(
                        'src'=>$this->files[$i],
                        'url'=>RPC_SERVER_URL.'post.php?a=attache&mailid='.$this->id.'&hash='.$this->filesopts['hashes'][$i]
                    );
                }
            }
        }
        return $out;
    }



    /**
     * @param $data
     * @return bool
     */
    public function acceptmsg($data) {
        if (isset($data['email']) && isset($data['subject']) && isset($data['body']) && isset($data['mtype'])) {
            $_emails = explode(',',$data['email']);
            if (is_array($_emails)) {
                for ($i=0;$i<count($_emails);$i++) {
                    if ($_emails[$i]) {
                        if (!$this->_validate($_emails[$i])) {
                            error_log('Mail:acceptmsg >> some emails in wrong format');
                            return;
                        }
                    }
                }
            } else {
                if (!$this->_validate($data['email'])) {
                    error_log('Mail:acceptmsg >> email in wrong format');
                    return;
                }
            }
            if (!$data['subject']) {$data['subject']='(empty)';}
            switch($data['mtype']) {
                case 'TEXT':$this->_has_attache=false;$this->mtype=1;break;
                case 'HTML':$this->_has_attache=false;$this->mtype=2;break;
                case 'ATTACHE':$this->_has_attache=true;$this->mtype=3;break;
                case 'EMBIMG':$this->_has_attache=true;$this->mtype=4;break;
                default:
                    error_log('Mail:acceptmsg >> unknown mtype');
                    return false;
            }
            $this->emails=$data['email'];
            $this->body=$data['body'];
            $this->subject=$data['subject'];
            if (isset($data['attaches'])) {
                $this->files=$data['attaches'];
                for ($i=0;$i<count($data['attaches']);$i++) {
                    $this->filesopts['hashes'][]=md5(microtime().'B4%2hJ'.$i);
                    $this->filesopts['up'][]=false;
                }
            }
            if (isset($data['ids'])) {
                $this->filesopts['ids']=$data['ids'];
            }
            //error_log('Mail:acceptmsg >> all good');
            return true;
        } else {
            error_log('Mail:acceptmsg >> some parameters undefined');
        }
    }

    /**
     * @return boolean
     */
    public function attache_flag() {
        return $this->_has_attache;
    }


    /**
     * @param $email
     * @return bool
     */
    private function _validate($email) {
        if (preg_match('/^[^\W][a-zA-Z0-9_]+(\.[a-zA-Z0-9_]+)*\@[a-zA-Z0-9_]+(\.[a-zA-Z0-9_]+)*\.[a-zA-Z]{2,4}$/',$email)) {
            return true;
        }
    }


    /**
     *
     */
    public function sending() {
        //$cfg=false;
        /*if (!file_exists(RPC_ATTACHE_DIR.'sender.cfg')) {
            $cfg = array('num'=>0,'dt'=>mktime(0,0,0));
            file_put_contents(RPC_ATTACHE_DIR.'sender.cfg',serialize($cfg));
        } else {
            $cfg = unserialize(file_get_contents(RPC_ATTACHE_DIR.'sender.cfg'));
        }
        if ($cfg['num']==50) {
            if ($cfg['dt'] < mktime(0,0,0)) {
                //not today
                $cfg['num']=0;
                $cfg['dt'] = mktime(0,0,0);
            } else {
                //limit
                return;
            }
        }*/
        $_q = "select count(*) as c
        from ".MAILQUEUE_TABLE."
        where status=".self::STATUS_SENDED."
        where trydate > ".(time()-24*60*60);
        $res = $this->db->getrow($_q);
        $cnt = $res['c'];
        if ($cnt > RPC_DAILY_SEND_LIMIT) {
            error_log('Mail:sending >> RPC_DAILY_SEND_LIMIT');
            return;
        }
        $_q = "select count(*) as c
        from ".MAILQUEUE_TABLE."
        where status=".self::STATUS_SENDED."
        where trydate > ".(time()-60*60);
        $res = $this->db->getrow($_q);
        $cnt = $res['c'];
        if ($cnt > RPC_HOUR_SEND_LIMIT) {
            error_log('Mail:sending >> RPC_HOUR_SEND_LIMIT');
            return;
        }

        $_q = "select * from ".MAILQUEUE_TABLE." where status=".self::STATUS_READY." limit 50";
        $res = $this->db->getrows($_q);
        if (!$this->setupmail) $this->setup();
        for ($i=0;$i<count($res);$i++) {
            error_log('Mail:sending >> sending '.($i+1).' of '.(count($res)));
            $this->reset();
            $_mail = $res[$i];
            if ($_mail['emails']=='') {
                $this->db->update(MAILQUEUE_TABLE,array('status'=>self::STATUS_ERROR,'trydate'=>time()),'id = '.$_mail['id']);
                continue;
            }
            $emails_arr = explode(',',$_mail['emails']);
            if (!is_array($emails_arr)) {
                $emails_arr=array($_mail['emails']);
            }
            $err_cnt = 0;
            for ($e=0;$e<count($emails_arr);$e++) {
                if (!$emails_arr[$e]) continue;
                //if ($cfg['num']>50) break;
                if (!$emails_arr[$e]) {
                    error_log('Mail:sending >> bad email');
                    continue;
                }
                $this->m->AddAddress($emails_arr[$e]);
                $parser = new Parser($this->db);
                $parser->fillPersonalInfoByEmail($emails_arr[$e]);
                $parser->setText($_mail['subject']);
                $this->m->Subject=$parser->make();
                $parser->setText($_mail['body']);
                $this->m->Body=$parser->make();

                switch($_mail['mtype']){
                    case 1://TEXT
                        $this->m->IsHTML(false);
                        break;
                    case 2://HTML
                        $this->m->IsHTML(true);
                        break;
                    case 3://ATTACH
                        $this->m->IsHTML(true);
                        $files = unserialize($_mail['files']);
                        $filesopts=unserialize($_mail['fileopts']);
                        for ($i=0;$i<count($files);$i++) {
                            if (isset($filesopts['up'][$i]) && file_exists($files[$i])) {
                                $this->m->AddAttachment($files[$i]);
                            }
                        }
                        break;
                    case 4://IMAGES
                        $this->m->IsHTML(true);
                        $files = unserialize($_mail['files']);
                        $filesopts=unserialize($_mail['fileopts']);
                        for ($i=0;$i<count($files);$i++) {
                            if ($filesopts['up'][$i] && file_exists($files[$i])) {
                                $this->m->AddEmbeddedImage($files[$i],$filesopts['ids'][$i]);
                            }
                        }
                        break;
                    default:
                        continue;
                }
                if ($this->m->Send()) {
                    //$cfg['num']++;
                    $cfg['dt'] = mktime(0,0,0);
                    $this->db->update(MAILQUEUE_TABLE,array('status'=>self::STATUS_SENDED,'trydate'=>time()),'id = '.$_mail['id']);
                } else {
                    $err_cnt++;
                    error_log('Mail:sending >> fail');
                }
            }
            file_put_contents(RPC_ATTACHE_DIR.'sender.cfg',serialize($cfg));
            if ($err_cnt>0) {
                $this->db->update(MAILQUEUE_TABLE,array('status'=>self::STATUS_ERROR,'trydate'=>time()),'id = '.$_mail['id']);
            }
        }
    }



    /**
     *
     */
    private function setup() {
        $this->setupmail=true;
        $this->m = new PHPMailer(true);
        $this->m->IsSMTP();
        $this->m->SMTPAuth = false;
        $this->m->Host = RPC_MAIL_SMTPHOST;
        $this->m->Port = RPC_MAIL_SMTPPORT;
        //$this->m->Username = RPC_MAIL_SMTPUSER;//MAIL_SMTPUSER;
        //$this->m->Password = RPC_MAIL_SMTPPASS;//MAIL_SMTPPASS;
        $this->m->SetFrom(MAIL_SUPPORT_FROM_ADDR,RPC_MAIL_FROMNAME);
        $this->m->AddReplyTo(MAIL_SUPPORT_FROM_ADDR,RPC_MAIL_FROMNAME);
        $this->m->CharSet = RPC_MAIL_CHARSET;
        $this->setupmail=true;
    }

    /**
     *
     */
    private function reset() {
        if ($this->m) {
            $this->m->Subject='';
            $this->m->Body='';
            $this->m->ClearAllRecipients();
            $this->m->ClearAttachments();
        }
    }

    /**
     * @param $mailid
     * @return bool
     */
    public function getMailById($mailid) {
        $_q = "select * from ".MAILQUEUE_TABLE." where id = $mailid";
        $res = $this->db->getrow($_q);
        if (isset($res['id'])) {
            $this->id = $res['id'];
            $this->emails = $res['emails'];
            $this->subject = $res['subject'];
            $this->body = $res['body'];
            $this->files = unserialize($res['files']);
            $this->filesopts = unserialize($res['fileopts']);
            return true;
        }
    }

    /**
     * @param $hash
     * @return int
     */
    public function getFileIdByHash($hash) {
        $fail = -1;
        if (is_array($this->filesopts)) {
            for ($i=0;$i<count($this->filesopts['hashes']);$i++) {
                if ($this->filesopts['hashes'][$i] == $hash) {
                    return $i;
                }
            }
        }
        return $fail;
    }

    /**
     * @param $fileid
     * @param $path
     * @return mixed
     */
    public function setFileUploaded($fileid,$path) {
        error_log('Mail:setFileUploaded >> init');
        if (!is_array($this->files)) return;
        if (!$this->id) return;
        if (isset($this->filesopts['up'][$fileid]) && isset($this->files[$fileid]) ) {
            $this->files[$fileid]=$path;
            $this->filesopts['up'][$fileid]=true;
            error_log('Mail:setFileUploaded >> OK');
            return $this->db->update(MAILQUEUE_TABLE,array(
                'files'=>serialize($this->files),
                'fileopts'=>serialize($this->filesopts)
            ), 'id = '.$this->id);
        }
    }

    /**
     * @return mixed
     */
    public function setMailReady() {
        error_log('Mail:setMailReady >> init');
        if (!$this->id) return;
        error_log('Mail:setMailReady >> OK');
        return $this->db->update(MAILQUEUE_TABLE,array('status'=>self::STATUS_READY,'trydate'=>time()),'id = '.$this->id);
    }
}
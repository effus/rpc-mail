<?php
/**
 * File: rpc.class.php
 * Created: 18.05.12 11:47
 * Comment:
 */

include RPC_CLASSES_DIR."auth.class.php";
include RPC_CLASSES_DIR."db.class.php";
include RPC_CLASSES_DIR."mail.class.php";

class Rpc {
    private $error;
    private $output;
    private $out_files;
    private $out_confirm;
    private $db;
    private $mail;
    private $auth;
    private $methods;

    public function __construct() {
        $this->auth = new Auth();
        $this->db = new Db();
        $this->mail = new Mail($this->db);
    }

    /**
     * call of magic method
     * @param $methodname
     * @param $params
     */
    public function __call($methodname,$params) {
        if (isset($this->methods[$methodname])) {
            $methodcall = $this->methods[$methodname];
            if (method_exists($this, $methodcall)) {
                $this->$methodcall($params);
            }  else $this->rpcError('10');
        } else $this->rpcError('11');
    }

    public function __destruct() {
    }

    /**
     * strange dancing of array indexed...
     * on client: array('key'), on process: array(0=>array('key')),
     * in params of magic method: array(0=>array(0=>array('key')))
     * @param $data
     * @return mixed
     */
    private function _fixParamsData($data) {
        if (isset($data[0]['email'])) return $data[0];
        if (isset($data[0][0]['email'])) return $data[0][0];
    }

    /**
     * @param $code
     */
    private function rpcError($code) {
        include RPC_CONFIG_DIR."errors.php";
        $this->error = $errors[$code];
        error_log('RPCMAIL:rpc.class >> '.$this->error);
    }

    /**
     * Register magic methods
     * @param $rpcmethod
     * @param $classmethod
     */
    public function register($rpcmethod,$classmethod) {
        $this->methods[$rpcmethod]=$classmethod;
    }

    /**
     *
     */
    public function processRemoteRequest() {
        $postdata = file_get_contents("php://input");
        if ($postdata) {
            //error_log('Rpc:processRemoteRequest >> '.$postdata);
            $data = unserialize($postdata);
            if (isset($data['method']) && isset($data['params'])) {
                $_method = $data['method'];
                $_params = $data['params'];
                //error_log(print_r($_params,true));
                $this->$_method($_params);
            } else $this->rpcError('30');
        } else $this->rpcError('20');
    }

    /**
     * @return mixed
     */
    public function output() {
        header('Content-type: text/plain');
        $out = array(
            'result'=>$this->output,
            'error'=>$this->error
        );
        if ($this->out_files) {
            $out['files']=$this->out_files;
        }
        if ($this->out_confirm) {
            $out['confirm']=$this->out_confirm;
        }
        $sout = serialize($out);
        die($sout);
    }

    /**
     * Magic method:sendmail
     * @param $data
     */
    public function sendmail($data) {
        $data=$this->_fixParamsData($data);
        if ($this->auth->check($data)) {
            if ($this->mail->acceptmsg($data)) {
                $res = false;
                if (!$this->mail->attache_flag()) {
                    // simple mail, immediatly sending
                    $res = $this->mail->queuemsg(Mail::STATUS_READY);
                    if ($res) {
                        $this->output='Mail sended';
                    } else $this->output='Mail in queue';
                } else {
                    // mail with attache
                    $this->mail->queuemsg(Mail::STATUS_STORE);
                    $res = $this->mail->do_resp();
                    $this->out_files = $res['files'];
                    $this->out_confirm = $res['confirm'];
                    $this->output = 'ATCMD';
                }
            } else $this->rpcError('41');
        } else $this->rpcError('40');
    }
}
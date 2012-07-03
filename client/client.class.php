<?php
class RpcMail {
    private $_error;
    private $_encdata;

    /**
     * @param $method
     * @param $params
     * @return mixed|string
     */
    public function __call($method,$params) {

        $content=serialize(array(
            'method'=>$method,
            'params'=>$params
        ));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, RPC_SERV);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_USERAGENT, "rpcmailclient");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/plain'));
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, AUTH_USER.':'.AUTH_PASS);

        $rpcres = curl_exec( $ch );
        if (curl_errno($ch)) {
            error_log('CURL error >> '.curl_errno($ch).' : http_code:'.curl_getinfo($ch, CURLINFO_HTTP_CODE).
                ' ssl_verify_result:'.curl_getinfo($ch, CURLINFO_SSL_VERIFYRESULT));
        }

        error_log('CURL >> responce ['.$rpcres.']');
        curl_close($ch);

        $resp = $this->_processResponce($rpcres);
        if ($resp=='ATCMD') {
            return $this->cmdAttaches();
        } else return $resp;
    }

    /**
     * -------------------- Send attachments --------------
     * procedure:
     *  1.  Send mtype 'ATTACHE' or 'EMBIMG' with field 'attaches'
     *  2.  Reseive result 'ATCMD' and addition field 'files' with links and src files to post files
     * and field 'confirm' with link to confirm.
     *  3.  Post files
     *  4.  Open link for confirm, after that message will be send
     *
     * @return string
     */
    private function cmdAttaches() {
        error_log('rpcmailclient.php:cmdAttaches >> init: '.print_r($this->_encdata,true));
        if (isset($this->_encdata['files']) && isset($this->_encdata['confirm'])) {
            for ($i=0;$i<count($this->_encdata['files']);$i++) {
                $_file = $this->_encdata['files'];
		$postresult=true;
                for ($i=0;$i<count($_file);$i++) {
                    $_result = $this->postFile($_file[$i]['src'],$_file[$i]['url']);
		    if ($_result=='POSTFALSE') $postresult=false;
                }
                if (!$postresult) return 'Fail to upload file';
            }
            $res = file_get_contents($this->_encdata['confirm']);
            return $res;
        } else {
            error_log('rpcmailclient.php:cmdAttaches >> some links undefined');
        }
    }

    /**
     * @param $src
     * @param $url
     * @return mixed
     */
    private function postFile($src,$url) {
        if (!file_exists($src)) return;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, "rpcmailclient");
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, AUTH_USER.':'.AUTH_PASS);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        $post = array(
            "attache"=>"@$src",
        );
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    /**
     * @param $rpcdata
     * @return mixed
     */
    private function _processResponce($rpcdata) {
        if ($rpcdata) {
            $data = unserialize($rpcdata);
            $this->_encdata = $data;
            if (isset($data['result'])) {
                if ($data['result']!=='') {
                    $this->_error = '';
                    return $data['result'];
                } else {
                    error_log('RpcMailClient >> RPC fail');
                    if (isset($data['error'])) {
                        $this->_error = $data['error'];
                    } else $this->_error = 'Bad result';
                }
            } else $this->_error = 'Bad result';
        } else $this->_error = 'Bad result';
    }

    /**
     * @return mixed
     */
    public function getError() {
        return $this->_error;
    }
}
?>

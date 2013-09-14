<?php

namespace esmeralda_api\util;

class SlimWrapper extends \Slim\Slim{

    public function __construct($options){
        parent::__construct($options);
    }

    public function render($tpl, $params = array(), $status = NULL){
        $req = $this->request();
        $accept = $req->headers('Accept');
        //if (stripos($accept, 'json')){
            parent::render($tpl.".json", $params); 
            $resp = $this->response();
            $resp['Content-Type'] = 'application/json';
        //}else{
        //    parent::render($tpl.".htm", $params); 
        //    $resp = $this->response();
        //    $resp['Content-Type'] = 'text/html';
        //}
    }
}

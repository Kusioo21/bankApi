<?php
namespace BankAPI;

class LoginResponse {
    private $token;
    private $error;
    
    public function __construct(string $token, string $error) {
        $this->token = $token;
        $this->error = $error;
    }
    public function getJSON() {
        $array = array();
        $array['token'] = $this->token;
        $array['error'] = $this->error;
        return json_encode($array);
    }
    public function send() {
        if($this->error != "") {
            header('HTTP/1.1 401 Unauthorized');
        } else {
            header('HTTP/1.1 200 OK');
        }
        header('Content-Type: application/json');
        echo $this->getJSON();
    }
}
?>
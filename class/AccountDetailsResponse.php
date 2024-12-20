<?php
namespace BankAPI;
use mysqli;

class AccountDetailsResponse {
    private array $account;
    private string $error;

    public function __construct() {
        $this->error = "";
    }
    public function getJSON() {
        $array = array();
        $array['account'] = $this->account;
        $array['error'] = $this->error;
        return json_encode($array);
    }
    public function setAccount(array $account) {
        $this->account = $account;
    }
    public function setError(string $error) {
        $this->error = $error;
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
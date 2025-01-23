<?php
namespace BankAPI;

class TransfersRequest {
    
    private $token;

    public function __construct() {
        $data = file_get_contents('php://input');
        $data = json_decode($data, true);
        $this->token = $data['token'];
    }

    public function getToken() : string {
        return $this->token;
    }
}
?>
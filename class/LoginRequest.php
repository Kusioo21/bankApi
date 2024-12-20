<?php
namespace BankAPI;

class LoginRequest {
    private $login;
    private $password;
    /**
     * Konstruktor pracuje na surowych danych otrzymanych z php://input
     */
    public function __construct() {
        $data = file_get_contents('php://input');
        $data = json_decode($data, true);
        $this->login = $data['login'];
        $this->password = $data['password'];
    }
    public function getLogin() : string {
        return $this->login;
    }
    public function getPassword() : string {
        return $this->password;
    }
}

?>
<?php
namespace BankAPI;
use mysqli;

class AccountDetailsRequest {
    private string $token;


    public function __construct()
    {
        //zakładamy, że aplikacja przekazała nam token w postaci danych JSON
        //przeczytaj surowe dane wejściowe z PHP
        $data = file_get_contents('php://input');
        //przekształć JSON wejściowe w tablicę asocjacyjną
        $dataArray = json_decode($data, true);
        //zakładam, ze w paczce danych jest token pod nazwą "token"
        $this->token = $dataArray['token'];
    }
    public function getToken() : string {
        return $this->token;
    }

}
?>
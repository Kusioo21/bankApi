<?php
namespace BankAPI;

class TransfersResponse {
    
    private $transfers;

    public function __construct(array $transfers) {
        $this->transfers = $transfers;
    }

    public function getJSON() {
        return json_encode([
            'transfers' => $this->transfers
        ]);
    }

    public function send() {
        header('HTTP/1.1 200 OK');
        header('Content-Type: application/json');
        echo $this->getJSON();
    }

    public function sendError(string $message) {
        header('HTTP/1.1 400 Bad Request');
        header('Content-Type: application/json');
        echo json_encode([
            'error' => $message
        ]);
    }
}
?>
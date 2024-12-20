<?php
namespace BankAPI;
use mysqli;
use Exception;
/**
 * Class Token
 * 
 * This class provides functionalities to perform specific operations regarding
 * tokens in our virtual bank.
 */
class Token {
    static function new(string $ip, int $userId, mysqli $db) : string {
        //wygeneruj unikatowy hash w oparciu
        //o adres IP, id użytkownika i czas
        $hash = hash('sha256', $ip . $userId . time());
        //przygotuj zapytanie
        $sql = "INSERT INTO token (token, ip, user_id) VALUES (?, ?, ?)";
        $query = $db->prepare($sql);
        //podmień znaki zapytania na zmienne
        $query->bind_param('ssi', $hash, $ip, $userId);
        //jeśli nie uda się insert do bazy danych to wyrzuć wyjątek
        if(!$query->execute())
            throw new Exception('Could not create token');
        else {
            //w innym wypadku zwróć token
            return $hash;
        }
    }
    //funkcja sprawdzająca poprawność tokenu
    static function check(string $token, string $ip, mysqli $db) : bool {
        //kwerenda
        $sql = "SELECT * FROM token WHERE token = ? AND ip = ?";
        //przygotuj zapytanie
        $query = $db->prepare($sql);
        //podstaw zmienne
        $query->bind_param('ss', $token, $ip);
        //wykonaj zapytanie
        $query->execute();  
        //pobierz wynik
        $result = $query->get_result();
        //jeżeli wynik jest pusty to zwróć false
        if($result->num_rows == 0) {
            //nie ma takiego tokena
            return false;
        } else {
            //jest taki token
            return true;
        }
    }
    //funkcja zwracająca id użytkownika na podstawie tokenu
    static function getUserId(string $token, mysqli $db) : int {
        //szukamy id użytkownika na podstawie tokenu
        //szukamy najnowszego istniejacego tokenu
        //szukamy maksymalnie jednego
        $sql = "SELECT user_id FROM token WHERE token = ? 
                    ORDER BY id DESC LIMIT 1";
        $query = $db->prepare($sql);
        $query->bind_param('s', $token);
        $query->execute();
        $result = $query->get_result();
        //jeśli nie ma tokenu to wyrzuć wyjątek
        if($result->num_rows == 0) {
            throw new Exception('Invalid token');
        } else {
            //zwróć wiersz z tabeli jako tablicę asocjacyjną
            $row = $result->fetch_assoc();
            //wyciągnij i zwróć id użytkownika
            return $row['user_id'];
        }
    }
}
?>
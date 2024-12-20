<?php
namespace BankAPI;
use mysqli;
use Exception;
/**
 * Class User
 *
 * This class provides functionalities to perform specific operations.
 * It includes methods to handle user authentication such as login.
 *
 */
class User {
    /**
     * This method is used to authenticate user. 
     * It checks if the user with given login and password 
     * exists in the database and returns user id if the user exists.
     * 
     * @param string $login - user login
     * @param string $password - user password
     * @param mysqli $db - database connection
     * 
     * @return int - user id
     */
    static function login(string $login, string $password, mysqli $db) : int {
        $sql = "SELECT id, passwordHash FROM user WHERE email = ?";
        //przygotuj zapytanie - mysqli prepared statement
        $query = $db->prepare($sql);
        //podmień znaki zapytania na zmienne
        $query->bind_param('s', $login);
        //wykonaj zapytanie
        $query->execute();
        //pobierz wynik
        $result = $query->get_result();
        //jeżeli wynik jest pusty to wrzuć wyjątek
        if($result->num_rows == 0) {
            //nie ma takiej pary użytkownik/hasło
            throw new Exception('Invalid login or password');
        } else {
            //pobierz id użytkownika
            //fetch_assoc zwraca tablicę asocjacyjną z wynikami zapytania
            //$user reprezentuje jeden wiersz z tabeli user
            $user = $result->fetch_assoc();
            $id = $user['id'];
            $hash = $user['passwordHash'];
            //sprawdzić czy hasło jest poprawne
            if(password_verify($password, $hash)) {
                //zwróciło true - hasło jest poprawne
                //zwracamy id użytkownika
                return $id;
            } else {
                //hasło jest niepoprawne
                throw new Exception('Invalid login or password');
            }
        }
    }
}
?>
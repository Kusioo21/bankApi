<?php
//klasa odpowiadająca za routing czyli przetwarzanie URL zapytania skierowanego do serwera API
//wszystko po localhost/bankAPI trafi dzieki temu do tego skryptu
require_once('Route.php');
//model odpowiadający za tabelę account w bazie danych - umożliwia operacje na rachunkach
require_once('model/Account.php');
//model użytkownika
require_once('model/User.php');
//model tokena
require_once('model/Token.php');
require_once('model/Transfer.php');
require_once('class/LoginRequest.php');
require_once('class/LoginResponse.php');
require_once('class/AccountDetailsRequest.php');
require_once('class/AccountDetailsResponse.php');

//połączenie do bazy danych
//TODO: wyodrębnić zmienne dotyczące środowiska do pliku konfiguracyjnego
$db = new mysqli('localhost', 'root', '', 'bankAPI');
//ustawienie kodowania znaków na utf8 dla bazy danych
$db->set_charset('utf8');

//użyj przestrzeni nazw od klasy routingu i od naszej klasy od rachunków
use Steampixel\Route;
use BankAPI\Account;
use BankAPI\User;
use BankAPI\Transfer;
use BankAPI\Token;
use BankAPI\LoginRequest;
use BankAPI\LoginResponse;
use BankAPI\AccountDetailsRequest;
use BankAPI\AccountDetailsResponse;


//jeśli ktoś zapyta API bez żadnego parametru
//zwróć hello world
//TODO: to jest tylko do testów  - usunąć później
Route::add('/', function() {
  echo 'Hello world!';
});
//ścieżka używana przez aplikację okienkową do logowania
//aplikacja wysyła  nam login i hasło zakodowane JSON metodą post
//API odpowiada do aplikacji wysyłając token w formacie JSON
/**
 * Endpoint służący do zalogowania użytkownika
 * Przyjmuje od aplikacji dane w formacie JSON
 * { login = "login_z_formatki", password = "hasło_z_formatki" }
 */
Route::add('/login', function() use($db) {
  //utwórz obiekt żądania    
  $request = new LoginRequest();
  try {
    //spróbuj zalogować użytkownika
    $id = User::login($request->getLogin(), $request->getPassword(), $db);
    //wygeneruj nowy token dla tego użytkownika i tego IP
    $ip = $_SERVER['REMOTE_ADDR'];
    $token = Token::new($ip, $id, $db);
    //stwórz obiekt odpowiedzi
    $response = new LoginResponse($token, "");
    $response->send();
  } catch (Exception $e) {
    $response = new LoginResponse("", $e->getMessage());
    $response->send();
    return;
  }
  
}, 'post');

//metoda zwracająca do aplikacji szczegóły rachunku 
//metoda identyfikuje użytkownika na podstawie tokenu
//sprawdza w bazie i zwraca dane pierwszego znalezionego rachunku
Route::add('/account/details', function() use($db) {
    
    $request = new AccountDetailsRequest();
    $response = new AccountDetailsResponse();
    //sprawdz poprawność tokena
    if(!Token::check($request->getToken(), $_SERVER['REMOTE_ADDR'], $db)) {
        //jeżeli token jest niepoprawny to zapisz błąd w odpowiedzi
        $response->setError('Invalid token');
    }
    //pobierz id użytkownika na podstawie tokena
    $userId = Token::getUserId($request->getToken(), $db);
    //wyciągamy numer rachunku i zwracamy go jako json
    $accountNo = Account::getAccountNo($userId, $db);
    $account = Account::getAccount($accountNo, $db);
    //ładujemy dane o koncie do odpowiedzi
    $response->setAccount($account->getArray());
    //wysyłamy odpowiedź
    $response->send();
}, 'post');

//ścieżka wyświetla dane dotyczące rachunku bankowego po jego numerze
//jeżeli ktoś zapyta API o /account/1234 to zwróci dane rachunku o numerze 1234
//klasa Route podstawia argumenty z URL (wyrażenie regularne) do funkcji
Route::add('/account/([0-9]*)', function($accountNo) use($db) {
    //$accountNo to numer rachunku, którego dane chcę pobrać z bazy
    //funkcja statyczna pobiera informacje o rachunku z bazy danych i tworzy obiekt rachunku
    $account = Account::getAccount($accountNo, $db);
    //ustaw nagłówek odpowiedzi na JSON żeby przeglądarka wiedziała jak interpretować dane
    header('Content-Type: application/json');
    //zwróć dane w formacie JSON korzystając z funkcji udostępniającej dane prywatne jako tablicę
    return json_encode($account->getArray());
});

//endpoint do wykonywania przelewów
Route::add('/transfer/new', function() use($db) {
    //zakładamy, że aplikacja przekazała nam token w postaci danych JSON
    //przeczytaj surowe dane wejściowe z PHP
    $data = file_get_contents('php://input');
    //przekształć JSON wejściowe w tablicę asocjacyjną
    $dataArray = json_decode($data, true);
    //zakładam, ze w paczce danych jest token pod nazwą "token"
    $token = $dataArray['token'];
    //sprawdz poprawność tokena
    if(!Token::check($token, $_SERVER['REMOTE_ADDR'], $db)) {
        //jeżeli token jest niepoprawny to zwróć błąd
        header('HTTP/1.1 401 Unauthorized');
        //opcjonalnie
        return json_encode(['error' => 'Invalid token']);
    }
    //pobierz id użytkownika na podstawie tokena
    $userId = Token::getUserId($token, $db);
    //pobierz numer rachunku użytkownika
    $source = Account::getAccountNo($userId, $db);
    //pobierz numer rachunku docelowego i kwotę przelewu
    //z zapytania skierowanego do API
    $target = $dataArray['target'];
    if(!Account::ifExists($target, $db)) {
      header('HTTP/1.1 401 Unauthorized');
      return json_encode(['error' => 'Target account does not exist']);
    }
    $amount = $dataArray['amount'];
    //todo: sprawdź czy użytkownik ma wystarczająco dużo środków na koncie
    
    //wykonujemy nowy przelew
    Transfer::new($source, $target, $amount, $db);
    $currentBalance = Account::getAccount($source, $db)->getArray()['amount'];
    $target = $dataArray['target'];
    $amount = $dataArray['amount'];

    if ($amount <= 0) {
      header('HTTP/1.1 400 Bad Request');
      return json_encode(['error' => 'Kwota musi być dodatnia']);
    }

    if($currentBalance < $amount) {
        header('HTTP/1.1 400 Bad Request');
        return json_encode(['error' => 'Brak wystarczających środków na koncie']);
    }
    header('Status: 200');
    return json_encode(['status' => 'OK']);
}, 'post');

//ta linijka musi być na końcu
//musi tu być nazwa folderu w którym "mieszka" API
Route::run('/bankAPI');

$db->close();
?>
<?php
namespace BankAPI;
use mysqli;
use Exception;
use mysqli_sql_exception;

/**
 * Class Transfer
 * 
 * This class provides functionalities to perform specific operations regarding
 * transfers in our virtual bank.
 */

class Transfer {
    public static function new(int $source, int $target, int $amount, mysqli $db) : void {
        //Upewnij się, że kwota jest większa niż 0
        if ($amount <= 0) {
            throw new Exception('Kwota przelewu musi być większa niż 0.');
        }

        // Sprawdź, czy konto źródłowe ma wystarczające środki
        $sql = "SELECT amount FROM account WHERE accountNo = ?";
        $query = $db->prepare($sql);
        $query->bind_param('i', $source);
        $query->execute();
        $result = $query->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['amount'] < $amount) {
            throw new Exception('Brak wystarczających środków na koncie źródłowym.');
        }

        // Rozpocznij transakcję
        $db->begin_transaction();
        try {
            // SQL - odjęcie kwoty z rachunku źródłowego
            $sql = "UPDATE account SET amount = amount - ? WHERE accountNo = ?";
            $query = $db->prepare($sql);
            $query->bind_param('ii', $amount, $source);
            $query->execute();

            // Dodaj kwotę do rachunku docelowego
            $sql = "UPDATE account SET amount = amount + ? WHERE accountNo = ?";
            $query = $db->prepare($sql);
            $query->bind_param('ii', $amount, $target);
            $query->execute();

            // Zapisz informację o przelewie do bazy danych
            $sql = "INSERT INTO transfer (source, target, amount) VALUES (?, ?, ?)";
            $query = $db->prepare($sql);
            $query->bind_param('iii', $source, $target, $amount);
            $query->execute();

            // Zakończ transakcję
            $db->commit();
        } catch (mysqli_sql_exception $e) {
            // Jeśli wystąpił błąd, wycofaj transakcję
            $db->rollback();
            // Rzuć wyjątek
            throw new Exception('Transfer failed');
        }
    }
}
?>

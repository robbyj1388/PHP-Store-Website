<?php

// This function will connect to the database from the given credentials (db.ini)
function connectDB() {
    $config = parse_ini_file("/local/my_web_files/evanderl/db.ini");
    
    $dbh = new PDO($config['dsn'], $config['username'], $config['password']);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $dbh;
}

// This method will authenticate a user based on parameters from the login page. I attempted to try and stop it from sql injection, but if it didn't work I suck
function authenticate($user, $passwd) {
    try {
        $dbh = connectDB();
        
        // SQL statement here
        $statement = $dbh->prepare("SELECT count(*) FROM lab8_customer " .
            "where username = :username and password = sha2(:passwd, 256)");
        
        $statement->bindParam(":username", $user);
        $statement->bindParam(":passwd", $passwd);
        $statement->execute();
        $row = $statement->fetch();
        $dbh = null;
        return $row[0];
        
        
    } 
    
    // Incase of any error in the database connection, display in HTML here
    catch (PDOException $e) {
        print "Error " . $e->getMessage() . "<br/>";
        die();  // yeesh
    }
}



// This function retrieves all accounts for a given user. They will be displayed on the accounts page
function get_accounts($user) {
    try {
        $dbh = connectDB();

        // SQL statement here
        $statement = $dbh->prepare("SELECT account_no, balance FROM lab8_accounts where username = :username");
        $statement->bindParam(":username", $user);
        $statement->execute();

        // Return all matching rows
        return $statement->fetchAll(); 
        
    } 
    
    // Incase of any error in the database connection, display in HTML here
    catch (PDOException $e) {
        print "Error! " . $e->getMessage() . "<br/>";
        die();  // yeesh
    }
}

// The function transfers money from one bank account to another
function transfer($from, $to, $amount, $user) {
    try {
        $dbh = connectDB();
        
        // Start a transaction
        $dbh->beginTransaction();

        // Check if the 'from' account has enough balance with this statement
        $statement = $dbh->prepare("select balance from lab8_accounts where account_no = :from");
        $statement->bindParam(":from", $from);
        $statement->execute();
        $row = $statement->fetch();

        // If there is not enough money to transfer, and if the account exists
        if ($row) {
            $currentBalance = $row[0];
            if ($currentBalance < $amount) {
                $dbh->rollBack();
                $dbh = null;
                return "Not enough balance in account $from";
            }
        } 
        
        // If the account doesn't exist
        else {
            $dbh->rollBack();
            $dbh = null;
            return "Account $from does not exist";
        }

        // Subtract money from 'from' account with this query
        $statement = $dbh->prepare("update lab8_accounts set balance = balance - :amount where account_no = :from");
        $statement->bindParam(":amount", $amount);
        $statement->bindParam(":from", $from);
        $statement->execute();
        $rowCount = $statement->rowCount();
        
        // Check if there isn't exactly one matching row
        if ($rowCount != 1) {
            $dbh->rollback();
            $dbh = null;
            return "Something is not right (debit). Rows affected: " . $rowCount;
        }

        // Add money to the 'to' account with this query
        $statement = $dbh->prepare("update lab8_accounts set balance = balance + :amount where account_no = :to");
        $statement->bindParam(":amount", $amount);
        $statement->bindParam(":to", $to);
        $statement->execute();
        $rowCount = $statement->rowCount();
        
        // Check if there isn't exactly one matching row
        if ($rowCount != 1) {
            $dbh->rollback();
            $dbh = null;
            return "Something is not right (credit). Account $to may not exist. Rows affected: " . $rowCount;
        }
        
        // If all checks passed, we can finally transfer the money
        $dbh->commit();
        $dbh = null;
        return "Money has been transferred successfully";

    } 
    
    // Error exception rollback
    catch (Exception $e) {
        $dbh->rollBack();
        $dbh = null;
        return "Failed: " . $e->getMessage();
    }
}

?>
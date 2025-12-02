<?php
    function connectDB()
    {
        $config = parse_ini_file("/local/my_web_files/robbyj/db.ini");
        $dbh = new PDO($config['dsn'], $config['username'], $config['password']);
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $dbh;
    }

    function authenticate($user, $passwd) {
        try {
            $dbh = connectDB();
            
            // Check if in Emplyee table
            $stmt = $dbh->prepare("SELECT employee_id FROM Employee WHERE user = :user AND pass = SHA2(:pass,256)");
            $stmt->bindParam(":user", $user);
            $stmt->bindParam(":pass", $passwd);
            $stmt->execute();
            $id = $stmt->fetchColumn();
            if ($id){
                return ['type'=>1, 'id'=>$id];  // Employee
            }
            // Check if in Customer table
            $stmt = $dbh->prepare("SELECT customer_id FROM Customer WHERE user = :user AND pass = SHA2(:pass,256)");
            $stmt->bindParam(":user", $user);
            $stmt->bindParam(":pass", $passwd);
            $stmt->execute();
            $id = $stmt->fetchColumn();
            if ($id){
                return ['type'=>2, 'id'=>$id];  // Customer
            }
            
            return ['type'=>0, 'id'=>null];  // Not found
        } catch (PDOException $e) {
            die("Error! " . $e->getMessage());
        }
    }


    // Check if the user must reset their default password
    function needsPasswordReset($user) {
        try {
            $dbh = connectDB();
            $sql = "SELECT must_update_password FROM Employee WHERE user = :username";
            $stmt = $dbh->prepare($sql);
            $stmt->bindParam(":username", $user, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $dbh = null;

            return $result && $result['must_update_password']; // true if must update, false if not
        } catch (PDOException $e) {
            error_log("DB Error in needsPasswordReset: " . $e->getMessage());
            return false;
        }
    }

    // Update password for given user
    function updatePassword($user, $pass){
        try {
            $dbh = connectDB();
            $hash = hash("sha256", $pass);

            // Use PDO prepare and bindParam
            $stmt = $dbh->prepare("UPDATE Employee SET pass = :pass, must_update_password = 0 WHERE user = :user");
            $stmt->bindParam(":pass", $hash, PDO::PARAM_STR);
            $stmt->bindParam(":user", $user, PDO::PARAM_STR);

            return $stmt->execute(); // returns true if successful
        } catch (PDOException $e) {
            error_log("DB Error in updatePassword: " . $e->getMessage());
            return false;
        } finally {
            $dbh = null; // close connection
        }
    }


    function get_accounts($user)
    {
        //connect to database
        //retrieve the data and display
        try {
            $dbh = connectDB();
            $statement = $dbh->prepare("SELECT account_no, balance FROM lab8_accounts where username = :username");
            $statement->bindParam(":username", $user);
            $statement->execute();
            return $statement->fetchAll();
            $dbh = null;
        } catch (PDOException $e) {
            print "Error!" . $e->getMessage() . "<br/>";
            die();
        }
    };

    function transfer($from, $to, $amount, $user)
    {
        try {
            $dbh = connectDB();
            $dbh->beginTransaction();
            // check if there are enough balance in the from account
            $statement = $dbh->prepare("select balance from lab8_accounts where account_no=:from ");
            $statement->bindParam(":from", $from);
            $result = $statement->execute();
            $row = $statement->fetch();
            if ($row) {
                $currentBalance = $row[0];
                if ($currentBalance < $amount) {
                    $dbh->rollBack();
                    $dbh = null;
                    return "Not enough balance in $from";;
                }
            } else {
                $dbh->rollBack();
                $dbh = null;
                return "Account $from does not exist";
            }
            $statement = $dbh->prepare("update lab8_accounts set balance = balance - :amount " .
            "where account_no=:from");
            $statement->bindParam(":amount", $amount);
            $statement->bindParam(":from", $from);
            $result = $statement->execute();
            $rowCount = $statement->rowCount();
            if ($rowCount != 1) {
                $dbh->rollback();
                return "Something is not right because the total number of rows that will be affected is " .
                $rowCount;
            }
            $statement = $dbh->prepare("update lab8_accounts set balance = balance + :amount " .
            "where account_no= :to");
            $statement->bindParam(":amount", $amount);
            $statement->bindParam(":to", $to);
            $result = $statement->execute();
            $rowCount = $statement->rowCount();
            if ($rowCount != 1) {
                $dbh->rollback();
                return "Something is not right because the total number of rows that will be affected is " .
                $rowCount;
            }
            $dbh->commit();
            return "Money has been transfered successfully";
        } catch (Exception $e) {
            $dbh->rollBack();
            echo "Failed: " . $e->getMessage();
        }
    }
?>
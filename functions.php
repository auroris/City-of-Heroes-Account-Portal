<?php

/* HTTPS Redirect */
if (!(isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || 
   $_SERVER['HTTPS'] == 1) ||  
   isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&   
   $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'))
{
   $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
   header('HTTP/1.1 301 Moved Permanently');
   header('Location: ' . $redirect);
   exit();
}

/* Opens connection to the SQL DB Server */
function OpenConnection()
{  
    try
    {
        $serverName = "localhost\sqlexpress";  
        $connectionOptions = array("Database"=>"cohauth",  
            "Uid"=>"CoHDB", "PWD"=>"bqaDDMA7QUKNABYdKQrj");  
        $conn = sqlsrv_connect($serverName, $connectionOptions);
        if($conn == false)  
            die("<pre>" . print_r(sqlsrv_errors(), true) . "</pre>");
        return $conn;
    }
    catch(Exception $e)  
    {  
       die(print_r($e));
    }
}

/* Checksum algorithm */
function adler32($data)
{
    $mod_adler = 65521;
    $a = 1;
    $b = 0;
    $len = strlen($data);
    for($index = 0; $index < $len; $index++)
    {
        $a = ($a + ord($data[$index])) % $mod_adler;
        $b = ($b + $a) % $mod_adler;
    }

    return ($b << 16) | $a;
}

/* Generate password hash */
function game_hash_password($authname, $password)
{
    $authname = strtolower($authname);
    $a32 = adler32($authname);
    $a32hex = sprintf('%08s', dechex($a32));
    $a32hex = substr($a32hex, 6, 2) . substr($a32hex, 4, 2) . substr($a32hex, 2, 2) . substr($a32hex, 0, 2);
    $digest = hash('sha512', $password . $a32hex, TRUE);
    return $digest;
}

/* Counts the number accounts (# of entries in the coh_auth.dbo.user_accounts table) */
function countAccounts()
{
    $conn = OpenConnection();
    $iTotalAccounts = sqlsrv_query($conn, "SELECT count(*) FROM dbo.user_account");
    sqlsrv_fetch($iTotalAccounts);
    return sqlsrv_get_field($iTotalAccounts, 0);
}

/* Counts the number of characters (# entries in the cohdb.dbo.ents table) */
function countCharacters()
{
    $conn = OpenConnection();
    $iTotalChars = sqlsrv_query($conn, "SELECT count(*) FROM cohdb.dbo.ents");
    sqlsrv_fetch($iTotalChars);
    return sqlsrv_get_field($iTotalChars, 0);
}

/* Routine to create an account */
/* I created a unique index on the table cohauth.dbo.user_account for the column uid. If there is some sort of collision
   on UID creation, then one of the registration attempts will error out. */
function CreateAccount()
{
    $conn = OpenConnection();

    // Verify that username is valid
    if (!ctype_alnum($_POST["username"]) || strlen($_POST["username"]) == 0 || strlen($_POST["username"]) > 14)
    {
        return "Error: Username must be maximum 14 characters; only letters and numbers.";
    }
    $username = $_POST["username"];
    
    // Verify that password is valid
    if (!ctype_print($_POST["password"]) || strlen($_POST["password"]) < 8 || strlen($_POST["password"]) > 16)
    {
        return "Error: Password must be 8 to 16 characters.";
    }
    $password = $_POST["password"];

    // Generate a new account ID and password hash
    $iAccountID = sqlsrv_query($conn, "SELECT max(uid) FROM dbo.user_account");
    sqlsrv_fetch($iAccountID);
    $id = sqlsrv_get_field($iAccountID, 0) + 1;
    $hash = bin2hex(game_hash_password($username, $password));
    
    // Insert the database data
    sqlsrv_begin_transaction($conn);
    
    if(sqlsrv_query($conn, "INSERT INTO cohauth.dbo.user_account (account, uid, forum_id, pay_stat) VALUES (?, ?, ?, 1014)", array($username, $id, $id)) === false) {
        sqlsrv_rollback($conn);
        
        $handle = fopen('create_errors.txt', 'a') or die('Cannot open file create_errors.txt');
        fwrite($handle, "Step 1: Error creating account for " . $username . " with UID " . $id . ":\n");
        fwrite($handle, print_r(sqlsrv_errors(), true));
        fclose($handle);
        
        return "Unable to create your account; something went wrong on step 1.";
    }
    
    if (sqlsrv_query($conn, "INSERT INTO cohauth.dbo.user_auth (account, password, salt, hash_type) VALUES (?, CONVERT(BINARY(128),?), 0, 1)", array($username, $hash)) === false) {
        sqlsrv_rollback($conn);
        
        $handle = fopen('create_errors.txt', 'a') or die('Cannot open file create_errors.txt');
        fwrite($handle, "Step 2: Error creating account for " . $username . " with UID " . $id . ":\n");
        fwrite($handle, print_r(sqlsrv_errors(), true));
        fclose($handle);
        
        return "Unable to create your account; something went wrong on step 2.";
    }
    
    if (sqlsrv_query($conn, "INSERT INTO cohauth.dbo.user_data (uid, user_data) VALUES (?, 0x0080C2E000D00B0C000000000CB40058)", array($id)) === false) {
        sqlsrv_rollback($conn);
        
        $handle = fopen('create_errors.txt', 'a') or die('Cannot open file create_errors.txt');
        fwrite($handle, "Step 3: Error creating account for " . $username . " with UID " . $id . ":\n");
        fwrite($handle, print_r(sqlsrv_errors(), true));
        fclose($handle);
        
        return "Unable to create your account; something went wrong on step 3.";
    }
    
    if (sqlsrv_query($conn, "INSERT INTO cohauth.dbo.user_server_group (uid, server_group_id) VALUES (?, 1)", array($id)) === false) {
        sqlsrv_rollback($conn);
        
        $handle = fopen('create_errors.txt', 'a') or die('Cannot open file create_errors.txt');
        fwrite($handle, "Step 4: Error creating account for " . $username . " with UID " . $id . ":\n");
        fwrite($handle, print_r(sqlsrv_errors(), true));
        fclose($handle);
        
        return "Unable to create your account; something went wrong on step 4.";
    }
   
    sqlsrv_commit($conn);
    
    return "Account created successfully! You may log in immediately.";
}

/* routine to change a password */
function ChangePassword()
{
    $conn = OpenConnection();

    // Verify that username is valid
    if (!ctype_alnum($_POST["username"]) || strlen($_POST["username"]) == 0 || strlen($_POST["username"]) > 14)
    {
        return "Error: Username must be maximum 14 characters; only letters and numbers.";
    }
    $username = $_POST["username"];
    
    // Verify that password is valid
    if (!ctype_print($_POST["password"]) || strlen($_POST["password"]) < 8 || strlen($_POST["password"]) > 16)
    {
        return "Error: Password must be 8 to 16 characters.";
    }
    $password = $_POST["password"];
    $hash = bin2hex(game_hash_password($username, $password));
    
    // Verify that the new password is valid
    if (!ctype_print($_POST["new_password"]) || strlen($_POST["new_password"]) < 8 || strlen($_POST["new_password"]) > 16)
    {
        return "Error: New password must be 8 to 16 characters.";
    }
    $new_password = $_POST["new_password"];
    $new_hash = bin2hex(game_hash_password($username, $new_password));
    
    // Verify that the old username and password match an account in the database
    $found = sqlsrv_query($conn, "SELECT 1 FROM dbo.user_auth WHERE UPPER(account) = UPPER(?) AND convert(varchar, password) = SUBSTRING(?, 1, 30)", array($username, $hash));
    if (sqlsrv_fetch($found) === true)
    {
        // Account found, update password
        sqlsrv_query($conn, "UPDATE dbo.user_auth SET password = CONVERT(BINARY(128),?) WHERE UPPER(account) = UPPER(?)", array($new_hash, $username));
        return "Your password has been updated successfully.";
    }
    
    return "That username and password does not match our records.";
}
?>
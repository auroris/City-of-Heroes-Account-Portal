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

/* Routine to create an account */
/* I created a unique index on the table cohauth.dbo.user_account for the column uid. If there is some sort of collision
   on UID creation, then one of the registration attempts will error out. */
function CreateAccount(\Monolog\Logger $logger)
{
    $conn = OpenConnection();

    // Verify that username is valid
    if (!ctype_alnum($_POST["username"]) || strlen($_POST["username"]) == 0 || strlen($_POST["username"]) > 14)
    {
        return ['success' => false, 'message' => "Error: Username must be maximum 14 characters; only letters and numbers."];
    }
    $username = $_POST["username"];
    
    // Verify that password is valid
    if (!ctype_print($_POST["password"]) || strlen($_POST["password"]) < 8 || strlen($_POST["password"]) > 16)
    {
        return ['success' => false, 'message' => "Error: Password must be 8 to 16 characters."];
    }
    $password = $_POST["password"];

    // Generate a new account ID and password hash
    $iAccountID = sqlsrv_query($conn, "SELECT max(uid) FROM dbo.user_account");
    sqlsrv_fetch($iAccountID);
    $id = sqlsrv_get_field($iAccountID, 0) + 1;
    $hash = bin2hex(game_hash_password($username, $password));
    
    // Insert the database data
    sqlsrv_begin_transaction($conn);
    
    $sql1 = "INSERT INTO cohauth.dbo.user_account (account, uid, forum_id, pay_stat) VALUES (?, ?, ?, 1014)";
    $sql2 = "INSERT INTO cohauth.dbo.user_auth (account, password, salt, hash_type) VALUES (?, CONVERT(BINARY(128),?), 0, 1)";
    $sql3 = "INSERT INTO cohauth.dbo.user_data (uid, user_data) VALUES (?, 0x0080C2E000D00B0C000000000CB40058)";
    $sql4 = "INSERT INTO cohauth.dbo.user_server_group (uid, server_group_id) VALUES (?, 1)";
    
    if(sqlsrv_query($conn, $sql1, array($username, $id, $id)) === false ||
        sqlsrv_query($conn, $sql2, array($username, $hash)) === false ||
        sqlsrv_query($conn, $sql3, array($id)) === false ||
        sqlsrv_query($conn, $sql4, array($id)) === false
    ) {
        sqlsrv_rollback($conn);
        $logger->error("Error creating account for " . $username . " with UID " . $id . ":\n" . print_r(sqlsrv_errors(), true));
        return ['success' => false, 'message' => "Unable to create your account; something went wrong."];
    }
    
    sqlsrv_commit($conn);
    
    return ['success' => true, 'username' => $username, 'uid' => $id, 'message' => "Account created successfully! You may log in immediately."];
}

function LoginAccount(\Monolog\Logger $logger)
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
    
    // Verify that the username and password match an account in the database
    $found = sqlsrv_query($conn, "SELECT 1 FROM cohauth.dbo.user_auth WHERE UPPER(account) = UPPER(?) AND convert(varchar, password) = SUBSTRING(?, 1, 30)", array($username, $hash));
    if (sqlsrv_fetch($found) === true)
    {
        return ['success' => true, 'username' => $username];
    }
    
    return ['success' => false, 'message' => "That username and password does not match our records."];
}
?>
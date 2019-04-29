<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

return function (App $app) {
    $container = $app->getContainer();

    $app->get('/', function (Request $request, Response $response, array $args) use ($container) {
        /* Counts the number accounts (# of entries in the coh_auth.dbo.user_accounts table) */
        $conn = OpenConnection();
        $iTotalAccounts = sqlsrv_query($conn, "SELECT count(*) FROM dbo.user_account");
        sqlsrv_fetch($iTotalAccounts);

        /* Counts the number of characters (# entries in the cohdb.dbo.ents table) */
        $iTotalChars = sqlsrv_query($conn, "SELECT count(*) FROM cohdb.dbo.ents");
        sqlsrv_fetch($iTotalChars);
        
        $vars = [
            'accounts' => sqlsrv_get_field($iTotalAccounts, 0),
            'characters' => sqlsrv_get_field($iTotalChars, 0)
        ];
        
        // Render index view
        return $container->get('renderer')->render($response, 'page-index.phtml', $vars);
    });
    
    $app->get('/create', function (Request $request, Response $response, array $args) use ($container) {
        return $container->get('renderer')->render($response, 'page-create-account.phtml');
    });
    
    $app->post('/create', function (Request $request, Response $response, array $args) use ($container) {
        //$container->get('logger')->info("Slim-Skeleton '/' route");
        
        $result = CreateAccount($container->get('logger'));
        
        if ($result['success'] == true) {
            $_SESSION["username"] = $result['username'];
            return $container->get('renderer')->render($response, 'page-create-account-success.phtml', ['message' => $result['message']]);
        }
        else {
            return $container->get('renderer')->render($response, 'page-create-account-error.phtml', ['message' => $result['message']]);
        }
    });
    
    $app->get('/login', function (Request $request, Response $response, array $args) use ($container) {
        if (isset($_SESSION["username"])) {
            return $response->withRedirect('./manage'); 
        }
        
        return $container->get('renderer')->render($response, 'page-login.phtml');
    });
    
    $app->post('/login', function (Request $request, Response $response, array $args) use ($container) {
        $result = LoginAccount($container->get('logger'));
        
        if ($result['success'] == true) {
            $_SESSION["username"] = $result['username'];
            return $response->withRedirect('./manage'); 
        }
        else {
            return $container->get('renderer')->render($response, 'page-login.phtml', ['message' => $result['message']]);
        }
    });
    
    $app->get('/manage', function (Request $request, Response $response, array $args) use ($container) {
        if (!isset($_SESSION["username"])) {
            return $container->get('renderer')->render($response, 'page-generic-message.phtml', ['title' => 'Error', 'message' => "You must be logged in to do that."]);
        }
        
        $characters = array();
        
        $conn = OpenConnection();
        $stmt = sqlsrv_query($conn, "select * FROM cohdb.dbo.ents WHERE authname = ?", array($_SESSION["username"]));
        while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            array_push($characters, $row);
        }

        return $container->get('renderer')->render($response, 'page-manage.phtml', ['username' => $_SESSION["username"], 'characters' => $characters]);
    });
    
    $app->post('/changepassword', function (Request $request, Response $response, array $args) use ($container) {
        if (!isset($_SESSION["username"])) {
            return $container->get('renderer')->render($response, 'page-generic-message.phtml', ['title' => 'Error', 'message' => "You must be logged in to do that."]);
        }
        
        $conn = OpenConnection();

        // Verify that the new password is valid
        if (!ctype_print($_POST["new_password"]) || strlen($_POST["new_password"]) < 8 || strlen($_POST["new_password"]) > 16)
        {
            return $container->get('renderer')->render($response, 'page-generic-message.phtml', ['title' => 'Error', 'message' => "Error: New password must be 8 to 16 characters."]);
        }
        $new_password = $_POST["new_password"];
        $new_hash = bin2hex(game_hash_password($username, $new_password));

        sqlsrv_query($conn, "UPDATE dbo.user_auth SET password = CONVERT(BINARY(128),?) WHERE UPPER(account) = UPPER(?)", array($new_hash, $username));
        return $container->get('renderer')->render($response, 'page-generic-message.phtml', ['title' => 'Success', 'message' => "Your password has been changed successfully."]);
    });
    
    $app->get('/logout', function (Request $request, Response $response, array $args) use ($container) {
        session_unset();
        session_destroy();
        return $response->withRedirect('./');
    });
};

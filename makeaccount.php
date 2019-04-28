<?php
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

function game_hash_password($authname, $password)
{
        $authname = strtolower($authname);
        $a32 = adler32($authname);
        $a32hex = sprintf('%08s', dechex($a32));
        $a32hex = substr($a32hex, 6, 2) . substr($a32hex, 4, 2) . substr($a32hex, 2, 2) . substr($a32hex, 0, 2);
        $digest = hash('sha512', $password . $a32hex, TRUE);
        return $digest;
}

$authname = "test";
$password = "password";
$id = "1";
if (isset($_POST['authname']) && isset($_POST['password']))
{
$authname = trim($_POST['authname']);
$password = trim($_POST['password']);
$id = trim($_POST['id']);

$hash = bin2hex(game_hash_password($authname, $password));

$sql1 = "INSERT INTO cohauth.dbo.user_account (account, uid, forum_id, pay_stat) VALUES ('$authname', $id, $id, 1014);";
$sql2 = "INSERT INTO cohauth.dbo.user_auth (account, password, salt, hash_type) VALUES ('$authname', CONVERT(BINARY(128),'$hash'), 0, 1);";
$sql3 = "INSERT INTO cohauth.dbo.user_data (uid, user_data) VALUES ($id, 0x0080C2E000D00B0C000000000CB40058);";
$sql4 = "INSERT INTO cohauth.dbo.user_server_group (uid, server_group_id) VALUES ($id, 1)";

print ($sql1."<br>");
print ($sql2."<br>");
print ($sql3."<br>");
print ($sql4."<br>");
echo '<br>';
}

echo '<form method="post" autocomplete="off">';
echo '<span style="display: inline-block; width: 80px;">ID: </span><input name="id" value="'.$id.'"><br>';
echo '<span style="display: inline-block; width: 80px;">Login: </span><input type="text" value="'.$authname.'" name="authname" maxlength=14> <small>(maximum 14 characters; only letters and numbers)</small><br>';
echo '<span style="display: inline-block; width: 80px;">Password: </span><input type="text" value="'.$password.'" name="password" maxlength=16> <small>(between 8 and 16 characters)</small><br>';
echo '<br>';
echo '<input style="display: inline-block; width: 160px;" type="submit" value="Create SQL"></form>';

echo '<br>';
echo 'Issues? Contact @Crust First#1836';
echo '<br>';
echo '<a href="makeaccount.phps">source for this page</a>';
?>


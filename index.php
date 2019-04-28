<?php require('functions.php'); ?>
<html>
<head>
    <title>Account Portal</title>
    <link href="style.css" rel="stylesheet" type="text/css" />
</head>
<body>

<div id="logo"><img src="coh_logo.png"></div>

<div id="content">

<?php if (!isset($_POST['create']) && !isset($_POST['change'])) : ?>

<div class="block">
    <div class="blocktitle">
        About this server
    </div>
    <div class="blockbody">
        <p>This City of Heroes private server was created for friends to play together in a fun atmosphere.</p>
        <p>There are <?php echo countAccounts(); ?> registered accounts holding <?php echo countCharacters(); ?> characters.</p>
    </div>
</div>

<div class="block">
    <div class="blocktitle">
        Account Management
    </div>
    <div class="blockbody">
        <p>To create an account, enter a username and password then click "Create Your Account". If you have an account and want to change your password, enter your existing username and password, then enter your new password and click "Change Your Password".</p>
        <form method="post">
            <table width="100%">
                <tr>
                    <td align="right"><label for="username">Username</label><br /><small>(maximum 14 characters; only letters and numbers)</small></td>
                    <td><input type="text" name="username" maxlength=14></td>
                </tr><tr>
                    <td align="right"><label for="password">Password</label><br /><small>(8 to 16 characters)</small></td>
                    <td><input type="password" name="password" maxlength=16></td>
                </tr><tr>
                <tr>
                    <td colspan="2" align="right">
                        <input type="submit" name="create" value="Create Your Account">
                    </td>
                </tr>
                </tr><tr>
                    <td align="right"><label for="change_password">Change Password</label><br /><small>(8 to 16 characters)</small></td>
                    <td><input type="password" name="new_password"></td>
                </tr><tr>
                <tr>
                    <td colspan="2" align="right">
                        <input type="submit" name="change" value="Change Your Password">
                    </td>
                </tr>
            </table>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if (isset($_POST['create'])) : ?>

<div class="block">
    <div class="blocktitle">
        Server message
    </div>
    <div class="blockbody">
        <p><?php echo CreateAccount(); ?></p>
    </div>
</div>
<?php endif; ?>

<?php if (isset($_POST['change'])) : ?>
<div class="block">
    <div class="blocktitle">
        Server message
    </div>
    <div class="blockbody">
        <p><?php echo ChangePassword(); ?></p>
    </div>
</div>
<?php endif; ?>

<div class="block">
    <div class="blocktitle">
        Client Download
    </div>
    <div class="blockbody">
        <p>To download the client, please follow these steps:</p>
        <ol>
            <li>Download the client <a href="https://drive.google.com/open?id=1rHNhoAPn6haVt6afLTxoGvh5pAPjT_Vy">from here</a>.</li>
            <li>Extract it somewhere convenient.</li>
            <li>Run "AurorisCOH.bat" and login with the account created above.</li>
        </ol>
        <p>If you already have the SCORE client downloaded, you can instead launch with the following command line:
<pre>start .\score.exe -auth 52.183.6.161 -patchdir score 
        -noversioncheck -project "coh"</pre></p>
    </div>
</div>

<div class="block">
    <div class="blocktitle">
        Discord and Support
    </div>
    <div class="blockbody">
        <p><a href="https://discord.gg/UyBcpxa">Join the community on Discord.</a></p>
    </div>
</body>
</html>
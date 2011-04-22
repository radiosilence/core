<h1>Login</h1>
<form method="POST" action="<?=($login_action ? $login_action : '/login')?>">
<p><input type="text" name="username" id="login_username" placeholder="<?=($user_field ? $user_field : 'Username')?>"/></p>
<p><input type="password" name="password" id="login_password" placeholder="Password"/></p>
<p><?=$_message?></p>
<p><button id="login">Log In</button></p>
</form>
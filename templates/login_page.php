<h1>Login</h1>
<form method="POST" action="<?=($login_action ? $login_action : '/login')?>">
<p><input type="text" name="username" id="login_username" placeholder="<?=($user_field ? $user_field : 'Username')?>" class="login_field"/></p>
<p><input type="password" name="password" id="login_password" placeholder="Password"  class="login_field"/></p>
<p><?=$_message?></p>
<p><button id="login" action="<?=($login_action ? $login_action : '/login')?>">Log In</button></p>
</form>
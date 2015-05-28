<?php
	session_start();
	if (isset($_SESSION["USER"])) {
		$errorMessage = "正常にログアウトされました.<br>またのご利用をお待ちしています.";
	}
	else {
		$errorMessage = "セッションが時間切れです.<br>続行するにはもう一度ログインしてください.";
	}

	// session vars clear
	$_SESSION = array();
	// garvage cookie
	if (ini_get("session.use_cookies")) {
		$params = session_get_cookie_params();
		setcookie(session_name(), '', time() - 42000,
			$params["path"], $params["domain"],
			$params["secure"], $params["httponly"]
		);
	}
	// session clear
	@session_destroy();
?>
<!DOCTYPE html>
<html>
<!-- header -->
<head>
<?php include '_htmlhead.html'; ?>
<title>LOGOUT</title>
</head><!-- header/end -->
<body>
<div id="container">


	<div id="logout" class="loginout">
		<h1>Syneco BOARd</h1>
		<h2>open-sourced sensor device network for synecoculture</h2>
		<p><br><?php echo $errorMessage; ?><br><a href="./login.php">ログイン画面に戻る.</a></p>
	</div>








	<footer>© Sony Computer Science Laboratories, Inc.</footer>

</div>
</body>
</html>
<!-- EOF -->
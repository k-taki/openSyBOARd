<?php
	session_start();
	$errorMessage = "";
	$viewUserId = htmlspecialchars($_POST['userid'], ENT_QUOTES);


	// Login status check:
	if (isset($_SESSION['USER'])) {
		header("Location: ./");
		exit;
	}


	// Connect MySQL
	$db = mysqli_connect('localhost', 'toadrockie', 'cr0akcroAk', 'SyMain');
	if (!$db) {
		die('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
	}
	mysqli_set_charset($db, "utf8");


	// If pushed LOGIN BUTTON:
	if (isset($_POST['login'])) {

		// Find user from Database
		$uid = $_POST['userid'];
		$passwdhash = mysqli_query($db, "SELECT password FROM user WHERE username = '$uid' ");
		$q_field = mysqli_fetch_row($passwdhash);
		// Password hash is $q_field[0];
		$input_passhash = hash('sha256', $_POST['password']);


		// Authentication succeeded
		if ($input_passhash == $q_field[0]) {
			// Newly generate session ID
			session_regenerate_id(TRUE);
			$_SESSION['USER'] = $_POST['userid'];
			header("Location: ./");
			// Write access.log
			$logstring = "[".date(DATE_RFC850)."] ".$_SERVER['REMOTE_ADDR']."(".$_SERVER['REMOTE_HOST'].") Trys login as '".$_POST['userid']."' by ".$_SERVER['HTTP_USER_AGENT'].", Succeeded.\r";
			file_put_contents("accesslog.txt", $logstring, FILE_APPEND);
			exit;
		}
		// Failure
		else {
			$errorMessage = "ログインできません. ユーザIDもしくはパスワードが間違っています.";
			// Write access.log
			$logstring = "[".date(DATE_RFC850)."] ".$_SERVER['REMOTE_ADDR']."(".$_SERVER['REMOTE_HOST'].") Trys login as '".$_POST['userid']."' by ".$_SERVER['HTTP_USER_AGENT'].", Failure.\r";
			file_put_contents("accesslog.txt", $logstring, FILE_APPEND);
		}
	}















	mysqli_close($dblink);
?>
<!DOCTYPE html>
<html>
<!-- header -->
<head>
<?php include '_htmlhead.html'; ?>
<title>LOGIN</title>
</head><!-- header/end -->
<body>
<div id="container">


	<form id="loginForm" name="loginForm" action="<?php print($_SERVER['PHP_SELF']) ?>" method="POST">
		<h1>Syneco BOARd (beta)</h1>
		<h2>open-sourced sensor device network for synecoculture</h2>
		<fieldset id="loginField">
			<legend>LOGIN</legend>
			<label for="userid">ユーザID</label><input type="text" id="userid" name="userid" value="<?php echo $viewUserId ?>"><br>
			<label for="password">パスワード</label><input type="password" id="password" name="password" value=""><br>
			<label></label><input type="submit" id="login" name="login" value="ログイン"><br>
			<p style="color:#cc3333;"><?php echo $errorMessage ?></p>
		</fieldset>
		<p>
		ゲストユーザはIDに"guest", パスワードに"sy"を入力してください.<br>
		近日中にCookie対応します.<br>
		最新のChrome/Safariにて動作を確認しています.
		</p>
	</form>








	<footer>© Sony Computer Science Laboratories, Inc.</footer>

</div>
</body>
</html>
<!-- EOF -->
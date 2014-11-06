<?php
	session_start();
	session_regenerate_id(TRUE);

	// Login status check:
	if (!isset($_SESSION['USER'])) {
		header("Location: logout.php");
		exit;
	}


	// Check debug print and reset
	if (!isset($_SESSION['ERROR'])) {
		$ERRORSTRING = "Unknown error.";
	} else {
		$ERRORSTRING = $_SESSION['ERROR'];
		unset($_SESSION['ERROR']);
	}






?>
<!DOCTYPE html>
<html>
<!-- header -->
<head>
<?php include '_htmlhead.html'; ?>
<title>Request unavailable</title>
</head><!-- header/end -->
<body>
<div id="container">


	<div id="logout">
		<h1>Request unavailable</h1>
		<p><br>
		The page you requested doesn't exist or you don't have permission to view.<br>
		要求されたページは存在しないか,表示する権限がありません.<br>
		<?php echo "[Debug print: ".$ERRORSTRING."]"; ?><br>
		<br>
		<a href="./">Back to TOP / トップページへ戻る</a><br>
		</p>
	</div>








	<footer>© Sony Computer Science Laboratories, Inc.</footer>

</div>
</body>
</html>
<!-- EOF -->
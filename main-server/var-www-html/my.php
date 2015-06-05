<?php
	// error?
	error_reporting(E_ERROR);
	ini_set( 'display_errors', 1 );
?>

<?php
	session_start();
	session_regenerate_id(TRUE);

	// Login status check:
	if (!isset($_SESSION['USER'])) {
		header("Location: logout.php");
		exit;
	}


	// Guest is not parmitted to use this page 
	if ($_SESSION['ADMIN_LEVEL'] == 0) {
		header("Location: ./");
		exit;
	}


	// Connect MySQL
	$db = mysqli_connect('localhost', 'toadrockie', 'cr0akcroAk', 'SyMain');
	if (!$db) {
		die('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
	}
	mysqli_set_charset($db, "utf8");


	// Get own devices
	$esc_USERID = $_SESSION['USER_ID']; // *need escape var | user_id
	if ($_SESSION['ADMIN_LEVEL'] == 3) {
		$devicequery = mysqli_query($db, "SELECT * FROM device ORDER BY device_id DESC"); //admin user gets all devices
	} else {
		$devicequery = mysqli_query($db, "SELECT * FROM device WHERE user_id = '$esc_USERID' ORDER BY device_id DESC");
	}
	$row_cnt = mysqli_num_rows($devicequery);


	// alert is set by other page?
	if (isset($_SESSION['ALERT'])) { $alert = $_SESSION['ALERT']; unset($_SESSION['ALERT']); } else { $alert = ""; }


	// Change:Config
	if (isset($_POST['email'])) { 
		$esc_NEWEM = $_POST['email'];
		if (preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/", $esc_NEWEM)) { // valid e-mail address
			$Q_STRING = "UPDATE user SET email = '$esc_NEWEM' WHERE user_id = '$esc_USERID'";
			$alt1 = mysqli_query($db, $Q_STRING);
			unset($_POST['email']);
		} else {
			$err_conf = "メールアドレスが不正です。";
		}
	}
	if (isset($_POST['language'])) { 
		$esc_NEWLN = $_POST['language'];
		$Q_STRING = "UPDATE user SET language_id = '$esc_NEWLN' WHERE user_id = '$esc_USERID'";
		$alt2 = mysqli_query($db, $Q_STRING);
		unset($_POST['language']);
	}
	if ($alt1 and $alt2) { $alert = '<p id="alert">Config: 変更されました。</p>'; }
	if (isset($err_conf)) { $alert = '<p id="alert">Config: '.$err_conf.'</p>'; unset($err_conf); } // Error output


	// Change:Password
	if (isset($_POST['passwd'])) {
		if ($_POST['passwd'] == $_POST['confpass']) { // Password matching with confirm
		if(preg_match("/^(?=.*[0-9])(?=.*[a-zA-Z])[0-9a-zA-Z]{6,15}$/", $_POST['passwd'])) { // Password rule check
			$esc_NEWPS = hash('sha256', $_POST['passwd']); // hash!
			$Q_STRING = "UPDATE user SET password = '$esc_NEWPS' WHERE user_id = '$esc_USERID'";
			$passq = mysqli_query($db, $Q_STRING);
			if ($passq) { $err_pass = "正常に変更されました。"; } else { $err_pass = "エラーが発生しました。変更は保存されていません。"; }
		} else {
			$err_pass = "英数字混在の6-15文字で入力してください。";
			}
		} else {
			$err_pass = "パスワードが一致しません。もう一度入力してください。";
		}
		if (isset($err_pass)) { $alert = '<p id="alert">Password: '.$err_pass.'</p>'; unset($err_pass); } // Error output
		unset($_POST['passwd']);
	}


	// Get about my own information
	$esc_USERNM = $_SESSION['USER']; // *need escape var | userame
	$uif = mysqli_query($db, "SELECT * FROM user WHERE user_id = '$esc_USERID' ");
	$uq_field = mysqli_fetch_row($uif);
	$esc_EM = $uq_field[3]; // *need escape var | email
	$esc_GRP = $uq_field[4]; // *need escape var | group_id
	$esc_LNG = $uq_field[5]; // *need escape var | language_id
	$esc_MES = $uq_field[6]; // *need escape var | message
	$gr = mysqli_query($db, "SELECT * FROM user_groups WHERE group_id = '$esc_GRP' ");
	$gq_field = mysqli_fetch_row($gr);
	$esc_GRPNM = $gq_field[1]; // *need escape var | GroupName
	$ln = mysqli_query($db, "SELECT * FROM language");
	$ln_cnt = mysqli_num_rows($ln);
	if ($esc_MES == "") { $esc_MES = " ありません。" ; } else { $esc_MES = "<br>".$esc_MES ; }


	// Make new device page and jump to dev.php
	if ($_POST['addnew'] == "do!") {
		$newname = $esc_USERNM."\'s New Device";
		$newdesc = "This Device page was created at ".date("D M j G:i:s T Y")." by ".$esc_USERNM."." ;
		$newack = "Please regenerate to get a new ACCESSKEY (".time().")" ;
		$Q_STRING = "INSERT INTO device VALUES ('',$esc_USERID,'$newname','$newack',9,GeomFromText('POINT(139.730594 35.626061)'),1,0,0,NULL,'$newdesc','_noimg.png',0,0,0)";
		$DEV_Q = mysqli_query($db, $Q_STRING);
		$newdevID = mysqli_insert_id($db);
		$FTC_Q = mysqli_query($db, "INSERT INTO device_fetch VALUES ($newdevID,NULL)");
		header("Location: dev.php?id=".$newdevID);
		exit;
	}















	mysqli_close($db);
?>
<!DOCTYPE html>
<html>
<!-- header -->
<head>
<?php include '_htmlhead.html'; ?>
<title><?php echo $_SESSION['USER']; ?>'s Userpage / Open Sensor Storage & CMS Service</title>
</head><!-- header/end -->
<body>
<div id="container">
<?php include '_htmlbodyheader.html'; ?>
<!-- X ---------------------------------------------------------------------- X -->







	<?php include '_htmlsidebar.html'; ?>


	<article id="notice">
		<h2>My Page</h2>
		<?php echo $alert; ?>
		<p>現在登録されているデバイス数: <?php echo $row_cnt; ?></p>
		<p>あなたへのお知らせ:<?php echo $esc_MES; ?></p>
	</article>


	<section class="bb">
		------------------------- 登録日が新しい順に表示しています -------------------------
	</section>




<?php
	// Show your own devices
	for ($i=1; $i<=$row_cnt; $i++) {
		$devq_field = mysqli_fetch_row($devicequery);
		// Select article color
		$articletype = "device_normal";
		if ($devq_field[12] == 1) { $articletype = "device_user"; } //is_user=1
		if ($devq_field[13] == 1) { $articletype = "device_hidden"; } //is_hidden=1
		// ECHO html lang:
		echo '<article class=' . $articletype . '>';
		echo '<img src="./device_images/' . $devq_field[11] . '" alt="Device image">'; //device photo image
		echo '<h4><a href="./dev.php?id='. $devq_field[0] .'">' . $devq_field[2] . '</a></h4>'; //device name
		echo '<p>' . $devq_field[10] . '</p>'; //device description
		echo '</article>';
	}
?>




	<section class="bb">
		---------------------------------------------------------------------------
	</section>




	<article id="config">
		<h2>Add New Device</h2>
		<form action="<?php print($_SERVER['PHP_SELF']); ?>" method="POST" style="margin-bottom:20px;">
			<input type="hidden" name="addnew" value="do!"><input type="submit" value="Create and Jump to My new device page!">
		</form>
		<h2>General Config</h2>
		<form action="<?php print($_SERVER['PHP_SELF']); ?>" method="POST">
		<table>
			<tr><th>User ID </th><td> <?php echo $_SESSION['USER']; ?></td></tr>
			<tr><th>User Group </th><td> <?php echo $esc_GRPNM; ?></td></tr>
			<tr><th>Email Address </th><td> <?php echo '<input type="text" placeholder="Your new email address here" name="email" value="'.$esc_EM.'" style="width:300px;">'; ?></td></tr>
			<tr><th>Language </th><td> <select name="language">
			<?php
			for ($i=1; $i<=$ln_cnt; $i++) {
				$lq_field = mysqli_fetch_row($ln);
				if ($i == $esc_LNG) { echo '<option value="'.$i.'" selected="selected">'.$lq_field[2].'</option>'; } else { echo '<option value="'.$i.'">'.$lq_field[2].'</option>'; }
			}
			?>
			</select></td></tr>
		</table>
		<span><input type="submit" value="Change my config"><input type="reset" value="Revert to previous"></span>
		</form>
		<h2>Password</h2>
		<form action="<?php print($_SERVER['PHP_SELF']); ?>" method="POST">
		<table>
			<tr><th>New Password </th><td> <?php echo '<input type="password" placeholder="6-15 letters, alphabets&nums" name="passwd" value="" style="width:200px;">'; ?></td></tr>
			<tr><th>Confirm </th><td> <?php echo '<input type="password" placeholder="please retype same password" name="confpass" value="" style="width:200px;">'; ?></td></tr>
		</table>
		<span><input type="submit" value="Change my password"></span>
		</form>
	</article>








<!-- X ---------------------------------------------------------------------- X -->
	<footer>© Sony Computer Science Laboratories, Inc.</footer></div>
</body>
</html>
<!-- EOF -->
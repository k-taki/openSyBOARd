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


	// Get about my own information
	$uif = mysqli_query($db, "SELECT * FROM user WHERE user_id = '$esc_USERID' ");
	$uq_field = mysqli_fetch_row($uif);
	$esc_EM = $uq_field[3]; // *need escape var | email
	$esc_GRP = $uq_field[4]; // *need escape var | group_id
	$esc_LNG = $uq_field[5]; // *need escape var | language_id
	$esc_MES = $uq_field[6]; // *need escape var | message
	$gr = mysqli_query($db, "SELECT * FROM user_groups WHERE group_id = '$esc_GRP' ");
	$gq_field = mysqli_fetch_row($gr);
	$esc_GRPNM = $gq_field[1]; // *need escape var | GroupName
	$ln = mysqli_query($db, "SELECT * FROM language WHERE id = '$esc_LNG' ");
	$lq_field = mysqli_fetch_row($ln);
	$esc_LNGNM = $gq_field[2]; // *need escape var | LanguageName







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
		<h2>My Devices</h2>
		<p>現在登録されているデバイス数: <?php echo $row_cnt; ?></p>
		<p>あなたへのお知らせ:<br><?php echo $esc_MES; ?></p>
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









<!-- X ---------------------------------------------------------------------- X -->
	<footer>© Sony Computer Science Laboratories, Inc.</footer></div>
</body>
</html>
<!-- EOF -->
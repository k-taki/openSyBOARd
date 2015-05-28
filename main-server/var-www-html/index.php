<?php
	//session_set_cookie_params(0, '/', $httponly=true);
	session_start();
	session_regenerate_id(TRUE);

	// Login status check:
	if (!isset($_SESSION['USER'])) {
		header("Location: login.php");
		exit;
	}


	// Set Cookie
	//setcookie("");


	// Connect MySQL
	$db = mysqli_connect('localhost', 'toadrockie', 'cr0akcroAk', 'SyMain');
	if (!$db) {
		die('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
	}
	mysqli_set_charset($db, "utf8");
	


	// Get user id and group
	$esc_USER = $_SESSION['USER']; // *need escape var
	$query = mysqli_query($db, "SELECT group_id, user_id FROM user WHERE username = '$esc_USER' ");
	$q_field = mysqli_fetch_row($query);
	$groupid = $q_field[0];
	$_SESSION['USER_ID'] = $q_field[1];
	$query = mysqli_query($db, "SELECT name FROM user_groups WHERE group_id = '$groupid' ");
	$q_field = mysqli_fetch_row($query);
	$_SESSION['GROUP'] = $q_field[0];


	// Get user level (is admin or not)
	// 0 = only "guest"
	// 1 = public user (authorized)
	// 2 = admin like user
	// 3 = only "admin"
	$query = mysqli_query($db, "SELECT admin_level FROM user_groups WHERE group_id = '$groupid' ");
	$q_field = mysqli_fetch_row($query);
	$_SESSION['ADMIN_LEVEL'] = $q_field[0];
	// Get user grants (enable view hidden contents or not)
	$query = mysqli_query($db, "SELECT view_hidden FROM user_groups WHERE group_id = '$groupid' ");
	$q_field = mysqli_fetch_row($query);
	$_SESSION['VIEW_HIDDEN'] = $q_field[0];


	// Get the number of devices, and listing up
	if ($_SESSION['VIEW_HIDDEN'] == 1) {
		$devicequery = mysqli_query($db, "SELECT * FROM device");
		$row_cnt = mysqli_num_rows($devicequery);
	} else {
		$devicequery = mysqli_query($db, "SELECT * FROM device WHERE is_hidden = '0' ");
		$row_cnt = mysqli_num_rows($devicequery);
	}


	// Device info page redirect (session var?)
	if (isset($_SESSION['DEVINFO_ID'])) {
		$esc_DID = $_SESSION['DEVINFO_ID'];
		unset($_SESSION['DEVINFO_ID']);
		header("Location: dev.php?id=".$esc_DID);
		exit;
	}






	mysqli_close($db);
?>
<!DOCTYPE html>
<html>
<!-- header -->
<head>
<?php include '_htmlhead.html'; ?>
<title>SyBOAR Mainpage / Open Sensor Storage & CMS Service</title>
</head><!-- header/end -->
<body>
<div id="container">
<?php include '_htmlbodyheader.html'; ?>
<!-- X ---------------------------------------------------------------------- X -->



<!--
 　　　　　.（⌒)
　　　∧__∧ （~)
　　（｡･ω･｡)( )
　　{￣￣￣￣}
　　{~￣お_＿} ソースコード、みちゃいましたね・・・
　　{~￣茶_＿} まあお茶でもどうぞ
　　{＿＿＿＿}
　　　┗━━━┛
-->



	<?php include '_htmlsidebar.html'; ?>


	<article id="notice">
		<h2>Welcome to open-SyBOARd!</h2>
		<p>現在登録されている全デバイス数: <?php echo $row_cnt; ?></p>
		<p>お知らせ:<br><?php include '_message'; ?></p>
	</article>


	<section class="bb">
		------------------------- 全デバイスの中からランダムに3件を表示しています -------------------------
	</section>







<?php
	// Show random 3 articles
	for ($i=1; $i<=3; $i++) {
		$randrow = rand(0, ($row_cnt-1));
		mysqli_data_seek($devicequery, $randrow); //random select one in rows
		$devq_field = mysqli_fetch_row($devicequery);
		// Select article color
		$articletype = "device_normal";
		if ($devq_field[12] == 1) { $articletype = "device_user"; } //is_user=1
		if ($devq_field[13] == 1) { $articletype = "device_hidden"; } //is_hidden=1
		// ECHO html lang:
		echo '<article class="' . $articletype . '">';
		echo '<img src="./device_images/' . $devq_field[11] . '" alt="Device image">'; //device photo image
		echo '<h4><a href="./dev.php?id='. $devq_field[0] .'">' . $devq_field[2] . '</a></h4>'; //device name
		echo '<p>' . $devq_field[10] . '</p>'; //device description
		echo '</article>';
	}
?>









<!-- X ---------------------------------------------------------------------- X -->
	<footer>© Sony Computer Science Laboratories, Inc.</footer>
</div>
</body>
</html>
<!-- EOF -->
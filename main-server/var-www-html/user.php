<?php
	session_start();
	session_regenerate_id(TRUE);


	// Self method (POST)


	// Login status check:
	if (!isset($_SESSION['USER'])) {
		header("Location: login.php");
		exit;
	}


	// Connect MySQL
	$db = mysqli_connect('localhost', 'toadrockie', 'cr0akcroAk', 'SyMain');
	if (!$db) {
		die('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
	}
	mysqli_set_charset($db, "utf8");


	// Get about my own information
	$esc_USERID = $_SESSION['USER_ID']; // *need escape var
	$uif = mysqli_query($db, "SELECT * FROM user WHERE user_id = '$esc_USERID' ");
	$uq_field = mysqli_fetch_row($uif);
	$esc_EM = $uq_field[3]; // *need escape var
	$esc_GRP = $uq_field[4]; // *need escape var
	$gr = mysqli_query($db, "SELECT * FROM user_groups WHERE group_id = '$esc_GRP' ");
	$gq_field = mysqli_fetch_row($gr);
	$esc_GRPNM = $gq_field[1]; // *need escape var


	// Select article color
	$articletype = "device_normal";
	if ($devq_field[13] == 1) { $articletype = "device_user"; } //is_user=1
	if ($devq_field[14] == 1) { $articletype = "device_hidden"; } //is_hidden=1


	// Redirect general user if they access hidden device (except owner)
	if ($devq_field[14] == 1 and $_SESSION['VIEW_HIDDEN'] == 0 and $esc_OWNID != $esc_USERID) {
		$_SESSION['ERROR'] = "The request is referring hidden device.";
		header("Location: sorry.php");
		exit;
	}


	// Get device's location (position)
	$posquery = mysqli_query($db, "SELECT X(location), Y(location) FROM device WHERE device_id = '$esc_DEVID' ");
	$posq_field = mysqli_fetch_row($posquery);


	// Get data rows by the device from data table
	$dataquery = mysqli_query($db, "SELECT * FROM data WHERE device_id = '$esc_DEVID' ORDER BY timestamp DESC");
	$datrow_cnt = mysqli_num_rows($dataquery);
	// Get data location (position)
	$dpquery = mysqli_query($db, "SELECT X(location), Y(location) FROM data WHERE device_id = '$esc_DEVID' ORDER BY timestamp DESC");

















	mysqli_close($db);
?>
<!DOCTYPE html>
<html>
<!-- header -->
<head>
<?php include '_htmlhead.html'; ?>
<title>SyBOAR User page / Open Sensor Storage & CMS Service</title>
</head><!-- header/end -->
<body>
<div id="container">
<?php include '_htmlbodyheader.html'; ?>
<!-- X ---------------------------------------------------------------------- X -->











	<?php echo '<article id="user">'; ?>
		<?php echo '<img src="./device_images/' . $devq_field[11] . '" alt="Device image">'; ?>


		<!-- Device information table -->
		<table id="info">
			<tr><th>Device ID</th>
				<td><?php echo $devq_field[0]; ?></td></tr>
			<tr><th>Owner</th>
				<td><?php echo $uq_field[0]; ?></td></tr>
			<tr><th>Place</th>
				<td><?php echo $devq_field[9]; ?></td></tr>
			<tr><th>Location</th>
				<td>
				<?php if ($devq_field[6] == 0) : ?>
					<?php echo 'Indefinite'; ?>
				<?php else : ?>
					<?php echo '<a href="http://maps.google.com/maps?q='.$posq_field[1].','.$posq_field[0].'" target="_blank">'.$posq_field[0].', '.$posq_field[1].'</a>'; ?></td></tr>
				<?php endif; ?>
			<tr><th>Altitude</th>
				<td><?php echo $devq_field[7]; ?> m</td></tr>
			<tr><th>Attached Height</th>
				<td><?php echo $devq_field[8]; ?> mm</td></tr>
			<tr><th>Time Zone</th>
				<td><?php echo $devq_field[4]; ?> hour(s)</td></tr>
			<tr><th>Status</th>
				<?php $enb = 'Error'; if ($devq_field[15] == 0) { $enb = 'Available'; }; if ($devq_field[15] == 1) { $enb = 'Unavailable'; }; ?>
				<td><?php echo $enb; ?></td></tr>
		</table>


		<!-- User name -->
		<h2>User: <?php echo $_SESSION['USER']; ?></h2>


		<!-- Information(able alterate) -->
		<h2>Infomation</h2>
		<p> <!-- User ID (alt=forbidden) -->
			User ID : <?php echo $esc_USERID; ?>
		</p>
		<p> <!-- User group (alt=forbidden) -->
			User group : <?php echo $esc_GRPNM; ?>
		</p>
		<p> <!-- User group (alt=allowed) -->
			User Email Address : <?php echo '<input type="text" placeholder="email address here" name="email" value="'.$esc_EM.'" style="width:200px;">'; ?>
		</p>
		
		<input type="text" placeholder="apikey" name="apikey" value="11de2381a3c490ed48c5ba69c7f8aefc53" style="width:300px;">
















		<!-- Config -->
		<h2>Config</h2>
		<section class="dat">
			------------------------- 準備中です -------------------------
		</section>


	</article>












<!-- X ---------------------------------------------------------------------- X -->
	<footer>© Sony Computer Science Laboratories, Inc.</footer></div>
</body>
</html>
<!-- EOF -->
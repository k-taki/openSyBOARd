<?php
	session_start();
	session_regenerate_id(TRUE);


	// Device selected by url?
	// e.g. "/deviceinfo.php?id=4"
	if (!isset($_GET['id'])) {
		header("Location: ./");
		exit;
	}


	// Login status check:
	if (!isset($_SESSION['USER'])) {
		// ?id=x -> SESSION var and jump login page
		$_SESSION['DEVINFO_ID'] = $_GET['id'];
		header("Location: login.php");
		exit;
	}


	// Connect MySQL
	$db = mysqli_connect('localhost', 'toadrockie', 'cr0akcroAk', 'SyMain');
	if (!$db) {
		die('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
	}
	mysqli_set_charset($db, "utf8");


	// Get about selected device
	$esc_USERID = $_SESSION['USER_ID']; // *need escape var
	$esc_DEVID = $_GET['id']; // *need escape var
	$devicequery = mysqli_query($db, "SELECT * FROM device WHERE device_id = '$esc_DEVID' "); //get selected device's row
	$devq_field = mysqli_fetch_row($devicequery);
	if (!isset($devq_field[0])) { // if no device id on database
		$_SESSION['ERROR'] = "Device ID is not exist on this database.";
		header("Location: sorry.php");
		exit;
	}


	// Select article color
	$articletype = "device_normal";
	if ($devq_field[13] == 1) { $articletype = "device_user"; } //is_user=1
	if ($devq_field[14] == 1) { $articletype = "device_hidden"; } //is_hidden=1


	// Get who is owner
	$esc_OWNID = $devq_field[1];
	$userquery = mysqli_query($db, "SELECT username FROM user WHERE user_id = '$esc_OWNID' ");
	$uq_field = mysqli_fetch_row($userquery);


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

















	mysqli_close($dblink);
?>
<!DOCTYPE html>
<html>
<!-- header -->
<head>
<?php include '_htmlhead.html'; ?>
<title>SyBOAR Device information / Open Sensor Storage & CMS Service</title>
</head><!-- header/end -->
<body>
<div id="container">
<?php include '_htmlbodyheader.html'; ?>
<script type="text/javascript" src="./js/flotr2.min.js"></script>
<!-- X ---------------------------------------------------------------------- X -->











	<?php echo '<article id="device" class="' . $articletype . '">'; ?>
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


		<!-- Device name -->
		<h2><?php echo "Device: ".$devq_field[2]; ?></h2>
		<!-- Device description -->
		<p><?php echo $devq_field[10]; ?></p>


		<!-- Datalist -->
		<h2>Latest Data</h2>
		<table id="data">
			<!-- Data field name -->
			<tr><th>LOCALTIME</th><th>LOCATION</th><th>PLACE</th><th>CONTENT</th><th>DATA STRING</th><th>TAGS</th></tr>
			<!-- Data body -->
<?php
			for ($i=1; $i<=$datrow_cnt; $i++) {
				// RENEW data
				$datq_field = mysqli_fetch_row($dataquery);
				$dpq_field = mysqli_fetch_row($dpquery);
				// table row
				echo "<tr>";
					// timestamp
					echo "<td>".$datq_field[2]." ".$datq_field[3]."</td>";
					// location
					echo "<td>";
					if ($datq_field[7] == 1) {
						echo " - ";
					} else {
						echo '<a href="http://maps.google.com/maps?q='.$dpq_field[1].','.$dpq_field[0].'" target="_blank">'.$dpq_field[0].', '.$dpq_field[1].'</a>';
					}
					echo "</td>";
					// place
					echo "<td>".$datq_field[10]."</td>";
					// contentfile
					if ($datq_field[11] == "NO_CONTENT") {
						echo "<td> - </td>";
					} else {
						echo '<td><a href="../../contents/'.$datq_field[11].'" target="_blank">'.$datq_field[12].'</a></td>';
					}
					// datastring
					echo "<td>".$datq_field[13]."</td>";
					// tags
					echo "<td>".$datq_field[14]."</td>";
				echo "</tr>";
				if ($i >=5) { break; }
			}
?>
		</table>


		<!-- Graph -->
		<h2>Graph View</h2>
		<section id="graph">
		<script type="text/javascript">
			(function basic(container) {
				var d1 = [
					[1, 70],
					[2, 68],
					[3, 65],
					[4, 67],
					[5, 64],
					[6, 61],
					[7, 60],
					[8, 62],
					[9, 68],
					[10, 67],
					[11, 70],
					[12, 72]
					],
				d2 = [
					[1, 70],
					[2, 69],
					[3, 70],
					[4, 71],
					[5, 69],
					[6, 70],
					[7, 69],
					[8, 68],
					[9, 69],
					[10, 70],
					[11, 73],
					[12, 75]
					],
				data = [{
					data: d1,
					label: "2012年"
					}, {
					data: d2,
					label: "2000年"
				}];

			function labelFn(label) {
				return label;
			}

			graph = Flotr.draw(container, data, {
				legend: {
					position: "se",
					labelFormatter: labelFn,
					backgroundColor: "#D2E8FF"
				},
				HtmlText: false
			});
		})(document.getElementById("graph"));
		</script>
		</section>


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
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
<script type="text/javascript" src="./js/flotr2.js"></script>
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
				$datq_field = mysqli_fetch_row($dataquery); // DATA-ENTRY
				$dpq_field = mysqli_fetch_row($dpquery); // PLACE
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
				if ($i >=5) { break; } // latest 5 entries
			}
?>
		</table>







		<!-- Graph -->
		<h2>Graph View</h2>
		<section id="graph">
		<script type="text/javascript">
		(function basic_time(container) {
			var
			d1    = [],
			start = new Date("2009/01/01 01:00").getTime(),
			options,
			graph,
			i, x, o;
			
			for (i = 0; i < 100; i++) {
				x = start+(i*1000*3600*24*36.5);
				d1.push([x, i+Math.random()*30+Math.sin(i/20+Math.random()*2)*20+Math.sin(i/10+Math.random())*10]);
			}
			
			options = {
				xaxis : {
					mode : 'time', 
					labelsAngle : 45
				},
				selection : {
					mode : 'x'
				},
				HtmlText : false,
				title : 'Time'
			};
			
			// Draw graph with default options, overwriting with passed options
			function drawGraph (opts) {
			// Clone the options, so the 'options' variable always keeps intact.
			o = Flotr._.extend(Flotr._.clone(options), opts || {});
			// Return a new graph.
			return Flotr.draw(
				container,
				[ d1 ],
				o
				);
			}
			
			graph = drawGraph();
			
			Flotr.EventAdapter.observe(container, 'flotr:select', function(area){
				// Draw selected area
				graph = drawGraph({
					xaxis : { min : area.x1, max : area.x2, mode : 'time', labelsAngle : 45 },
					yaxis : { min : area.y1, max : area.y2 }
				});
			});
			
			// When graph is clicked, draw the graph with default area.
			Flotr.EventAdapter.observe(container, 'flotr:click', function () { graph = drawGraph(); });
		})
		(document.getElementById("editor-render-0"));



<?php  // PHP_CODE -----------------------------------------------------------------------------
	//ini_set('display_errors', 'On');
				mysqli_data_seek($dataquery,0); // index reset
				for ($i=0; $i<=$datrow_cnt; $i++) {
					// RENEW
					$datq_field = mysqli_fetch_row($dataquery);
					$val_X[$i] = $datq_field[3]; // time(h:m:s)
					
					// GET DATA STRING (a entry)
					$data_PART = explode(")", $datq_field[13]); // ) (key-left split) ex: "TEMP(22.1"+"HUMD(56.0"+...
					$data_NUM = count($data_PART); // number of data-type
					
					for ($j=0; $j<$data_NUM; $j++) {
						$data_part_split = explode("(", $data_PART[$j]); // ( (key-right split) ex: "TEMP"+"22.1"
						$val_Y[$j][$i] = (float)$data_part_split[1]; // define val_Y[key-index][entry-number]=value
						$series_name[$j] = $data_part_split[0]; // key name
					}
					
					// Convert datetime to unix-epoch
					$ex_date = explode("-", $datq_field[2]); $ex_time = explode(":", $datq_field[3]);
					$ep_timestamp[$i] = mktime($ex_time[0],$ex_time[1],$ex_time[2],$ex_date[1],$ex_date[2],$ex_date[0]);
					
					
					// DRAW GRAPH
					if ($i>=4) {
						echo "[ new Date(".$ep_timestamp[$i]."), ".$val_Y[0][$i]."]"; // delete last comma
						break; // latest 5 entries
					} else {
						echo "[ new Date(".$ep_timestamp[$i]."), ".$val_Y[0][$i]."],";
					}
				}
?> // PHP_CODE -----------------------------------------------------------------------------





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
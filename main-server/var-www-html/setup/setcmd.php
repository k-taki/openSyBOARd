<!DOCTYPE html>
<html>
<head>
	<meta charset="Shift_JIS">
	<meta http-equiv="refresh" content="5 ; http://ec2-54-249-238-92.ap-northeast-1.compute.amazonaws.com/setup/">
	<title>LiveCamduino3G! - Altered cmdfile</title>
</head>
<body>
<?php
	$cmd = $_POST["cmd"]."";
	$tempf = "temp.s";
	$ff = fopen($tempf, 'w');
	fputs($ff, $cmd);
	fclose($ff);
	$setf = "cmd.s";
	copy($tempf, $setf);
	echo 'Selected command = ['.$cmd.']';
?>
<br>
Redirect to http://ec2-54-249-238-92.ap-northeast-1.compute.amazonaws.com/setup/ after 5seconds...<br>
</body>
</html>

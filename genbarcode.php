<html>
<head>
	<meta http-equiv="Content-Language" content="nl">
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
	<title>Barcode</title>
</head>
<body onload="window.print()">
<div style='text-align: center;'>
<?php

echo "<img src=\"barcode.php?text=".$_GET['productnummer']."\" alt=\"".$_GET['productnummer']."\" />";
echo "<br>".$_GET['productnummer'];

?>
</div>
</body>
</html>
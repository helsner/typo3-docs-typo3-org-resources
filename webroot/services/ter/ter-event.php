<?php
	$NL = "\n";
	echo '<pre>'. $NL;
	echo 'This is \'ter-event.php\''. $NL . $NL;
	echo '$_GET:'. $NL;
	$r = print_r($_GET, 1);
	echo htmlspecialchars($r) . $NL;
	echo '$_POST:'. $NL;
	$r = print_r($_POST, 1);
	echo htmlspecialchars($r) . $NL;
	echo '</pre>'. $NL;
?>

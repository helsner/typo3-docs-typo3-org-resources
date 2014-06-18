<?php

error_reporting(E_ALL);
ini_set('error_reporting', E_ALL);

$NL = "\n";
$f2path = '/home/mbless/public_html/services/log-of-notifications.txt';
$f3path = '/home/mbless/public_html/services/args-of-last-request.txt';

echo $f2path;
echo '<br>' . $NL;
$f2 = fopen($f2path, 'a');
if ($f2) {
	$github = FALSE;
	$github = @json_decode($_POST['payload'], TRUE);
	$github_repository_url = $github['repository']['url'];
	fwrite($f2, $github_repository_url . $NL);

	$f1 = fopen('/home/mbless/public_html/services/known-github-manuals.txt', 'r');
	while (!feof($f1)) {
		$line = fgets($f1);
		if ($line !== FALSE) {
			$line = trim($line);
			// skip comment lines
			if (strpos($line, '#') !== 0) {
				$parts = explode(',', $line);
				if ($github_repository_url === trim($parts[0])) {
					$requrl = trim($parts[1]);
				}
				if (strlen($requrl)) {
					$cmd = 'wget -q -O /dev/null ' . $requrl;
					exec($cmd);
					fwrite($f2, $cmd . $NL);
				}
			}
		}
	}
	fclose($f1);
	fclose($f2);
} else {
	echo 'Could not open file ' . $f2path ;
}

$f3 = fopen($f3path, 'a');
if ($f3) {
	if (1) {
		fwrite($f3, '/* -------------------------------------------------- */' . $NL);
	}
	if (1) {
		$args = print_r($_POST, TRUE);
		echo '$_POST:' . htmlspecialchars($args);
		fwrite($f3, '$_POST:' . $NL . $NL . $args);
		fwrite($f3, $NL);
	}
	if (1) {
		$args = print_r($_GET, TRUE);
		echo '$_GET:' . htmlspecialchars($args);
		fwrite($f3, '$_GET:' . $NL . $NL . $args);
		fwrite($f3, $NL);
	}
	if (1) {
		$args = print_r($github, TRUE);
		echo '$payload:' . htmlspecialchars($args);
		fwrite($f3, '$payload:' . $NL . $NL . $args);
		fwrite($f3, $NL);
	}
	fclose($f3);
}

?>
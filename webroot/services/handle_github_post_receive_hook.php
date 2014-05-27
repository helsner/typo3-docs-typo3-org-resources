<?php

error_reporting(E_ALL);
ini_set('error_reporting', E_ALL);

$NL = "\n";
$f2path = '/home/mbless/public_html/services/log-of-notifications.txt';
$f3path = '/home/mbless/public_html/services/args-of-last-request.txt';

$mapping = array(
	'https://github.com/froemken/typo3-extbase-guide' => 'http://docs.typo3.org/~mbless/github.com/froemken/typo3-extbase-guide.git.make/request_rebuild.php',
	'https://github.com/froemken/typo3-fluid-guide' => 'http://docs.typo3.org/~mbless/github.com/froemken/typo3-fluid-guide.git.make/request_rebuild.php',
	'https://github.com/marble/typo3-incoming-notes' => 'http://docs.typo3.org/~mbless/github.com/marble/typo3-incoming-notes.git.make/request_rebuild.php',
);

echo $f2path;
echo '<br>' . $NL;
$f2 = fopen($f2path, 'a');
if ($f2) {
	if (1) {
		$github = FALSE;
		$github = @json_decode($_POST['payload'], TRUE);
		$url = $github['repository']['url'];
		fwrite($f2, $url . $NL);

		$requrl = $mapping[$url];
		if (!strlen($requrl)) {
			$f1 = fopen('/home/mbless/public_html/services/known-github-manuals.txt', 'r');
			while (!strlen($requrl) && !feof($f1)) {
				$line = fgets($f1);
				$parts = explode(',', $line);
				if ($url == trim($parts[0])) {
					$requrl = trim($parts[1]);
				}
			}
			fclose($f1);
		}
		if (strlen($requrl)) {
			$cmd = 'wget -q -O /dev/null ' . $requrl;
			exec($cmd);
			fwrite($f2, $cmd . $NL);
		}
	}
	fclose($f2);
} else {
	echo "Could not open file.";
}

$f3 = fopen($f3path, 'w');
if ($f3) {
	if (1) {
		$args = print_r($_POST, TRUE);
		echo htmlspecialchars($args);
		fwrite($f3, $args);
		fwrite($f3, $NL);
	}
	if (1) {
		$args = print_r($_GET, TRUE);
		echo htmlspecialchars($args);
		fwrite($f3, $args);
		fwrite($f3, $NL);
	}
	if (1) {
		$args = print_r($github, TRUE);
		echo htmlspecialchars($args);
		fwrite($f3, $args);
		fwrite($f3, $NL);
	}
	fclose($f3);
}

?>
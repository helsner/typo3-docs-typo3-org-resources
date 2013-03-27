<?php
/*
 * assumptions: if $abspath does not exist, we should test '.../latest/...'
 * legal: latest, 1.0, 1.0.0, 1.0.0..., but NOT just '1'!
 *
 */
ini_set('display_errors',1);
error_reporting(E_ALL);

function isVersionPart($part) {
    $result = False;
    $hits = '';
    if (preg_match('~^(\d+(\.\d+)+)$|^latest$~', $part, $hits)) {
        $result = TRUE;
    }
    return $result;
}


echo '<pre>';
$docs_webroot = '/home/mbless/public_html';
$docs_host = 'docs.typo3.org';
$trueabspath = null;
$NL = "\n";
$url = $_GET['url'];
$arr = parse_url($url);
print_r($arr);
$path = $arr['path'];
if (0) { // realistic examples
    $arr['scheme'] === 'http';
    $arr['host'] === 'docs.typo3.org';
    $arr['path'] === '/typo3cms/TyposcriptReference/Index.html';
}
if (strlen($path)) {
    $abspath = $docs_webroot . $path . '/latest/100.3x'    ;
    echo $abspath . $NL;
    // print_r(stat($abspath));

    $is_file = is_file($abspath);
    $is_dir = is_dir($abspath);
    $is_link = is_link($abspath);

    echo 'is_file(): ' . is_file($abspath) . $NL;
    echo 'is_dir(): ' . is_dir($abspath) . $NL;
    echo 'is_link(): ' . is_link($abspath) . $NL;

    $parts = explode('/', $abspath);
    $lastpart = array_slice($parts, -1, 1);
    $lastpart = $lastpart[0];

    echo '$lastpart:' . $lastpart . $NL;
    echo 'isVersionPart($lastpart):' . isVersionPart($lastpart) . $NL;

}
if (0) {
    $handle = opendir($abspath);
    if ($handle) {
        echo "Directory handle: $handle\n";
        echo "Files:\n";

        /* Das ist der korrekte Weg, ein Verzeichnis zu durchlaufen. */
        while (false !== ($file = readdir($handle))) {
            echo $file . $NL;
        }
        closedir($handle);
    }
}
if ( $is_dir or $is_file) {
    $trueabspath = $abspath;
} else {
    array_splice($parts, -1, 0, 'latest');
    $temp = implode('/', $parts);
    $is_file = is_file($temp);
    $is_dir = is_dir($temp);
    $is_link = is_link($temp);
    if ( $is_dir or $is_file) {
        $trueabspath = $temp;
    }
}
if (! $trueabspath) {
    return;
}



echo '</pre>';
//phpinfo();
?>

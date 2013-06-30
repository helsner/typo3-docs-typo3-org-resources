<?php

$data = json_decode($_POST['json'], 1);
$NL = "\n";
list($day, $time) = explode(',', strftime('%Y-%m-%d,%H:%M:%S', time()));
if ($data) {
    $result = print_r($data, 1) . $NL;
} else {
    $result = 'da war nix' . $NL;
}
if (1) {
	$f2name = "zzzlog-1.txt";
    $f2 = fopen($f2name, 'w');
    fwrite($f2, $result);
    fclose($f2);
}
if (1 or $data) {
    $f2name = "zzzlog-2.txt";
    $f2 = fopen($f2name, 'a');
    $r = '';

    $r .= $day;
    $r .= ',';
    $r .= $time;
    $r .= ',';

    $r .= isset($data['repositoryUrl']) ? $data['repositoryUrl'] : 'repositoryUrl?';
    $r .= ',';
    $r .= isset($data['project']) ? $data['project'] : 'project?';
    $r .= ',';
    $r .= isset($data['branch']) ? $data['branch'] : 'branch?';
    $r .= $NL;
    fwrite($f2, $r);
    fclose($f2);
}

// $kp - "known projects"
$kp = array();
$kp['Documentation/DocsTypo3Org']['master'] = 'Documentation/DocsTypo3Org.git.make';
$kp['Documentation/TYPO3/Book/ExtbaseFluid']                  ['master'] = '.git.make';
$kp['Documentation/TYPO3/Example/ExtensionManual']            ['master'] = '.git.make';
$kp['Documentation/TYPO3/Example/OfficialManual']             ['master'] = '.git.make';
$kp['Documentation/TYPO3/Guide/FrontendLocalization']         ['master'] = '.git.make';
$kp['Documentation/TYPO3/Guide/Installation']                 ['master'] = '.git.make';
$kp['Documentation/TYPO3/Guide/Maintenance']                  ['master'] = '.git.make';
$kp['Documentation/TYPO3/Guide/Security']                     ['master'] = '.git.make';
$kp['Documentation/TYPO3/Reference/CodingGuidelines']         ['master'] = '.git.make';
$kp['Documentation/TYPO3/Reference/CodingGuidelines']         ['4-5']    = '.git.make_4.5';
$kp['Documentation/TYPO3/Reference/CodingGuidelines']         ['4-6']    = '.git.make_4.6';
$kp['Documentation/TYPO3/Reference/CodingGuidelines']         ['4-7']    = '.git.make_4.7';
$kp['Documentation/TYPO3/Reference/CodingGuidelines']         ['6-0']    = '.git.make_6.0';
$kp['Documentation/TYPO3/Reference/CodingGuidelines']         ['6-1']    = '.git.make_6.1';
$kp['Documentation/TYPO3/Reference/CodingGuidelines']         ['6-2']    = '.git.make_6.2';
$kp['Documentation/TYPO3/Reference/CoreApi']                  ['master']           = '.git.make';
$kp['Documentation/TYPO3/Reference/CoreApi']                  ['doc_core_api_4-5'] = '.git.make_4.5';
$kp['Documentation/TYPO3/Reference/CoreApi']                  ['doc_core_api_4-6'] = '.git.make_4.6';
$kp['Documentation/TYPO3/Reference/CoreApi']                  ['doc_core_api_4-7'] = '.git.make_4.7';
$kp['Documentation/TYPO3/Reference/CoreApi']                  ['6-0']              = '.git.make_6.0';
$kp['Documentation/TYPO3/Reference/CoreApi']                  ['6-1']              = '.git.make_6.1';
$kp['Documentation/TYPO3/Reference/CoreApi']                  ['6-2']              = '.git.make_6.2';
$kp['Documentation/TYPO3/Reference/FileAbstractionLayer']     ['master'] = '.git.make';
$kp['Documentation/TYPO3/Reference/IndexedSearch']            ['master'] = '.git.make';
$kp['Documentation/TYPO3/Reference/InsideTypo3']              ['master'] = '.git.make';
$kp['Documentation/TYPO3/Reference/Skinning']                 ['master'] = '.git.make';
$kp['Documentation/TYPO3/Reference/Tca']                      ['master'] = '.git.make';
#kp['Documentation/TYPO3/Reference/Tca']                      ['4-7']    = '.git.make_4.7';
$kp['Documentation/TYPO3/Reference/Tca']                      ['6.0']    = '.git.make_6.0';
$kp['Documentation/TYPO3/Reference/Tca']                      ['6-1']    = '.git.make_6.1';
$kp['Documentation/TYPO3/Reference/Tca']                      ['6-2']    = '.git.make_6.2';
$kp['Documentation/TYPO3/Reference/Tsconfig']                 ['master'] = '.git.make';
#kp['Documentation/TYPO3/Reference/Tsconfig']                 ['4-7']    = '.git.make_4.7';
$kp['Documentation/TYPO3/Reference/Tsconfig']                 ['6-0']    = '.git.make_6.0';
$kp['Documentation/TYPO3/Reference/Tsconfig']                 ['6-1']    = '.git.make_6.1';
$kp['Documentation/TYPO3/Reference/Tsconfig']                 ['6-2']    = '.git.make_6.2';
$kp['Documentation/TYPO3/Reference/Typo3Services']            ['master'] = '.git.make';
$kp['Documentation/TYPO3/Reference/Typoscript']               ['master'] = '.git.make';
$kp['Documentation/TYPO3/Reference/Typoscript']               ['4-7']    = '.git.make_4.7';
$kp['Documentation/TYPO3/Reference/Typoscript']               ['6-0']    = '.git.make_6.0';
$kp['Documentation/TYPO3/Reference/Typoscript']               ['6-1']    = '.git.make_6.1';
$kp['Documentation/TYPO3/Reference/Typoscript']               ['6-2']    = '.git.make_6.2';
$kp['Documentation/TYPO3/Reference/TyposcriptSyntax']         ['master'] = '.git.make';
$kp['Documentation/TYPO3/Reference/TyposcriptSyntax']         ['4-7']    = '.git.make_4.7';
$kp['Documentation/TYPO3/Reference/TyposcriptSyntax']         ['6-0']    = '.git.make_6.0';
$kp['Documentation/TYPO3/Reference/TyposcriptSyntax']         ['6-1']    = '.git.make_6.1';
$kp['Documentation/TYPO3/Reference/TyposcriptSyntax']         ['6-2']    = '.git.make_6.2';
$kp['Documentation/TYPO3/Tutorial/Editors']                   ['master'] = '.git.make';
$kp['Documentation/TYPO3/Tutorial/GettingStarted']            ['master'] = '.git.make';
$kp['Documentation/TYPO3/Tutorial/Templating']                ['master'] = '.git.make';
$kp['Documentation/TYPO3/Tutorial/Typoscript45Minutes']       ['master'] = '.git.make';
$kp['FLOW3/Packages/TYPO3.FLOW3']                             ['master'] = '.git.make';
$kp['FLOW3/Packages/TYPO3.TYPO3']                             ['master'] = '.git.make';
$kp['TYPO3v4/Extensions/news']                                ['master'] = '.git.make';


$requestUrl = '';
if ($data and ($data['repositoryUrl'] == 'git://git.typo3.org/')) {
    $project = $data['project'];
    $branch = $data['branch'];
    $suffix = $kp[$project][$branch];
    if (strlen($suffix)) {
        $requestUrl .= 'http://docs.typo3.org/~mbless/git.typo3.org/';   // Documentation/TYPO3/Reference/
        $requestUrl .= $project . $suffix;
        $requestUrl .= '/request_rebuild.php';
    }
}

$cmd = '';
if (strlen($requestUrl)) {
    $cmd = 'wget -q -O /dev/null ' . $requestUrl;
    exec($cmd);
}

if (1 or $data) {
    $f2name = 'zzzlog-3.txt';
    $f2 = fopen($f2name, 'a');
    $r = '';

    $r .= $day;
    $r .= ',';
    $r .= $time;
    $r .= ',';

    $r .= isset($data['repositoryUrl']) ? $data['repositoryUrl'] : 'repositoryUrl?';
    $r .= ',';
    $r .= isset($data['project']) ? $data['project'] : 'project?';
    $r .= ',';
    $r .= isset($data['branch']) ? $data['branch'] : 'branch?';
    $r .= ',';
    $r .= empty($cmd) ? 'cmd?' : $cmd;
    $r .= $NL;
    fwrite($f2, $r);
    fclose($f2);
}

?>
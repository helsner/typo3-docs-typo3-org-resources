<?php

if (0) {
    error_reporting(-1);
    error_reporting(E_ALL ^ E_NOTICE);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
}

class VersionMatcher {

    var $dd = 1;     // do debug?
    var $webRootPath = '/home/mbless/public_html';
    var $knownPathBeginnings = array(
        // longest paths first!
        '/flow/drafts/',
        '/flow/',
        '/neos/drafts/',
        '/neos/',
        '/typo3cms/drafts/',
        '/typo3cms/extensions/',
        '/typo3cms/',
    );
    var $resolveSymlink = array(
        '/typo3cms/drafts/'     => '/TYPO3/drafts/',
        '/typo3cms/extensions/' => '/TYPO3/extensions/',
        '/typo3cms/'            => '/TYPO3/',
    );
    var $cont               = true;    // continue?
    var $url                = '';      // 'http://docs.typo3.org/typo3cms/TyposcriptReference/4.7/Setup/Page/Index.html?id=3#abc'
    var $urlPart1           = '';      // 'http://docs.typo3.org'
    var $urlPart2           = '';      // '/typo3cms/'
    var $urlPart3           = '';      // '4.7/TyposcriptReference/Setup/Page/Index.html?id=3#abc'
    var $filePathToUrlPart2 = '';      // '/TYPO3/'

    var $baseFolder         = '';      // 'TyposcriptReference'
    var $versionPath        = '';      // '4.7'
    var $relativePath       = '';      // 'Setup/Page'
    var $htmlFile           = '';      // 'Index.html'
    var $query              = '';      //  '?id=3'
    var $fragment           = '';      //  '#abc'

    var $parsedUrl;                    // array

    var $resultVersions     = array();     // the result!
    var $htmlResult         = '';
    var $htmlResultIntro    = '
		<table>
			<tr>
				<th>Go to version</th>
			</tr>
	';
    var $htmlResultTrailer  = '
		</table>
	';

    function __construct() {
        // pass
    }

    function unparse_url($parsed_url) {
        $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        $host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
        $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
        $pass     = ($user || $pass) ? "$pass@" : '';
        $path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
        return "$scheme$user$pass$host$port$path$query$fragment";
    }

    function isValidVersionFolderName($filename) {
        $isValid = false;
        if (!$isValid) { // named versions
            if (in_array( $filename, array('latest', 'stable'))) {
                $isValid = true;
            }
        }
        if (!$isValid) { // numbered versions (like 1.2.3...)
            $pattern = '~\d+(\.\d+)*~is';
            $subpattern = array();
            $result = preg_match($pattern, $filename, $subpattern);
            if ($result and ($subpattern[0] === $filename)) {
                $isValid = true;
            }
        }
        return $isValid;
    }

    function parseUrl() {
        $this->parsedUrl = parse_url($this->url);
        $this->urlPart1 = '';
        $this->urlPart1 .= isset($this->parsedUrl['scheme'  ]) ?       $this->parsedUrl['scheme'  ] . '://' : '';
        $this->urlPart1 .= isset($this->parsedUrl['host'    ]) ?       $this->parsedUrl['host'    ]         : '';
        $this->query     = isset($this->parsedUrl['query'   ]) ? '?' . $this->parsedUrl['query'   ]         : '';
        $this->fragment  = isset($this->parsedUrl['fragment']) ? '#' . $this->parsedUrl['fragment']         : '';

        // Step 1: find urlPart2 and urlPart3
        $found = false;
        foreach ($this->knownPathBeginnings as $this->urlPart2) {
            if ($this->startsWith($this->parsedUrl['path'], $this->urlPart2)) {
                $found = true;
                break;
            }
        }
        if ($found) {
            if (strlen($this->resolveSymlink[$this->urlPart2])) {
                $this->filePathToUrlPart2 = $this->resolveSymlink[$this->urlPart2];
            } else {
                $this->filePathToUrlPart2 = $this->urlPart2;
            }
            $this->urlPart3 = substr($this->parsedUrl['path'], strlen($this->urlPart2));
            $this->urlPart3PathSegments = explode('/', $this->urlPart3);
        } else {
            $this->cont = false;
        }
        if ($this->cont and (count($this->urlPart3PathSegments) < 2)) {
            $this->cont = false;
        }
        if ($this->cont) {
            $this->baseFolder = $this->urlPart3PathSegments[0];
            if (count($this->urlPart3PathSegments) > 2) {
                if ($this->isValidVersionFolderName($this->urlPart3PathSegments[1])) {
                    $this->versionPath = $this->urlPart3PathSegments[1];
                    $i = 2;
                } else {
                    $this->versionPath = '';
                    $i = 1;
                }
            }
        }
        if ($this->cont) {
            $this->relativePath = array_slice($this->urlPart3PathSegments, $i);
            $this->htmlFile = array_pop($this->relativePath);
            $this->relativePath = implode('/', $this->relativePath);
        }
        //$this->dump_and_die($this);
        return;
    }

    function startsWith($haystack, $needle) {
        return substr($haystack, 0, strlen($needle)) === $needle;
    }

    function generateOutput() {
        $NL = "\n";
        $result = $this->htmlResultIntro;
        $rowCount = 0;

        // 'absPathToHtmlFile' => $absPathToHtmlFile,
        // 'urlPart1'          => $this->urlPart1,
        // 'urlPart2'          => $this->urlPart2,
        // 'baseFolder'        => $this->baseFolder,
        // 'versionFolder'     => $versionFolder,
        // 'relativePath'      => $this->relativePath,
        // 'baseHtmlFile'      => $baseHtmlFile,
        // 'directHtmlFile'    => $directHtmlFile,

        if ($this->cont and count($this->resultVersions)) {

            krsort($this->resultVersions);

            foreach ($this->resultVersions as $k => $v) {
                if (intval($rowCount % 2)) {
                    $rowClass = ' class="row-odd"';
                } else {
                    $rowClass = '';
                }
                $valueBase = '-';
                if (strlen($v['baseHtmlFile'])) {
                    $destUrl = $v['urlPart1'] . $v['urlPart2'] . $v['baseFolder'] . '/';
                    if (strlen($v['versionFolder']) and $v['versionFolder'] !== 'stable') {
                        $destUrl .= $v['versionFolder'] . '/';
                    }
                    if (!(strlen($v['baseHtmlFile'] === 'Index.html' or $v['baseHtmlFile'] === 'index.html'))) {
                        $destUrl .= $v['baseHtmlFile'];
                    }
                    $valueBase = '<a href="' . htmlspecialchars($destUrl) . '">' . htmlspecialchars($k) . '</a>';
                }
                $valueDirect = '-';
                if (strlen($v['directHtmlFile'])) {
                    $destUrl = $v['urlPart1'] . $v['urlPart2'] . $v['baseFolder'] . '/';
                    if (strlen($v['versionFolder']) and $v['versionFolder'] !== 'stable') {
                        $destUrl .= $v['versionFolder'] . '/';
                    }
                    if (strlen($v['relativePath'])) {
                        $destUrl .= $v['relativePath'] . '/';
                    }
                    if (!(strlen($v['directHtmlFile'] === 'Index.html' or $v['directHtmlFile'] === 'index.html'))) {
                        $destUrl .= $v['directHtmlFile'];
                    }
                    $destUrl .= $v['query'];
                    $destUrl .= $v['fragment'];
                    $valueDirect = '<a href="' . htmlspecialchars($destUrl) . '">' . htmlspecialchars($k) . '</a>';
                }
                $result .= '<tr' . $rowClass . '>';
                if ($valueDirect !== '-') {
                    $result .= '<td class="rollover">' . $valueDirect . '</td>';
                } elseif ($valueBase !== '-') {
                    $result .= '<td class="rollover">' . $valueBase . '</td>';
                } else {
                    $result .= '<td>' . $valueBase . '</td>';
                }
                $result .= '</tr>';
                $rowCount += 1;
            }
        } else {
            $result .= '<tr><td>-</td></tr>';
            if (0) {
                $result .= '<tr>td><pre>'
                    . htmlspecialchars(print_r($this, 1))
                    . '</pre></td></tr>';
            }
        }
        $result .= $this->htmlResultTrailer;
        if (0 and $this->dd) { // test
            $result = '
            <table>
                <tr>
                    <th>Go to START of version</th>
                    <th>Go to SAME PAGE of version</th>
                </tr>
                <tr>
                    <td><a href="http://docs.typo3.org/">latest</a></td>
                    <td><a href="http://docs.typo3.org/">latest</a></td>
                </tr>
                <tr>
                    <td><a href="http://docs.typo3.org/">stable</a></td>
                    <td><a href="http://docs.typo3.org/">stable</a></td>
                </tr>
                <tr class="row-odd">
                    <td><a href="http://docs.typo3.org/">1.01</a></td>
                    <td><a href="http://docs.typo3.org/">1.01</a></td>
                </tr>
            </table>
        ';
        }
        return $result;
    }
    function findVersions() {
        // $this->webRootPath
        // $this->$baseFolder,
        // $this->$versionPath,
        // $this->$relativePath,
        // $this->$indexFile
        if (!$this->cont) {
            return;
        }
        $absPathToManual = $this->webRootPath . $this->filePathToUrlPart2 . $this->baseFolder;
        $this->absPathToManual = $absPathToManual;
        $directory  = opendir($absPathToManual);

        while (false !== ($filename = readdir($directory))) {
            if (1
                and $filename !== '.'
                    and $filename !== '..'
                        and is_dir($absPathToManual . '/' . $filename)
                            and $this->isValidVersionFolderName($filename)
            ) {
                $versionFolder = $filename;
                $baseFound = false;
                $baseHtmlFile = '';
                $directFound = false;
                $directHtmlFile = '';
                foreach (array($this->htmlFile, 'Index.html', 'index.html') as $htmlFile) {
                    if (!strlen($htmlFile)) {
                        $htmlFile = 'Index.html';
                    }
                    if (!$baseFound) {
                        $absPathToHtmlFile = implode('/', array($absPathToManual, $versionFolder, $htmlFile));
                        if (file_exists ($absPathToHtmlFile)) {
                            $baseHtmlFile = $htmlFile;
                            $baseFound = true;
                        }
                    }
                    if (!$directFound) {
                        $absPathToHtmlFile = implode('/', array($absPathToManual, $versionFolder, $this->relativePath, $htmlFile));
                        if (file_exists ($absPathToHtmlFile)) {
                            $directHtmlFile = $htmlFile;
                            $directFound = true;
                        }
                    }
                    if ($baseFound and $directFound) {
                        break;
                    }
                }
                if ($baseFound) {
                    $this->resultVersions[$versionFolder] = array(
                        'absPathToHtmlFile' => $absPathToHtmlFile,
                        'query'             => $this->query,
                        'fragment'          => $this->fragment,
                        'urlPart1'          => $this->urlPart1,
                        'urlPart2'          => $this->urlPart2,
                        'baseFolder'        => $this->baseFolder,
                        'versionFolder'     => $versionFolder,
                        'relativePath'      => $this->relativePath,
                        'baseHtmlFile'      => $baseHtmlFile,
                        'directHtmlFile'    => $directHtmlFile,
                    );
                }
            }
        }
    }



    function dump_and_die($arg) {
        echo '<pre>';
        echo htmlspecialchars(print_r($arg, 1));
        echo '<pre>';
        die();
    }

    function processTheUrl($url, $doDebug=null, $webRootPath=null) {
        $this->url = $url;
        if (!is_null($webRootPath)) {
            $this->webRootPath = $webRootPath;
        }
        if (!is_null($doDebug)) {
            $this->dd = $doDebug;
        }
        $this->parseUrl();
        $this->findVersions();
        $this->htmlResult = $this->generateOutput();
        return $this->htmlResult;
    }
}

$vm = new VersionMatcher();
if (1 and 'live') {
    $url = $_GET['url'];
    $htmlResult = $vm->processTheUrl($url);
} else {
    $url = $_GET['url'];
    $url = false;                                   // path: ''
    $url = 'http://docs.typo3.org';                 // path: ''
    $url = 'http://docs.typo3.org/';                // path: '/'
    $url = 'http://docs.typo3.org//';               // path: '//', 3 segments
    $url = 'http://docs.typo3.org/Index.html';      // path: '/Index.html', 2 segments
    $url = 'Index.html';                            // path: 'Index.html', 1 segments
    $url = 'http://docs.typo3.org/typo3cms/TyposcriptReference/#';      // path: 'Index.html', 4 segments
    $url = 'http://docs.typo3.org/typo3cms/TyposcriptReference/4.7/Setup/Page/Index.html?id=3#abc';      // path: 'Index.html', 1 segments
    $url = false;
    $htmlResult = $vm->processTheUrl($url, 1, '/home/marble/htdocs/LinuxData200/t3doc/versionswitcher/webroot');
}

echo $htmlResult;

?>
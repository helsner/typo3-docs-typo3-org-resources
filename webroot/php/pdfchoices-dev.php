<?php

/* mb, 2013-8-18, 2013-08-19 */

if (0) {
    error_reporting(-1);
    error_reporting(E_ALL ^ E_NOTICE);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
}

$doDump = 0;
if (1) {
    $devIp = '87.123.243.232';
    if ($_SERVER['REMOTE_ADDR'] === $devIp) {
        $doDump = 1;
    }
// http://docs.typo3.org/php/pdfchoices.php?url=http://docs.typo3.org/typo3cms/drafts/github/marble/DocumentationStarter/a/b/
}


class PdfMatcher {

    var $dd = 1;     // do debug?
    var $webRootPath = '/home/mbless/public_html';
    var $knownPathBeginnings = array(
        // longest paths first!
        '/flow/drafts/',
        '/flow/',
        '/neos/drafts/',
        '/neos/',
        '/typo3cms/drafts/github/*/',
        '/typo3cms/drafts/',
        '/typo3cms/extensions/',
        '/typo3cms/',
    );
    var $resolveSymlink = array(
        '/TYPO3/drafts/'        => '/typo3cms/drafts/',
        '/TYPO3/extensions/'    => '/typo3cms/extensions/',
        '/TYPO3/'               => '/typo3cms/',
    );
    var $cont               = true;    // continue?
    var $url                = '';      // 'http://docs.typo3.org/typo3cms/TyposcriptReference/en-us/4.7/Setup/Page/Index.html?id=3#abc'
    var $urlPart1           = '';      // 'http://docs.typo3.org'
    var $urlPart2           = '';      // '/typo3cms/'
    var $urlPart3           = '';      // 'TyposcriptReference/4.7/Setup/Page/Index.html?id=3#abc'
    var $filePathToUrlPart2 = '';      // '/typo3cms/'     had been before: '/TYPO3/'

    var $baseFolder         = '';      // 'TyposcriptReference'
    var $localePath         = '';      // 'en-us'
    var $versionPath        = '';      // '4.7'
    var $relativePath       = '';      // 'Setup/Page'
    var $htmlFile           = '';      // 'Index.html'
    var $query              = '';      //  '?id=3'
    var $fragment           = '';      //  '#abc'

    var $parsedUrl;                    // array

    var $resultPdf          = array();     // the result!
    var $htmlResult         = '';
    var $htmlResultIntro    = '';
    var $htmlResultTrailer  = '';

    function __construct() {
        // pass
    }

    function isValidVersionFolderName($filename) {
        $isValid = false;
        if (!$isValid) { // named versions
            if (in_array( $filename, array('latest', 'stable'))) {
                $isValid = true;
            }
        }
        if (!$isValid) { // numbered versions (like 1.2[.3[.4[.nnn]]])
            $pattern = '~\d+\.\d+(\.\d+)*~is';
            $subpattern = array();
            $result = preg_match($pattern, $filename, $subpattern);
            if ($result and ($subpattern[0] === $filename)) {
                $isValid = true;
            }
        }
        return $isValid;
    }

    function isValidLocaleFolderName($segment) {
        $isValid = false;
        if (!$isValid) { // xx-xx)
            $pattern = '~[a-z][a-z]-[a-z][a-z]~';
            $result = preg_match($pattern, $segment);
            if ($result) {
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

        // urlpart1: 'http://docs.typo3.org'
        // urlpart2: '/typo3cms/'
        // urlpart3: 'TyposcriptReference/4.7/Setup/Page/Index.html'

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
        # urlPart3PathSegments: array('TyposcriptReference', 'en-us', '4.7', 'Setup', 'Page', 'Index.html');
        if ($this->cont and (count($this->urlPart3PathSegments) < 2)) {
            $this->cont = false;
        }
        $i = 0;
        if ($this->cont) {
            $this->baseFolder = $this->urlPart3PathSegments[$i]; // 'TyposcriptReference'
            $i += 1;
        }
        if ($this->cont) {
            $segment = $this->urlPart3PathSegments[$i];
            if ($this->isValidLocaleFolderName($segment)) {
                $i += 1;
                $this->localePath = $segment;   // 'en-us'
            }
        }
        if ($this->cont) {
            $segment = $this->urlPart3PathSegments[$i];
            if ($this->isValidVersionFolderName($segment)) {
                $this->versionPath = $segment; // '4.7'
                $i += 1;
            }
        }
        if ($this->cont) {
            $this->relativePath = array_slice($this->urlPart3PathSegments, $i); // array('Setup', 'Page','Index.html')
            $this->htmlFile = array_pop($this->relativePath);                   // 'Index.html'
            $this->relativePath = implode('/', $this->relativePath);            // 'Setup/Page'
        }
        return;
    }

    function startsWith($haystack, $needle) {
        if (0 && $GLOBALS['doDump']) {
            echo $haystack . '<br>';
            echo $needle . '<br><br>';
        }
        if (strpos($needle, '*') === False) {
            $result = (substr($haystack, 0, strlen($needle)) === $needle);
        } else {
            $long = explode('/', $haystack);
            $short = explode('/', $needle);
            $result = count($long) >= count($short);
            if ($result) {
                $i = 0;
                foreach($short as $part) {
                    if (! ($part === '*' or $part === $long[$i] or $part ==='')) {
                        $result = False;
                        break;
                    }
                    $i = $i + 1;
                }
            }
        }
        return $result;
    }

    function generateOutput() {
        $result = '';
        if ($this->cont) {
            $result = '<li><a href="' . $this->pdfUrl . '">PDF</a></li>';
        } else {
            // highly sophisticated debugging technique :-)
            $result = '<li>' . $this->pdfUrl . '</li>';
            $result = '<li>' . $this->curlResult . '</li>';
            $result = '<li>' . $this->pdf_http_status . '</li>';
            $result = '<li>' . $this->curl_errno . '</li>';
            $result = '';
        }
        return $result;
    }
    function findPdf() {
        if (!$this->cont) {
            return;
        }
        $this->pdfUrl = '';
        $this->pdfUrl .= $this->urlPart1;     // 'http://docs.typo3.org'
        $this->pdfUrl .= $this->urlPart2;     // '/typo3cms/'
        $this->pdfUrl .= $this->baseFolder;   // 'TyposcriptReference'
        $this->pdfUrl .= strlen($this->localePath)  ? '/' . $this->localePath  : '';    // 'en-us'
        $this->pdfUrl .= strlen($this->versionPath) ? '/' . $this->versionPath : '';    // '4.7'
        $this->pdfUrl .= '/_pdf/';

        /* Let's check for the existence of a PDF file. We're reading HEAD of the URL and thereby follow redirects.
         * At the shell:
         *      $cmd = 'curl --location --silent --output /dev/null/ --fail --head ' . $pdfUrl
         *      # Exitcode 0: success
         *      # Exitcode 22: No pdf found
         */
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->pdfUrl);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $this->curlResult = curl_exec($ch);
        $this->curl_errno = curl_errno($ch);
        $this->pdf_http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (0) {
            // doesn't work reliably:
            if ($this->pdf_http_status !== 200) {
                $this->cont = False;
            }
        }
        if ($this->curl_errno) {
            // this works! Needs CURLOPT_FAILONERROR.
            // Will the the exitcode == curl_errno to 22 on error.
            $this->cont = False;
        }
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
        $this->findPdf();
        $this->htmlResult = $this->generateOutput();
        return $this->htmlResult;
    }
}

$pm = new PdfMatcher();
if (1 and 'live') {
    $url = $_GET['url'];
    $htmlResult = $pm->processTheUrl($url);
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
    # $url = false;
    $htmlResult = $pm->processTheUrl($url, 1, '/home/marble/htdocs/LinuxData200/t3doc/versionswitcher/webroot');
}

echo $htmlResult;

?>
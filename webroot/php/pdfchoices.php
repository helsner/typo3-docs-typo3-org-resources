<?php

/* mb, 2013-8-18, 2013-08-19 */

/**
 * This class creates a link based on a requested URL.
 * This link either points to a folder with a PDF file or - if the file does not exist -
 * to a documentation page with instructions on how to set up PDF creation.
 */
class PdfMatcher {

    protected $webRootPath = '/home/mbless/public_html';
    /**
     * @var boolean TRUE, if the current page is a glue page, false otherwise
     */
    protected $currentProjectIsGluePage = FALSE;
    /**
     * @var string URL to the PDF file
     */
    protected $pdfUrl = '';
    /**
     * @var string URL to the page with docs on how to set up rendering.
     * Do not change unless the page is moved accordingly!
     */
    protected $pdfDocumentationUrl = 'https://docs.typo3.org/Overview/PdfFiles.html';
    protected $knownPathBeginnings = array(
        // longest paths first!
        '/typo3cms/drafts/github/*/',
        '/typo3cms/drafts/',
        '/typo3cms/extensions/',
        '/typo3cms/',
    );
    protected $cont               = true;                           // continue?
    protected $url                = '';                             // 'https://docs.typo3.org/typo3cms/TyposcriptReference/en-us/4.7/Setup/Page/Index.html?id=3#abc'
    protected $urlPart1           = 'https://docs.typo3.org';       // 'https://docs.typo3.org'
    protected $urlPart2           = '';                             // '/typo3cms/'
    protected $urlPart3           = '';                             // 'TyposcriptReference/4.7/Setup/Page/Index.html?id=3#abc'

    protected $baseFolder         = '';                             // 'TyposcriptReference'
    protected $localePath         = '';                             // 'en-us'
    protected $versionPath        = '';                             // '4.7'
    protected $relativePath       = '';                             // 'Setup/Page'
    protected $htmlFile           = '';                             // 'Index.html'

    /** @var array The URL, which the visitor is on, split up into its segments */
    protected $parsedUrl          = array();

    /** @var boolean Information, whether the PDF file exists or not */
    protected $pdfExists          = TRUE;
    /** @var string The resulting HTML code of the link */
    protected $htmlResult         = '';

    public function __construct() {
        // pass
    }

    protected function isValidVersionFolderName($filename) {
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

    protected function isValidLocaleFolderName($segment) {
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

    /**
     * Before doing anything with the user-provided URL at all, validate that it really is what we expect it should be.
     * It maybe is not a URL at all.
     * It might point to a different server anywhere in the web, so that we would then run our request against that server.
     * Also could it contain malicious segments, e.g. for path traversal.
     *
     * @param $url string The complete string as it was provided by the user
     * @return void
     */
    protected function validateUrl($url) {
        /** @var boolean Whether the provided URL is valid or not */
        $isValidUrl = FALSE;

        /**
         * Check, if what we have is a valid URL
         * Pattern for checking validity of URLs, written by Diego Perini
         * See https://gist.github.com/dperini/729294
         * This expression has best results at https://mathiasbynens.be/demo/url-regex
         */
        $validUrlPattern = '%^(?:(?:https?|ftp)://)(?:\S+(?::\S*)?@|\d{1,3}(?:\.\d{1,3}){3}|(?:(?:[a-z\d\x{00a1}-\x{ffff}]+-?)*[a-z\d\x{00a1}-\x{ffff}]+)(?:\.(?:[a-z\d\x{00a1}-\x{ffff}]+-?)*[a-z\d\x{00a1}-\x{ffff}]+)*(?:\.[a-z\x{00a1}-\x{ffff}]{2,6}))(?::\d+)?(?:[^\s]*)?$%iu';
        if(preg_match($validUrlPattern, $url)) {
            $isValidUrl = TRUE;
        }

        // Make sure that the host portion of the URL points to our own server and not somewhere else.
        // Without such a check it is possible to run the following cURL request on arbitrary servers.
        if(!$this->startsWith($url, $this->urlPart1)) {
            $isValidUrl = FALSE;
        }

        if($isValidUrl === FALSE) {
            die();
        }
    }

    protected function parseUrl() {
        $this->parsedUrl = parse_url($this->url);

        // urlpart1: 'https://docs.typo3.org'
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
            $this->urlPart3 = substr($this->parsedUrl['path'], strlen($this->urlPart2));
            $this->urlPart3PathSegments = explode('/', $this->urlPart3);
        } else {
            $this->cont = false;
        }

        /* Check, if we are on a glue page. On these pages, count($this->urlPart3PathSegments) is 0 or 1. */
        if (count($this->urlPart3PathSegments) < 2) {
            $this->currentProjectIsGluePage = TRUE;
            $this->pdfExists = FALSE;
            $this->cont = FALSE;
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

    /**
     * Returns if the provided string starts with the specified characters/string.
     *
     * @return mixed The extracted part of the string if it does, FALSE if it does not
     */
    protected function startsWith($haystack, $needle) {
        return substr($haystack, 0, strlen($needle)) === $needle;
    }

    /**
     * Creates the HTML output, which should be inserted into the page.
     *
     * @return $result string HTML code of the link
     */
    protected function generateOutput() {
        $result = '';
        // Only show link, if this is a normal documentation project. Do not show it on the glue pages.
        if (!$this->currentProjectIsGluePage) {
            if ($this->pdfExists) {
                $linkUrl = $this->pdfUrl;
            } else {
                $linkUrl = $this->pdfDocumentationUrl;
            }
            $result = '<li><a href="' . $linkUrl . '">PDF</a></li>';
        }
        return $result;
    }

    /**
     * Check, if the PDF file exists.
     *
     * If the PDF file exists, store the URL to that file in $this->pdfUrl,
     * otherwise set $this->pdfExists to FALSE.
     *
     * @return void
     */
    protected function findPdf() {
        if (!$this->cont) {
            return;
        }
        /**
         * Do an _internal_ check for the according file, based on $this->webRootPath.
         * Opposed to an external check, this prevents useless and expensive HTTP requests; after all we are in the same filesystem currently...
         */
        /** @var string Absolute path of the folder, in which the PDF file will be located, if it exists */
        $pdfAbsoluteFolderPath = '';
        $pdfAbsoluteFolderPath .= $this->webRootPath;  // '/home/mbless/public_html'
        $pdfAbsoluteFolderPath .= $this->urlPart2;     // '/typo3cms/'
        $pdfAbsoluteFolderPath .= $this->baseFolder;   // 'TyposcriptReference'
        $pdfAbsoluteFolderPath .= strlen($this->localePath)  ? '/' . $this->localePath  : '';    // 'en-us'
        // Explicitly add "stable" here; if called externally, a redirect in .htaccess would do that, but not for the internal check we are doing here
        $pdfAbsoluteFolderPath .= strlen($this->versionPath) ? '/' . $this->versionPath : '/stable';    // '4.7'
        $pdfAbsoluteFolderPath .= '/_pdf/';

        // Check, if there is a .pdf file in that folder.
        /** @var mixed Array with the absolute path of the PDF file or an empty array if no matching file was found or FALSE in case of error */
        $matchedFiles = glob($pdfAbsoluteFolderPath . '*.pdf');

        // Assuming there maximally is exactly one PDF file in that folder, the relevant path is in $matchedFiles[0] now, if a PDF file exists.
        if(is_array($matchedFiles) && isset($matchedFiles[0])) {
            /** @var string Filename of the PDF file */
            $pdfFileName = basename($matchedFiles[0]);
            // Build the external URL by replacing the internal path with the domain name.
            $this->pdfUrl = '';
            $this->pdfUrl .= str_replace($this->webRootPath, $this->urlPart1, $pdfAbsoluteFolderPath);
            // Link to the file itself instead of only to the folder. Saves one .htaccess redirect on each hit.
            $this->pdfUrl .= $pdfFileName;
        } else {
            $this->pdfExists = FALSE;
        }
    }

    /**
     * Main logic of the class.
     *
     * Generates the link to the PDF file in HTML format.
     * @param $url string The complete URL as it was requested by the website visitor
     * @param $webRootPath mixed Internal server path to the main folder, which contains the different rendered projects
     * @return string The HTML code with the link
     */
    public function processTheUrl($url, $webRootPath=NULL) {
        $this->validateUrl($url);
        $this->url = $url;
        if (!is_null($webRootPath)) {
            $this->webRootPath = $webRootPath;
        }
        $this->parseUrl();
        $this->findPdf();
        $this->htmlResult = $this->generateOutput();
        return $this->htmlResult;
    }
}

$pm = new PdfMatcher();

$url = $_GET['url'];
$htmlResult = $pm->processTheUrl($url);

echo $htmlResult;

?>
<?php

namespace eftec\AutoLoadOne;

//*************************************************************
use Exception;

if (!defined('_AUTOLOAD_USER')) {
    define('_AUTOLOAD_USER', 'autoloadone');
} // user (web interface)
if (!defined('_AUTOLOAD_PASSWORD')) {
    define('_AUTOLOAD_PASSWORD', 'autoloadone');
} // password (web interface)
if (!defined('_AUTOLOAD_ENTER')) {
    define('_AUTOLOAD_ENTER', true);
} // if you want to auto login (skip user and password) then set to true
if (!defined('_AUTOLOAD_SELFRUN')) {
    define('_AUTOLOAD_SELFRUN', true);
} // if you want to self run the class.
if (!defined('_AUTOLOAD_ONLYCLI')) {
    define('_AUTOLOAD_ONLYCLI', false);
} // if you want to use only cli. If true, it disabled the web interface.
if (!defined('_AUTOLOAD_SAVEPARAM')) {
    define('_AUTOLOAD_SAVEPARAM', true);
} // true if you want to save the parameters.
//*************************************************************
// @ini_set('max_execution_time', 300); // Limit of 5 minutes.
/**
 * Class AutoLoadOne.
 *
 * @copyright Jorge Castro C. MIT License https://github.com/EFTEC/AutoLoadOne
 *
 * @version 1.14 2019-06-08
 * @noautoload
 */
class AutoLoadOne
{
    const VERSION = '1.14';
    const JSON_UNESCAPED_SLASHES = 64;
    const JSON_PRETTY_PRINT = 128;
    const JSON_UNESCAPED_UNICODE = 256;

    public $rooturl = '';
    public $fileGen = '';
    public $savefile = 1;
    public $savefileName = 'autoload.php';
    public $stop = 0;
    public $button = 0;
    public $excludeNS = '';
    public $excludePath = '';
    public $externalPath = '';
    public $log = '';
    public $logStat = '';
    public $result = '';
    public $cli = '';
    public $logged = false;
    public $current = '';
    public $t1 = 0;
    public $debugMode = false;
    public $statNumClass = 0;
    public $statNumPHP = 0;
    public $statConflict = 0;
    public $statError = 0;
    public $statNameSpaces = [];
    public $statByteUsed = 1024;
    public $fileConfig = 'autoloadone.json';

    public $extension = '.php';

    private $excludeNSArr;
    private $excludePathArr;
    private $baseGen;

    /**
     * AutoLoadOne constructor.
     */
    public function __construct()
    {
        $this->fileGen = getcwd(); // dirname($_SERVER['SCRIPT_FILENAME']);
        $this->rooturl = getcwd(); // dirname($_SERVER['SCRIPT_FILENAME']);
        $this->t1 = microtime(true);
        $this->fileConfig = basename($_SERVER['SCRIPT_FILENAME']); // the config name shares the same name than the php but with extension .json
        $this->fileConfig = getcwd().'/'.str_replace($this->extension, '.json', $this->fileConfig);
        //var_dump($this->fileConfig);
    }

    private function getAllParametersCli()
    {
        $this->rooturl = $this->fixSeparator($this->getParameterCli('folder'));
        $this->fileGen = $this->fixSeparator($this->getParameterCli('filegen'));
        $this->fileGen = ($this->fileGen == '.') ? $this->rooturl : $this->fileGen;
        $this->savefile = $this->getParameterCli('save');
        $this->savefileName = $this->getParameterCli('savefilename', 'autoload.php');
        $this->stop = $this->getParameterCli('stop');
        $this->current = $this->getParameterCli('current', true);
        $this->excludeNS = $this->getParameterCli('excludens');
        $this->excludePath = $this->getParameterCli('excludepath');
        $this->externalPath = $this->getParameterCli('externalpath');
        $this->debugMode = $this->getParameterCli('debug');
    }

    /**
     * @param $key
     * @param string $default is the defalut value is the parameter is set without value.
     *
     * @return string
     */
    private function getParameterCli($key, $default = '')
    {
        global $argv;
        $p = array_search('-'.$key, $argv);
        if ($p === false) {
            return '';
        }
        if ($default !== '') {
            return $default;
        }
        if (count($argv) >= $p + 1) {
            return $this->removeTrailSlash($argv[$p + 1]);
        }

        return '';
    }

    private function removeTrailSlash($txt)
    {
        return rtrim($txt, '/\\');
    }

    private function initSapi()
    {
        global $argv;
        $v = $this::VERSION.' (c) Jorge Castro';
        echo <<<eot


   ___         __         __                 __ ____           
  / _ | __ __ / /_ ___   / /  ___  ___ _ ___/ // __ \ ___  ___ 
 / __ |/ // // __// _ \ / /__/ _ \/ _ `// _  // /_/ // _ \/ -_)
/_/ |_|\_,_/ \__/ \___//____/\___/\_,_/ \_,_/ \____//_//_/\__/  $v

eot;
        echo "\n";
        if (count($argv) < 2) {
            // help
            echo "-current (scan and generates files from the current folder)\n";
            echo "-folder (folder to scan)\n";
            echo '-filegen (folder where autoload'.$this->extension." will be generate)\n";
            echo "-save (save the file to generate)\n";
            echo "-savefilename (the filename to be generated. By default its autoload.php)\n";
            echo "-excludens (namespace excluded)\n";
            echo "-excludepath (path excluded)\n";
            echo "-externalpath (external paths)\n";
            echo "------------------------------------------------------------------\n";
        } else {
            $this->getAllParametersCli();
            $this->fileGen = ($this->fileGen == '') ? getcwd() : $this->fileGen;
            $this->button = 1;
        }
        if ($this->current) {
            $this->rooturl = getcwd();
            $this->fileGen = getcwd();
            $this->savefile = 1;
            $this->savefileName = 'autoload.php';
            $this->stop = 0;
            $this->button = 1;
            $this->excludeNS = '';
            $this->externalPath = '';
            $this->excludePath = '';
        }

        echo '-folder '.$this->rooturl." (folder to scan)\n";
        echo '-filegen '.$this->fileGen.' (folder where autoload'.$this->extension." will be generate)\n";
        echo '-save '.($this->savefile ? 'yes' : 'no')." (save filegen)\n";
        echo '-savefilename '.$this->savefileName." (save filegen name)\n";
        echo '-excludens '.$this->excludeNS." (namespace excluded)\n";
        echo '-excludepath '.$this->excludePath." (path excluded)\n";
        echo '-externalpath '.$this->externalPath." (path external)\n";
        echo "------------------------------------------------------------------\n";
    }

    public static function encode($data, $options = 448)
    {
        if (PHP_VERSION_ID >= 50400) {
            $json = json_encode($data, $options);
            if (false === $json) {
                self::throwEncodeError(json_last_error());
            }

            if (PHP_VERSION_ID < 50428 || (PHP_VERSION_ID >= 50500 && PHP_VERSION_ID < 50512) || (defined('JSON_C_VERSION') && version_compare(phpversion('json'), '1.3.6', '<'))) {
                $json = preg_replace('/\[\s+\]/', '[]', $json);
                $json = preg_replace('/\{\s+\}/', '{}', $json);
            }

            return $json;
        }

        $json = json_encode($data);
        if (false === $json) {
            self::throwEncodeError(json_last_error());
        }

        $prettyPrint = (bool) ($options & self::JSON_PRETTY_PRINT);
        $unescapeUnicode = (bool) ($options & self::JSON_UNESCAPED_UNICODE);
        $unescapeSlashes = (bool) ($options & self::JSON_UNESCAPED_SLASHES);

        if (!$prettyPrint && !$unescapeUnicode && !$unescapeSlashes) {
            return $json;
        }

        return self::format($json, $unescapeUnicode, $unescapeSlashes);
    }

    private static function throwEncodeError($code)
    {
        switch ($code) {
            case JSON_ERROR_DEPTH:
                $msg = 'Maximum stack depth exceeded';
                break;
            case JSON_ERROR_STATE_MISMATCH:
                $msg = 'Underflow or the modes mismatch';
                break;
            case JSON_ERROR_CTRL_CHAR:
                $msg = 'Unexpected control character found';
                break;
            case JSON_ERROR_UTF8:
                $msg = 'Malformed UTF-8 characters, possibly incorrectly encoded';
                break;
            default:
                $msg = 'Unknown error';
        }

        throw new \RuntimeException('JSON encoding failed: '.$msg);
    }

    public static function format($json, $unescapeUnicode, $unescapeSlashes)
    {
        $result = '';
        $pos = 0;
        $strLen = strlen($json);
        $indentStr = '    ';
        $newLine = "\n";
        $outOfQuotes = true;
        $buffer = '';
        $noescape = true;

        for ($i = 0; $i < $strLen; $i++) {
            $char = substr($json, $i, 1);

            if ('"' === $char && $noescape) {
                $outOfQuotes = !$outOfQuotes;
            }

            if (!$outOfQuotes) {
                $buffer .= $char;
                $noescape = '\\' === $char ? !$noescape : true;
                continue;
            } elseif ('' !== $buffer) {
                if ($unescapeSlashes) {
                    $buffer = str_replace('\\/', '/', $buffer);
                }

                if ($unescapeUnicode && function_exists('mb_convert_encoding')) {
                    $buffer = preg_replace_callback('/(\\\\+)u([0-9a-f]{4})/i', function ($match) {
                        $l = strlen($match[1]);

                        if ($l % 2) {
                            $code = hexdec($match[2]);

                            if (0xD800 <= $code && 0xDFFF >= $code) {
                                return $match[0];
                            }

                            return str_repeat('\\', $l - 1).mb_convert_encoding(
                                pack('H*', $match[2]),
                                'UTF-8',
                                'UCS-2BE'
                            );
                        }

                        return $match[0];
                    }, $buffer);
                }

                $result .= $buffer.$char;
                $buffer = '';
                continue;
            }

            if (':' === $char) {
                $char .= ' ';
            } elseif ('}' === $char || ']' === $char) {
                $pos--;
                $prevChar = substr($json, $i - 1, 1);

                if ('{' !== $prevChar && '[' !== $prevChar) {
                    $result .= $newLine;
                    for ($j = 0; $j < $pos; $j++) {
                        $result .= $indentStr;
                    }
                } else {
                    $result = rtrim($result);
                }
            }

            $result .= $char;

            if (',' === $char || '{' === $char || '[' === $char) {
                $result .= $newLine;

                if ('{' === $char || '[' === $char) {
                    $pos++;
                }

                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indentStr;
                }
            }
        }

        return $result;
    }

    /**
     * @return bool|int
     */
    private function saveParam()
    {
        if (!_AUTOLOAD_SAVEPARAM) {
            return false;
        }
        $param = [];
        $param['rooturl'] = $this->rooturl;
        $param['fileGen'] = $this->fileGen;
        $param['savefile'] = $this->savefile;
        $param['savefileName'] = $this->savefileName;
        $param['excludeNS'] = $this->excludeNS;
        $param['excludePath'] = $this->excludePath;
        $param['externalPath'] = $this->externalPath;

        $remote = [];
        $remote['rooturl'] = '';
        $remote['destination'] = $this->fileGen;
        $remote['name'] = '';
        $remoteint = '1';

        $git = [];
        $git['destination'] = $this->fileGen;
        $git['name'] = 'biurad/hello-world 1.* --no-dev';
        $gitint = '1';

        $generatedvia = 'AutoloadOne';
        $date = date('Y/m/d h:i');

        return @file_put_contents(
            $this->fileConfig,
            $this->encode(
                [
                    'application' => $generatedvia,
                    'generated'   => $date,
                    'local'       => $param,
                    'remote'      => [$remoteint => $remote],
                    'git'         => [$gitint => $git],
                ]
            )
        );
    }

    /**
     * @return bool
     */
    private function loadParam()
    {
        if (!_AUTOLOAD_SAVEPARAM) {
            return false;
        }
        $txt = @file_get_contents($this->fileConfig->local);
        if ($txt === false) {
            return false;
        }
        $param = json_decode($txt, true);
        $this->fileGen = @$param['fileGen'];
        $this->fileGen = ($this->fileGen == '.') ? $this->rooturl : $this->fileGen;
        $this->savefile = @$param['savefile'];
        $this->savefileName = @$param['savefileName'];
        $this->excludeNS = @$param['excludeNS'];
        $this->excludePath = @$param['excludePath'];
        $this->externalPath = @$param['externalPath'];

        return true;
    }

    private function initWeb()
    {
        @ob_start();
        // Not in cli-mode
        @session_start();
        $this->logged = @$_SESSION['log'];
        if (!$this->logged) {
            $user = @$_POST['user'];
            $password = @$_POST['password'];
            if (($user == _AUTOLOAD_USER && $password == _AUTOLOAD_PASSWORD) || _AUTOLOAD_ENTER) {
                $_SESSION['log'] = '1';
                $this->logged = 1;
            } else {
                sleep(1); // sleep a second
                $_SESSION['log'] = '0';
                @session_destroy();
            }
            @session_write_close();
        } else {
            $this->button = @$_POST['button'];
            if (!$this->button) {
                $loadOk = $this->loadParam();
                if ($loadOk === false) {
                    $this->addLog('Unable to load configuration file <b>'.$this->fileConfig.'</b>. It is not obligatory', 'warning');
                }
            } else {
                $this->debugMode = isset($_GET['debug']) ? true : false;
                $this->rooturl = $this->removeTrailSlash(@$_POST['rooturl'] ? $_POST['rooturl'] : $this->rooturl);
                $this->fileGen = $this->removeTrailSlash(@$_POST['fileGen'] ? $_POST['fileGen'] : $this->fileGen);
                $this->fileGen = ($this->fileGen == '.') ? $this->rooturl : $this->fileGen;
                $this->excludeNS = $this->cleanInputFolder(
                    $this->removeTrailSlash(
                        @$_POST['excludeNS'] ? $_POST['excludeNS'] : $this->excludeNS
                    )
                );
                $this->excludePath = $this->cleanInputFolder(
                    $this->removeTrailSlash(
                        @$_POST['excludePath'] ? $_POST['excludePath'] : $this->excludePath
                    )
                );
                $this->externalPath = $this->cleanInputFolder(
                    $this->removeTrailSlash(
                        @$_POST['externalPath'] ? $_POST['externalPath'] : $this->externalPath
                    )
                );
                $this->savefile = (@$_POST['savefile']) ? @$_POST['savefile'] : $this->savefile;
                $this->savefileName = (@$_POST['savefileName']) ? @$_POST['savefileName'] : $this->savefileName;
                $this->stop = @$_POST['stop'];
                $ok = $this->saveParam();
                if ($ok === false) {
                    $this->addLog('Unable to save configuration file <b>'.$this->fileConfig.'</b>. It is not obligatory.', 'warning');
                }
            }
            if ($this->button == 'logout') {
                @session_destroy();
                $this->logged = 0;
                @session_write_close();
            }
        }
    }

    /**
     * @param $value
     *
     * @return string
     */
    private function cleanInputFolder($value)
    {
        $v = str_replace("\r\n", "\n", $value); // remove windows line carriage
        $v = str_replace(",\n", "\n", $v); // remove previous ,\n if any and converted into \n. It avoids duplicate ,,\n
        $v = str_replace("\n", ",\n", $v); // we add ,\n again.
        $v = str_replace('\\,', ',', $v); // we remove trailing \
        $v = str_replace('/,', ',', $v); // we remove trailing /
        return $v;
    }

    public function init()
    {
        $this->log = '';
        $this->logStat = '';

        if (php_sapi_name() == 'cli') {
            $this->initSapi();
        } else {
            if (_AUTOLOAD_ONLYCLI) {
                echo 'You should run it as a command line parameter.';
                die(1);
            }
            $this->initWeb();
        }
    }

    public function genautoload($file, $namespaces, $namespacesAlt, $pathAbsolute, $autoruns)
    {
        $template = <<<'EOD'
<?php
/**
 * This class is used for autocomplete.
 * Class _AUTOLOAD_
 * @noautoload it avoids to index this class
 * @generated by AutoLoadOne {{version}} generated {{date}}
 * @copyright Copyright Jorge Castro C - MIT License. https://github.com/EFTEC/AutoLoadOne
 */
${{tempname}}__debug = true;

/* @var string[] Where $_arrautoloadCustom['namespace\Class']='folder\file.php' */
${{tempname}}__arrautoloadCustom = [
{{custom}}
];

/* @var string[] Where $_arrautoload['namespace']='folder' */
${{tempname}}__arrautoload = [
{{include}}
];

/* @var boolean[] Where $_arrautoload['namespace' or 'namespace\Class']=true if it's absolute (it uses the full path) */
${{tempname}}__arrautoloadAbsolute = [
{{includeabsolute}} 
];

/**
 * @param $class_name
 * @throws Exception
 */
function {{tempname}}__auto($class_name)
{
    // its called only if the class is not loaded.
    $ns = dirname($class_name); // without trailing
    $ns = ($ns == '.') ? '' : $ns;
    $cls = basename($class_name);
    // special cases
    if (isset($GLOBALS['{{tempname}}__arrautoloadCustom'][$class_name])) {
        {{tempname}}__loadIfExists($GLOBALS['{{tempname}}__arrautoloadCustom'][$class_name], $class_name);
        return;
    }
    // normal (folder) cases
    if (isset($GLOBALS['{{tempname}}__arrautoload'][$ns])) {
        {{tempname}}__loadIfExists($GLOBALS['{{tempname}}__arrautoload'][$ns] . '/' . $cls . '{{extension}}', $ns);
        return;
    }
}

/**
 * We load the file.    
 * @param string $filename
 * @param string $key key of the class it could be the full class name or only the namespace
 * @throws Exception
 */
function {{tempname}}__loadIfExists($filename, $key)
{
    if (isset($GLOBALS['{{tempname}}__arrautoloadAbsolute'][$key])) {
        $fullFile = $filename; // its an absolute path
        if (strpos($fullFile, '../') === 0) { // Or maybe, not, it's a remote-relative path.
            $oldDir = getcwd();  // we copy the current url
            chdir(__DIR__);
        }
    } else {
        $fullFile = __DIR__ . "/" . $filename; // its relative to this path
    }
    if ((@include $fullFile) === false) {
        if ($GLOBALS['{{tempname}}__debug']) {
            throw  new Exception("AutoLoadOne Error: Loading file [" . __DIR__ . "/" . $filename . "] for class [" . basename($filename) . "]");
        } else {
            throw  new Exception("AutoLoadOne Error: No file found.");
        }
    } else {
        if (isset($oldDir)) {
            chdir($oldDir);
        }
    }
}

spl_autoload_register(function ($class_name) {
    {{tempname}}__auto($class_name);
});
// autorun
{{autorun}}

EOD;
        $custom = '';
        foreach ($namespacesAlt as $k => $v) {
            $custom .= "\t'$k' => '$v',\n";
        }
        if ($custom != '') {
            $custom = substr($custom, 0, -2);
        }
        $include = '';

        foreach ($namespaces as $k => $v) {
            $include .= "\t'$k' => '$v',\n";
        }
        $include = rtrim($include, ",\n");
        $includeAbsolute = '';
        foreach ($pathAbsolute as $k => $v) {
            if ($v) {
                $includeAbsolute .= "\t'$k' => true,\n";
            }
        }
        $includeAbsolute = rtrim($includeAbsolute, ",\n");
        $autorun = ''; //
        foreach ($autoruns as $k => $v) {
            $autorun .= "@include __DIR__.'$v';\n";
        }
        //

        $template = str_replace('{{custom}}', $custom, $template);
        $template = str_replace('{{include}}', $include, $template);
        $template = str_replace('{{includeabsolute}}', $includeAbsolute, $template);
        $template = str_replace('{{tempname}}', uniqid('s'), $template);

        $template = str_replace('{{autorun}}', $autorun, $template);
        $template = str_replace('{{version}}', $this::VERSION, $template);
        $template = str_replace('{{extension}}', $this->extension, $template);
        $template = str_replace('{{date}}', date('Y/m/d h:i:s'), $template);

        // 1024 is the memory used by code, *1.3 is an overhead, usually it's mess.
        $this->statByteUsed = (strlen($include) + strlen($includeAbsolute) + strlen($custom)) * 1.3 + 1024;
        if ($this->savefile) {
            $ok = @file_put_contents($file, $template);
            if ($ok) {
                $this->addLog("File <b>$file</b> generated", 'info');
            } else {
                $this->addLog("Unable to write file <b>$file</b>. Check the folder and permissions. You could write it manually.", 'error');
                $this->statError++;
            }
            $this->addLog('&nbsp;');
        }

        return $template;
    }

    public function is_absolute_path($path)
    {
        if ($path === null || $path === '') {
            return false;
        }

        return $path[0] === DIRECTORY_SEPARATOR || preg_match('~\A[A-Z]:(?![^/\\\\])~i', $path) > 0;
    }

    public function listFolderFiles($dir)
    {
        $arr = [];
        $this->listFolderFilesAlt($dir, $arr);

        return $arr;
    }

    private function fixRelative($path)
    {
        if (strpos($path, '..') !== false) {
            return getcwd().'/'.$path;
        } else {
            return $path;
        }
    }

    public function listFolderFilesAlt($dir, &$list)
    {
        if ($dir === '') {
            return [];
        }
        $ffs = @scandir($this->fixRelative($dir));
        if ($ffs === false) {
            $this->addLog("\nError: Unable to reader folder [$dir]. Check the name of the folder and the permissions", 'error');
            $this->statError++;

            return [];
        }
        foreach ($ffs as $ff) {
            if ($ff != '.' && $ff != '..') {
                if (strlen($ff) >= 5) {
                    if (substr($ff, -4) == $this->extension) {
                        $list[] = $dir.'/'.$ff;
                    }
                }
                if (is_dir($dir.'/'.$ff)) {
                    $this->listFolderFilesAlt($dir.'/'.$ff, $list);
                }
            }
        }

        return $list;
    }

    /**
     * @param $filename
     * @param string $runMe
     *
     * @return array
     */
    public function parsePHPFile($filename, &$runMe)
    {
        $runMe = '';
        $r = [];

        try {
            if (is_file($this->fixRelative($filename))) {
                $content = file_get_contents($this->fixRelative($filename));
            } else {
                return [];
            }
            if ($this->debugMode) {
                echo $filename.' trying token...<br>';
            }
            $tokens = token_get_all($content);
        } catch (Exception $ex) {
            echo "Error in $filename\n";
            die(1);
        }
        foreach ($tokens as $p => $token) {
            if (is_array($token) && ($token[0] == T_COMMENT || $token[0] == T_DOC_COMMENT)) {
                if (strpos($token[1], '@noautoload') !== false) {
                    $runMe = '@noautoload';

                    return [];
                }
                if (strpos($token[1], '@autorun') !== false) {
                    if (strpos($token[1], '@autorunclass') !== false) {
                        $runMe = '@autorunclass';
                    } else {
                        if (strpos($token[1], '@autorun first') !== false) {
                            $runMe = '@autorun first';
                        } else {
                            $runMe = '@autorun';
                        }
                    }
                }
            }
        }
        $nameSpace = '';
        $className = '';
        /*echo "<pre>";
        var_dump($tokens);
        echo "</pre>";
        */
        foreach ($tokens as $p => $token) {
            if (is_array($token) && $token[0] == T_NAMESPACE) {
                // We found a namespace
                $ns = '';
                for ($i = $p + 2; $i < $p + 30; $i++) {
                    if (is_array($tokens[$i])) {
                        $ns .= $tokens[$i][1];
                    } else {
                        // tokens[$p]==';' ??
                        break;
                    }
                }
                $nameSpace = $ns;
            }

            $isClass = false;
            // A class is defined by a T_CLASS + an space + name of the class.
            if (
                is_array($token) && ($token[0] == T_CLASS
                    || $token[0] == T_INTERFACE
                    || $token[0] == T_TRAIT)
                && is_array($tokens[$p + 1]) && $tokens[$p + 1][0] == T_WHITESPACE
            ) {
                $isClass = true;
                if (is_array($tokens[$p - 1]) && $tokens[$p - 1][0] == T_PAAMAYIM_NEKUDOTAYIM && $tokens[$p - 1][1] == '::') {
                    // /namespace/Nameclass:class <-- we skip this case.
                    $isClass = false;
                }
            }

            if ($isClass) {

                // encontramos una clase
                $min = min($p + 30, count($tokens) - 1);
                for ($i = $p + 2; $i < $min; $i++) {
                    if (is_array($tokens[$i]) && $tokens[$i][0] == T_STRING) {
                        $className = $tokens[$i][1];
                        break;
                    }
                }
                $r[] = ['namespace' => trim($nameSpace), 'classname' => trim($className)];
            }
        } // foreach
        return $r;
    }

    public function genPath($path)
    {
        $path = $this->fixSeparator($path);
        if (strpos($path, $this->baseGen) == 0) {
            $min1 = strripos($path, '/');
            $min2 = strripos($this->baseGen.'/', '/');
            //$min=min(strlen($path),strlen($this->baseGen));
            $min = min($min1, $min2);
            $baseCommon = $min;

            for ($i = 0; $i < $min; $i++) {
                if (substr($path, 0, $i) != substr($this->baseGen, 0, $i)) {
                    $baseCommon = $i - 2;
                    break;
                }
            }
            /*if (substr($path,1,2)==':/') {
                // windows style c:/somefolder
                $baseCommon=0;
            }
            */
            // moving down the relative path (/../../)
            $c = substr_count(substr($this->baseGen, $baseCommon), '/');
            $r = str_repeat('/..', $c);
            // moving up the relative path
            $r2 = substr($path, $baseCommon);

            return $r.$r2;
        } else {
            $r = substr($path, strlen($this->baseGen));
        }

        return $r;
    }

    public function fixSeparator($fullUrl)
    {
        return str_replace('\\', '/', $fullUrl); // replace windows path for linux path.
    }

    /**
     * returns dir name linux way.
     *
     * @param $url
     * @param bool $ifFullUrl
     *
     * @return mixed|string
     */
    public function dirNameLinux($url, $ifFullUrl = true)
    {
        $url = trim($url);
        $dir = ($ifFullUrl) ? dirname($url) : $url;
        $dir = $this->fixSeparator($dir);
        $dir = rtrim($dir, '/'); // remove trailing /
        return $dir;
    }

    public function addLog($txt, $type = '')
    {
        if (php_sapi_name() == 'cli') {
            echo "\t".$txt."\n";
        } else {
            switch ($type) {
                case 'error':
                    $this->log .= "<div class='bg-danger'>$txt</div>";
                    break;
                case 'warning':
                    $this->log .= "<div class='bg-warning'>$txt</div>";
                    break;
                case 'info':
                    $this->log .= "<div class='bg-primary'>$txt</div>";
                    break;
                case 'success':
                    $this->log .= "<div class='bg-success'>$txt</div>";
                    break;
                case 'stat':
                    $this->logStat .= "<div >$txt</div>";
                    break;
                case 'statinfo':
                    $this->logStat .= "<div class='bg-primary'>$txt</div>";
                    break;
                case 'staterror':
                    $this->logStat .= "<div class='bg-danger'>$txt</div>";
                    break;
                default:
                    $this->log .= "<div>$txt</div>";
                    break;
            }
        }
    }

    /**
     * returns the name of the filename if the original filename constains .php then it is not added, otherwise
     * it is added.
     *
     * @return string
     */
    public function getFileName()
    {
        if (strpos($this->savefileName, '.php') === false) {
            return $this->savefileName.$this->extension;
        } else {
            return $this->savefileName;
        }
    }

    public function process()
    {
        $this->rooturl = $this->fixSeparator($this->rooturl);
        $this->fileGen = $this->fixSeparator($this->fileGen);
        if ($this->rooturl) {
            $this->baseGen = $this->dirNameLinux($this->fileGen.'/'.$this->getFileName());
            $files = $this->listFolderFiles($this->rooturl);
            $filesAbsolute = array_fill(0, count($files), false);

            $extPathArr = explode(',', $this->externalPath);
            foreach ($extPathArr as $ep) {
                $ep = $this->dirNameLinux($ep, false);
                $files2 = $this->listFolderFiles($ep);
                foreach ($files2 as $newFile) {
                    $files[] = $newFile;
                    $filesAbsolute[] = true;
                }
            }
            $ns = [];
            $nsAlt = [];
            $pathAbsolute = [];
            $autoruns = [];
            $autorunsFirst = [];
            $this->excludeNSArr = str_replace(["\n", "\r", ' '], '', $this->excludeNS);
            $this->excludeNSArr = explode(',', $this->excludeNSArr);

            $this->excludePathArr = $this->fixSeparator($this->excludePath);
            $this->excludePathArr = str_replace(["\n", "\r"], '', $this->excludePath);
            $this->excludePathArr = explode(',', $this->excludePathArr);
            foreach ($this->excludePathArr as &$item) {
                $item = trim($item);
            }

            $this->result = '';
            if ($this->button) {
                foreach ($files as $key => $f) {
                    $f = $this->fixSeparator($f);
                    $runMe = '';
                    $pArr = $this->parsePHPFile($f, $runMe);

                    $dirOriginal = $this->dirNameLinux($f);
                    if (!$filesAbsolute[$key]) {
                        $dir = $this->genPath($dirOriginal); //folder/subfolder/f1
                        $full = $this->genPath($f); ///folder/subfolder/f1/F1.php
                    } else {
                        $dir = dirname($f); //D:/Dropbox/www/currentproject/AutoLoadOne/examples/folder
                        $full = $f; //D:/Dropbox/www/currentproject/AutoLoadOne/examples/folder/NaturalClass.php
                    }
                    $urlFull = $this->dirNameLinux($full); ///folder/subfolder/f1
                    $basefile = basename($f); //F1.php

                    // echo "$dir $full $urlFull $basefile<br>";

                    if ($runMe != '') {
                        switch ($runMe) {
                            case '@autorun first':
                                $autorunsFirst[] = $full;
                                $this->addLog("Adding autorun (priority): <b>$full</b>");
                                break;
                            case '@autorunclass':
                                $autoruns[] = $full;
                                $this->addLog("Adding autorun (class, use future): <b>$full</b>");
                                break;
                            case '@autorun':
                                $autoruns[] = $full;
                                $this->addLog("Adding autorun: <b>$full</b>");
                                break;
                        }
                    }
                    foreach ($pArr as $p) {
                        $nsp = $p['namespace'];
                        $cs = $p['classname'];
                        $this->statNameSpaces[$nsp] = 1;
                        $this->statNumPHP++;
                        if ($cs != '') {
                            $this->statNumClass++;
                        }

                        $altUrl = ($nsp != '') ? $nsp.'\\'.$cs : $cs; // namespace

                        if ($nsp != '' || $cs != '') {
                            if ((!isset($ns[$nsp]) || $ns[$nsp] == $dir) && $basefile == $cs.$this->extension) {
                                // namespace doesn't exist and the class is equals to the name
                                // adding as a folder
                                $exclude = false;
                                if (in_array($nsp, $this->excludeNSArr) && $nsp != '') {
                                    //if ($this->inExclusion($nsp, $this->excludeNSArr) && $nsp!="") {
                                    $this->addLog("\tIgnoring namespace (path specified in <b>Excluded NameSpace</b>): <b>$altUrl -> $full</b>", 'warning');
                                    $exclude = true;
                                }
                                if ($this->inExclusion($dir, $this->excludePathArr)) {
                                    $this->addLog("\tIgnoring relative path (path specified in <b>Excluded Path</b>): <b>$altUrl -> $dir</b>", 'warning');
                                    $exclude = true;
                                }
                                if ($this->inExclusion($dirOriginal, $this->excludePathArr)) {
                                    $this->addLog("\tIgnoring full path (path specified in <b>Excluded Path</b>): <b>$altUrl -> $dirOriginal</b>", 'warning');
                                    $exclude = true;
                                }

                                if (!$exclude) {
                                    if ($nsp == '') {
                                        $this->addLog("Adding Full map (empty namespace): <b>$altUrl -> $full</b> to class <i>$cs</i>");
                                        $nsAlt[$altUrl] = $full;
                                        $pathAbsolute[$altUrl] = $filesAbsolute[$key];
                                    } else {
                                        if (isset($ns[$nsp])) {
                                            $this->addLog("\tReusing the folder: <b>$nsp -> $dir</b> to class <i>$cs</i>", 'success');
                                        } else {
                                            $ns[$nsp] = $dir;
                                            $pathAbsolute[$nsp] = $filesAbsolute[$key];
                                            $this->addLog("Adding Folder as namespace: <b>$nsp -> $dir</b> to class <i>$cs</i>");
                                        }
                                    }
                                }
                            } else {
                                // custom namespace 1-1
                                // a) if filename has different name with the class
                                // b) if namespace is already defined for a different folder.
                                // c) multiple namespaces
                                if (isset($nsAlt[$altUrl])) {
                                    $this->addLog("\tError Conflict:Class with name <b>$altUrl -> $dir</b> is already defined. File $f", 'error');
                                    $this->statConflict++;
                                    if ($this->stop) {
                                        die(1);
                                    }
                                } else {
                                    if ((!in_array($altUrl, $this->excludeNSArr) || $nsp == '')
                                        && !$this->inExclusion($urlFull, $this->excludePathArr)
                                    ) {
                                        $this->addLog("Adding Full: <b>$altUrl -> $full</b> to class <i>$cs</i>");
                                        $nsAlt[$altUrl] = $full;
                                        $pathAbsolute[$altUrl] = $filesAbsolute[$key];
                                    }
                                }
                            }
                        }
                    }
                    if (count($pArr) == 0) {
                        $this->statNumPHP++;
                        if ($runMe == '@noautoload') {
                            $this->addLog("\tIgnoring <b>$full</b> Reason: <b>@noautoload</b> found", 'warning');
                        } else {
                            $this->addLog("\tIgnoring <b>$full</b> Reason: No class found on file.", 'warning');
                        }
                    }
                }
                foreach ($autorunsFirst as $auto) {
                    $this->addLog("Adding file <b>$auto</b> Reason: <b>@autoload first</b> found");
                }
                foreach ($autoruns as $auto) {
                    $this->addLog("Adding file <b>$auto</b> Reason: <b>@autoload</b> found");
                }
                $autoruns = array_merge($autorunsFirst, $autoruns);
                $this->result = $this->genautoload($this->fileGen.'/'.$this->getFileName(), $ns, $nsAlt, $pathAbsolute, $autoruns);
            }
            if ($this->statNumPHP === 0) {
                $p = 100;
            } else {
                $p = round((count($ns) + count($nsAlt)) * 100 / $this->statNumPHP, 2);
            }
            if ($this->statNumClass === 0) {
                $pc = 100;
            } else {
                $pc = round((count($ns) + count($nsAlt)) * 100 / $this->statNumClass, 2);
            }
            $this->addLog('Number of Classes: <b>'.$this->statNumClass.'</b>', 'stat');
            $this->addLog('Number of Namespaces: <b>'.count($this->statNameSpaces).'</b>', 'stat');
            $this->addLog('<b>Number of Maps:</b> <b>'.(count($ns) + count($nsAlt)).'</b> (you want to reduce it)', 'stat');
            $this->addLog('Number of PHP Files: <b>'.$this->statNumPHP.'</b>', 'stat');
            $this->addLog('Number of PHP Autorun: <b>'.count($autoruns).'</b>', 'stat');
            $this->addLog('Number of conflicts: <b>'.$this->statConflict.'</b>', 'stat');
            if ($this->statError) {
                $this->addLog('Number of errors: <b>'.$this->statError.'</b>', 'staterror');
            }

            $this->addLog('Ratio map per file: <b>'.$p.'%  '.$this->evaluation($p).'</b> (less is better. 100% means one map/one file)', 'statinfo');
            $this->addLog('Ratio map per classes: <b>'.$pc.'% '.$this->evaluation($pc).'</b> (less is better. 100% means one map/one class)', 'statinfo');
            $this->addLog('Map size: <b>'.round($this->statByteUsed / 1024, 1)." kbytes</b> (less is better, it's an estimate of the memory used by the map)", 'statinfo');
        } else {
            $this->addLog('No folder specified');
        }
    }

    private function evaluation($percentage)
    {
        switch (1 == 1) {
            case $percentage === 0:
                return 'How?';
                break;
            case $percentage < 10:
                return 'Awesome';
                break;
            case $percentage < 25:
                return 'Good';
                break;
            case $percentage < 40:
                return 'Acceptable';
                break;
            case $percentage < 80:
                return 'Bad.';
                break;
        }

        return 'BAAAAAD!';
    }

    /**
     * @param string   $path
     * @param string[] $exclusions
     *
     * @return bool
     */
    private function inExclusion($path, $exclusions)
    {
        foreach ($exclusions as $ex) {
            if ($ex != '') {
                if (substr($ex, -1, 1) == '*') {
                    $bool = $this->startwith($path, substr($ex, 0, -1));
                    if ($bool) {
                        return true;
                    }
                }
                if (substr($ex, 0, 1) == '*') {
                    $bool = $this->endswith($path, $ex);
                    if ($bool) {
                        return true;
                    }
                }
                if (strpos($ex, '*') === false) {
                    if ($path == $ex) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function startwith($string, $test)
    {
        return strpos($string, $test) === 0;
    }

    public function endswith($string, $test)
    {
        $strlen = strlen($string);
        $testlen = strlen($test);
        if ($testlen > $strlen) {
            return false;
        }

        return substr_compare($string, $test, $strlen - $testlen, $testlen) === 0;
    }

    public function render()
    {
        if ($this->debugMode) {
            ob_clean();
        }

        if (php_sapi_name() == 'cli') {
            $t2 = microtime(true);
            echo "\n".(round(($t2 - $this->t1) * 1000) / 1000)." sec. Finished\n";
        } else {
            if (!$this->logged) {
                $web = <<<'LOGS'
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>AutoLoadOneGenerator Login Screen</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="http://raw.githubusercontent.com/EFTEC/AutoLoadOne/master/doc/favicon.ico">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css" integrity="sha384-HSMxcRTRxnN+Bdg0JdbxYKrThecOKuH5zCYotlSAcp1+c8xmyTe9GYg1l9a69psu" crossorigin="anonymous">


  </head>
  
  <body>
  <br>
    <div class="section">
      <div class="container">
        <div class="row">
          <div class="col-md-12">
            <div class="panel panel-primary">
              <div class="panel-heading">
                <h3 class="panel-title">Login Screen</h3>
              </div>
              <div class="panel-body">
                <form class="form-horizontal" role="form" method="post">
                  <div class="form-group">
                    <div class="col-sm-2">
                      <label for="inputEmail3" class="control-label">User</label>
                    </div>
                    <div class="col-sm-10">
                      <input type="text" name="user" class="form-control" id="inputEmail3" placeholder="User">
                    </div>
                  </div>
                  <div class="form-group">
                    <div class="col-sm-2">
                      <label for="inputPassword3" class="control-label">Password</label>
                    </div>
                    <div class="col-sm-10">
                      <input type="password" name="password" class="form-control" id="inputPassword3" placeholder="Password">
                    </div>
                  </div>
                  <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-10">
                      <button type="submit" class="btn btn-default">Sign in</button>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </body>

</html>
LOGS;
                echo $web;
            } else {
                $web = <<<'TEM1'
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>AutoLoadOneGenerator {{version}}</title>
    
    <link rel="shortcut icon" href="http://raw.githubusercontent.com/EFTEC/AutoLoadOne/master/doc/favicon.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1">    
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css" integrity="sha384-HSMxcRTRxnN+Bdg0JdbxYKrThecOKuH5zCYotlSAcp1+c8xmyTe9GYg1l9a69psu" crossorigin="anonymous">    
</head>
      
  <body>
  <br>
    <div class="section">
      <div class="container-fluid">
        <div class="row">
          <div class="col-md-12">
            <div class="panel panel-primary">
              <div class="panel-heading">
                <h3 class="panel-title">AutoLoadOneGenerator {{version}}.<div  class='pull-right' ><a style="color:white;" href="https://github.com/EFTEC/AutoLoadOne">Help Page</a></div></h3>
              </div>             
              <div class="panel-body">
                <form class="form-horizontal" role="form" method="post">

                  <div class="form-group">
                    <div class="col-sm-2">
                      <label class="control-label">Root Folder <span class="text-danger">(Req)</span> </label>
                    </div>
                    <div class="col-sm-10">
                      <input type="text" class="form-control" placeholder="ex. \htdoc\web  or c:\htdoc\web"
                      name="rooturl" value="{{rooturl}}">
                      <em><b>Examples:</b> Absolute paths: c:\root\folder, c:/root/folder, /root/folder</em>
                      <em>Relative paths: folder/other, folder\other</em><br>
                      <em>Extension scanned: <b>{{extension}}</b></em><br>
                      <em>PHP files that contain the comment <b>@noautoload</b> are ignored</em><br>
                      <em>PHP files that don't contain a class/interface are ignored. Its allowed to have multiple classes per file</em><br>
                      <em>PHP files that contain the comment <b>"@autorun"</b> are executed (even if they don't have a class)</em><br>
                      <em>PHP files that contain the comment <b>"@autorun first"</b> are executed with priority</em><br>
                    </div>
                  </div>
                  <div class="form-group">
                    <div class="col-sm-2">
                      <label class="control-label">Result folder <span class="text-danger">(Req)</span>
                        <br>
                      </label>
                    </div>
                    <div class="col-sm-10">
                      <input type="text" class="form-control" placeholder="ex. /etc/httpd/web or c:\apache\htdoc"
                      name="fileGen" value="{{fileGen}}">
                      <em>Full/relative path (local file) where the autoload{{extension}} will be generated.<br>
                      Note: This path is also used to determine the relativity of the includes</em>
                    </div>
                  </div>
    
                  <div class="form-group">
                    <div class="col-sm-2">
                      <label class="control-label">Save .php file <span class="text-primary">(Optional)</span>
                        <br>
                      </label>
                    </div>
                    <div class="col-sm-10">                       
                      <input type="text" class="form-control" placeholder="filename to generate"
                      name="savefileName" value="{{savefileName}}">
                      <div class="checkbox">
                        <label>
                          <input type="checkbox" name="savefile" value="1" {{savefile}}>Save File</label>
                      </div>                      
                      <em>The php file that will be generated. You could generate it manually (copy and paste the result)<br>
                    </div>
                  </div>
                  <div class="form-group">
                    <div class="col-sm-2">
                      <label class="control-label">External Library <span class="text-primary">(Optional)</span>
                        <br>
                      </label>
                    </div>
                    <div class="col-sm-10">
                      <textarea class="form-control" rows="5" name="externalPath">{{externalPath}}</textarea>
                      <em>Folder(s) of the external library without trailing "/" separated by comma or a new line. Example
                      /mynamespace,/mynamespace2<br>The folders will be added as absolute path however
                      , it's possible to use a relative path. Example:<br>
                      C:\temp\folder<br>
                      /folder/somefolder<br>
                      ../../mapache-commons\lib</em></div>
                  </div>                                
                  <div class="form-group">
                    <div class="col-sm-2">
                      <label class="control-label">Excluded Namespace <span class="text-primary">(Optional)</span>
                        <br>
                      </label>
                    </div>
                    <div class="col-sm-10">
                      <textarea class="form-control" rows="5" name="excludeNS">{{excludeNS}}</textarea>
                      <em>Namespaces without trailing "/" separated by comma or a new line. It includes local and external folders.                      
                        <br>Example
                      /mynamespace,/mynamespace2</em></div>
                  </div>
                  <div class="form-group">
                    <div class="col-sm-2">
                      <label class="control-label">Excluded Path <span class="text-primary">(Optional)</span></label>
                    </div>
                    <div class="col-sm-10">
                      <textarea class="form-control" rows="5" name="excludePath">{{excludePath}}</textarea>
                      <em>Relative path without trailing "/" separated by comma or a new line. 
                       <br>Example
                      vendor/pchart/class</em><br>
                      <em>You could also use wildcards :<br>
                       /path* for any folder that starts with "/path*,"path/folder".."<br>
                       */path for any folder that ends with "*/path"</em></div>
                  </div>

                  <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-10">
                      <div class="checkbox">
                        <label>
                          <input type="checkbox" name="stop" value=1 {{stop}}>
                          <em>Stop on conflict (class defined more than one time)</em><br>
                          
                          <em>A conflict is a class defined more than one time (same name and same namespace). It usually happens for classes defined for a test (or classes without a namespace)</em>
                          </label>
                          
                      </div>
                    </div>
                  </div>
                  <div class="form-group" >
                    <div class="col-sm-2">
                      <label class="control-label">Log</label>
                    </div>
                    <div class="col-sm-10">
                      <div class="form-control" style="height:150px; overflow-y: scroll;">{{log}}</div>
                    </div>
                  </div>    
                  <div class="form-group" >
                    <div class="col-sm-2">
                      <label class="control-label">Statistic</label>
                    </div>
                    <div class="col-sm-10">
                      <div class="form-control" style="height:220px; overflow-y: auto;">{{logstat}}</div>
                    </div>
                  </div>                                 
                  <div class="form-group" >
                    <div class="col-sm-2">
                      <label class="control-label">Result</label>
                    </div>
                    <div class="col-sm-10">
                      <textarea  class="form-control" readonly rows="14" >{{result}}</textarea>
                    </div>
                  </div>
                  <div class="form-group" >
                    <div class="col-sm-2">
                      <label class="control-label">Cli</label>
                    </div>
                    <div class="col-sm-10">
                      <textarea  class="form-control" readonly rows="3" >{{cli}}</textarea>
                    </div>
                  </div>                  
                  <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-10">
                      <button type="submit" name="button" value="1" class="btn btn-primary">Generate</button>
                      &nbsp;&nbsp;&nbsp;
                      <button type="submit" name="button" value="logout" class="btn btn-default">Logout</button>
                    </div>
                  </div>
                  
                </form>
              </div>
              <div class="panel-footer">
                <h3 class="panel-title">&copy; <a href="https://github.com/EFTEC/AutoLoadOne">Jorge Castro C.</a> {{ms}}</h3>
              </div> 
            </div>
          </div>
        </div>
      </div>
    </div>
  </body>

</html>    
TEM1;

                $web = str_replace('{{rooturl}}', $this->rooturl, $web);
                $web = str_replace('{{fileGen}}', $this->fileGen, $web);
                $web = str_replace('{{extension}}', $this->extension, $web);

                $web = str_replace('{{excludeNS}}', $this->excludeNS, $web);
                $web = str_replace('{{externalPath}}', $this->externalPath, $web);
                $web = str_replace('{{excludePath}}', $this->excludePath, $web);
                $web = str_replace('{{savefile}}', ($this->savefile) ? 'checked' : '', $web);
                $web = str_replace('{{savefileName}}', $this->savefileName, $web);
                $web = str_replace('{{stop}}', ($this->stop) ? 'checked' : '', $web);

                $web = str_replace('{{log}}', $this->log, $web);
                $web = str_replace('{{logstat}}', $this->logStat, $web);
                $web = str_replace('{{version}}', $this::VERSION, $web);
                $web = str_replace('{{result}}', $this->result, $web);

                $this->cli = "php autoloadone.php -folder \"{$this->rooturl}\" -filegen \"{$this->fileGen}\" -save ";

                $tmp = str_replace("\n", '', $this->excludeNS);
                $tmp = str_replace("\r", '', $tmp);
                $this->cli .= "-excludens \"{$tmp}\" ";

                $tmp = str_replace("\n", '', $this->externalPath);
                $tmp = str_replace("\r", '', $tmp);
                $this->cli .= "-externalpath \"{$tmp}\" ";

                $tmp = str_replace("\n", '', $this->excludePath);
                $tmp = str_replace("\r", '', $tmp);
                $this->cli .= "-excludepath \"{$tmp}\"";

                $web = str_replace('{{cli}}', $this->cli, $web);

                $t2 = microtime(true);
                $ms = (round(($t2 - $this->t1) * 1000) / 1000).' sec.';

                $web = str_replace('{{ms}}', $ms, $web);
                echo $web;
            }
        }
    }
} // end class AutoLoadOne

if (_AUTOLOAD_SELFRUN || php_sapi_name() == 'cli') {
    $auto = new AutoLoadOne();
    $auto->init();
    $auto->process();
    $auto->render();
}

// @noautoload
/*
 * @noautoload
 */

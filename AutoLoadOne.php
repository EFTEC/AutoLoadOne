<?php
namespace eftec\AutoLoadOne;
//*************************************************************
use Exception;

define("_AUTOLOADUSER","autoloadone");
define("_AUTOLOADPASSWORD","autoloadone");
define("_AUTOLOADENTER",true); // if you want to auto login (skip user and password) then set to true

//*************************************************************
ini_set('max_execution_time', 300); // Limit of 5 minutes.
/**
 * Class AutoLoadOne
 * @copyright Jorge Castro C. MIT License https://github.com/EFTEC/AutoLoadOne
 * @version 1.1
 * @noautoload
 * @package eftec\AutoLoadOne
 *
 */
class AutoLoadOne {

    const VERSION="1.1";

    var $rooturl=__DIR__;
    var $fileGen="";
    var $savefile=0;
    var $stop=0;
    var $button=0;
    var $excludeNS="";
    var $excludePath="";
    var $log="";
    var $result="";
    var $logged=false;
    var $current="";
    var $t1=0;
    var $debugMode=false;
    var $statNumClass=0;
    var $statNumPHP=0;
    var $statNameSpaces=array();
    private $excludeNSArr;
    private $excludePathArr;
    private $baseGen;

    /**
     * AutoLoadOne constructor.
     */
    public function __construct()
    {
        $this->fileGen=__DIR__;
        $this->t1=microtime(true);
    }
    function getAllParametersCli() {
        $this->rooturl=$this->getParameterCli("folder");
        $this->fileGen=$this->getParameterCli("filegen");
        $this->savefile=$this->getParameterCli("save");
        $this->stop=$this->getParameterCli("stop");
        $this->current=$this->getParameterCli("current",true);
        $this->excludeNS=$this->getParameterCli("excludens");
        $this->excludePath=$this->getParameterCli("excludepath");
        $this->debugMode=$this->getParameterCli("debug");
    }

    /**
     * @param $key
     * @param string $default is the defalut value is the parameter is set without value.
     * @return string
     */
    function getParameterCli($key,$default='') {
        global $argv;
        $p=array_search("-".$key,$argv);
        if ($p===false) return "";
        if ($default!=='') return $default;
        if (count($argv)>=$p+1) {

            return $argv[$p + 1];
        }
        return "";
    }


    function initSapi() {
        global $argv;
        echo "------------------------------------------------------------------\n";
        echo " AutoLoadOne Generator ".$this::VERSION." (c) Jorge Castro\n";
        echo "------------------------------------------------------------------\n";


        if (count($argv)<2) {
            // help
            echo "Help:\n";
            echo "-current (scan and generates files from the current folder)\n";
            echo "-folder (folder to scan)\n";
            echo "-filegen (folder where autoload.php will be generate)\n";
            echo "-save (save the file to generate)\n";
            echo "-excludens (namespace excluded)\n";
            echo "-excludepath (path excluded)\n";
            echo "------------------------------------------------------------------\n";
        } else {
            $this->getAllParametersCli();
            $this->fileGen=($this->fileGen=="")?getcwd():$this->fileGen;
            $this->button=1;
        }
        if ($this->current) {
            $this->rooturl=getcwd();
            $this->fileGen=getcwd();
            $this->savefile=1;
            $this->stop=0;
            $this->button=1;
            $this->excludeNS="";
            $this->excludePath="";
        }

        echo "-folder ".$this->rooturl." (folder to scan)\n";
        echo "-filegen ".$this->fileGen." (folder where autoload.php will be generate)\n";
        echo "-save ".($this->savefile?"yes":"no")." (save filegen)\n";
        echo "-excludens ".$this->excludeNS." (namespace excluded)\n";
        echo "-excludepath ".$this->excludePath." (path excluded)\n";
        echo "------------------------------------------------------------------\n";
    }
    function initWeb() {
        @ob_start();
        // Not in cli-mode
        @session_start();
        $this->logged=@$_SESSION["log"];
        if (!$this->logged) {
            $user=@$_POST["user"];
            $password=@$_POST["password"];
            if (($user==_AUTOLOADUSER && $password==_AUTOLOADPASSWORD) || _AUTOLOADENTER ) {
                $_SESSION["log"]="1";
                $this->logged=1;
            } else {
                sleep(1); // sleep a second
                $_SESSION["log"]="0";
                @session_destroy();
            }
            @session_write_close();
        } else {
            $this->debugMode=isset($_GET['debug'])?true:false;
            $this->rooturl=@$_POST["rooturl"]?$_POST["rooturl"]:$this->rooturl;
            $this->fileGen=@$_POST["fileGen"]?$_POST["fileGen"]:$this->fileGen;
            $this->excludeNS=@$_POST["excludeNS"]?$_POST["excludeNS"]:$this->excludeNS;
            $this->excludePath=@$_POST["excludePath"]?$_POST["excludePath"]:$this->excludePath;

            $this->savefile=@$_POST["savefile"];
            $this->stop=@$_POST["stop"];
            $this->button=@$_POST["button"];
            if ($this->button=="logout") {
                @session_destroy();
                $this->logged=0;
                @session_write_close();
            }


        }
    }

    function init() {
        if (php_sapi_name() == "cli") {
            $this->initSapi();
        } else {
           $this->initWeb();
        }
    }

    function genautoload($file,$namespaces,$namespacesAlt) {
        if ($this->savefile) {
            try {
                $fp = @fopen($file, "w");
                if (!$fp) throw new Exception("Error");
            } catch (Exception $e) {
                $this->addLog("ERROR: Unable to save file $file ".$php_errormsg);
                return false;
            }
        }
        $template=<<<'EOD'
<?php
/**
 * This class is used for autocomplete.
 * Class _AutoLoad
 * @noautoload it avoids to index this class
 * @generated by AutoLoadOne {{version}} generated {{date}}
 * @copyright Copyright Jorge Castro C - MIT License. https://github.com/EFTEC/AutoLoadOne
 */
class _AutoLoad
{
    var $debug=false;
    private $_arrautoloadCustom = array(
{{custom}}
    );
    private $_arrautoload = array(
{{include}}
    );
    /**
     * _AutoLoad constructor.
     * @param bool $debug
     */
    public function __construct($debug=false)
    {
        $this->debug = $debug;
    }
    /**
     * @param $class_name
     * @throws Exception
     */
    public function auto($class_name) {
        // its called only if the class is not loaded.
        $ns = dirname($class_name); // without trailing
        $ns=($ns==".")?"":$ns;        
        $cls = basename($class_name);
        // special cases
        if (isset($this->_arrautoloadCustom[$class_name])) {
            $this->loadIfExists($this->_arrautoloadCustom[$class_name] );
            return;
        }
        // normal (folder) cases
        if (isset($this->_arrautoload[$ns])) {
            $this->loadIfExists($this->_arrautoload[$ns] . "\\" . $cls . ".php");
            return;
        }
    }

    /**
     * @param $filename
     * @throws Exception
     */
    public function loadIfExists($filename)
    {
        if((@include __DIR__."\\".$filename) === false) {
            if ($this->debug) {
                throw  new Exception("AutoLoadOne Error: Loading file [".__DIR__."\\".$filename."] for class [".basename($filename)."]");
            } else {
                throw  new Exception("AutoLoadOne Error: No file found.");
            }
        }
    }
} // end of the class _AutoLoad
if (defined('_AUTOLOADONEDEBUG')) {
    $_autoLoad=new _AutoLoad(_AUTOLOADONEDEBUG);
} else {
    $_autoLoad=new _AutoLoad(false);
}
spl_autoload_register(function ($class_name)
{
    global $_autoLoad;
    $_autoLoad->auto($class_name);
});
EOD;
        $custom="";
        foreach($namespacesAlt as $k=>$v) {
            $custom.="\t\t'$k' => '$v',\n";
        }
        if ($custom!="") {
            $custom=substr($custom,0,-2);
        }
        $include="";
        foreach($namespaces as $k=>$v) {
            $include.="\t\t'$k' => '$v',\n";
        }
        if ($include!="") {
            $include=substr($include,0,-2);
        }

        $template=str_replace("{{custom}}",$custom,$template);
        $template=str_replace("{{include}}",$include,$template);
        $template=str_replace("{{version}}",$this::VERSION,$template);
        $template=str_replace("{{date}}", date("Y/m/d h:i:s"),$template);

        if ($this->savefile) {
            fwrite($fp, $template);
            fclose($fp);
            $this->addLog("File $file generated");
        }
        return $template;

    }
    function listFolderFiles($dir) {
        $arr=array();
        $this->listFolderFilesAlt($dir,$arr);
        return $arr;
    }
    function listFolderFilesAlt($dir,&$list){
        $ffs = scandir($dir);
        foreach ( $ffs as $ff ){
            if ( $ff != '.' && $ff != '..' ){
                if ( strlen($ff)>=5 ) {
                    if ( substr($ff, -4) == '.php' ) {
                        $list[] = $dir.'/'.$ff;
                    }
                }
                if( is_dir($dir.'/'.$ff) )
                    $this->listFolderFilesAlt($dir.'/'.$ff,$list);
            }
        }
        return $list;
    }
    /**
     * @param $filename
     * @return array
     */
    function parsePHPFile($filename) {
        $r=array();
        try {
            $content=file_get_contents($filename);
            $err="";
            if ($this->debugMode) {
                echo $filename . " trying token...<br>";
            }
            $tokens = token_get_all($content);
            /*
            echo $filename;
            echo "<pre>";
            var_dump(token_name(377));
            var_dump(token_name(378));
            var_dump($tokens);
            echo "</pre>";
            die(1);
            */
        } catch(Exception $ex) {
            echo "Error in $filename\n";
            die(1);
        }
        foreach($tokens as $p=>$token) {
            if (is_array($token) && ($token[0]==T_COMMENT ||$token[0]==T_DOC_COMMENT)) {
                if (strpos($token[1],"@noautoload")!==false) {
                    return array();
                }
            }
        }
        $nameSpace="";
        $className="";
        foreach($tokens as $p=>$token) {
            if (is_array($token) && $token[0]==T_NAMESPACE) {
                // encontramos un namespace
                $ns="";
                for($i=$p+2;$i<$p+30;$i++) {
                    if (is_array($tokens[$i])) {
                        $ns.=$tokens[$i][1];
                    } else {
                        // tokens[$p]==';' ??
                        break;
                    }
                }
                $nameSpace=$ns;
            }
            if (is_array($token) && $token[0]==T_CLASS) {
                // encontramos una clase
                $min=min($p+30,count($tokens)-1);
                for($i=$p+2;$i<$min;$i++) {
                    if (is_array($tokens[$i]) && $tokens[$i][0]==T_STRING) {
                        $className=$tokens[$i][1];
                        break;
                    }
                }
                $r[]=array('namespace'=>trim($nameSpace),'classname'=>trim($className));
            }

        } // foreach
        return $r;
    }
    function genPath($path) {
        $path=str_replace("\\","/",$path);
        if (strpos($path,$this->baseGen)==0) {
            $min1=strripos($path,"/");
            $min2=strripos($this->baseGen,"/");
            //$min=min(strlen($path),strlen($this->baseGen));
            $min=min($min1,$min2);
            $baseCommon=$min;
            for($i=0;$i<$min;$i++) {
                if (substr($path,0,$i)!=substr($this->baseGen,0,$i)) {
                    $baseCommon=$i-2;

                    break;
                }
            }
            // cuanto hay que retroceder
            $c=substr_count(substr($this->baseGen,$baseCommon),"/");
            $r=str_repeat("/..",$c);
            // hay que avanzar
            $r2=substr($path,$baseCommon);
            return $r.$r2;
        } else {
            $r=substr($path, strlen($this->baseGen));
        }
        return $r;
    }
    /**
     * returns dir name linux way
     * @param $fullUrl
     * @return mixed|string
     */
    function dirNameLinux($fullUrl) {
        $dir = dirname($fullUrl);
        $dir=str_replace("\\","/",$dir); // replace windows path for linux path.
        //$dir=str_replace("/",DIRECTORY_SEPARATOR,$dir); // replace windows path for linux path.
        $dir = rtrim($dir, "/"); // remove trailing /
        return $dir;
    }


    function addLog($txt) {
        if (php_sapi_name() == "cli") {
            echo "\t".$txt . "\n";
        } else {
            $this->log .= $txt . "\n";
        }
    }

    function process() {
        if ($this->rooturl) {
            $this->baseGen=$this->dirNameLinux($this->fileGen."/autoload.php");
            $files = $this->listFolderFiles($this->rooturl);
            $ns = array();
            $nsAlt = array();
            $this->excludeNSArr = str_replace("\n", "", $this->excludeNS);
            $this->excludeNSArr = str_replace("\r", "", $this->excludeNSArr);
            $this->excludeNSArr = str_replace(" ", "", $this->excludeNSArr);
            $this->excludeNSArr = explode(",", $this->excludeNSArr);

            $this->excludePathArr = str_replace("\n", "", $this->excludePath);
            $this->excludePathArr = str_replace("\r", "", $this->excludePathArr);
            $this->excludePathArr = str_replace(" ", "", $this->excludePathArr);
            $this->excludePathArr = explode(",", $this->excludePathArr);

            $this->log = "";
            $this->result = "";
            if ($this->button) {
                foreach ($files as $f) {
                    $pArr = $this->parsePHPFile($f);
                    $dir = $this->dirNameLinux($f);
                    $dir = $this->genPath($dir);
                    $full = $this->genPath($f);
                    $urlFull = $this->dirNameLinux($full);
                    $basefile = basename($f);
                    foreach ($pArr as $p) {


                        $nsp = $p['namespace'];
                        $cs = $p['classname'];
                        $this->statNameSpaces[$nsp]=1;
                        $this->statNumPHP++;
                        if ($cs!='') {
                            $this->statNumClass++;
                        }

                        $altUrl = ($nsp != "") ? $nsp . '\\' . $cs : $cs;

                        if ($nsp != "" || $cs != "") {
                            if ((!isset($ns[$nsp]) || $ns[$nsp] == $dir) && $basefile == $cs . ".php") {
                                // namespace doesn't exist and the class is equals to the name
                                // adding as a folder

                                if ((!in_array($nsp, $this->excludeNSArr) || $nsp=="") && !in_array($dir, $this->excludePathArr)) {
                                    if ($nsp=="") {
                                        $this->addLog("Adding Full (empty namespace): $altUrl=$full");
                                        $nsAlt[$altUrl] = $full;
                                    } else {
                                        $ns[$nsp] = $dir;
                                        $this->addLog("Adding Folder: $nsp=$dir");
                                    }

                                }
                            } else {
                                // custom namespace 1-1
                                // a) if filename has different name with the class
                                // b) if namespace is already defined for a different folder.
                                // c) multiple namespaces
                                if (isset($nsAlt[$altUrl])) {
                                    $this->addLog("Error Conflict:Class on $altUrl already defined.");
                                    if ($this->stop) {
                                        die(1);
                                    }
                                } else {
                                    if ((!in_array($altUrl, $this->excludeNSArr) || $nsp=="") && !in_array($urlFull, $this->excludePathArr)) {
                                        $this->addLog("Adding Full: $altUrl=$full");
                                        $nsAlt[$altUrl] = $full;
                                    }
                                }
                            }
                        }
                    }
                    if (count($pArr)==0) {
                        $this->statNumPHP++;
                        $this->addLog("Ignoring $full. Reason: No class found on file.");
                    }
                }

                $this->result = $this->genautoload($this->fileGen."/autoload.php", $ns, $nsAlt);
            }
            $this->addLog("Stat number of classes: ".$this->statNumClass);
            $this->addLog("Stat number of namespaces: ".count($this->statNameSpaces));
            $this->addLog("Stat number of PHP Files: ".$this->statNumPHP);

        } else {
            $this->addLog("No folder specified");
        }
    }
    function render() {
        if ($this->debugMode) {
            ob_clean();
        }

        if (php_sapi_name() == "cli") {
            $t2=microtime(true);
            echo "\n".(round(($t2-$this->t1)*1000)/1000)." sec. Finished\n";


        } else {

            if (!$this->logged) {
                $web=<<<'LOGS'
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous" />

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
            }   else {



                $web = <<<'TEM1'
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous" />    
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
                      <em>Root folder to scan.</em>
                    </div>
                  </div>
                  <div class="form-group">
                    <div class="col-sm-2">
                      <label class="control-label">Generated File <span class="text-danger">(Req)</span>
                        <br>
                      </label>
                    </div>
                    <div class="col-sm-10">
                      <input type="text" class="form-control" placeholder="ex. /etc/httpd/web or c:\apache\htdoc"
                      name="fileGen" value="{{fileGen}}">
                      <em>Full path (local file) where the autoload.php will be generated.<br>
                      Note: This path is also used to determine the relativity of the includes</em>
                    </div>
                  </div>
                  <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-10">
                      <div class="checkbox">
                        <label>
                          <input type="checkbox" name="savefile" value="1" {{savefile}}>Save File</label>
                      </div>
                    </div>
                  </div>                  
                  <div class="form-group">
                    <div class="col-sm-2">
                      <label class="control-label">Excluded Namespace
                        <br>
                      </label>
                    </div>
                    <div class="col-sm-10">
                      <textarea class="form-control" name="excludeNS">{{excludeNS}}</textarea>
                      <em>Namespaces without trailing "/" separated by comma. Example
                      /mynamespace</em></div>
                  </div>
                  <div class="form-group">
                    <div class="col-sm-2">
                      <label class="control-label">Excluded Path</label>
                    </div>
                    <div class="col-sm-10">
                      <textarea class="form-control" name="excludePath">{{excludePath}}</textarea>
                      <em>Relative path without trailing "/" separated by comma. Example
                      vendor/pchart/class</em></div>
                  </div>

                  <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-10">
                      <div class="checkbox">
                        <label>
                          <input type="checkbox" name="stop" value=1 {{stop}}>
                          <em>Stop on conflict (class defined more than one time)</em></label>
                      </div>
                    </div>
                  </div>
                  <div class="form-group" draggable="true">
                    <div class="col-sm-2">
                      <label class="control-label">Log</label>
                    </div>
                    <div class="col-sm-10">
                      <textarea class="form-control" readonly rows="10">{{log}}</textarea>
                    </div>
                  </div>                  
                  <div class="form-group" draggable="true">
                    <div class="col-sm-2">
                      <label class="control-label">Result</label>
                    </div>
                    <div class="col-sm-10">
                      <textarea class="form-control" readonly rows="10">{{result}}</textarea>
                    </div>
                  </div>
                  <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-10">
                      <button type="submit" name="button" value="1" class="btn btn-default">Generate</button>
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


                $web=str_replace("{{rooturl}}",$this->rooturl,$web);
                $web=str_replace("{{fileGen}}",$this->fileGen,$web);



                $web=str_replace("{{excludeNS}}",$this->excludeNS,$web);
                $web=str_replace("{{excludePath}}",$this->excludePath,$web);
                $web=str_replace("{{savefile}}",($this->savefile)?"checked":"",$web);
                $web=str_replace("{{stop}}",($this->stop)?"checked":"",$web);

                $web=str_replace("{{log}}",$this->log,$web);
                $web=str_replace("{{version}}",$this::VERSION,$web);
                $web=str_replace("{{result}}",$this->result,$web);

                $t2=microtime(true);
                $ms=(round(($t2-$this->t1)*1000)/1000)." sec.";

                $web=str_replace("{{ms}}",$ms,$web);
                echo $web;
            }
        }

    }

} // end class AutoLoadOne





$auto=new AutoLoadOne();
$auto->init();
$auto->process();
$auto->render();


// @noautoload
/**
 * @noautoload
 */











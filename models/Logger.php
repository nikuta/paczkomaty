<?php
/**
 * Helper class used for debugging purposes
 *
 * @author Rafał Kozłowski
 */
class Logger {
    private $logFile;
    private $logDir;
    
    const TYPE_ERROR = 1;
    const TYPE_MESSAGE = 2;
    const TYPE_DEBUG = 3;
    
    public $types = array(
        self::TYPE_ERROR => "Error",
        self::TYPE_MESSAGE => "Message",
        self::TYPE_DEBUG => "Debug"
    );

    public function __construct() {
        $now = new DateTime();
        $this->logDir = "../logs/";
        $this->logFile = $now->format("Y-m-d")."_pakomato.log";
    }
    
    /**
     * Dispatcher function used only if script is called directly
     */
    public function dispatcher(){
        ini_set("display_errors", "On");
        error_reporting(E_ALL & ~E_NOTICE);
        
        $refClass = new ReflectionClass($this);
        $action = $_GET['action']."Action";
        if($refClass->hasMethod($action)){
            $refMethod = new ReflectionMethod($this, $action);
            if($refMethod->isPublic()){
                $this->$action();
            } else {
                //header("Location: /");
            }
        } else {
            //header("Location: /");
        }
    }

    public function writeLog($valToDump,$varType){
        $file = fopen($this->logDir."/".$this->logFile, "a+");
        $now = new DateTime();
        $row = array(
            $now->format("Y-m-d H:i:s"),
            print_r($valToDump,true),
            $varType
        );
        fwrite($file, implode("|", $row)."\n");
        fclose($file);
    }
    
    public function readLogAction(){
        $logFile = preg_replace("/[^a-z0-9-_.]/","",$_GET['logFile']);
        $list = $this->listFiles();
        if(in_array($logFile, $list)){
            $file = fopen($this->logDir."/".$logFile,"r");
            while($text = fread($file, 200)){
                echo nl2br($text);
            }
        }
        echo '<br /><br /><a href="?action=listFiles">powrot do listy</a>';
        
    }
    
    public function listFilesAction(){
        $list = $this->listFiles();
        foreach ($list as $file){
            echo '<a href="?action=readLog&logFile='.$file.'">'.$file."</a><br />";
        }
    }
    
    public function cleanupLogsAction(){
        $now = new DateTime();
        
    }
    
    /**
     * Returns list of all log files
     * @return array Files list
     */
    private function listFiles(){
        $return = array();
        $dirHandler = opendir($this->logDir);
        while($entry = readdir($dirHandler)){
            if($entry != "." && $entry != ".."){
                $return[] = $entry;
            }
        }
        return $return;
    }
    
}

if(isset($_GET['action'])){
    $logger = new Logger();
    $logger->dispatcher();
    $logger->writeLog("jakis testowy komunikat o błędzie",  Logger::TYPE_MESSAGE);
}
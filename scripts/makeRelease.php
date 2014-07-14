#!/usr/bin/env php
<?php
include "../vendor/autoload.php";
use Symfony\Component\Yaml\Parser;

ini_set("display_errors","On");
error_reporting(E_ALL);

class Releaser {
    private $conf;
    private $yml;
    private $moduleVersion;
    private $moduleName;
    private $modulePath;
    private $releasePath;
    private $encodedDir;

    public function __construct($configFile) {
        $this->yml = new Parser();
        $this->conf = $this->yml->parse(file_get_contents($configFile));
        $this->moduleName = $this->conf['module-name'];
        
        $tmpDir = explode("/",str_replace("\\","/",__DIR__));
        array_pop($tmpDir);
        $this->modulePath = implode("/",$tmpDir);
        $this->releasePath = $this->modulePath."/".$this->conf['release-path'];
        $this->encodedPath = $this->modulePath."/".$this->conf['encoded-dir'];
        
        $versionFile = file_get_contents($this->modulePath."/".$this->conf['version-file']);
        echo $this->modulePath."/".$this->moduleName."/".$this->conf['version-file']."\n";        
        preg_match('/\$this->version\s*=\s*([0-9])+.([0-9]+)/',$versionFile,$mathes);
        list(,$vMajor,$vMinor) = $mathes;
        $this->moduleVersion = $vMajor.".".$vMinor;
    } 
    
    private function createDir($path){
        echo "Katalog ".$path;
        if(!file_exists($path)){
            if(mkdir($path,$this->conf['dir-perms'])){
                echo " - ok";
            }else{
                die (" - nie moge utworzyć katalogu!\n"); 
            }
        }else { echo " - juz istnieje"; }
        echo "\n";
    }

    public function executeConfig($node = null,$parent = ""){
        if($node==null){ $node = $this->conf['root']; }
        
        if(isset($node['path'])){
            $this->createDir($this->releasePath."/".$this->moduleName.$parent.$node['path']);
        }
        if(isset($node['files']) && is_array($node['files'])){
            foreach ($node['files'] as $file){
                
                $srcDir = $this->modulePath;
                if(isset($file['from'])){ $srcDir .= "/".$this->conf[$file['from']]."/"; }
                else { $srcDir .= $parent.$node['path']."/"; }

                $dstDir = $this->releasePath."/".$this->moduleName.$parent.$node['path']."/";
                
                echo "kopiuje plik: ".$srcDir.$file['name']." -> ".$dstDir.$file['name'];
                if(copy($srcDir.$file['name'],$dstDir.$file['name'])) { echo " - ok\n"; }
                else { die(" - blad zapisu pliku\n"); }
            }
        }
        if(isset($node["subdirs"])){        
            foreach($node["subdirs"] as $subdir){
                $this->executeConfig($subdir,$parent.$node['path']."/");
            }
        }
    }
    
    public function getModuleName(){
        return $this->moduleName." v".$this->moduleVersion;
    }
    
    public function makeArchiveFile(){
        $rootPath = $this->releasePath."/".$this->moduleName;

        $zip = new ZipArchive;
        $zip->open($this->releasePath."/".$this->moduleName.$this->moduleVersion.".zip", ZipArchive::OVERWRITE);

        $files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator($rootPath), RecursiveIteratorIterator::LEAVES_ONLY );

        foreach ($files as $name => $file) {
            if($file->getFilename()!=".."){
                $filePath = $file->getRealPath();
                $localFile = str_replace($this->releasePath."/", "", $filePath);
                if($file->getFilename() == "."){
                    $zip->addEmptyDir(dirname($localFile));
                }else{
                    $zip->addFile($filePath, $localFile);
                }
            }
        }

        $zip->close();
    }
}
$rls = new Releaser("dirStructure.yml");

echo "\n\n*******************************************\n";
echo "Moduł: ".$rls->getModuleName()."\n";
echo "*******************************************\n\n";
echo "Tworzę  strukturę modułu \n";

$rls->executeConfig();
$rls->makeArchiveFile();

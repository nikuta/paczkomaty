<?php
define ('PACZKOMATY_PATH', _PS_MODULE_DIR_."pakomato/");

function displayPakomatoAssert($file,$line,$code,$message=null){
    echo '<div class="pakomato-assert">'
        . '<div class=pakomato-assert-line>File: '.$file.'</div>'
        . '<div class=pakomato-assert-line>Line: '.$line.'</div>'
        . '<div class=pakomato-assert-line>Code: '.$code.'</div>'
        .($message!=null).'<div class=pakomato-assert-line>Message: '.$message.'</div>'
        . '</div>';
}
assert_options(ASSERT_CALLBACK,'displayPakomatoAssert');
if(isset($_GET['debug'])){
    if($_GET['debug'] =="start"){
        Configuration::updateValue("PAKOMATO_DEBUG","true");
    }
    if($_GET['debug'] == "stop"){
        Configuration::updateValue("PAKOMATO_DEBUG","false");
    }
}

if(Configuration::get("PAKOMATO_DEBUG")){
    ini_set("display_errors", "On");
    error_reporting(E_ALL & ~E_NOTICE);
}

$psVersion = implode(".",array_slice(explode(".",_PS_VERSION_),0,2));
assert(defined("_PS_VERSION_"));
$filename = PACZKOMATY_PATH.'controllers/Pakomato'.str_replace(".", "", $psVersion).'.php';

if(file_exists($filename)){ require_once $filename; }

class pakomato extends pakomatoBase{
    
}
?>

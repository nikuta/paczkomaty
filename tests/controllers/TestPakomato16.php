<?php
/**
 * PHPUnit test class for TestPakomato16
 *
 * @author Rafał Kozłowski
 */

define ('PACZKOMATY_PATH', _PS_MODULE_DIR_."pakomato/");

class TestPakomato16 extends PHPUnit_Framework_TestCase{
    private $conf;
    
    public function __construct($name = NULL, array $data = array(), $dataName = '') {
        $this->conf = YamlReader::getConfig("config");
        parent::__construct($name, $data, $dataName);
    }

    public function testCodList(){
        $argc = "";
        $argv = "index.php";
        require_once $this->conf['ps_config_file'];
        $obj = new Pakomato();
        
    }
}

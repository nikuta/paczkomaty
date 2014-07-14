<?php
/**
 * Bootstrap file fot unit testing
 *
 * @author Rafal Kozlowski
 */
require_once 'bootstrap.inc.php';

use Symfony\Component\Yaml\Parser;

abstract class YamlReader {
    private static $parser = null;
    
    public static function getInstance(){
        if( self::$parser == null ){
            self::$parser = new Parser();
        }
        return self::$parser;
    }
    
    public static function getConfig($file){
        $parser = self::getInstance();
        return $parser->parse(file_get_contents($file.".yml"));
    }
}
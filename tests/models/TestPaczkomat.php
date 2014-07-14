<?php

/**
 * Unit Test for class models/Paczkomat.php
 *
 * @author rafal
 */

class TestPaczkomat extends PHPUnit_Framework_TestCase{
    
    private $testData;
    
    public function __construct($name = NULL, array $data = array(), $dataName = '') {
        $this->testData = YamlReader::getConfig(dirname(__FILE__)."/TestPaczkomatDataProvider");
        parent::__construct($name, $data, $dataName);
    }

    /**
     * @dataProvider testSprawdzKontoDP
     */
    public function testSprawdzKonto($email,$expectedResult){
        $this->assertEquals($expectedResult,Paczkomat::sprawdzKonto($email));
    }
    
    /**
     * Data prowider for testSprawdzKonto method
     */
    public function testSprawdzKontoDP(){
        return $this->testData["testSprawdzKonto"];
    }
}

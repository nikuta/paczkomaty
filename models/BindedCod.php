<?php
class BindedCod {
    public $id_module;
    public $name;
    public $binded;
    
    public function __construct($id,$name,$binded) {
        $this->id = $id;
        $this->name = $name;
        $this->binded = $binded;
    }
}
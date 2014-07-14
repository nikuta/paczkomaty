<?php
//Model dla Prestashop v 1.4.x
class PakomatoUserSettings extends ObjectModel implements IPakomatoModel
{
    const TABLE_NAME='pakomato_user_settings';
    const TABLE_ID='id';
    
	public $id;
	public $id_customer;
	public $machine;
	public $machine_cod;
	public $phone;
    
    protected $table = self::TABLE_NAME;
    protected $identifier = self::TABLE_ID;
    
    public function getFields()
	{
		parent::validateFields();
        $result = array();
        $reflection = new ReflectionClass($this);
        $fields = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
        foreach($fields as $f){            
            $prop = $f->name;
            $met = new ReflectionProperty($this, $prop);
            if($prop != self::TABLE_ID && !$met->isStatic()){
                $result[$prop] = pSQL($this->$prop);
            }
        }
		return $result;
	}

    public static function getCreateSql(){        
        $sql = "CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_.self::TABLE_NAME."` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `id_customer` int(11) NOT NULL,
            `machine` text NOT NULL,
            `machine_cod` text NOT NULL,
            `phone` varchar(25) DEFAULT NULL,
            PRIMARY KEY (`id`) )";
        return $sql;
    }
    
    protected function hydrate($data){
        $reflection = new ReflectionClass($this);
        $fields = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
        foreach($fields as $f){            
            $prop = $f->name;
            $met = new ReflectionProperty($this, $prop);
            if(!$met->isStatic()){
                $this->$prop = $data[$prop];
            }
        }
    }

    public static function getByCustomerId($customerId)
	{
		$sql = "SELECT * FROM "._DB_PREFIX_.self::TABLE_NAME." WHERE id_customer=".(int)$customerId;        
        $me = get_class();
		$res = Db::getInstance()->executeS($sql);
        $ret = array();
        foreach ($res as $item){            
            $obj = new $me();
            $obj->hydrate($item);            
            $ret[] = $obj;
        }
		return $ret;
	}  
}
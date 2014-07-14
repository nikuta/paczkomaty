<?php

class PakomatoOrder extends ObjectModel implements IPakomatoModel
{    
	public $id;
	public $id_order;
	public $paczkomat;
	public $customer_phone;
	public $selfsend;
	public $insurance;
	public $cod;
	public $size;
	public $sender_machine;
    public $label_type;
    
    const TABLE_NAME="pakomato_orders";
    const TABLE_ID="id";
    
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
    
    public static function getCreateSql(){
        $sql = "CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_.self::TABLE_NAME."` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `id_order` int(11) NOT NULL,
            `paczkomat` text,
            `customer_phone` varchar(30) DEFAULT NULL,
            `selfsend` int(1) DEFAULT NULL,
            `insurance` int(11) DEFAULT NULL,
            `cod` float DEFAULT NULL,
            `size` varchar(2) NOT NULL DEFAULT 'A',
            `sender_machine` text,
            `label_type` varchar(10) DEFAULT '".Paczkomat::ETYKIETA_STANDARD."',
            PRIMARY KEY (`id`)
        )";
        return $sql;
    }

	public static function getByOrderId($orderId)
	{        
		$sql = "SELECT * FROM "._DB_PREFIX_.self::TABLE_NAME." WHERE id_order=".(int)$orderId;                
		$res = Db::getInstance()->executeS($sql);        
        $me = get_class();
		if(is_array($res) && count($res)>0)
		{                        
            $obj = new $me();            
            $obj->hydrate(array_shift($res));
            return $obj;
		}else return false;
	}
}
?>

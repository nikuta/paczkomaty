<?php

class PakomatoPack extends ObjectModel implements IPakomatoModel
{
    const TABLE_NAME="pakomato_packs";
    const TABLE_ID="id";
    
	public $id;
	public $id_order;
	public $customer_machine;
	public $sender_machine;
	public $sticker_file;
	public $pack_status;
	public $cod;
	public $insurance;
	public $selfsend;
	public $customer_phone;
	public $customer_email;
	public $size;
	public $tracking_number;
	public $send_code;
	public $inpost_status;
	public $inpost_status_code;

	const JOB_CREATED = 0;
	const STICKER_CREATED = 1;
	const JOB_CANCELED = 2;
	const IN_TRANSIT = 3;
	const DELIVERED = 4;
    

	public static $status = array(
		self::JOB_CREATED => "Wygenerowane zlecenie",
		self::STICKER_CREATED => "Wygenerowana etykieta",
		self::JOB_CANCELED => "Zlecenie anulowane"
	);
    
    protected $table = self::TABLE_NAME;
    protected $identifier = self::TABLE_ID;
	
	public static function updateInpostStatus($packId=false)
	{
		$col = array();
		if($packId == false)
		{
			$sql = "
				SELECT
					*
				FROM
					"._DB_PREFIX_.self::TABLE_NAME."
				WHERE
					inpost_status_code='Created' OR
					inpost_status_code='Prepared' OR
					inpost_status_code='Sent' OR
					inpost_status_code='InTransit' OR
					inpost_status_code='Stored' OR
					inpost_status_code='Aviso' OR
					inpost_status_code='Claimed' OR
					inpost_status_code='CustomerDelivering' OR
					inpost_status_code='' OR
					inpost_status_code is null";
			$res = Db::getInstance()->executeS($sql);
			if(is_array($res))
			{                
                $col = self::hydrateCollection($res);
			}
		}else{
			$col[] = new PakomatoPack($packId);
		}

		foreach($col as $pack)
		{
			$res = Paczkomat::statusPaczki($pack->tracking_number);
			if($res['result']=="ok"){
				if($res['status_code']=="Delivered"){ $pack->pack_status = PakomatoPack::DELIVERED; }
				if($res['status_code']=="InTransit"){ $pack->pack_status = PakomatoPack::IN_TRANSIT; }				
				$pack->inpost_status = $res['status'];
				$pack->inpost_status_code = $res['status_code'];
				$pack->save();
			}
		}
	}
    
    public static function getCreateSql() {
        $sql = "CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_.self::TABLE_NAME."` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `id_order` int(11) NOT NULL,
            `id_shop` int(11) NOT NULL,
            `customer_machine` text NOT NULL,
            `sender_machine` text,
            `sticker_file` varchar(45) DEFAULT NULL,
            `pack_status` int(11) NOT NULL,
            `cod` float NOT NULL DEFAULT '0',
            `insurance` int(11) NOT NULL DEFAULT '0',
            `selfsend` tinyint(4) DEFAULT '1',
            `customer_phone` varchar(20) NOT NULL,
            `customer_email` varchar(45) NOT NULL,
            `size` varchar(2) NOT NULL DEFAULT 'A',
            `tracking_number` varchar(35) DEFAULT NULL,
            `send_code` varchar(10) DEFAULT NULL,
            `inpost_status` varchar(45) DEFAULT NULL,
            `inpost_status_code` varchar(45) DEFAULT NULL,
            PRIMARY KEY (`id`)
        )";            
        return $sql;
    }

    public static function getByOrderId($orderId)
	{
		$sql = "SELECT * FROM "._DB_PREFIX_.self::TABLE_NAME." WHERE id_order=".(int)$orderId;        
		$res = Db::getInstance()->executeS($sql);        
		if(is_array($res))
		{
			$packs = self::hydrateCollection($res);            
			return $packs;
		}
		return false;
	}
    
    protected static function hydrateCollection($items){
        $me = get_class();
        $res = array();
        if(is_array($items)){
            foreach ($items as $item){
                $obj = new $me();
                $obj->hydrate($item);
                $res[] = $obj;
            }
        }
        return $res;
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
}
?>

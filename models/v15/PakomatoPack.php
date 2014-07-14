<?php

class PakomatoPack extends ObjectModel implements IPakomatoModel
{
	public $id;
	public $id_order;
	public $id_shop;
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
    const TABLE_NAME="pakomato_packs";

	public static $status = array(
		self::JOB_CREATED => "Wygenerowane zlecenie",
		self::STICKER_CREATED => "Wygenerowana etykieta",
		self::JOB_CANCELED => "Zlecenie anulowane"
	);

	public static $definition = array(
		'table' => self::TABLE_NAME,
		'primary' => 'id',
		'multishop'=>true,
		'multilang_shop'=> true,
		'fields' => array(
			'id_order' => array(
				'type' => self::TYPE_INT,
				'required' => true
			),
			'id_shop' => array(
				'type' => self::TYPE_INT,
				'required' => false
			),
			'customer_machine' => array(
				'type' => self::TYPE_STRING,
				'required' => true
			),
			'sender_machine' => array(
				'type' => self::TYPE_STRING,
				'required' => false
			),
			'sticker_file' => array(
				'type' => self::TYPE_STRING,
				'required' => false
			),
			'pack_status' => array(
				'type' => self::TYPE_INT,
				'required' => true
			),
			'cod' => array(
				'type' => self::TYPE_FLOAT,
				'required' => true
			),
			'insurance' => array(
				'type' => self::TYPE_INT,
				'required' => true
			),
			'selfsend' => array(
				'type' => self::TYPE_INT,
				'required' => true
			),
			'customer_phone' => array(
				'type' => self::TYPE_STRING,
				'required' => true
			),
			'customer_email' => array(
				'type' => self::TYPE_STRING,
				'required' => true
			),
			'size' => array(
				'type' => self::TYPE_STRING,
				'required' => true
			),
			'tracking_number' => array(
				'type' => self::TYPE_STRING,
				'required' => false
			),
			'send_code' => array(
				'type' => self::TYPE_STRING,
				'required' => false
			),
			'inpost_status' => array(
				'type' => self::TYPE_STRING,
				'required' => false
			),
			'inpost_status_code' => array(
				'type' => self::TYPE_STRING,
				'required' => false
			)
		)
	);
	public static function updateInpostStatus($packId=false)
	{
		$col = array();
		if($packId == false)
		{
			$sql = "
				SELECT
					*
				FROM
					"._DB_PREFIX_.self::$definition['table']."
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
				$col = self::hydrateCollection(get_class(), $res);
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
		$sql = "SELECT * FROM "._DB_PREFIX_.self::$definition['table']." WHERE id_order=".(int)$orderId;
		$res = Db::getInstance()->executeS($sql);
		if(is_array($res))
		{
			$packs = self::hydrateCollection(get_class(), $res);
			return $packs;
		}
		return false;
	}
}
?>

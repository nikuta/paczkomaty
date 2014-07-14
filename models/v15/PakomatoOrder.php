<?php

class PakomatoOrder extends ObjectModel implements IPakomatoModel
{
	public $id;
	public $id_order;
	public $id_shop;
	public $paczkomat;
	public $customer_phone;
	public $selfsend;
	public $insurance;
	public $cod;
	public $size;
	public $sender_machine;
    public $label_type;
    
    const TABLE_NAME="pakomato_orders";

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
			'paczkomat' => array(
				'type' => self::TYPE_STRING,
				'required' => true
			),
			'customer_phone' => array(
				'type' => self::TYPE_STRING,
				'required' => true
			),
			'selfsend' => array(
				'type' => self::TYPE_INT,
				'required' => false
			),
			'insurance' => array(
				'type' => self::TYPE_INT,
				'required' => false
			),
			'cod' => array(
				'type' => self::TYPE_FLOAT,
				'required' => false
			),
			'size' => array(
				'type' => self::TYPE_STRING,
				'required' => true
			),
			'sender_machine' => array(
				'type' => self::TYPE_STRING,
				'required' => false
			),
            'label_type' => array(
				'type' => self::TYPE_STRING,
				'required' => false
			)
		)
	);
    
    public static function getCreateSql(){
        $sql = "CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_.self::TABLE_NAME."` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `id_order` int(11) NOT NULL,
            `id_shop` int(11) NOT NULL,
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
		$sql = "SELECT * FROM "._DB_PREFIX_.self::$definition['table']." WHERE id_order=".(int)$orderId;
		$res = Db::getInstance()->executeS($sql);
		if(is_array($res) && count($res)>0)
		{
			$pmOrder = new PakomatoOrder();
			$pmOrder->hydrate(array_pop($res));
			return $pmOrder;
		}else return false;
	}
}
?>

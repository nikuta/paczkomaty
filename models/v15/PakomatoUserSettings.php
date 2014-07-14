<?php
//Model dla Prestashop 1.5.x
class PakomatoUserSettings extends ObjectModel implements IPakomatoModel
{
    const TABLE_NAME='pakomato_user_settings';
    const TABLE_ID='id';
    
	public $id;
	public $id_customer;
	public $machine;
	public $machine_cod;
	public $phone;
	public $id_shop;

    public static $definition = array(
		'table' => self::TABLE_NAME,
		'primary' => self::TABLE_ID,
		'multishop'=>true,
		'multilang_shop'=> true,
		'fields' => array(
			'id_customer' => array(
				'type' => self::TYPE_INT,
				'required' => true
			),
			'id_shop' => array(
				'type' => self::TYPE_INT,
				'required' => false
			),
			'machine' => array(
				'type' => self::TYPE_STRING,
				'required' => true
			),
			'machine_cod' => array(
				'type' => self::TYPE_STRING,
				'required' => true
			),
			'phone' => array(
				'type' => self::TYPE_STRING,
				'required' => false
			)
		)
	);

    public static function getCreateSql(){        
        $sql = "CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_.self::TABLE_NAME."` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `id_shop` int(11) NOT NULL,
            `id_customer` int(11) NOT NULL,
            `machine` text NOT NULL,
            `machine_cod` text NOT NULL,
            `phone` varchar(25) DEFAULT NULL,
            PRIMARY KEY (`id`) )";
        return $sql;
    }

	public static function getByCustomerId($customerId)
	{
		$sql = "SELECT * FROM "._DB_PREFIX_.self::TABLE_NAME." WHERE id_customer=".(int)$customerId;
        $me = get_class();
		$res = Db::getInstance()->executeS($sql);
        $ret = self::hydrateCollection(get_class(),$res);
		return $ret;
	}  
}
?>

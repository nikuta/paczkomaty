<?php
if (!defined('_PS_VERSION_'))
    exit;
function upgrade_module_1_1($module) {
    $module->registerHook('displayFooter');
    try{
        $sql[] = "ALTER TABLE `"._DB_PREFIX_."pakomato_orders` ADD COLUMN label_type varchar(20) DEFAULT '".Paczkomat::ETYKIETA_STANDARD."'";
        $sql[] = "ALTER TABLE `"._DB_PREFIX_."pakomato_user_settings` MODIFY COLUMN phone varchar(25) DEFAULT NULL'".Paczkomat::ETYKIETA_STANDARD."'";            
        foreach($sql as $s)
        Db::getInstance()->execute($s);        
    }catch(Exception $ex){
        
    }
  return true; 
}
?>


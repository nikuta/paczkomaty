<?php
global $inpost_data_dir, $inpost_api_url;
$inpost_data_dir = PACZKOMATY_PATH.'inpost/data';
$inpost_api_url  = 'https://api.paczkomaty.pl';

require_once PACZKOMATY_PATH.'inpost/functions.php';
require_once PACZKOMATY_PATH.'inpost/inpost.php';
require_once PACZKOMATY_PATH.'controllers/PakomatoCommon.php';
require_once PACZKOMATY_PATH.'models/Paczkomat.php';
require_once PACZKOMATY_PATH.'models/BindedCarrier.php';
require_once PACZKOMATY_PATH.'models/BindedCod.php';
require_once PACZKOMATY_PATH.'models/v15/PakomatoUserSettings.php';
require_once PACZKOMATY_PATH.'models/v15/PakomatoOrder.php';
require_once PACZKOMATY_PATH.'models/v15/PakomatoPack.php';


class PakomatoBase extends PakomatoCommon implements IPakomatoController{    
    		
	private $urlFopenWarning = 'Uwaga! Przed korzystaniem z modułu należy zmienić konfigurację PHP (php.ini). Opcja "allow_url_fopen" powinna mieć wartość "on". Jest to niezbędne do prawidłowej komunikacji z serwerem InPost. Jeżeli nie wiesz jak to zmienić, skontaktuj się z administratorem serwera.';        
    private static $boxDisplayed = false;
            
    const DOM_SELEKTOR_KURIEROW=".delivery_option_radio";
    const DOM_SELEKTOR_PLATNOSCI="#HOOK_PAYMENT";
    const DOM_SELEKTOR_PRZYCISKU=".cart_navigation input[type='submit']";
    const DOM_BRAK_TELEFONU_KOMUNIKAT="Proszę podać numer telefonu, aby otrzymywać powiadomienia o nadchodzących paczkach z usługi Paczkomaty.pl";
    const DOM_CZAS_KOMUNIKATOW="1";
    const DOM_LOGIN="test@testowy.pl";
    const DOM_HASLO="WqJevQy*X7";
    const DOM_GABARYT="A";
    const DOM_UBEZPIECZENIE="0";
    const DOM_ETYKIETA=Paczkomat::ETYKIETA_STANDARD;
    const DOM_REFETENCJA_TYP_INDEX=true;
    
    public function __construct()
	{	
		$this->tab = 'shipping_logistics';
        parent::__construct();
        $this->mCookie = $this->context->cookie;
        $this->mLink = $this->context->link;
        $this->mSmarty = $this->context->smarty;
	}
    
    private function checkUpgrade(){        
        $this->database_version = Db::getInstance()->getValue("SELECT version FROM `"._DB_PREFIX_."module` WHERE name='".$this->name."'");
        if($this->database_version != $this->version && $this->database_version > 0){            
            $this->checkSelectors();
            $this->installed = true;
            if(Module::initUpgradeModule($this)>0){
                $this->runUpgradeModule();
            }
        }        
    }

    public function install()
	{
        $this->_hooks = array('displayCarrierList','actionValidateOrder','displayAdminOrder','displayBackOfficeHeader','displayFooter');
		$installRes = parent::install();        		
		if (!$installRes){
			return false; }

        $wys = Paczkomat::paczkomatUżytkownika("test@testowy.pl");
		$wysPaczk = Paczkomat::pobierzDanePaczkomatu($wys['glowny']);
                        
        $refl = new ReflectionClass($this);
        foreach($this->_konf as $name=>$value){
            $defName = str_replace("KONFIG_", "DOM_", $name);
            if($refl->hasConstant($defName)){
                Configuration::updateValue(self::KONFIG_PREFIX.$value,$refl->getConstant($defName));
            }
        }
		foreach(array(self::KONFIG_PACZKOMAT_WYSYLKOWY=>base64_encode(serialize($wysPaczk['paczkomat']))) as $key=>$value){
            Configuration::updateValue(self::KONFIG_PREFIX.$key,$value);
        }
		return $installRes;  
        
	}

	public function uninstall()
	{
		parent::uninstall();
        
		foreach($this->_konf as $const=>$name)
		    Configuration::deleteByName(self::KONFIG_PREFIX.$name);
		return true;
	}

	public function getCodList()
	{
		$modules = PaymentModule::getInstalledPaymentModules();
		$binded = unserialize(Configuration::get(self::KONFIG_PREFIX.self::KONFIG_POBRANIE));		
		$list = array();
		foreach( $modules as $key=>$c)
            $list[] = new BindedCod($c['id_module'],$c['name'], ( is_array($binded) && in_array ($c['id_module'], $binded) ? "true" : "false" ));        
		return $list;
	}

	public function getCarriersList()
	{
		$carriers = Carrier::getCarriers($this->context->language->id);
		$binded = unserialize(Configuration::get(self::KONFIG_PREFIX.self::KONFIG_POWIAZANIA_KURIEROW));
		$list = array();
		foreach( $carriers as $key=>$c)
            $list[] = new BindedCarrier($c['id_carrier'], $c['name'],( is_array($binded) && isset( $binded[$c['id_carrier']] ) ? $binded[$c['id_carrier']] : "false") );
		return $list;
	}    

	/* ------------------------ SEKCJA HOOKS -------------------------------- */      
        
	public function hookDisplayBackOfficeHeader($params){
		if(Tools::getValue("pm_ajax")) $this->ajaxAdminPostProcess($params);
	}
    
    public function ajax_getAdminOrderTabAction(){
        $orderId = (int)Tools::getValue("order_id");
        $packs = PakomatoPack::getByOrderId($orderId);
        $this->simpleJson(true, "", array("packsCount"=>count($packs),"tabContent"=>$this->getAdminOrderTab( array("id_order"=>$orderId) )));
    }
    
    public function hookDisplayAdminOrder(){
        return '<script type="text/javascript" src="'._MODULE_DIR_."pakomato/js/p16-admin-order.js".'"></script>';
    }

	public function getAdminOrderTab($params)
	{
        $pmOrder = PakomatoOrder::getByOrderId($params['id_order']);
        if($pmOrder){

            $paczkomat = unserialize(base64_decode($pmOrder->paczkomat));
            $senderMachine = unserialize(base64_decode($pmOrder->sender_machine));
            $this->context->smarty->assign(array(
                'paczkomat'=>$paczkomat,
                'doWys'=> $senderMachine,
                'insurance'=>array("value"=>$pmOrder->insurance,"desc"=>  Paczkomat::$insurance[$pmOrder->insurance]),
                'selfsend'=>(int)$pmOrder->selfsend==1?"true":"false",
                'moduleDir'=>_MODULE_DIR_.$this->name."/",
                'cod_amount'=>$pmOrder->cod>0?$pmOrder->cod:"",
                'cod'=>$pmOrder->cod>0?"true":"false",
                'size'=>array("code"=>$pmOrder->size,"desc"=> $pmOrder->size." - ".Paczkomat::$packSizes[$pmOrder->size]),
                'check' => ini_get('allow_url_fopen'),
                'check_message' => $this->urlFopenWarning,
                'phone'=>$pmOrder->customer_phone,
                'ajax_url'=>$_SERVER['QUERY_STRING']."&id_order=".$params['id_order'],
                "js_url" => _MODULE_DIR_."pakomato/js/pakomato_admin_order.js",
                "css_url" => _MODULE_DIR_."pakomato/css/admin-order.css",
                "etykieta" => $pmOrder->label_type,
                "msgDelay" => Configuration::get(self::KONFIG_PREFIX.self::KONFIG_CZAS_KOMUNIKATOW),
                "presta16" => true,
                "presta14" => false
            ));

            return $this->display(PACZKOMATY_PATH,'adminOrder.tpl');
        }
	}
    
    public function hookDisplayFooter($params){
        if(self::$boxDisplayed){
            return $this->display(PACZKOMATY_PATH,'footer.tpl');
        }
    }

	public function hookActionValidateOrder($params)
	{
        $newOrder = $params['order'];
        $binded = unserialize(Configuration::get(self::KONFIG_PREFIX.self::KONFIG_POWIAZANIA_KURIEROW));

        if(is_array($binded) && isset($binded[$newOrder->id_carrier])){

            $usrList = PakomatoUserSettings::getByCustomerId($newOrder->id_customer);
            $userMachine = $usrList[0]->machine;
            if($binded[$newOrder->id_carrier]=="cod")$userMachine = $usrList[0]->machine_cod;

            if(is_array($usrList) && count($usrList) > 0)
            {
                $bindedCod = unserialize(Configuration::get(self::KONFIG_PREFIX.self::KONFIG_POBRANIE));
                $mod = Module::getInstanceByName($newOrder->module);

                $pakoOrder = new PakomatoOrder();
                $pakoOrder->id_order = $newOrder->id;
                $pakoOrder->paczkomat = $userMachine;
                $pakoOrder->customer_phone = $usrList[0]->phone;
                $pakoOrder->selfsend = Configuration::get(self::KONFIG_PREFIX.self::KONFIG_WYSYLKA_W_PACZKOMACIE);
                $pakoOrder->insurance = Configuration::get(self::KONFIG_PREFIX.self::KONFIG_UBEZPIECZENIE);
                $pakoOrder->size = Configuration::get(self::KONFIG_PREFIX.self::KONFIG_GABARYT);
                $pakoOrder->sender_machine = Configuration::get(self::KONFIG_PREFIX.self::KONFIG_PACZKOMAT_WYSYLKOWY);
                $pakoOrder->label_type = Configuration::get(self::KONFIG_PREFIX.self::KONFIG_ETYKIETA);

                if(is_array($bindedCod)){
                    if(in_array($mod->id, $bindedCod)){
                        $pakoOrder->cod = $newOrder->total_paid;
                    }
                }
                $pakoOrder->save();
            }
        }
		return true;
	}

	public function hookDisplayCarrierList($params)
	{        
        self::$boxDisplayed = true;
        $binded = unserialize(Configuration::get(self::KONFIG_PREFIX.self::KONFIG_POWIAZANIA_KURIEROW));
        $config['binded'] = $binded;
        if(is_array($binded))
        {
            $kodPocztowy = $params['address']->postcode;
            $phone = $params['address']->phone_mobile!=''?$params['address']->phone_mobile:$params['address']->phone;
            $userSettings = PakomatoUserSettings::getByCustomerId($params['cookie']->id_customer);
            $najblizszy = array();
            $najblizszyCod = array();

            if(is_array($userSettings) && count($userSettings) > 0){
                $userSettings = $userSettings[0];                    
                $userMachine = unserialize(base64_decode($userSettings->machine));
                $userMachineCod = unserialize(base64_decode($userSettings->machine_cod));
                if(Paczkomat::czyPaczkomatIstnieje($userMachine['name'],$userMachine['town']) && Paczkomat::czyPaczkomatIstnieje($userMachineCod['name'],$userMachineCod['town']))
                {
                    $najblizszy = $userMachine;
                    $najblizszyCod = $userMachineCod;
                    $phone = $userSettings->phone;
                }else{
                    $najblizszy = Paczkomat::znajdzNajblizszy($kodPocztowy,true);
                    $najblizszyCod = $najblizszy;					
                    $userSettings->machine = base64_encode(serialize($najblizszy));
                    $userSettings->machine_cod = base64_encode(serialize($najblizszy));
                    $userSettings->phone = $phone;
                    $userSettings->save();
                }
            }else{
                $najblizszy = Paczkomat::znajdzNajblizszy($kodPocztowy,true);
                $najblizszyCod = $najblizszy;
                $userSettings = new PakomatoUserSettings();
                $userSettings->id_customer = $params['cookie']->id_customer;
                $userSettings->machine = base64_encode(serialize($najblizszy));
                $userSettings->machine_cod = base64_encode(serialize($najblizszy));
                $userSettings->phone = $phone;
                $userSettings->save();
            }

            $this->context->smarty->assign(array(
                'najblizszy'=>$najblizszy,
                'najblizszy_cod'=>$najblizszyCod,
                'carrier'=>$params['cart']->id_carrier,
                'phone'=>$phone,
                'config'=>  json_encode($config),
                'ajax_url'=>$this->context->link->getModuleLink('pakomato','ajax',array(),Configuration::get('PS_SSL_ENABLED')?true:false),
                "moduleDir" => _MODULE_DIR_.$this->name."/",                    
                "carrier_selector" => Configuration::get(self::KONFIG_PREFIX.self::KONFIG_SELEKTOR_KURIEROW),
                "payment_selector" => Configuration::get(self::KONFIG_PREFIX.self::KONFIG_SELEKTOR_PLATNOSCI),
                "button_selector" => Configuration::get(self::KONFIG_PREFIX.self::KONFIG_SELEKTOR_PRZYCISKU),
                "np_message" => self::DOM_BRAK_TELEFONU_KOMUNIKAT,
                "opc"=>Configuration::get(self::KONFIG_PRESTA_OPC)
            ));
            return $this->display(PACZKOMATY_PATH,"carriersList.tpl");
        }
	}    

	/* ------------------------------ KONIEC HOOKS -------------------------------- */

	public function getContent()
	{
		if(Tools::getValue("pm_ajax")) $this->ajaxAdminPostProcess();
		$this->_html = '<h2>'.$this->displayName.'</h2>';		
		$this->checkUpgrade();
        $assign = array(
            "moduleDir" => _MODULE_DIR_.$this->name."/",
            "sizes"=>  Paczkomat::$packSizes,
            "check" => ini_get('allow_url_fopen')?'':$this->urlFopenWarning,
            "ajax_url" => $_SERVER['QUERY_STRING'],
            "ssl_enabled" => Configuration::get('PS_SSL_ENABLED')?'true':'false',
            "js_url" => _MODULE_DIR_.$this->name."/js/pakomato15.js",
            "css_url" => _MODULE_DIR_.$this->name."/css/admin-form.css",
            "carriers_selector" => Configuration::get(self::KONFIG_PREFIX.self::KONFIG_SELEKTOR_KURIEROW),
            "payments_selector" => Configuration::get(self::KONFIG_PREFIX.self::KONFIG_SELEKTOR_PLATNOSCI),
            "button_selector" => Configuration::get(self::KONFIG_PREFIX.self::KONFIG_SELEKTOR_PRZYCISKU),
            "np_message"=>Configuration::get(self::KONFIG_PREFIX.self::KONFIG_BRAK_TELEFONU_KOMUNIKAT),
            "showReqPhone"=>Configuration::get(self::KONFIG_PRESTA_WYMAGANY_TELEFON)==0?true:false,
            "license_message"=>"",
            "login"=>Configuration::get(self::KONFIG_PREFIX.self::KONFIG_LOGIN),
            "haslo"=>Configuration::get(self::KONFIG_PREFIX.self::KONFIG_HASLO),
            "presta15"=>true
        );
        
        $this->smarty->assign($assign);
        
        $this->_html .= $this->display(PACZKOMATY_PATH,"views/templates/admin/adminForm.tpl");
        $this->getCarriersList();
		return $this->_html;
	}
}
?>

<?php
global $inpost_data_dir, $inpost_api_url;
$inpost_data_dir = PACZKOMATY_PATH.'inpost/data';
$inpost_api_url  = 'http://api.paczkomaty.pl';

require_once PACZKOMATY_PATH.'inpost/functions.php';
require_once PACZKOMATY_PATH.'inpost/inpost.php';
require_once PACZKOMATY_PATH.'controllers/PakomatoCommon.php';
require_once PACZKOMATY_PATH.'models/Paczkomat.php';
require_once PACZKOMATY_PATH.'models/BindedCarrier.php';
require_once PACZKOMATY_PATH.'models/BindedCod.php';
require_once PACZKOMATY_PATH.'models/v14/PakomatoUserSettings.php';
require_once PACZKOMATY_PATH.'models/v14/PakomatoOrder.php';
require_once PACZKOMATY_PATH.'models/v14/PakomatoPack.php';

class PakomatoBase extends PakomatoCommon implements IPakomatoController{    
	private $urlFopenWarning = 'Uwaga! Przed korzystaniem z modułu należy zmienić konfigurację PHP (php.ini). Opcja "allow_url_fopen" powinna mieć wartość "on". Jest to niezbędne do prawidłowej komunikacji z serwerem InPost. Jeżeli nie wiesz jak to zmienić, skontaktuj się z administratorem serwera.';
    private static $boxDisplayed = false;
    protected $smarty;
    protected $cookie;
    protected $link;
    
    const DOM_SELEKTOR_KURIEROW=".carrier_action input";
    const DOM_SELEKTOR_PLATNOSCI="#HOOK_PAYMENT";
    const DOM_SELEKTOR_PRZYCISKU=".cart_navigation input[type='submit']";
    const DOM_BRAK_TELEFONU_KOMUNIKAT="Proszę podać numer telefonu, aby otrzymywać powiadomienia o nadchodzących paczkach z usługi Paczkomaty.pl";
    const DOM_CZAS_KOMUNIKATOW="1";
    const DOM_LOGIN="test@testowy.pl";
    const DOM_HASLO="WqJevQy*X7";
    const DOM_GABARYT="A";
    const DOM_UBEZPIECZENIE="0";
    const DOM_WYSYLKA_W_PACZKOMACIE="1";
    const DOM_ETYKIETA=Paczkomat::ETYKIETA_STANDARD;
    const DOM_REFETENCJA_TYP_INDEX=false;

    public function __construct()
	{				
        global $smarty, $cookie, $link;
        $this->mSmarty = $smarty;
        $this->mCookie = $cookie;
        $this->mLink = $link;
		$this->tab = 'shipping_logistics';
		$this->version = 1.1;
        parent::__construct();
	}
    
    public function install()
	{
        $this->_hooks = array('extraCarrier','header','newOrder','adminOrder','backOfficeHeader','footer');
		$installRes = parent::install();
		if (!$installRes)
			return false;
        
        $refl = new ReflectionClass($this);
        foreach($this->_konf as $name=>$value){
            $defName = str_replace("KONFIG_", "DOM_", $name);
            if($refl->hasConstant($defName)){
                Configuration::updateValue(self::KONFIG_PREFIX.$value,$refl->getConstant($defName));
            }
        }
        
        $wys = Paczkomat::paczkomatUżytkownika(self::DOM_LOGIN);
		$wysPaczk = Paczkomat::pobierzDanePaczkomatu($wys['glowny']);
		foreach(array(self::KONFIG_PACZKOMAT_WYSYLKOWY=>base64_encode(serialize($wysPaczk['paczkomat']))) as $key=>$value){
            Configuration::updateValue(self::KONFIG_PREFIX.$key,$value);
        }
		return $installRes;        
	}	    

	public function uninstall()
	{
		parent::uninstall();
        
		foreach($this->_konf as $k)
		    Configuration::deleteByName(self::KONFIG_PREFIX.$k);
		return true;
	}

	public function getCodList()
	{
		$modules = self::getInstalledPaymentModules();
		$binded = unserialize(Configuration::get(self::KONFIG_PREFIX.self::KONFIG_POBRANIE));		
		$list = array();
		foreach( $modules as $key=>$c)
            $list[] = new BindedCod($c['id_module'],$c['name'], ( is_array($binded) && in_array ($c['id_module'], $binded) ? "true" : "false" ));        
		return $list;
	}

	public function getCarriersList()
	{        
		$carriers = Carrier::getCarriers($this->mCookie->id_lang);
		$binded = unserialize(Configuration::get(self::KONFIG_PREFIX.self::KONFIG_POWIAZANIA_KURIEROW));
		$list = array();
		foreach( $carriers as $key=>$c)
            $list[] = new BindedCarrier($c['id_carrier'], $c['name'],( is_array($binded) && isset( $binded[$c['id_carrier']] ) ? $binded[$c['id_carrier']] : "false") );
		return $list;
	}    

	/* ------------------------ SEKCJA HOOKS -------------------------------- */      
        
	public function hookBackOfficeHeader($params){        
		if(Tools::getValue("pm_ajax")) $this->ajaxAdminPostProcess($params);
	}

    public function hookHeader($params){
        if(Tools::getValue("pm_ajax")) $this->ajaxFrontPostProcess($params);
    }

	public function hookAdminOrder($params)
	{
        $pmOrder = PakomatoOrder::getByOrderId($params['id_order']);

        if($pmOrder){                
            $paczkomat = unserialize(base64_decode($pmOrder->paczkomat));                
            $senderMachine = unserialize(base64_decode($pmOrder->sender_machine));
                $this->mSmarty->assign(array(
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
                    'ajax_url'=>$_SERVER['QUERY_STRING'],
                    "js_url" => _MODULE_DIR_."pakomato/js/pakomato_admin_order.js",
                    "css_url" => _MODULE_DIR_."pakomato/css/admin-order.css",
                    "etykieta" => $pmOrder->label_type,
                    "msgDelay" => Configuration::get(self::KONFIG_PREFIX.self::KONFIG_CZAS_KOMUNIKATOW),
                    "presta14"=>true
                ));

            return $this->display(PACZKOMATY_PATH,'views/templates/hook/adminOrder.tpl');
        }
	}
    
    public function hookFooter($params){
        return $this->display(PACZKOMATY_PATH,'views/templates/hook/footer.tpl');
    }

	public function hookNewOrder($params)
	{        
        $newOrder = $params['order'];
        $binded = unserialize(Configuration::get(self::KONFIG_PREFIX.self::KONFIG_POWIAZANIA_KURIEROW));            
        if(is_array($binded) && isset($binded[$newOrder->id_carrier])){

            $usrList = PakomatoUserSettings::getByCustomerId($newOrder->id_customer);
            $userMachine = $usrList[0]->machine;               
            if($binded[$newOrder->id_carrier]=="cod")$userMachine = $usrList[0]->machine_cod;

            if(is_array($usrList) && count($usrList) > 0)
            {                                        
                $pakoOrder = new PakomatoOrder();                                       
                $pakoOrder->id_order = $newOrder->id;                    
                $pakoOrder->paczkomat = $userMachine;
                $pakoOrder->customer_phone = $usrList[0]->phone;                    
                $pakoOrder->selfsend = Configuration::get(self::KONFIG_PREFIX.self::KONFIG_WYSYLKA_W_PACZKOMACIE);
                $pakoOrder->insurance = Configuration::get(self::KONFIG_PREFIX.self::KONFIG_UBEZPIECZENIE);
                $pakoOrder->size = Configuration::get(self::KONFIG_PREFIX.self::KONFIG_GABARYT);
                $pakoOrder->sender_machine = Configuration::get(self::KONFIG_PREFIX.self::KONFIG_PACZKOMAT_WYSYLKOWY);
                $pakoOrder->label_type = Configuration::get(self::KONFIG_PREFIX.self::KONFIG_ETYKIETA);                   
                $pakoOrder->cod=0;
                //checking if order payment is COD
                $bindedCod = unserialize(Configuration::get(self::KONFIG_PREFIX.self::KONFIG_POBRANIE));
                $mod = Module::getInstanceByName($newOrder->module);
                if(is_array($bindedCod)){
                    if(in_array($mod->id, $bindedCod)){
                        $pakoOrder->cod = (float)$newOrder->total_paid;
                    }
                }                    
                $pakoOrder->save();
            }                
        }
		return false;
	}

	public function hookExtraCarrier($params)
	{                                
			self::$boxDisplayed = true;
			$binded = unserialize(Configuration::get(self::KONFIG_PREFIX.self::KONFIG_POWIAZANIA_KURIEROW));
			$config['binded'] = $binded;            
			if(is_array($binded))
			{
				$kodPocztowy = $params['address']->postcode;
				$phone = $params['address']->phone_mobile!=''?$params['address']->phone_mobile:$params['address']->phone;
				$userSettings = PakomatoUserSettings::getByCustomerId($this->mCookie->id_customer);
                
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
				$this->mSmarty->assign(array(
					'najblizszy'=>$najblizszy,
					'najblizszy_cod'=>$najblizszyCod,
					'carrier'=>$params['cart']->id_carrier,
					'phone'=>$phone,
					'config'=>  json_encode($config),
					'ajax_url'=>$_SERVER['QUERY_STRING']."?pm_ajax=true",
					"moduleDir" => _MODULE_DIR_.$this->name."/",
                    "ssl_enabled" => Configuration::get('PS_SSL_ENABLED')?true:false,
                    "carrier_selector" => Configuration::get(self::KONFIG_PREFIX.self::KONFIG_SELEKTOR_KURIEROW),
                    "payment_selector" => Configuration::get(self::KONFIG_PREFIX.self::KONFIG_SELEKTOR_PLATNOSCI),
                    "button_selector" => Configuration::get(self::KONFIG_PREFIX.self::KONFIG_SELEKTOR_PRZYCISKU),
                    "np_message" => Configuration::get(self::KONFIG_PREFIX.self::KONFIG_BRAK_TELEFONU_KOMUNIKAT),
                    "opc"=>Configuration::get(self::KONFIG_PRESTA_OPC),
                    "presta14"=>true
				));                                
                return $this->display(PACZKOMATY_PATH,"views/templates/hook/carriersList.tpl");
			}
        return '';
	}    

	/* ------------------------------ KONIEC HOOKS -------------------------------- */

	public function getContent()
	{        
		if(Tools::getValue("pm_ajax")) $this->ajaxAdminPostProcess();
		$this->_html = '<h2>'.$this->displayName.'</h2>';		
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
            "haslo"=>Configuration::get(self::KONFIG_PREFIX.self::KONFIG_HASLO)
        );
        foreach ($this->_konf as $k=>$v)
            $assign[$k] = Tools::getValue(self::KONFIG_PREFIX.$k,Configuration::get(self::KONFIG_PREFIX.$k));
        $this->mSmarty->assign($assign);
        
        $this->_html .= $this->display(PACZKOMATY_PATH,"views/templates/admin/adminForm.tpl");
        $this->getCarriersList();
		return $this->_html;
	}
    
    public static function getInstalledPaymentModules()
	{
		return Db::getInstance()->executeS('
		SELECT DISTINCT m.`id_module`, h.`id_hook`, m.`name`, hm.`position`
		FROM `'._DB_PREFIX_.'module` m
		LEFT JOIN `'._DB_PREFIX_.'hook_module` hm ON hm.`id_module` = m.`id_module`
		LEFT JOIN `'._DB_PREFIX_.'hook` h ON hm.`id_hook` = h.`id_hook`
		WHERE h.`name` = \'payment\'
		AND m.`active` = 1
		');
	}
}

?>
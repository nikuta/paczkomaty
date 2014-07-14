<?php
interface IPakomatoModel{    
    public static function getCreateSql();
}
interface IPakomatoController{    
    public function getCarriersList();
    public function getCodList();
    public function getContent();
    public function install();
    public function uninstall();    
}

abstract class PakomatoCommon extends Module{
    
    const NAZWA_MODULU="pakomato";
        
    const KONFIG_ETYKIETA="typ_etykiety";
    const KONFIG_LOGIN="login";
    const KONFIG_HASLO="haslo";
    const KONFIG_GABARYT="dom_gabaryt";
    const KONFIG_UBEZPIECZENIE="dom_ubezp";
    const KONFIG_PACZKOMAT_WYSYLKOWY="wys_paczkomat";
    const KONFIG_POWIAZANIA_KURIEROW="powiazania";
    const KONFIG_PREFIX="pakomato_";
    const KONFIG_POBRANIE="cod";
    const KONFIG_WYSYLKA_W_PACZKOMACIE="selfsend";
    const KONFIG_CZAS_KOMUNIKATOW="komun_czas";
    const KONFIG_DANE_NADAWCY="dane_nadawcy";
    const KONFIG_SELEKTOR_KURIEROW="lista_kurierow";
    const KONFIG_SELEKTOR_PLATNOSCI="lista_platnosci";
    const KONFIG_SELEKTOR_PRZYCISKU="przycisk_dalej";
    const KONFIG_BRAK_TELEFONU_KOMUNIKAT="brak_tel_komunikat";
    const KONFIG_PRESTA_WYMAGANY_TELEFON="PS_ONE_PHONE_AT_LEAST";
    const KONFIG_PRESTA_OPC="PS_ORDER_PROCESS_TYPE";
    const KONFIG_REFETENCJA_TYP_INDEX="ref_index";
    
    public $name = self::NAZWA_MODULU;
	protected $_html = '';
	protected $_postErrors = array();
	protected $_moduleName = self::NAZWA_MODULU;
    protected $_konf = array();
    protected $psVersion;
    protected $_hooks = array();
    
    
    protected $mCookie;
    protected $mLink;
    protected $mSmarty;


    public function __construct(){
        $this->version = 1.2;
        $this->displayName = $this->l('Paczkomaty 24/7');
        $this->author = 'Prestasolutions.pl';
        $this->psVersion = implode(".",array_slice(explode(".",_PS_VERSION_),0,2));
        parent::__construct();        
        $reflection = new ReflectionClass($this);
        $const = $reflection->getConstants();
        foreach($const as $name=>$value){
            if(preg_match("/KONFIG_*/", $name)){
                $this->_konf[$name] = $value;
            }
        }
        
    }
    
    public function install(){
        $res = parent::install();
        $res = $res && $this->attachToHooks();
        $this->createDbTables();
        return $res;
    }

    /**
	 * Tworzymy tabele w bazie danych podczas instalacji
	 */
	protected function createDbTables()
	{   
        $sql = array();       
        $sql[] = PakomatoOrder::getCreateSql();
        $sql[] = PakomatoPack::getCreateSql();
        $sql[] = PakomatoUserSettings::getCreateSql();
                
		foreach($sql as $s)
			Db::getInstance()->execute($s);
	}
    
    protected function attachToHooks(){        
        $installRes = true;
        foreach($this->_hooks as $hook){
			$installRes = ($installRes && $this->registerHook($hook));
        }        
        return $installRes;
    }


    private function checkSelectors(){
        $carrier = Configuration::get(self::KONFIG_PREFIX.self::KONFIG_SELEKTOR_KURIEROW);
        $payment = Configuration::get(self::KONFIG_PREFIX.self::KONFIG_SELEKTOR_PLATNOSCI);
        $button = Configuration::get(self::KONFIG_PREFIX.self::KONFIG_SELEKTOR_PRZYCISKU);
        
        if($carrier=="")Configuration::updateValue(self::KONFIG_PREFIX.self::KONFIG_SELEKTOR_KURIEROW,pSQL(self::DOMYSLNY_SELEKTOR_KURIEROW));
        if($payment="")Configuration::updateValue(self::KONFIG_PREFIX.self::KONFIG_SELEKTOR_PLATNOSCI,pSQL(self::DOMYSLNY_SELEKTOR_PLATNOSCI));
        if($button="")Configuration::updateValue(self::KONFIG_PREFIX.self::KONFIG_SELEKTOR_PRZYCISKU,pSQL(self::DOMYSLNY_SELEKTOR_PRZYCISKU));
    }
    
    /* ------------------------------ SEKCJA AJAX --------------------------------*/
        
    protected function ajaxAdminPostProcess($params=null)
	{            
        $action = Tools::getValue("action");
        $action = "ajax_".$action."Action";
        if(method_exists($this, $action)){
            $reflection = new ReflectionMethod($this,$action);                       
            if($reflection->isPublic() || $reflection->isProtected() ){
                $this->$action($params);
            }else{
                $this->jsonResponse(array("result"=>"error","message"=>"Nie masz prawa korzystać z tej metody!!"));
            }
        }else{
            $this->jsonResponse(array("result"=>"error","message"=>"Funkcja nie istnieje!!"));
        }
        exit();
	}
    
    public function ajaxFrontPostProcess($params=null){
        $action = Tools::getValue("action");
        $action = "ajax_".$action."Action";
        if(method_exists($this, $action)){
            $reflection = new ReflectionMethod($this,$action);
            if($reflection->isPublic()){
                $this->$action($params);
            }else{
                $this->jsonResponse(array("result"=>"error","message"=>"Nie masz prawa korzystać z tej metody!!"));
            }
        }else{
            $this->jsonResponse(array("result"=>"error","message"=>"Funkcja nie istnieje!!"));
        }
        exit();
    }
    
    protected function ajax_forceUpgradeAction(){
        $version = (float)Tools::getValue("upgrade_version");
        if(file_exists(PACZKOMATY_PATH."upgrade/Upgrade-".$version.".php")){
            include PACZKOMATY_PATH."upgrade/Upgrade-".$version.".php";
            $function = "upgrade_module_".str_replace(".","_", $version);
            if(function_exists($function)){
                $function($this);
                $this->simpleJson(true,"Skrypt został wykonany");
            }   
        }
        $this->simpleJson (false,"Podana wersja skryptu nie istnieje");
    }
    
    protected function ajax_saveNoPhoneMessageAction(){
        $msg = pSQL(Tools::getValue("message"));
        Configuration::updateValue(self::KONFIG_PREFIX.self::KONFIG_BRAK_TELEFONU_KOMUNIKAT,$msg);
        $this->simpleJson(true,"Wiadomość dla klienta została zmieniona");
    }
    
    protected function ajax_turnOnPhoneReqAction(){
        Configuration::updateValue(self::KONFIG_PRESTA_WYMAGANY_TELEFON,1);
        $this->simpleJson(true,"Opcja konfiguracyjna Prestashop 'Preferencje/Klienci/Numer Telefonu' została włączona");
    }
    
    protected function ajax_saveButtonSelectorAction(){
        $new = pSQL(Tools::getValue("newSelector"));
        Configuration::updateValue(self::KONFIG_PREFIX.self::KONFIG_SELEKTOR_PRZYCISKU,$new);
        $this->simpleJson(true,"Selektor przycisku został zapisany (".$new.")");
    }
    
    protected function ajax_saveCarriersSelectorAction(){
        $new = pSQL(Tools::getValue("newSelector"));
        Configuration::updateValue(self::KONFIG_PREFIX.self::KONFIG_SELEKTOR_KURIEROW,$new);
        $this->simpleJson(true,"Selektor listy kurierów został zapisany (".$new.")");
    }
    
    protected function ajax_savePaymentsSelectorAction(){
        $new = pSQL(Tools::getValue("newSelector"));
        Configuration::updateValue(self::KONFIG_PREFIX.self::KONFIG_SELEKTOR_PLATNOSCI,$new);
        $this->simpleJson(true,"Selektor listy płatności został zapisany (".$new.")");
    }


    protected function ajax_resetCacheAction(){
        $dir = PACZKOMATY_PATH.'inpost/data/';        
        inpost_download_machines();
        inpost_download_pricelist();
        $this->simpleJson(true,"Lista paczkomatów oraz cennik zostały zaktualizowane");
    }
    
    protected function ajax_setSenderDataAction(){
        $data = (Tools::getValue("data"));
        foreach($data as $k=>$v){
            $data[pSQL($k)]=pSQL($v);
        }
        
        Configuration::updateValue(self::KONFIG_PREFIX.self::KONFIG_DANE_NADAWCY,  base64_encode(serialize($data)));
        $this->simpleJson(true,"Dane nadawcy zostały zapisane",$data);
    }
    
    protected function ajax_getSenderDataAction(){
        $this->simpleJson(true,"",unserialize(base64decode(Configuration::get(self::KONFIG_PREFIX.self::KONFIG_DANE_NADAWCY))));
    }
    
    protected function ajax_setMessageTimeAction(){
        $newTime = (int)Tools::getValue("newTime");
        Configuration::updateValue(self::KONFIG_PREFIX.self::KONFIG_CZAS_KOMUNIKATOW,$newTime);
        $this->simpleJson(true,"Czas wyświetlania komunikatów został zmieniony na ".$newTime." sek.");
    }
    
    protected function ajax_switchLabelTypeAction(){
        $staryTyp = Configuration::get(self::KONFIG_PREFIX.self::KONFIG_ETYKIETA);
        $nowyTyp = Paczkomat::ETYKIETA_STANDARD;
        if($staryTyp == Paczkomat::ETYKIETA_STANDARD)
            $nowyTyp = Paczkomat::ETYKIETA_A6P;
        Configuration::updateValue(self::KONFIG_PREFIX.self::KONFIG_ETYKIETA,$nowyTyp);
        $this->simpleJson(true,"Domyślny typ etykiety został zmieniony na ".Paczkomat::opisRozmiaruEtykiety($nowyTyp),$nowyTyp);
    }
    
    protected function ajax_switchOrderLabelTypeAction(){        
        $pmOrder = PakomatoOrder::getByOrderId(Tools::getValue("id_order"));
        if($pmOrder->label_type=='' || $pmOrder->label_type == Paczkomat::ETYKIETA_STANDARD){
            $pmOrder->label_type = Paczkomat::ETYKIETA_A6P;
        }else{
            $pmOrder->label_type = Paczkomat::ETYKIETA_STANDARD;
        }
        $pmOrder->save();
        $this->simpleJson(
            true, 
            "Typ etykiety dla tego zlecenia został ustawiony na: ".Paczkomat::opisRozmiaruEtykiety($pmOrder->label_type), 
            array("newSize"=>$pmOrder->label_type,"newDescription"=>  Paczkomat::opisRozmiaruEtykiety($pmOrder->label_type))
        );
    }

	protected function ajax_setOrderSizeAction()
	{
		$newSize = Tools::getValue("newSize");
		if(key_exists($newSize, Paczkomat::$packSizes))
		{
			$orderId = (int)Tools::getValue("id_order");
			$pmOrd = PakomatoOrder::getByOrderId($orderId);
			$pmOrd->size = $newSize;
			$pmOrd->save();
			$this->jsonResponse(array("result"=>"ok","message"=>"Gabaryt Dla tego zamówienia został ustawiony na ".$newSize." - ".Paczkomat::$packSizes[$newSize]));
		}else{
			$this->jsonResponse(array("result"=>"error","message"=>"Niepoprawna nowa wartość gabarytu paczki"));
		}
	}

	protected function ajax_setOrderPhoneAction()
	{
		$orderId = (int)Tools::getValue("id_order");
		$phone = pSQL(Tools::getValue('newPhone'));        
		if(preg_match("/^([0-9]{7,9})$/", $phone)){
			$pmOrder = PakomatoOrder::getByOrderId($orderId);
			$pmOrder->customer_phone = $phone;
			$pmOrder->save();
			$this->jsonResponse(array("result"=>"ok","message"=>"Telefon klienta dla tego zamówienia został ustawiony na ".$phone));
		}else{
			$this->jsonResponse(array("result"=>"error","message"=>"Niepoprawny nowy telefon"));
		}
	}

	protected function ajax_cancelJobAction(){
		$login = Configuration::get(self::KONFIG_PREFIX.self::KONFIG_LOGIN);
		$pass = Configuration::get(self::KONFIG_PREFIX.self::KONFIG_HASLO);
		$packId = (int)Tools::getValue("packId");
		$pack = new PakomatoPack($packId);

		$res = Paczkomat::anulujZlecenie($login, $pass, $pack->tracking_number);
		if($res['result']=="ok")
		{
			$pack->pack_status = PakomatoPack::JOB_CANCELED;
			$pack->save();
			PakomatoPack::updateInpostStatus($pack->id);
		}
		$this->jsonResponse($res);
	}

	protected function ajax_generateStickerAction(){
		$login = Configuration::get(self::KONFIG_PREFIX.self::KONFIG_LOGIN);
		$pass = Configuration::get(self::KONFIG_PREFIX.self::KONFIG_HASLO);        
        $pmOrder = PakomatoOrder::getByOrderId(Tools::getValue("id_order"));        
        
		$packId = (int)Tools::getValue("packId");
		if($packId>0)
		{
			$pack = new PakomatoPack($packId);
			if(strlen($pack->tracking_number)>0){
				$res = Paczkomat::pobierzEtykietę($login, $pass, $pack->tracking_number, $pmOrder->label_type);
				if($res['result']=="ok"){
					$fileName = md5(time().$pack->tracking_number).".pdf";
					$fp = fopen(PACZKOMATY_PATH."stickers/".$fileName,"w");
					fwrite($fp, $res['sticker']);
					fclose($fp);
					$pack->sticker_file = $fileName;
					$pack->pack_status = PakomatoPack::STICKER_CREATED;
					$pack->save();
					PakomatoPack::updateInpostStatus($pack->id);
					$this->jsonResponse(array("result"=>"ok","message"=>"Etykieta została wygenerowana"));
				}else $this->jsonResponse ($res);
			}else $this->jsonResponse (array("result"=>"error","message"=>"Niepoprawny numer paczki"));
		}else $this->jsonResponse (array("result"=>"error","message"=>"Niepoprawny identyfikator paczki"));
	}

	protected function ajax_getPackInfoAction()
	{
		$packId = (int)Tools::getValue("packId");
		$pack = new PakomatoPack($packId);

		$ret = array(
			'customer_machine'=>unserialize(base64_decode($pack->customer_machine)),
			'customer_phone'=>$pack->customer_phone,
			'customer_email'=>$pack->customer_email,
			'selfsend'=>$pack->selfsend,
			'cod'=>$pack->cod,
			'insurance'=>  $pack->insurance,
			'insurance_desc'=>  Paczkomat::$insurance[$pack->insurance],
			'size'=> $pack->size,
			'size_desc' => Paczkomat::$packSizes[$pack->size],
			'status' => $pack->pack_status,
			'status_desc'=>  PakomatoPack::$status[$pack->pack_status],
			'inpost_status'=> $pack->inpost_status
		);
		if($pack->selfsend>0){
			$ret['send_code'] = $pack->send_code;
			$ret['sender_machine'] = unserialize(base64_decode($pack->sender_machine));
		}
		$this->jsonResponse(array(
			"result"=>"ok",
			"pack"=>$ret
		));
	}

	protected function ajax_getPacksAction(){
		$orderId = (int)Tools::getValue("id_order");
		$packs = PakomatoPack::getByOrderId($orderId);        
		if(is_array($packs) && count($packs)>0){
			foreach($packs as $p){
				$ret[] = array(
					"id"=>$p->id,
					"track"=>$p->tracking_number,
					"status"=>$p->pack_status,
					"status_desc"=>  PakomatoPack::$status[$p->pack_status],
					"file"=>$p->sticker_file,
					"inpost_status"=>$p->inpost_status
				);
			}

			$this->jsonResponse(array(
				"result"=>"ok",
				"packs"=>$ret
			));
		}else{
			$this->jsonResponse(array(
				"result"=>"empty"
			));
		}
	}

	protected function ajax_createPackageAction(){
		$orderId = (int)Tools::getValue("id_order");
		$order = new Order($orderId);
		$customer = new Customer($order->id_customer);
		$emp = new Employee($this->mCookie->id_employee);
		$pmOrder = PakomatoOrder::getByOrderId($orderId);
		$machine = unserialize(base64_decode($pmOrder->paczkomat));
		$pmPack = new PakomatoPack();
        
		$pmPack->customer_email = $customer->email;
		$pmPack->customer_phone = $pmOrder->customer_phone;
		$pmPack->id_order = $orderId;
		$pmPack->insurance = $pmOrder->insurance;
		$pmPack->pack_status = 0;
		$pmPack->selfsend = $pmOrder->selfsend;
		$pmPack->sticker_file = '';
		$pmPack->cod = $pmOrder->cod;
		$pmPack->size = $pmOrder->size;        

		//sprawdzanie paczkomatu klienta
		if(Paczkomat::czyPaczkomatIstnieje($machine['name'],$pmPack->cod>0?true:false)){
			$pmPack->customer_machine = $pmOrder->paczkomat;
		}else{
			if($pmPack->cod>0)$this->jsonResponse(array("result"=>"error","message"=>"Wybrany przez kienta paczkomat '".$machine['name']."' nie obsługuje pobrania. Paczka nie została utworzona"));
			else $this->jsonResponse(array("result"=>"error","message"=>"Wybrany przez klienta paczkomat '".$machine['name']."' nie istnieje w systemie Inpost. Paczka nie została utworzona"));
		}

		$senderMachineCode = '';
		//sprawdzanie paczkomatu do wysyłki
		if($pmPack->selfsend>0){
			$sendMachine = unserialize(base64_decode($pmOrder->sender_machine));
			$senderMachineCode = $sendMachine['name'];
			$send = Paczkomat::pobierzDanePaczkomatu($senderMachineCode);
			if($send['result']=="ok"){
				$pmPack->sender_machine = base64_encode(serialize($send['paczkomat']));
			}else{
				$this->jsonResponse(array("result"=>"error","message"=>"Wybrany paczkomat do wysyłki '".$senderMachineCode."' nie istnieje w systemie Inpost. Paczka nie została utworzona"));
			}
		}else{
			$pmPack->sender_machine = "";
		}

        $ref = "Zamówienie nr: ".sprintf(" %06d",$orderId);
        if(Configuration::get(self::KONFIG_PREFIX.self::KONFIG_REFETENCJA_TYP_INDEX)==1 && isset($order->reference)){
            $ref = "Zamówienie: ".$order->reference;            
        }
        
		$config = array(
			'customer_email'=>$pmPack->customer_email,
			'customer_phone'=>$pmPack->customer_phone,
			'customer_machine'=>$machine['name'],
			'size'=>$pmPack->size,
			'insurance'=>$pmPack->insurance,
			'cod'=>$pmPack->cod,
			'selfsend'=>$pmOrder->selfsend,
			'order_id'=>$ref,
			'sender_fname'=>$emp->firstname,
			'sender_lname'=>$emp->lastname,
			'sender_email'=>Configuration::get($this->name."_login"),
			'sender_login'=>Configuration::get($this->name."_login"),
			'sender_pass'=>Configuration::get($this->name."_haslo"),
			'sender_machine'=>$senderMachineCode,
            'sender_data'=>  unserialize(base64_decode(Configuration::get(self::KONFIG_PREFIX.self::KONFIG_DANE_NADAWCY)))
		);

		$res = Paczkomat::wygenerujPaczke($config);
		if($res['result']=="ok"){
			$pmPack->tracking_number = $res['createdPack']['packcode'];
			if($pmPack->selfsend>0)
				$pmPack->send_code = $res['createdPack']['customerdeliveringcode'];
			$pmPack->save();            
			//PakomatoPack::updateInpostStatus($pmPack->id);
		}
		$this->jsonResponse($res);
	}

	protected function ajax_setDefaultSelfsendAction(){
		$oldVal = Tools::getValue("oldVal");
		$newVal = ($oldVal=="true"?false:true);
		Configuration::updateValue($this->name."_selfsend",$newVal);
		$this->jsonResponse(array("result"=>"ok","message"=>"Domyślna wysyłka w paczkomacie została ".($newVal==true?"włączona.":"wyłączona."),"newSelfsend"=>$newVal));
	}

	protected function ajax_updateOrderCodAmountAction()
	{
		$orderId = (int)Tools::getValue("id_order");
		$newAmount = (float)Tools::getValue("amount");
		if($newAmount > 0)
		{
			$pmOrder = PakomatoOrder::getByOrderId($orderId);
			$pmOrder->cod = $newAmount;
			$pmOrder->save();
			$this->jsonResponse(array("result"=>"ok","message"=>"Kwota pobrania została ustawiona na: ".$pmOrder->cod."zł"));
		}  else {
			$this->jsonResponse(array("result"=>"error","message"=>"Kwota pobrania musi być wyższa niż 0zł"));
		}
	}

	protected function ajax_setOrderCodAction(){
		$orderId = (int)Tools::getValue("id_order");
		$oldState = Tools::getValue("cod")=="true"?true:false;
		$pmOrder = PakomatoOrder::getByOrderId($orderId);

		if($oldState){
			$pmOrder->cod = 0;
			$pmOrder->save();
			$this->jsonResponse(array("result"=>"ok","message"=>"Pobranie zostało wyłączone w tym zamówieniu","newCodState"=>!$oldState,"amount"=>0));
		}else{
			$order = new Order($orderId);
			$pmOrder->cod = $order->total_paid;
			$pmOrder->save();
			$this->jsonResponse(array("result"=>"ok","message"=>"Pobranie zostało załączone w tym zamówieniu i ustawione na kwotę: ".$pmOrder->cod."zł","newCodState"=>!$oldState,"amount"=>$pmOrder->cod));
		}
	}

	protected function ajax_getOrderCodAction()
	{
		$orderId = (int)Tools::getValue("id_order");
		$order = new Order($orderId);
		$this->jsonResponse(array("result"=>"ok","message"=>print_r($order,true)));
	}

	protected function ajax_setOrderCustomerMachineAction(){
		$orderId = (int)Tools::getValue("id_order");
		$newCode = Tools::getValue("newCode");        
		$res = Paczkomat::pobierzDanePaczkomatu($newCode);        
		if($res['result']=="ok"){
			$pmOrder = PakomatoOrder::getByOrderId($orderId);
			$pmOrder->paczkomat = base64_encode(serialize($res['paczkomat']));            
			$pmOrder->save();
			$this->jsonResponse(array("result"=>"ok","paczkomat"=>$res['paczkomat'],"message"=>"Paczkomat klienta do tego zamówienia został ustawiony na ".$res['paczkomat']['name']));
		}else {
			$this->jsonResponse(array("result"=>"error","message"=>"Wybrany paczkomat nie istnieje lub jest nieczynny"));
		}
	}
	protected function ajax_setOrderSenderMachineAction(){
		$orderId = (int)Tools::getValue("id_order");
		$newCode = Tools::getValue("newCode");
		$res = Paczkomat::pobierzDanePaczkomatu($newCode);
		if($res['result']=="ok"){
			$pmOrder = PakomatoOrder::getByOrderId($orderId);
			$pmOrder->sender_machine = base64_encode(serialize($res['paczkomat']));
			$pmOrder->save();

			$this->jsonResponse(array("result"=>"ok","paczkomat"=>$res['paczkomat'],"message"=>"Paczkomat wysyłki do tego zamówienia został ustawiony na ".$res['paczkomat']['name']));
		}else {
			$this->jsonResponse(array("result"=>"error","message"=>"Wybrany paczkomat nie istnieje lub jest nieczynny"));
		}
	}

	protected function ajax_setOrderInsuranceAction(){
		$newIns = (int)Tools::getValue('newInsurance');
		$orderId = (int)Tools::getValue('id_order');

		if(key_exists($newIns,Paczkomat::$insurance)){
			$pmOrder = PakomatoOrder::getByOrderId($orderId);
			$pmOrder->insurance = $newIns;
			$pmOrder->save();
			$this->jsonResponse(array("result"=>"ok","newInsurance"=>$newIns,"message"=>"Ubezpieczenie paczki dla tego zamówienia zostało ustawione na kwotę ".Paczkomat::$insurance[$newIns]));
		}else{
			$this->jsonResponse(array("result"=>"error","message"=>"Nie można ustawić takiej wartości dla ubezpieczenia. Dozwolone są wartości: 0, 5000, 10000, 20000"));
		}

	}

	protected function ajax_setOrderSelfsendAction($params)
	{
		$orderId = (int)Tools::getValue("id_order");
		$selfsend = Tools::getValue('selfsend')=="true"?false:true;

		$pmOrder = PakomatoOrder::getByOrderId($orderId);
		$pmOrder->selfsend = (int)$selfsend;
		$pmOrder->save();

		$this->jsonResponse(array("result"=>"ok","newSelfsend"=>$selfsend));
	}

	public function ajax_updateUserMachineAction(){
		$newCode = Tools::getValue("newCode");        
		$cod = Tools::getValue("codMachine")=="true"?true:false;

		$res = Paczkomat::pobierzDanePaczkomatu($newCode);
		if($res['result']=="ok"){
			$userConfList = PakomatoUserSettings::getByCustomerId($this->mCookie->id_customer);
			if(is_array($userConfList) && count($userConfList)>0)
			{
				if($cod)
					$userConfList[0]->machine_cod = base64_encode(serialize($res['paczkomat']));
				else
					$userConfList[0]->machine = base64_encode(serialize($res['paczkomat']));
				$userConfList[0]->save();
			}
			$this->jsonResponse(array("result"=>"ok","paczkomat"=>$res['paczkomat'],"message"=>"Paczkomat do odbierania przesyłek został zmieniony"));
		}else {
			$this->jsonResponse(array("result"=>"error","message"=>"Wybrany paczkomat nie istnieje lub jest nieczynny"));
		}
	}

	public function ajax_updateUserPhoneAction(){
		$phone = str_replace(' ', '', trim(Tools::getValue('newPhone')));
		if(preg_match('/^[+]?([0-9]?)[(|s|-|.]?([0-9]{3})[)|s|-|.]*([0-9]{3})[s|-|.]*([0-9]{3,4})$/', $phone))
		{                        
			$usrList = PakomatoUserSettings::getByCustomerId($this->mCookie->id_customer);            
			if(is_array($usrList) && count($usrList)>0){
                //z jakiegoś powodu inaczej nie chce działać 
                $conf = new PakomatoUserSettings($usrList[0]->id);
                $conf->phone = $phone;
                $conf->save();               
				$this->jsonResponse(array("result"=>"ok","message"=>"Nowy numer do otrzymywania powiadomień to: ".$phone,"newPhone"=>$phone));
			}else{
				$this->jsonResponse(array("result"=>"error","message"=>"Nie udało się zapisać nowego numeru telefonu"));
			}
		}  else {
			$this->jsonResponse(array("result"=>"error","message"=>"Podany numer (".addslashes($phone).") jest nieprawdłowy"));
		}

	}

	protected function ajax_setDefaultInsuranceAction()
	{
		$newIns = Tools::getValue("insurance");
		if($newIns == "0" || $newIns == "5000" || $newIns == "10000" || $newIns == "20000")
		{
			Configuration::updateValue($this->name.'_dom_ubezp',$newIns);
			$this->jsonResponse(array(
				"result"=>"ok",
				"message"=>"Domyślne ubezpieczenie przesyłki zostało ustawione na ".$newIns
			));
		}else
			$this->jsonResponse(array(
				"result"=>"error",
				"message"=>"Błędny symbol domyślnego ubezpieczenia. Dozwolone są tylko wartości '0','5000, '10000' oraz '20000'"
			));
	}

	protected function ajax_getInsurancesAction()
	{
		$insur = Paczkomat::$insurance;
		$this->jsonResponse(array(
			"result"=>"ok",
			"list"=>$insur ));
	}

	protected function ajax_setDefaultSizeAction()
	{
		$newSize = pSQL(Tools::getValue("size"));
		if($newSize == "A" || $newSize == "B" || $newSize == "C")
		{
			Configuration::updateValue($this->name.'_dom_gabaryt',$newSize);
			$this->jsonResponse(array(
				"result"=>"ok",
				"message"=>"Domyślny gabaryt przesyłki został ustawiony (".$newSize.")"
			));
		}else
			$this->jsonResponse(array(
				"result"=>"error",
				"message"=>"Błędny symbol nowego gabarytu. Dozwolone są tylko wartości 'A','B' oraz 'C'"
			));
	}
        
	protected function ajax_getSizesAction()
	{
		$sizes = Paczkomat::$packSizes;
		$this->jsonResponse(array(
			"result"=>"ok",
			"list"=>$sizes ));
	}       

	protected function ajax_bindCarrierAction()
	{
		$carrierId = (int)Tools::getValue("carrierId");
		$binded = unserialize(Configuration::get($this->name."_powiazania"));
		if(!is_array($binded))$binded = array();
		$binded[$carrierId] = "true";
		Configuration::updateValue($this->name."_powiazania",serialize($binded));

		$this->jsonResponse(array(
			"result"=>"ok",
			"message"=>"Powiązano moduł z kurierem ( wszystkie paczkomaty )"
		));
	}

	protected function ajax_bindCarrierCodAction(){
		$carrierId = (int)Tools::getValue("carrierId");
		$binded = unserialize(Configuration::get($this->name."_powiazania"));
		if(!is_array($binded))$binded = array();
		$binded[$carrierId] = "cod";
		Configuration::updateValue($this->name."_powiazania",serialize($binded));

		$this->jsonResponse(array(
			"result"=>"ok",
			"message"=>"Powiązano moduł z kurierem ( tylko paczkomaty pobraniowe )"
		));
	}

	protected function ajax_unbindCarrierAction()
	{
		$carrierId = Tools::getValue("carrierId");
		$binded = unserialize(Configuration::get($this->name."_powiazania"));
		if(!is_array($binded))$binded = array();
		unset($binded[$carrierId]);
		Configuration::updateValue($this->name."_powiazania",serialize($binded));
		$this->jsonResponse(array(
			"result"=>"ok",
			"message"=>"Usunięto powiązanie modułu z kurierem"
		));

	}

	protected function ajax_bindCodAction()
	{
		$codId = (int)Tools::getValue("codId");
		$binded = unserialize(Configuration::get($this->name."_cod"));
		if(!is_array($binded))$binded = array();
		if(!in_array($codId, $binded))
			array_push ($binded, $codId);
		Configuration::updateValue($this->name."_cod",serialize($binded));

		$this->jsonResponse(array(
			"result"=>"ok",
			"message"=>"Płatność została zaznaczona jako pobraniowa"
		));
	}

	protected function ajax_unbindCodAction()
	{
		$codId = Tools::getValue("codId");
		$binded = unserialize(Configuration::get($this->name."_cod"));
		if(!is_array($binded))$binded = array();
		if(in_array($codId, $binded)){
			$key = array_search($codId, $binded);
			unset($binded[$key]);
		}
		Configuration::updateValue($this->name."_cod",serialize($binded));
		$this->jsonResponse(array(
			"result"=>"ok",
			"message"=>"Usunięto zaznaczenie płatności jako pobraniowej"
		));

	}
    
    protected function ajax_changeRefTypeAction(){
        $isIndex = Tools::getValue("newValue")=="true"?true:false;
        Configuration::updateValue(self::KONFIG_PREFIX.self::KONFIG_REFETENCJA_TYP_INDEX,$isIndex);
        $this->simpleJson(true,"Pole referencja na etykiecie będzie zawierało ".($isIndex?"indeks zamówienia":"numer zamówienia"),array("new_value"=>$isIndex?1:0));
    }

    protected function ajax_getConfigAction()
	{                
		$wys = unserialize(base64_decode(Configuration::get(self::KONFIG_PREFIX.self::KONFIG_PACZKOMAT_WYSYLKOWY)));        
		$ret = array(
			'wys_paczkomat' => $wys['name'],
			'def_size' => array( 'id'=> Configuration::get(self::KONFIG_PREFIX.self::KONFIG_GABARYT), 'desc'=>  Paczkomat::$packSizes[Configuration::get(self::KONFIG_PREFIX.self::KONFIG_GABARYT)]),
			'def_insur' => array('id'=> Configuration::get(self::KONFIG_PREFIX.self::KONFIG_UBEZPIECZENIE), 'desc' => Paczkomat::$insurance[Configuration::get(self::KONFIG_PREFIX.self::KONFIG_UBEZPIECZENIE)]),
			'carriers' => $this->getCarriersList(),
			'selfsend' => (bool)Configuration::get(self::KONFIG_PREFIX.self::KONFIG_WYSYLKA_W_PACZKOMACIE),
			'cod'=>$this->getCodList(),
            'label_type'=>Configuration::get(self::KONFIG_PREFIX.self::KONFIG_ETYKIETA),
            'message_time'=>Configuration::get(self::KONFIG_PREFIX.self::KONFIG_CZAS_KOMUNIKATOW),
            'sender_config'=>  unserialize(base64_decode(Configuration::get(self::KONFIG_PREFIX.self::KONFIG_DANE_NADAWCY))),
            'wymagany_telefon' => Configuration::get(self::KONFIG_PREFIX.self::KONFIG_PRESTA_WYMAGANY_TELEFON)==0?'true':'false',
            'ref_typ'=>Configuration::get(self::KONFIG_PREFIX.self::KONFIG_REFETENCJA_TYP_INDEX)
		);        
		$this->jsonResponse(array("result"=>"ok","config"=>$ret));
	}

	protected function ajax_savePaczkomatAction()
	{
		$sel = pSQL(Tools::getValue("paczkomat"));
		$new = Paczkomat::pobierzDanePaczkomatu($sel);
		Configuration::updateValue($this->name."_wys_paczkomat",  base64_encode(serialize($new['paczkomat'])));
		if($sel != "")
			$this->jsonResponse(array("result"=>"ok","message"=>"Wybrany Paczkomat (".$sel.") został zapisany jako domyślny do wysyłki"));
		else
			$this->jsonResponse(array("result"=>"error","message"=>"Nie wybrano żadnego Paczkomatu, nie zapisano zmian!"));
	}

	public function ajax_getPaczkomatInfoAction()
	{
		$code = Tools::getValue("machineCode");
		$this->jsonResponse(Paczkomat::pobierzDanePaczkomatu($code));
	}

	public function ajax_getPaczkomatyAction()
	{
		$cod = false;
		if(isset($_POST['cod']) && $_POST['cod']=="true")$cod = true;
		$this->jsonResponse(Paczkomat::listaPaczkomatów("",$cod));                
		if($list['result']=="ok")
			$this->jsonResponse(array("result"=>"ok","list"=>$list['list']));
		else
			$this->jsonResponse(array("result"=>"error","message"=>$list['message']));
	}

	protected function ajax_chceckPaczkomatyAccountAction()
	{
		$login = Configuration::get($this->name."_login");
		if(Paczkomat::sprawdzKonto($login))
		{
			$wys = Configuration::get($this->name."_wys_paczkomat");
			if($wys=="")
			{
				$res = Paczkomat::paczkomatUżytkownika($login);
				if($res['result'] == "ok")
				{
					$wys = Paczkomat::pobierzDanePaczkomatu($res['glowny']);
					Configuration::updateValue($this->name."_wys_paczkomat", base64_decode(serialize($wys['paczkomat'])));
				}
				else
					$this->jsonResponse(array("result"=>"error","message"=>"Brak zapisanego Paczkomatu do wysyłki"));
			}
			$this->jsonResponse(array("result"=>"ok","message"=>"","glowny"=>$wys));
		}
		else
			$this->jsonResponse(array("result"=>"error","message"=>"Podany e-mail nie jest zarejestrowanym loginem w systemie Paczkomaty 24/7"));
	}

	protected function ajax_saveAccountAction(){
		$login = pSQL($_POST["login"]);
		$pass = pSQL($_POST["pass"]);
		if(Validate::isEmail($login))
		{
			if(Paczkomat::sprawdzKonto($login))
			{
				if(Configuration::get($this->name."_wys_paczkomat")=="")
				{
					$pacz = Paczkomat::paczkomatUżytkownika($login);
					if($pacz['result']=="ok" && $pacz['glowny']!=''){
						$sendMachine = Paczkomat::pobierzDanePaczkomatu($pacz['glowny']);
						Configuration::updateValue($this->name."_wys_paczkomat", base64_encode(serialize($sendMachine['paczkomat'])));
					}
				}
				if($pass!="")
				{
					Configuration::updateValue($this->name."_login",$login);
					Configuration::updateValue($this->name."_haslo",$pass);
					$this->jsonResponse(array("result"=>"ok","message"=>"Login i hasło zostały zapisane"));
				}
				else $this->jsonResponse(array("result"=>"error","message"=>"Hasło nie może być puste"));
			}
			else $this->jsonResponse(array("result"=>"error","message"=>"Podany e-mail nie jest zarejestrowanym loginem w systemie Paczkomaty 24/7"));
		}
		else $this->jsonResponse(array("result"=>"error","message"=>"Login musi być podany w postaci adresu e-mail"));
	}
    
    public function simpleJson($success=true,$message="",$data=null){
        $res['result'] = $success==true?"ok":"error";
        if($message!="")$res['message']=$message;
        if(!is_null($data))$res['data']=$data;
        $this->jsonResponse($res);
    }

	public function jsonResponse($data)
	{
		header("Content-type: application/json");
		die(json_encode($data));
	}

	/* ------------------------------ KONIEC SEKCJI AJAX --------------------------------*/

}

function print_rr($val){
    echo "<pre>";
    print_r($val);
    echo "</pre>";
}
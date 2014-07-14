<?php
class Paczkomat
{
    const GABARYT_A='A';
    const GABARYT_B='B';
    const GABARYT_C='C';
    
    const UBEZPIECZENIE_0 = "0";
    const UBEZPIECZENIE_5 = "5000";
    const UBEZPIECZENIE_10 = "10000";
    const UBEZPIECZENIE_20 = "20000";
    
    const ETYKIETA_STANDARD='STANDARD';
    const ETYKIETA_A6P='A6P';
    
    const PLATNOSC_BRAK=0;
    const PLATNOSC_KARTA=1;
    const PLATNOSC_POBRANIE=2;    
    
	public static $packSizes = array(
		self::GABARYT_A=>'8 x 38 x 64cm',
		self::GABARYT_B=>'19 x 38 x 64 cm',
		self::GABARYT_C=>'41 x 38 x 64 cm');

	public static $insurance = array(
		self::UBEZPIECZENIE_0=>'bez ubezpieczenia',
		self::UBEZPIECZENIE_5=>'do 5000zł',
		self::UBEZPIECZENIE_10=>'do 10000zł',
		self::UBEZPIECZENIE_20=>'do 20000zł');

	public static $paymentTypes = array(
		self::PLATNOSC_BRAK=>"Brak",
		self::PLATNOSC_KARTA=>"Karta",
		self::PLATNOSC_POBRANIE=>"Pobranie"
	);
    
    private static $labelsTypes = array(
        self::ETYKIETA_STANDARD=>array("opis"=>"Standardowa","parametr"=>""),
        self::ETYKIETA_A6P=>array("opis"=>"A6P","parametr"=>"A6P")
    );
        
    public static function pobierzWojewodztwa(){
        $res = inpost_get_machine_list();
        print_r($res);
    }

    public function pobierzMiasta($wojewodztwo){

    }

	public function __construct()
	{            
	}

	/**
	 * Funkcja sptawdza czy w serwisie Paczkomaty 24/7 jest założone konto na podany login
	 * @param e-mail $login Login do systemu Paczkomaty 24/7
	 * @param String $pass Hasło do systemu Paczkomaty 24/7
	 * @return boolean
	 */
	public static function sprawdzKonto($login)
	{
		$res = inpost_find_customer($login);
		if(isset($res['preferedBoxMachineName']) && $res['preferedBoxMachineName'] != "")
			return true;
		else
			return false;
	}

	public static function czyPaczkomatIstnieje($kodPaczkomatu,$miasto="",$tylkoPobraniowe=false)
	{
		$lista = self::listaPaczkomatów($miasto,$tylkoPobraniowe);
		if($lista['result']=="ok"){
			foreach($lista['list'] as $item){
				if($item['name'] == $kodPaczkomatu) return true;
			}
		}
		return false;
	}

	public static function pobierzDanePaczkomatu($kodPaczkomatu,$miasto="")
	{
		$lista = self::listaPaczkomatów($miasto);
		if($lista['result']=="ok"){
			foreach($lista['list'] as $item){
				if($item['name'] == $kodPaczkomatu) return array("result"=>"ok","paczkomat"=>$item);
			}
		}
		return array("result"=>"error","message"=>"Paczkomat o podanym kodzie nie istnieje");
	}

	/**
	 * Funkcja wyszukuje paczkomat znajdujący się najbliżej podanego kodu pocztowego
	 * @param String $kodPaczkomatu kod pocztowy, w pobliżu którego będzie wyszukany paczkomat akceptowany format kodu pocztowego to xx-xxx lub xxxxx
	 * @param Boolean $tylkoPobraniowe parametr określający czy wyszukać tylko paczkomaty z możliwością odbioru przesyłki pobraniowej (true) czy wszystkie (false)
	 * @return array lista znalezionych paczkomatów
	 */
	public static function znajdzNajblizszy($kodPocztowy,$tylkoPobraniowe=true)
	{
		$kod = str_replace(' ','',trim($kodPocztowy));
		if(preg_match('/[0-9]{2}-[0-9]{3}/', $kod))
		{
			$kodPocztowy = $kod;
		}elseif(preg_match('/[0-9]{5}/', $kod)){
			$kod1 = substr($kod, 0, 2);
			$kod2 = substr($kod,2,3);
			$kodPocztowy = $kod1."-".$kod2;
		}  else {
			$kodPocztowy = "00-901";
		}
		$tmp = Paczkomat::najbliższePaczkomaty($kodPocztowy, $tylkoPobraniowe);
		if($tmp['result']=="ok" && is_array($tmp['list']) && count($tmp['list'])>0) return $tmp['list'][0];
		return false;
	}

	public static function anulujZlecenie($login,$haslo,$idPaczki){
		$res = inpost_cancel_pack($login, $haslo, $idPaczki);
		if(isset($res['error']) && is_array($res['error']))
		{
			return array("result"=>"error","message"=>"Wystąpił błąd podczas znulowania zlecenia. ".$res['error']['key'].": ".$res['error']['message']);
		}
		return array("result"=>"ok","message"=>"Paczka została anulowana");
	}

	public static function paczkomatUżytkownika($login)
	{
		$res = inpost_find_customer($login);
		if(isset($res['preferedBoxMachineName']) && $res['preferedBoxMachineName'] != "")
		{
			$ret = array("result"=>"ok", "glowny"=>"", "alternatywny"=>"");
			$ret['glowny'] = $res['preferedBoxMachineName'];
			if(isset($res['alternativeBoxMachineName']) && $res['alternativeBoxMachineName'] != "")
			{
				$ret['alternatywny'] = $res['alternativeBoxMachineName'];
			}
			return $ret;
		}
		else return array("result"=>"error","message"=>$res['error']['message']);
	}

	public static function statusPaczki($packId)
	{
		$status = array(
			'Created' => 'Zlecenie utworzone',
			'Prepared' => 'Gotowa do wysyłki',
			'Sent' => 'Przesyłka Nadana',
			'InTransit' => 'W drodze',
			'Stored' => 'Oczekuje na odbiór',
			'Avizo' => 'Ponowne awizo',
			'Expired' => 'Nie odebrana',
			'Delivered' => 'Dostarczona',
			'RetunedToAgency' => 'Przekazana do Oddziału',
			'Cancelled' => 'Anulowana',
			'Claimed' => 'Przyjęto zgłoszenie reklamacyjne',
			'ClaimProcessed' => 'Rozpatrzono zgłoszenie reklamacyjne',
			'CustomerDelivering' => 'Odbiór od nadawcy',
			'LabelExpired' => 'Etykieta straciła ważność'
		);
		$res = inpost_get_pack_status($packId);

		if(is_array($res) && isset($res['error']))
		{
			return array("result"=>"error","message"=>("Wystąpił błąd w zapytaniu do Inpost. ".$res['error']['key'].": ".$res['error']['message']));
		}
		if(key_exists($res, $status)){
			return array("result"=>"ok","status"=>$status[$res],"status_code"=>$res);
		}  else {
			return array("result"=>"ok","status"=>$res,"status_code"=>$res);
		}
	}

	public static function pobierzEtykietę($login,$haslo,$nrPaczki,$typ=Paczkomat::ETYKIETA_STANDARD){        
		$resp = inpost_get_sticker($login, $haslo, $nrPaczki,self::parametrRozmiaruEtykiety($typ));
		if(is_array($resp) && isset($resp['error']))
		{
			return array("result"=>"error","message"=>$resp['error']['message']);
		}else{
			return array("result"=>"ok","sticker"=>$resp);
		}
	}
    
    public static function opisRozmiaruEtykiety($typ){
        if(key_exists($typ, self::$labelsTypes)){
            return self::$labelsTypes[$typ]['opis'];
        }else return self::$labelsTypes[self::ETYKIETA_STANDARD]['opis'];
    }
    
    public static function parametrRozmiaruEtykiety($typ){
        if(key_exists($typ, self::$labelsTypes)){            
            return self::$labelsTypes[$typ]['parametr'];
        }else return self::$labelsTypes[self::ETYKIETA_STANDARD]['parametr'];
    }

    public static function listaPaczkomatów($miejscowość="",$tylkoPobraniowe=false)
	{
		$res = inpost_get_machine_list($miejscowość,$tylkoPobraniowe?"t":null);                
		if(is_array($res) && count($res)>0 )
			return array("result"=>"ok","list"=>$res);
		else
			return array("result"=>"error","message"=>"Zapytanie do serwera Inpost nie zwróciło żadnego paczkomatu");
	}

	public static function najbliższePaczkomaty($kodPocztowy="",$tylkoPobraniowe=false)
	{
		$res = inpost_find_nearest_machines($kodPocztowy,$tylkoPobraniowe?"t":null);
		if(is_array($res) && count($res)>0 )
			return array("result"=>"ok","list"=>$res);
		else
			return array("result"=>"error","message"=>"Zapytanie do serwera Inpost nie zwróciło żadnego paczkomatu");
	}

	public static function wygenerujPaczke($config)
	{
		error_reporting(E_ALL | ~E_NOTICE);
        $sData = $config['sender_data'];       
         
		$pack = array(0=>array(
			'adreseeEmail' => $config['customer_email'],
			'senderEmail' => $config['sender_email'],
			'phoneNum' => $config['customer_phone'],
			'boxMachineName' => $config['customer_machine'],
			'alternativeBoxMachineName' => '',
			'customerDelivering' => '',
			'senderBoxMachineName' => '',
			'packType' => $config['size'],
			'insuranceAmount' => $config['insurance'],
			'onDeliveryAmount' => $config['cod'],
			'customerRef' => $config['order_id'],
			'senderAddress' => array(
				'name' => $sData['pmImie'],
				'surName' => $sData['pmNazwisko'],
				'email' => $config['sender_email'],
				'phoneNum' => $sData['pmTelefon'],
				'street' => $sData['pmUlica'],
				'buildingNo' => $sData['pmDom'],
				'flatNo' => $sData['pmMieszkanie'],
				'town' => $sData['pmMiasto'],
				'zipCode' => $sData['pmKod1']."-".$sData['pmKod2'],
				'province' => $sData['pmWojewodztwo']
			)
		));        
		if($config['selfsend'] == 1)
		{
			$pack[0]['customerDelivering'] = '1';
			$pack[0]['senderBoxMachineName'] = $config['sender_machine'];
		}
		else
		{
			$pack[0]['customerDelivering'] = '0';
			$pack[0]['senderBoxMachineName'] = '';
		}       

		$res = inpost_send_packs($config['sender_login'],$config['sender_pass'],$pack,0,$config['selfsend']);
		if(isset($res[0]['error_key']))
			return array("result"=>"error","message"=>$res[0]['error_key']!=""?'Wystąpił błąd podczas tworzenia paczki. Id błędu: '.$res[0]['error_key'].'. Opis błędu:'.$res[0]['error_message']:'Wystąpił nieznany błąd w systemie zewnętrznym InPost');
		if(isset($res['error']['key']))
			return array("result"=>"error","message"=>$res['error']['key']!=""?'Wystąpił błąd podczas tworzenia paczki. Id błędu: '.$res['error']['key'].'. Opis błędu: '.$res['error']['message']:'Wystąpił nieznany błąd w systemie zewnętrznym InPost');
		if(is_array($res))
			return array("result"=>"ok","message"=>"Paczka została utworzona","createdPack"=>$res[0]);
		return array("result"=>"error","message"=>'Wystąpił nieznany błąd w systemie zewnętrznym InPost',"inpostResponse"=>$res);
	}
}

?>
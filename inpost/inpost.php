<?php

/*

Main API include file.
Revision 1.8

Copyright (c) 2011 InPost Sp. z o.o.

*/


function inpost_check_environment($verbose=0) {

  global $inpost_data_dir;

  $status = 1;
  if (!file_exists($inpost_data_dir)) {
    if ($verbose) echo "Paczkomaty API: path to proper data directory must be set (config.php)!<br/>";
    $status = 0;
  }

  if (!is_writable("$inpost_data_dir/time1.dat")) {
    if ($verbose) echo "Paczkomaty API: file data/time1.dat must be writable!<br/>";
    $status = 0;
  }

  if (!is_writable("$inpost_data_dir/time2.dat")) {
    if ($verbose) echo "Paczkomaty API: file data/time2.dat must be writable!<br/>";
    $status = 0;
  }

  if (!is_writable("$inpost_data_dir/cache1.dat")) {
    if ($verbose) echo "Paczkomaty API: file data/cache1.dat must be writable!<br/>";
    $status = 0;
  }

  if (!is_writable("$inpost_data_dir/cache2.dat")) {
    if ($verbose) echo "Paczkomaty API: file data/cache2.dat must be writable!<br/>";
    $status = 0;
  }

  if (!function_exists('xml_parser_create')) {
    if ($verbose) echo "Paczkomaty API: PHP xml_parser_create() function is required!<br/>";
    $status = 0;
  }

  if (!ini_get('allow_url_fopen')) {
    if ($verbose) echo "Paczkomaty API: PHP allow_url_fopen setting is required for server communication!<br/>";
    $status = 0;
  }

  return $status;

}

function inpost_get_params()
{
      global $inpost_data_dir,$inpost_api_url;
      if ($Contents = file_get_contents("$inpost_api_url/?do=getparams")) {
      $parsedXML = inpost_xml2array($Contents);
      $wynik=array();
      foreach ($parsedXML['paczkomaty'] as $name=>$array) $wynik[$name]=$array['value'];
      return $wynik;
    }
    return 0;
}

function inpost_get_machine_list($town='',$paymentavailable='') {

  global $inpost_data_dir;

  if (inpost_cache_is_valid(1)==0) {
    inpost_download_machines();
  }
  if ($cache = @file_get_contents("$inpost_data_dir/cache1.dat")) {
    $machineList = unserialize($cache);
    if (count($machineList)) {
      if ($town) {
        foreach ($machineList as $machine) {
          if ($machine[4]==$town) $resultList[] = $machine;
        }
        $machineList = $resultList;
      }
      if (count($machineList)) {
        $resultList = array();
        $i=0;
        foreach($machineList as $machine) {
			if( isset($machine[0]) && isset($machine[1]) && isset($machine[2]) && isset($machine[3]) && isset($machine[4]) )
			{
				if (!$paymentavailable || ($paymentavailable=='t' && $machine[7]=='t') ||  ($paymentavailable=='f' && $machine[7]=='f')) {
					$resultList[$i]['name'] = $machine[0];
					$resultList[$i]['street'] = $machine[1];
					$resultList[$i]['buildingnumber'] = $machine[2];
					$resultList[$i]['postcode'] = $machine[3];
					$resultList[$i]['town'] = $machine[4];
					$resultList[$i]['latitude'] = $machine[5];
					$resultList[$i]['longitude'] = $machine[6];
					if ($machine[7]=='t') $resultList[$i]['paymentavailable'] = 1;
					else $resultList[$i]['paymentavailable'] = 0;
					$resultList[$i]['operatinghours'] = $machine[8];
					$resultList[$i]['locationdescription'] = $machine[9];
					$resultList[$i]['paymentpointdescr'] = $machine[10];
					$resultList[$i]['partnerid'] = $machine[11];
					$resultList[$i]['paymenttype'] = $machine[12];
					$i++;
				}
			}

        }
        usort($resultList,'inpost_machine_sort');
        return $resultList;
      }
    }
  }
  return 0;

}

function inpost_get_pricelist() {

  global $inpost_data_dir;

  if (inpost_cache_is_valid(2)==0) {
    inpost_download_pricelist();
  }
  if ($cache = @file_get_contents("$inpost_data_dir/cache2.dat")) {
    return unserialize($cache);
  }
  return 0;

}




function inpost_get_pack_status($packcode) {

  global $inpost_api_url;

  if ($statusContents = @file_get_contents("$inpost_api_url/?do=getpackstatus&packcode=$packcode")) {
    $parsedXML = inpost_xml2array($statusContents);
    if (isset($parsedXML['paczkomaty']['error'])) {
      return array('error' => array('key' => $parsedXML['paczkomaty']['error']['attr']['key'],
                   'message' => $parsedXML['paczkomaty']['error']['value']));
    }
    $parsedXML = $parsedXML['paczkomaty'];
    $packStatus = $parsedXML['status']['value'];
    return $packStatus;
  }
  return 0;

}



function inpost_machines_dropdown($param)
{

   global $inpost_api_url;
   $machines_all = inpost_get_machine_list();
   $machines_with_names_as_keys=array();
   if (!isset($param['paymentavailable'])) $param['paymentavailable']=0;

   if(count($machines_all)) {
     foreach ($machines_all as $id => $machine) {
       if (($param['paymentavailable'] && $machine['paymentavailable']) || !$param['paymentavailable']) {
          $machines = $machines_with_names_as_keys[$machine['name']] = $machine;
       }
     }
   }

   if (isset($param['email'])) {
     $client = inpost_find_customer($param['email']);
     if (isset($client['error'])) return -1;
   }

   $result= "";
   $result.= "<select ";
   if (isset($param['class'])) $result.= " class=\"".$param['class']."\"";
   if (isset($param['name'])) $result.= " name=\"".$param['name']."\"";
   $result.= ">";


   if (isset($param['email'])) {
   // paczkomat domyślny
   if (isset($client['preferedBoxMachineName']) && array_key_exists($client['preferedBoxMachineName'], $machines_with_names_as_keys)) {
     $result.="<option disabled>Paczkomat domyślny</option>";
     $result.="<option value=\"".$client['preferedBoxMachineName']."\"";

     if (isset($param['email']) AND !isset($param['selected'])) {
         $result.=" selected=\"selected\"";
         unset($param['selected']);
     }

     $result.=">".$machines_with_names_as_keys[$client['preferedBoxMachineName']]['name']." ".$machines_with_names_as_keys[$client['preferedBoxMachineName']]['street']." ".$machines_with_names_as_keys[$client['preferedBoxMachineName']]['buildingnumber'].", ".$machines_with_names_as_keys[$client['preferedBoxMachineName']]['postcode']." ".$machines_with_names_as_keys[$client['preferedBoxMachineName']]['town'];
     if (isset($param['paymentavailable_suffix'])) $result .= $param['paymentavailable_suffix'];
     $result.= "</option>";
    }

     //paczkomat alternatywny
    if ($client['alternativeBoxMachineName'] && array_key_exists($client['alternativeBoxMachineName'], $machines_with_names_as_keys)) {
     $result.="<option disabled>Paczkomat alternatywny</option>";
     $result.="<option value=\"".$client['alternativeBoxMachineName']."\"";

     if (isset($param['selected']) AND $param['selected'] == $client['alternativeBoxMachineName']) {
         $result.=" selected=\"selected\"";
         unset($param['selected']);
     }

     $result.=">".$machines_with_names_as_keys[$client['alternativeBoxMachineName']]['name']." ".$machines_with_names_as_keys[$client['alternativeBoxMachineName']]['street']." ".$machines_with_names_as_keys[$client['alternativeBoxMachineName']]['buildingnumber'].", ".$machines_with_names_as_keys[$client['alternativeBoxMachineName']]['postcode']." ".$machines_with_names_as_keys[$client['alternativeBoxMachineName']]['town']."</option>";
    }


   }

   //paczkomaty w pobliżu kodu
   if (isset($param['postcode'])) {
    if (!isset($paymentavailable)) $paymentavailable= '';
    $machines = inpost_find_nearest_machines($param['postcode'], ($paymentavailable) ? 't':'');
    if (!empty($machines)) {
      $result.="<option disabled>Paczkomaty najbliżej kodu ".$param['postcode']."</option>";
      foreach ($machines as $machine) {

       $result.="<option value=\"".$machine['name']."\"";

       if (isset($param['selected'])) {
          if ($param['selected']==$machine['name']) {
             $result.=" selected=\"selected\"";
             unset($param['selected']);
         }
       }

       $result.=">".$machine['name']." ".$machine['street']." ".$machine['buildingnumber'].", ".$machine['postcode']." ".$machine['town']."</option>";


     }
    }
   }

   //wszystkie paczkomaty

    if (!empty($machines_with_names_as_keys)) {
      $result.="<option disabled>Wszytkie paczkomaty</option>";
      foreach ($machines_with_names_as_keys as $machine) {

       $result.="<option value=\"".$machine['name']."\"";

       if (isset($param['selected'])) {
         if ($param['selected']==$machine['name'])  {
           $result.=" selected=\"selected\"";
           unset($param['selected']);
         }
       }

       $result.=">".$machine['name']." ".$machine['street']." ".$machine['buildingnumber'].", ".$machine['postcode']." ".$machine['town']."</option>";


     }
    }


   $result.= "</select>";


   return $result;
}

function inpost_find_customer($email) {

    global $inpost_api_url;

    if ($customerContents = @file_get_contents("$inpost_api_url/?do=findcustomer&email=$email")) {
        $parsedXML = inpost_xml2array($customerContents);

        if (isset($parsedXML['paczkomaty']['error'])) {
            return array('error' => array('key' => $parsedXML['paczkomaty']['error']['attr']['key'], 'message' => $parsedXML['paczkomaty']['error']['value']));
        }
        $parsedXML = $parsedXML['paczkomaty']['customer'];
        if (isset($parsedXML['email']['value']) AND $parsedXML['email']['value'] == $email) {
            $preferedBoxMachineName = $parsedXML['preferedBoxMachineName']['value'];
            if (isset($parsedXML['alternativeBoxMachineName']['value']))
                $alternativeBoxMachineName = $parsedXML['alternativeBoxMachineName']['value'];
            else
                $alternativeBoxMachineName = '';
            return array('preferedBoxMachineName' => $preferedBoxMachineName, 'alternativeBoxMachineName' => $alternativeBoxMachineName);
        }
    }
    return 0;

}

function inpost_find_nearest_machines($postcode,$paymentavailable='') {

  global $inpost_api_url;

  if ($machinesContents = @file_get_contents("$inpost_api_url/?do=findnearestmachines&postcode=$postcode&paymentavailable=$paymentavailable")) {
    $parsedXML = inpost_xml2array($machinesContents);
    if (!isset($parsedXML['paczkomaty']['machine'])) return 0;
    $machines = $parsedXML['paczkomaty']['machine'];
    if (count($machines)) {
        $machineList = array();
      $allMachines = inpost_get_machine_list();
      $i=0;
      if(count($allMachines)) {
        foreach($allMachines as $machineDetails) {
          foreach($machines as $machine) {
            if (isset($machine['name']['value']) AND $machine['name']['value']==$machineDetails['name']) {
              $machineList[$i] = $machineDetails;
              $machineList[$i]['distance'] = $machine['distance']['value'];
              $i++;
            }
          }
        }
      }
      usort($machineList,'inpost_machine_distance_sort');
      return $machineList;
    }
  }
  return 0;

}

function inpost_get_towns() {

    $machines = inpost_get_machine_list();

    if (isset($machines) AND count($machines)) {
      foreach($machines as $machine) {
        $towns[] = $machine['town'];
      }
      $towns = array_unique($towns);
      sort($towns);
      return($towns);
    }

    return 0;
}


function inpost_create_customer_partner($email,$password,$customerData) {

    global $inpost_api_url;

    $inpost_api_url = str_replace('http://','https://',$inpost_api_url);

    $digest = inpost_digest($password);

    if (count($customerData)) {
        $_lastArgSeparatorOutput = ini_get('arg_separator.output');
        ini_set('arg_separator.output', '&');
        $customerXML =  "<paczkomaty>\n";
        $customerXML .= "<customer>\n";
        $customerXML .= "<email>".$customerData['email']."</email>\n";
        $customerXML .= "<mobileNumber>".$customerData['mobileNumber']."</mobileNumber>\n";
        $customerXML .= "<preferedBoxMachineName>".$customerData['preferedBoxMachineName']."</preferedBoxMachineName>\n";
        $customerXML .= "<alternativeBoxMachineName>".$customerData['alternativeBoxMachineName']."</alternativeBoxMachineName>\n";
        $customerXML .= "<phoneNum>".$customerData['phoneNum']."</phoneNum>\n";
        $customerXML .= "<street>".$customerData['street']."</street>\n";
        $customerXML .= "<town>".$customerData['town']."</town>\n";
        $customerXML .= "<postCode>".$customerData['postCode']."</postCode>\n";
        $customerXML .= "<building>".$customerData['building']."</building>\n";
        $customerXML .= "<flat>".$customerData['flat']."</flat>\n";
        $customerXML .= "<firstName>".$customerData['firstName']."</firstName>\n";
        $customerXML .= "<lastName>".$customerData['lastName']."</lastName>\n";
        $customerXML .= "<companyName>".$customerData['companyName']."</companyName>\n";
        $customerXML .= "<regon>".$customerData['regon']."</regon>\n";
        $customerXML .= "<nip>".$customerData['nip']."</nip>\n";
        $customerXML .= "</customer>\n";
        $customerXML .= "</paczkomaty>\n";

        $customerEmail = $customerData['email'];
        $customerData = array ('email' => $email, 'digest' => $digest, 'content' => $customerXML);
        $postData = http_build_query($customerData);
        if ($customerResponse = inpost_post_request("$inpost_api_url/?do=createcustomerpartner",$postData)) {
            $parsedXML = inpost_xml2array($customerResponse);
            if (isset($parsedXML['paczkomaty']['error'])) {
                return array('error' => array('key' => $parsedXML['paczkomaty']['error']['attr']['key'], 'message' => $parsedXML['paczkomaty']['error']['value']));
            }
            $parsedXML = $parsedXML['paczkomaty']['customer'];
            if (isset($parsedXML['email']['value']) AND $parsedXML['email']['value']==$customerEmail) {
                return array('email' => $parsedXML['email']['value']);
            }
        }
        ini_set('arg_separator.output', $_lastArgSeparatorOutput);
    }
    return 0;

}



function inpost_send_packs($email,$password,$packsData,$autoLabels=1,$selfSend=0) {

    global $inpost_api_url;

    $inpost_api_url = str_replace('http://','https://',$inpost_api_url);

    $digest = inpost_digest($password);

    if (count($packsData)) {

        $packsXML =  "<paczkomaty>\n";
        $packsXML .= "<autoLabels>$autoLabels</autoLabels>\n";
        $packsXML .= "<selfSend>$selfSend</selfSend>\n";
        foreach ($packsData as $packId => $packData) {
            $packsXML .= "<pack>\n";
            $packsXML .= "<id>".$packId."</id>\n";
            $packsXML .= "<adreseeEmail>".$packData['adreseeEmail']."</adreseeEmail>\n";
            $packsXML .= "<senderEmail>".$packData['senderEmail']."</senderEmail>\n";
            $packsXML .= "<phoneNum>".$packData['phoneNum']."</phoneNum>\n";
            $packsXML .= "<boxMachineName>".$packData['boxMachineName']."</boxMachineName>\n";
            if(array_key_exists('alternativeBoxMachineName', $packData))
                $packsXML .= "<alternativeBoxMachineName>".$packData['alternativeBoxMachineName']."</alternativeBoxMachineName>\n";
            $packsXML .= "<packType>".$packData['packType']."</packType>\n";
            if(array_key_exists('customerDelivering', $packData))
                $packsXML .= "<customerDelivering>".$packData['customerDelivering']."</customerDelivering>\n";
            else
                $packsXML .= "<customerDelivering>false</customerDelivering>\n";
            $packsXML .= "<insuranceAmount>".$packData['insuranceAmount']."</insuranceAmount>\n";
            $packsXML .= "<onDeliveryAmount>".$packData['onDeliveryAmount']."</onDeliveryAmount>\n";
            if(array_key_exists('customerRef', $packData))
                $packsXML .= "<customerRef>".$packData['customerRef']."</customerRef>\n";
            if(array_key_exists('senderBoxMachineName', $packData))
                $packsXML .= "<senderBoxMachineName>".$packData['senderBoxMachineName']."</senderBoxMachineName>\n";
            if(array_key_exists('senderAddress', $packData) and !empty($packData['senderAddress'])) {
                $packsXML .= "<senderAddress>\n";
                $tmpFieldsArray = array('name', 'surName', 'email', 'phoneNum', 'street', 'buildingNo', 'flatNo', 'town', 'zipCode', 'province');
                foreach($tmpFieldsArray as $tmpField) {
                    if(array_key_exists($tmpField, $packData['senderAddress']) && !empty($packData['senderAddress'][$tmpField])){
                        $packsXML .= "<$tmpField>".$packData['senderAddress'][$tmpField]."</$tmpField>\n";
                    }
                }
                $packsXML .= "</senderAddress>\n";
            }
            $packsXML .= "</pack>\n";
        }
        $packsXML .= "</paczkomaty>\n";

        $packsData = array ('email' => $email, 'digest' => $digest, 'content' => $packsXML);

        $_lastArgSeparatorOutput = ini_get('arg_separator.output');
        ini_set('arg_separator.output', '&');
        $postData = http_build_query($packsData);
        if ($packsResponse = inpost_post_request("$inpost_api_url/?do=createdeliverypacks",$postData)) {

            $parsedXML = inpost_xml2array($packsResponse);
            if (isset($parsedXML['paczkomaty']['error'])) {
                return array('error' => array('key' => $parsedXML['paczkomaty']['error']['attr']['key'], 'message' => $parsedXML['paczkomaty']['error']['value']));
            }


            if (isset($parsedXML['paczkomaty']['pack']))
                $packsData = $parsedXML['paczkomaty']['pack'];
            if (!isset($packsData[0])) {
                $temp = $packsData;
                $packsData = array();
                $packsData[0] = $temp;
            }
            if (count($packsData)) {
                foreach ($packsData as $packData) {
                    if (isset($packData['packcode']['value']))
                        $resultData[$packData['id']['value']]['packcode'] = $packData['packcode']['value'];
                    if (isset($packData['customerdeliveringcode']['value']))
                        $resultData[$packData['id']['value']]['customerdeliveringcode'] = $packData['customerdeliveringcode']['value'];
                    if (isset($packData['error']['attr']['key']))
                        $resultData[$packData['id']['value']]['error_key'] = $packData['error']['attr']['key'];
                    if (isset($packData['error']['value']))
                        $resultData[$packData['id']['value']]['error_message'] = $packData['error']['value'];
                }
                if (isset($resultData))
                    return $resultData;
                else
                    return array();
            }
        }
        ini_set('arg_separator.output', $_lastArgSeparatorOutput);
    }
    return 0;

}

function inpost_get_sticker($email,$password,$packCode,$labelType='') {

  global $inpost_api_url;

  $inpost_api_url = str_replace('http://','https://',$inpost_api_url);

  $digest = inpost_digest($password);

  if (isset($packCode)) {
    $_lastArgSeparatorOutput = ini_get('arg_separator.output');
    ini_set('arg_separator.output', '&');

    $customerData = array ('email' => $email, 'digest' => $digest, 'packcode' => $packCode,  'labeltype' => $labelType);
    $postData = http_build_query($customerData);
    if ($customerResponse = inpost_post_request("$inpost_api_url/?do=getsticker",$postData)) {
      if (strpos($customerResponse,'PDF')) return $customerResponse;
      $parsedXML = inpost_xml2array($customerResponse);
      if (isset($parsedXML['paczkomaty']['error'])) {
        return array('error' => array('key' => $parsedXML['paczkomaty']['error']['attr']['key'],
                   'message' => $parsedXML['paczkomaty']['error']['value']));
      }
    }

    ini_set('arg_separator.output', $_lastArgSeparatorOutput);
  }
  return 0;

}


function inpost_cancel_pack($email,$password,$packCode) {

  global $inpost_api_url;

 $inpost_api_url = str_replace('http://','https://',$inpost_api_url);

  $digest = inpost_digest($password);

  if (isset($packCode)) {
    $_lastArgSeparatorOutput = ini_get('arg_separator.output');
    ini_set('arg_separator.output', '&');

    $customerData = array ('email' => $email, 'digest' => $digest, 'packcode' => $packCode);
    $postData = http_build_query($customerData);


    if ($customerResponse = inpost_post_request("$inpost_api_url/?do=cancelpack",$postData)) {

      $parsedXML = inpost_xml2array($customerResponse);
      if (isset($parsedXML['paczkomaty']['error'])) {
        return array('error' => array('key' => $parsedXML['paczkomaty']['error']['attr']['key'],
                   'message' => $parsedXML['paczkomaty']['error']['value']));
      }
      else return $customerResponse;
    }

    ini_set('arg_separator.output', $_lastArgSeparatorOutput);
  }
  return 0;

}


function inpost_change_packsize($email,$password,$packCode, $packSize) {

  global $inpost_api_url;

  $inpost_api_url = str_replace('http://','https://',$inpost_api_url);

  $digest = inpost_digest($password);

  if (isset($packCode) && isset($packSize)) {
    $_lastArgSeparatorOutput = ini_get('arg_separator.output');
    ini_set('arg_separator.output', '&');

    $customerData = array ('email' => $email, 'digest' => $digest, 'packcode' => $packCode, 'packsize' => $packSize);
    $postData = http_build_query($customerData);


    if ($customerResponse = inpost_post_request("$inpost_api_url/?do=change_packsize",$postData)) {

      $parsedXML = inpost_xml2array($customerResponse);
      if (isset($parsedXML['paczkomaty']['error'])) {
        return array('error' => array('key' => $parsedXML['paczkomaty']['error']['attr']['key'],
                   'message' => $parsedXML['paczkomaty']['error']['value']));
      }
      else return $customerResponse;
    }

    ini_set('arg_separator.output', $_lastArgSeparatorOutput);
  }
  return 0;

}


function inpost_get_stickers($email,$password,$packCodes,$labelType='') {

  global $inpost_api_url;

  $inpost_api_url = str_replace('http://','https://',$inpost_api_url);

  $digest = inpost_digest($password);



  if (is_array($packCodes)) {
    //$customerEmail = $customerData['email'];
    $customerData = array ('email' => $email, 'digest' => $digest, 'packcodes' => $packCodes ,  'labeltype' => $labelType);

    $_lastArgSeparatorOutput = ini_get('arg_separator.output');
    ini_set('arg_separator.output', '&');
    $postData = http_build_query($customerData);

    if ($customerResponse = inpost_post_request("$inpost_api_url/?do=getstickers",$postData)) {
      if (strpos($customerResponse,'PDF')) return $customerResponse;
      $parsedXML = inpost_xml2array($customerResponse);
      if (isset($parsedXML['paczkomaty']['error'])) {
        return array('error' => array('key' => $parsedXML['paczkomaty']['error']['attr']['key'],
                   'message' => $parsedXML['paczkomaty']['error']['value']));
      }
    }

    ini_set('arg_separator.output', $_lastArgSeparatorOutput);
  }
  return 0;

}

function inpost_set_customer_ref($email,$password,$packCode,$customerRef) {

  global $inpost_api_url;

  $inpost_api_url = str_replace('http://','https://',$inpost_api_url);

  $digest = inpost_digest($password);

  if (isset($packCode)) {
    $_lastArgSeparatorOutput = ini_get('arg_separator.output');
    ini_set('arg_separator.output', '&');

    //$customerEmail = $customerData['email'];
    $customerData = array ('email' => $email, 'digest' => $digest, 'packcode' => $packCode,
                           'customerref' => $customerRef);
    $postData = http_build_query($customerData);
    if ($customerResponse = inpost_post_request("$inpost_api_url/?do=setcustomerref",$postData)) {
      if (strpos($customerResponse,'Set')) return 1;
      $parsedXML = inpost_xml2array($customerResponse);
      if (isset($parsedXML['paczkomaty']['error'])) {
        return array('error' => array('key' => $parsedXML['paczkomaty']['error']['attr']['key'],
                   'message' => $parsedXML['paczkomaty']['error']['value']));
      }
    }

    ini_set('arg_separator.output', $_lastArgSeparatorOutput);
  }
  return 0;


}

function inpost_get_confirm_printout($email,$password,$packCodes,$testPrintout=0) {

  global $inpost_api_url;

  $inpost_api_url = str_replace('http://','https://',$inpost_api_url);

  $digest = inpost_digest($password);

  if (is_array($packCodes)) {
    $_lastArgSeparatorOutput = ini_get('arg_separator.output');
    ini_set('arg_separator.output', '&');

    $packsXML =  "<paczkomaty>\n";
    $packsXML .= "<testprintout>$testPrintout</testprintout>\n";
    foreach ($packCodes as $packCode) {
      $packsXML .= "<pack>\n";
      $packsXML .= "<packcode>".$packCode."</packcode>\n";
      $packsXML .= "</pack>\n";
    }
    $packsXML .= "</paczkomaty>\n";

    $packsData = array ('email' => $email, 'digest' => $digest, 'content' => $packsXML);
    $postData = http_build_query($packsData);

    if ($customerResponse = inpost_post_request("$inpost_api_url/?do=getconfirmprintout",$postData)) {
      if (strpos($customerResponse,'PDF')) return $customerResponse;
      $parsedXML = inpost_xml2array($customerResponse);
      if (isset($parsedXML['paczkomaty']['error'])) {
        ini_set('arg_separator.output', $_lastArgSeparatorOutput);
        return array('error' => array('key' => $parsedXML['paczkomaty']['error']['attr']['key'],
                   'message' => $parsedXML['paczkomaty']['error']['value']));
      }
    }

    ini_set('arg_separator.output', $_lastArgSeparatorOutput);
  }
  return 0;

}


function inpost_get_packs_by_sender($email,$password,$parameters=array()) {

  global $inpost_api_url;

  $inpost_api_url = str_replace('http://','https://',$inpost_api_url);
  $digest = inpost_digest($password);
  $paramData= array('email' => $email, 'digest' => $digest);

  if (isset($parameters['status'])) $paramData['status'] = $parameters['status'];
  if (isset($parameters['startdate'])) $paramData['startdate'] = $parameters['startdate'];
  if (isset($parameters['enddate'])) $paramData['enddate'] = $parameters['enddate'];
  if (isset($parameters['is_conf_printed'])) $paramData['is_conf_printed'] = $parameters['is_conf_printed'];

  $_lastArgSeparatorOutput = ini_get('arg_separator.output');
  ini_set('arg_separator.output', '&');

  $postData = http_build_query($paramData);
  if ($packsResponse = inpost_post_request("$inpost_api_url/?do=getpacksbysender",$postData)) {
    $parsedXML = inpost_xml2array($packsResponse);

    $packsData = $parsedXML['paczkomaty']['pack'];
    if (!isset($packsData[0])) {
      $temp=$packsData;
      $packsData=array();
      $packsData[0]=$temp;
    }
    if (count($packsData)) {
      $i = 0;
      foreach ($packsData as $packData) {
        foreach ($packData as $param => $value) {
          if (isset($value['value']))  $resultData[$i][$param] = $value['value']; else $resultData[$i][$param] = '';
        }
        $i++;
      }
      return $resultData;
    }
  }

  ini_set('arg_separator.output', $_lastArgSeparatorOutput);

  return 0;

}



function inpost_get_cod_report($email,$password,$parameters=array()) {

  global $inpost_api_url;

  $inpost_api_url = str_replace('http://','https://',$inpost_api_url);
  $digest = inpost_digest($password);
  $paramData= array('email' => $email, 'digest' => $digest);

  if (isset($parameters['startdate'])) $paramData['startdate'] = $parameters['startdate'];
  if (isset($parameters['enddate'])) $paramData['enddate'] = $parameters['enddate'];

  $_lastArgSeparatorOutput = ini_get('arg_separator.output');
  ini_set('arg_separator.output', '&');

  $postData = http_build_query($paramData);
  if ($packsResponse = inpost_post_request("$inpost_api_url/?do=getcodreport",$postData)) {
    $parsedXML = inpost_xml2array($packsResponse);

    $packsData = $parsedXML['paczkomaty']['payment'];
    if (!isset($packsData[0])) {
      $temp=$packsData;
      $packsData=array();
      $packsData[0]=$temp;
    }
    if (count($packsData)) {
      $i = 0;
      foreach ($packsData as $packData) {
        foreach ($packData as $param => $value) {
          if (isset($value['value']))  $resultData[$i][$param] = $value['value']; else $resultData[$i][$param] = '';
        }
        $i++;
      }
      return $resultData;
    }
  }

  ini_set('arg_separator.output', $_lastArgSeparatorOutput);

  return 0;

}


function inpost_pay_for_pack($email,$password, $packcode) {

  global $inpost_api_url;

  $inpost_api_url = str_replace('http://','https://',$inpost_api_url);
  $digest = inpost_digest($password);
  $paramData= array('email' => $email, 'digest' => $digest, 'packcode' => $packcode);

  $_lastArgSeparatorOutput = ini_get('arg_separator.output');
  ini_set('arg_separator.output', '&');

  $postData = http_build_query($paramData);
  if ($packsResponse = inpost_post_request("$inpost_api_url/?do=payforpack",$postData)) {

  $parsedXML = inpost_xml2array($packsResponse);

      if (isset($parsedXML['paczkomaty']['error'])) {
        return array('error' => array('key' => $parsedXML['paczkomaty']['error']['attr']['key'],
                   'message' => $parsedXML['paczkomaty']['error']['value']));
      }
      else return 1;

  }

  ini_set('arg_separator.output', $_lastArgSeparatorOutput);

  return 0;

}

?>

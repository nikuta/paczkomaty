<?php

/*

External support functions.
Revision 1.2

Copyright (c) 2010 InPost Sp. z o.o.

*/

function inpost_cache_is_valid($cache) {

  global $inpost_data_dir,$inpost_api_url;

  if (isset($cache)) {
    $cachedTimestamp = file_get_contents("$inpost_data_dir/time$cache.dat");
    if ($lastModifiedContents = @file_get_contents("$inpost_api_url/?do=getparams")) {
      $parsedXML = inpost_xml2array($lastModifiedContents);
      $lastModifiedTimestamp = $parsedXML['paczkomaty']['last_update']['value'];
      if ($lastModifiedTimestamp>$cachedTimestamp) return 0;
      return 1;
    }
  }
  return -1;

}


function inpost_download_machines() {

  global $inpost_data_dir,$inpost_api_url;
    
  if ($machinesContents = @file_get_contents("$inpost_api_url/?do=listmachines_csv")) {    
    $machinesArray = explode("\n",$machinesContents);
    $machinesChecksum = $machinesArray[0];
    $machinesContents = substr($machinesContents,strlen($machinesChecksum)+1);
    if ($machinesChecksum != inpost_crc16($machinesContents)) return 0;
    if (count($machinesArray)) {
      array_shift($machinesArray);
      foreach ($machinesArray as $machine) {
        $machine = explode(";",$machine);
        $data[] = $machine;
      }
      
      if (($cacheHandle = @fopen("$inpost_data_dir/cache1.dat", "wb")) &&
           ($timeHandle = @fopen("$inpost_data_dir/time1.dat", "w"))) {
        fwrite($cacheHandle,serialize($data));
        fclose($cacheHandle);
        fwrite($timeHandle,time());
        fclose($timeHandle);
        return 1;
      }
    }
  }
  return 0;

}

function inpost_download_pricelist() {

  global $inpost_data_dir,$inpost_api_url;
  
  if ($pricelistContents = @file_get_contents("$inpost_api_url/?do=pricelist")) {
    $parsedXML = inpost_xml2array($pricelistContents);
    $parsedXML = $parsedXML['paczkomaty'];
    if(isset($parsedXML['on_delivery_payment']))
        $data['on_delivery_payment'] = $parsedXML['on_delivery_payment']['value'];
    if (isset($parsedXML['packtype']) AND count($parsedXML['packtype'])) {
      foreach ($parsedXML['packtype'] as $packtype) {
        $data[$packtype['type']['value']] = $packtype['price']['value'];
      }        
  if (!isset($parsedXML['insurance'][0]['limit'])) {
  $temp=$parsedXML['insurance'];
  $parsedXML['insurance']=array();
  $parsedXML['insurance'][]=$temp;
  }  
      
      
      foreach ($parsedXML['insurance'] as $insurance) {
        $data['insurance'][$insurance['limit']['value']] = $insurance['price']['value'];
      }
      
      if (($cacheHandle = fopen("$inpost_data_dir/cache2.dat", "wb")) &&
           ($timeHandle = fopen("$inpost_data_dir/time2.dat", "w"))) {          
        fwrite($cacheHandle,serialize($data));
        fclose($cacheHandle);      
        fwrite($timeHandle,time());
        fclose($timeHandle);
        return 1;
      }
    }
  }
  return 0;

}


function inpost_machine_sort($m1, $m2) {
  
  return strcmp($m1["name"], $m2["name"]);

}

function inpost_machine_distance_sort($m1, $m2) {

  if ($m1['distance'] == $m2['distance']) return 0;
  return ($m1['distance'] < $m2['distance']) ? -1 : 1;

}

function inpost_crc16($data) {

  $crc = 0xFFFF;
  for ($i = 0; $i < strlen($data); $i++) {
    $x = (($crc >> 8) ^ ord($data[$i])) & 0xFF;
    $x ^= $x >> 4;
    $crc = (($crc << 8) ^ ($x << 12) ^ ($x << 5) ^ $x) & 0xFFFF;
  }
  return $crc;

}

function inpost_xml2array($contents, $get_attributes=1) {

  if(!$contents) return array();
  if(!function_exists('xml_parser_create')) {
    return array();
  }
  $parser = xml_parser_create();
  xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, 0 );
  xml_parser_set_option( $parser, XML_OPTION_SKIP_WHITE, 1 );
  xml_parse_into_struct( $parser, $contents, $xml_values );
  xml_parser_free( $parser );

  if(!$xml_values) return;

  $xml_array = array();
  $parents = array();
  $opened_tags = array();
  $arr = array();
  $current = &$xml_array;

    foreach($xml_values as $data) {
        unset($attributes,$value);
        extract($data);
        $result = '';
        if($get_attributes) {
            $result = array();
            if(isset($value)) $result['value'] = $value;
            if(isset($attributes)) {
                foreach($attributes as $attr => $val) {
                    if($get_attributes == 1) $result['attr'][$attr] = $val;
                }
            }
        } elseif(isset($value)) {
            $result = $value;
        }

        if($type == 'open') {
            $parent[$level-1] = &$current;
            if(!is_array($current) or (!in_array($tag, array_keys($current)))) {
                $current[$tag] = $result;
                $current = &$current[$tag];
            } else {
                if(isset($current[$tag][0])) {
                    array_push($current[$tag], $result);
                } else {
                    $current[$tag] = array($current[$tag],$result);
                }
                $last = count($current[$tag]) - 1;
                $current = &$current[$tag][$last];
            }
        } elseif($type == 'complete') {
            if(!isset($current[$tag])) {
                $current[$tag] = $result;
            } else {
                if((is_array($current[$tag]) and $get_attributes == 0)
                        or (isset($current[$tag][0]) and is_array($current[$tag][0]) and $get_attributes == 1)) {
                    array_push($current[$tag],$result);
                } else {
                    $current[$tag] = array($current[$tag],$result);
                }
            }
        } elseif($type == 'close') {
            $current = &$parent[$level-1];
        }
    }
    return($xml_array);

}

  function inpost_post_request($url, $data)
  {

    $_lastArgSeparatorOutput = ini_get('arg_separator.output'); 
    ini_set('arg_separator.output', '&'); 
       
     $params = array('http' => array(
                  'method' => 'POST',
                  'header' => "Content-type: application/x-www-form-urlencoded\r\n"
                . "Content-Length: " . strlen($data) . "\r\n",
                  'content' => $data
               ));
     
     $ctx = stream_context_create($params);
     $fp = @fopen($url, 'rb', false, $ctx);     
     if (!$fp) {        
        return 0;
     }
     
     $response = '';
     while (!feof($fp)) {
     $response .= fread($fp, 8192);
     }


     if ($response === false || $response=='') {
        return 0;
     }
     return $response;
     ini_set('arg_separator.output', $_lastArgSeparatorOutput);
  }

  if (!function_exists('http_build_query')) {
    function http_build_query($data, $prefix='', $sep='', $key='') {
        $ret = array();
        foreach ((array)$data as $k => $v) {
            if (is_int($k) && $prefix != null) {
                $k = urlencode($prefix . $k);
            }
            if ((!empty($key)) || ($key === 0))  $k = $key.'['.urlencode($k).']';
            if (is_array($v) || is_object($v)) {
                array_push($ret, http_build_query($v, '', $sep, $k));
            } else {
                array_push($ret, $k.'='.urlencode($v));
            }
        }
        if (empty($sep)) $sep = ini_get('arg_separator.output');  
        return implode($sep, $ret);
    }// http_build_query
}

 function inpost_digest($string)
 {
  $version=phpversion();
  if ($version[0]<5)
  $digest = base64_encode(pack('H*', md5($string)));
  else
  $digest = base64_encode(md5($string,true));  
  
  return $digest;
 }



?>

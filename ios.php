<?php
// based on https://dokuwiki.nausch.org/doku.php/centos:mail_c6:autoconfig_3
if (getRequestEmail() != ''  and getRequestFullName() != '') {
  $debug = false;

  try {
    $config = loadConfig();
    $xml = file_get_contents("mail/ios.xml");
  } catch(Exception $e) {
    header("HTTP/1.0 500 Internal Server Error");
    die;
  }

  $xml = str_replace("%INFO/NAME%", $config['info']['name'], $xml);
  $xml = str_replace("%INFO/DOMAIN%", $config['info']['domain'], $xml);

  $xml = str_replace("%SERVER/IMAP/HOST%", $config['server']['imap']['host'], $xml);
  $xml = str_replace("%SERVER/IMAP/PORT%", $config['server']['imap']['port'], $xml);

  $xml = str_replace("%SERVER/SMTP/HOST%", $config['server']['smtp']['host'], $xml);
  $xml = str_replace("%SERVER/SMTP/PORT%", $config['server']['smtp']['port'], $xml);

  $xml = str_replace("%USEREMAIL%", getRequestEmail(), $xml);
  $xml = str_replace("%USERFULLNAME%", getRequestFullName(), $xml);

  $temp_config = tempnam(sys_get_temp_dir(), 'apple_template_');
  $filehandle_config = fopen($temp_config, 'w');
  fwrite($filehandle_config, $xml);
  fclose($filehandle_config);

  if($config['sign']['enabled']) {
    $temp_signed = tempnam(sys_get_temp_dir(), 'apple_signed_');
    
    $keypath = $config['sign']['keypath'];
    $crtpath = $config['sign']['crtpath'];
   
    if (!openssl_pkcs7_sign($temp_config, $temp_signed, "file://$crtpath", "file://$keypath", array(), 0)) {
      header("HTTP/1.0 500 Internal Server Error");
      die;
    }
   
    $signed_data = file_get_contents($temp_signed);
    $config_file = base64_decode(preg_replace('/(.+\n)+\n/', '', $signed_data, 1));
  }else{
    $config_file = file_get_contents($temp_config);
  }

  if(isset($debug) and $debug) {
    header("Content-Type: text/plain");
  }else{
    header('Content-type: application/x-apple-aspen-config; chatset=utf-8');
    header('Content-Disposition: attachment; filename="iPMC.mobileconfig"');
  }
 
  echo $config_file;

  unlink($temp_config);
  if($config['sign']['enabled']) unlink($temp_signed);
}else{ 
 echo file_get_contents('ios.html');
}

function loadConfig () {
  return json_decode(implode('', file('settings.json')), true);
}

function getRequestEmail () {
  $email = array_key_exists('email', $_REQUEST) ? $_REQUEST['email'] : null;
  return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function getRequestFullName () {
  return array_key_exists('fullname', $_REQUEST) ? $_REQUEST['fullname'] : null;
}
?>

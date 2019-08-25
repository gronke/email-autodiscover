<?php

try {
  $config = loadConfig();
  $xml = loadTemplate(determineTemplateFile());
} catch(Exception $e) {
  header("HTTP/1.0 500 Internal Server Error");
  exit;
}

header ("Content-Type:text/xml");

$xml = str_replace("%INFO/NAME%", $config['info']['name'], $xml);
$xml = str_replace("%INFO/URL%", $config['info']['url'], $xml);
$xml = str_replace("%INFO/DOMAIN%", $config['info']['domain'], $xml);
$xml = str_replace("%TTL%", $config['ttl'], $xml);


$xml = str_replace("%SERVER/SMTP/ENCRYPTION%", str_replace("STARTTLS", "TLS", $config['server']['smtp']['socket']), $xml);

$xml = str_replace("%SERVER/IMAP/SSL_ON%", isOnOrOff($config['server']['imap']['socket'] == "SSL"), $xml);

$xml = str_replace("%SERVER/IMAP/DOMAIN_REQUIRED%", isOnOrOff($config['server']['domain_required']), $xml);
$xml = str_replace("%SERVER/SMTP/DOMAIN_REQUIRED%", isOnOrOff($config['server']['domain_required']), $xml);

$xml = str_replace("%SERVER/IMAP/HOST%", $config['server']['imap']['host'], $xml);
$xml = str_replace("%SERVER/IMAP/PORT%", $config['server']['imap']['port'], $xml);
$xml = str_replace("%SERVER/IMAP/SOCKET%", $config['server']['imap']['socket'], $xml);

$xml = str_replace("%SERVER/SMTP/HOST%", $config['server']['smtp']['host'], $xml);
$xml = str_replace("%SERVER/SMTP/PORT%", $config['server']['smtp']['port'], $xml);
$xml = str_replace("%SERVER/SMTP/SOCKET%", $config['server']['smtp']['socket'], $xml);

$xml = str_replace("%EMAIL%", getRequestEmail(), $xml);

$xml = removeComments($xml);
$xml = beautify($xml);
echo $xml;


function removeComments ($xml) {
  $pattern = '/<!--(.*)?-->/sU';
  return preg_replace($pattern, '', $xml);
}

function beautify ($xml) {
  $dom = new DOMDocument;
  $dom->preserveWhiteSpace = false;
  $dom->loadXML($xml);
  $dom->formatOutput = true;
  return $dom->saveXml();
}

function isOnOrOff ($value) {
  return ($value === true) ? 'on' : 'off';
}

function loadConfig () {
  $content = file_get_contents('settings.json');
  if ($content === FALSE) {
    throw new Exception('Error reading settings.json.');
  }
  return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
}

function loadTemplate ($file) {
  $content = file_get_contents($file);
  if ($content === FALSE) {
    throw new Exception('Error reading template file.');
  }
  return $content;
}

function determineTemplateFile () {

  $template = $_GET['template'] ?? null;
  
  switch($template) {

    case "config-v1.1.xml":
      $file = 'mail/config-v1.1.xml';
      break;

    default:
      $file = 'mail/autodiscover.xml';
      break;

  }

  return $file;

}

function getRequestEmail () {

  $email = $_GET['email'] ?? null;
  return filter_var($email, FILTER_VALIDATE_EMAIL);

}

?>

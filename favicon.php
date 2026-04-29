<?php
use phpseclib3\Crypt\AES;

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");


if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405); 
    echo " ";
    exit;
}


require 'vendor/autoload.php';


$aes = new AES('cbc');

$key = 'PenePenePenePene';  
$aes->setKey($key);

$iv = random_bytes(16); 
$aes->setIV($iv);

$data = "HolaHolaHolaHola";
$encrypted = $aes->encrypt($data);
echo base64_encode($iv . $encrypted);


?>

<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type:application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS');
header('Access-Control-Allow-Credentials: true');
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../models/Security.php';
include_once '../../config/Database.php';
include_once '../response/Response.php';

$resp = new Response();
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $data = json_decode(file_get_contents('php://input'), true);
    $security = new Security(Database::getDBConnection());
    $resp->data = $security->createSecurity($data);
}else{
    header('Allow: POST');
    header('HTTP/1.1 405 Method Not Allowed');
    echo 'Unsupported HTTP method';
}
json_encode($resp)
?>

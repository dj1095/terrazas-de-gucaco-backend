<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type:application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS');
header('Access-Control-Allow-Credentials: true');
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../models/Security.php';
require_once '../service/SecurityService.php';
require_once '../helper/Utils.php';

$request_method = $_SERVER['REQUEST_METHOD'];
$uri = explode('/', $_SERVER['REQUEST_URI']);
$resource = $request_method . ' /' . end($uri);
$resp = new Response();
$securityService = new SecurityService();

try {
    switch ($resource) {

            //Get a single secuirty with given id 
        case preg_match('/^GET \/security\.php\?[A-za-z]+=[0-9]+$/', $resource) == 1:
            break;

            //Get all of the securities details
        case preg_match('/^GET \/security\.php$/', $resource) == 1:
            $data = json_decode(file_get_contents('php://input'), true);
            $users = $securityService->get();
            $resp = Utils::buildResponse(200, $users, "Fetch all users Succesful", null);
            echo json_encode($resp);
            break;

            //Create a security guard
        case preg_match('/^POST \/security\.php$/', $resource) == 1:
            $data = json_decode(file_get_contents('php://input'), true);
            $security = $securityService->createSecurity($data);
            $resp = Utils::buildResponse(200, array($security), "Security Created", null);
            echo json_encode($resp);
            break;
        default:
            http_response_code(400);
            $errorMessage = "No API found with the given Http Method and URL";
            $resp = Utils::buildResponse(400, [], null, $errorMessage);
            echo json_encode($resp);
            break;
    }
} catch (Exception $ex) {
    http_response_code(400);
    $errorMessage = $ex->getMessage();
    if($ex instanceof PDOException){
        $errorMessage = Utils::handleDBExceptions($ex);
    }
    $resp = Utils::buildResponse(400, [], null, $errorMessage);
    echo json_encode($resp);
}


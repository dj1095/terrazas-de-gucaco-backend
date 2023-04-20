<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token, Authorization');
header('Content-Type:application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,PATCH,OPTIONS');
header('Access-Control-Allow-Credentials: true');
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token, Authorization');
    header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,PATCH,OPTIONS');
    header('Access-Control-Allow-Credentials: true');
    exit;
  }

require_once '../../login/service/UserService.php';
require_once '../helper/Utils.php';
require_once '../helper/Constants.php';

$data = json_decode(file_get_contents('php://input'), true);
$request_method = $_SERVER['REQUEST_METHOD'];
$uri = explode('/', $_SERVER['REQUEST_URI']);
$resource = $request_method . ' /' . end($uri);
$resp = new Response();
$userService = new UserService();
try {
    switch ($resource) {
        case preg_match('/^POST \/validate_login\.php$/', $resource) == 1:
            $email = isset($data["email"]) ? $data["email"] : null;
            $password = isset($data["password"]) ? $data["password"] : null;
            $user = $userService->validateUser($email, $password);
            $resp = '';
            if(count($user) > 0){
                $resp = Utils::buildResponse(200, $user, "Found an User with Valid Credentials", null);
            }else{
                $resp = Utils::buildResponse(401, $user, null,"No User Found with this Credentials.");
            }
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
    //var_dump($ex);
    http_response_code(400);
    $errorMessage = $ex->getMessage();
    if ($ex instanceof PDOException) {
        $errorMessage = Utils::handleDBExceptions($ex);
    }
    $resp = Utils::buildResponse(400, [], null, $errorMessage);
    echo json_encode($resp);
}
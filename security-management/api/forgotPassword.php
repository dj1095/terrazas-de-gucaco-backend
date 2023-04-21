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
require_once '../../login/service/EmailService.php';
require_once '../helper/Utils.php';
require_once '../helper/Constants.php';

$data = json_decode(file_get_contents('php://input'), true);
$request_method = $_SERVER['REQUEST_METHOD'];
$uri = explode('/', $_SERVER['REQUEST_URI']);
$resource = $request_method . ' /' . end($uri);
$resp = new Response();
$userService = new UserService();
$email_service = new EmailService();
try {
    switch ($resource) {
        case preg_match('/^POST \/forgotPassword\.php$/', $resource) == 1:
            $email = isset($data["email"]) ? $data["email"] : "";
            $user = $userService->getUserDetails($email);
            $resp = '';
            if (count($user) > 0) {
                $user = $user[0];
                $subject = 'Password Recovery Email';
                $body = 'The password for '.$user["first_name"].' '.$user["last_name"].' is: '.$user["password"];
                $to_name = $user["first_name"].' '.$user["last_name"];
                $email_service->send_email($email, $to_name , $subject, $body);
                $resp = Utils::buildResponse(200, [], "Email Sent Succesfully", null);
            }else{
                $resp = Utils::buildResponse(401, [], null,"Invalid Email. User not found");
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

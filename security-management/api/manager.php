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

require_once '../helper/Utils.php';
require_once '../helper/Constants.php';
require_once '../service/ManagerService.php';

$request_method = $_SERVER['REQUEST_METHOD'];
$uri = explode('/', $_SERVER['REQUEST_URI']);
$resource = $request_method . ' /' . end($uri);
$resp = new Response();
$managerService = new ManagerService();
try {
    $data = json_decode(file_get_contents('php://input'), true);
    switch ($resource) {
        case preg_match('/^POST \/manager\.php$/', $resource) == 1:
            $manager = $managerService->createManager($data);
            $resp = Utils::buildResponse(200, $manager, "Manager Created", null);
            echo json_encode($resp);
            break;

        case preg_match('/^GET \/manager\.php$/', $resource) == 1:
            $users = $managerService->get();
            $message = count($users) > 0 ? "Fetch all users Succesful" : "No Results Found";
            $resp = Utils::buildResponse(200, $users, $message, null);
            echo json_encode($resp);
            break;

        case preg_match('/^PATCH \/manager\.php$/', $resource) == 1:
            $manager = $managerService->updateManagerDetails($data);
            $message = count($manager) > 0 ? "Update Successful" : "Unable to Update.";
            $resp = Utils::buildResponse(200, $manager, $message, null);
            echo json_encode($resp);
            break;
        
        case preg_match('/^DELETE \/manager\.php\?email=[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}+$/', $resource) == 1:
            $data['email'] = $_GET['email'];
            $isDeleted = $managerService->deleteManager($data);
            $message = $isDeleted ? "Manager Deleted Successfully" : "Unable to delete manager";
            $resp = Utils::buildResponse(200, [], $message, null);
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

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

require_once '../../models/Security.php';
require_once '../service/SecurityTimingsService.php';
require_once '../helper/Utils.php';

$request_method = $_SERVER['REQUEST_METHOD'];
$uri = explode('/', $_SERVER['REQUEST_URI']);
$resource = $request_method . ' /' . end($uri);
$resp = new Response();
$securityTimingsService = new SecurityTimingsService();
try {
    $data = json_decode(file_get_contents('php://input'), true);
    switch ($resource) {
            //Get a single secuirty with given id 
        case preg_match('/^GET \/timings\.php\?shift_id=[0-9]+&userId=[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $resource) == 1:
            //access id as route param
            $id = $_GET['shift_id'];
            $mangerId = $_GET['userId'];
            $timings = $securityTimingsService->getShiftDetails($id, $mangerId);
            $message = count($timings) > 0 ? "Fetch user succesful" : "No Results Found";
            $resp = Utils::buildResponse(200, $timings, $message, null);
            echo json_encode($resp);
            break;

            //Get all of the shift details
        case preg_match('/^GET \/timings\.php$/', $resource) == 1:
            $users = $securityTimingsService->get();
            $message = count($users) > 0 ? "Fetch all shifts Succesful" : "No Results Found";
            $resp = Utils::buildResponse(200, $users, $message, null);
            echo json_encode($resp);
            break;

            //Create a shift for security guard
        case preg_match('/^POST \/timings\.php$/', $resource) == 1:
            $security = $securityTimingsService->createShift($data);
            $resp = Utils::buildResponse(200, $security, "Shift Added", null);
            echo json_encode($resp);
            break;

            //Update shift details for security guard
        case preg_match('/^PATCH \/timings\.php$/', $resource) == 1:
            $security = $securityTimingsService->updateShiftDetails($data);
            $message = count($security) > 0 ? "Update Successful" : "Unable to Update. Shift Id does not exist";
            $resp = Utils::buildResponse(200, $security, $message, null);
            echo json_encode($resp);
            break;
        case preg_match('/^DELETE \/timings\.php\?shift_id=[0-9]+&userId=[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $resource) == 1:
            $data['id'] = $_GET['shift_id'];
            $data['userId'] = $_GET['userId'];
            $isDeleted = $securityTimingsService->deleteShiftDetails($data);
            $message = $isDeleted ? "Delete Successful" : "Unable to Delete. Security_id does not exist";
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
    var_dump($ex);
    http_response_code(400);
    $errorMessage = $ex->getMessage();
    if ($ex instanceof PDOException) {
        $errorMessage = Utils::handleDBExceptions($ex);
    }
    $resp = Utils::buildResponse(400, [], null, $errorMessage);
    echo json_encode($resp);
}

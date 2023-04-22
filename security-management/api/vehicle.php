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

require_once '../service/ResidentService.php';
require_once '../helper/Utils.php';
require_once '../helper/Constants.php';
require_once '../service/ManagerService.php';

$request_method = $_SERVER['REQUEST_METHOD'];
$uri = explode('/', $_SERVER['REQUEST_URI']);
$resource = $request_method . ' /' . end($uri);
$resp = new Response();
$residentService = new ResidentService();
try {
    $data = json_decode(file_get_contents('php://input'), true);
    switch ($resource) {
        case preg_match('/^GET \/vehicle\.php\?userId=[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $resource) == 1:
            //access id as route param
            $data["userId"] = isset($_GET['userId']) ? $_GET['userId'] : "";
            $users = $residentService->getVehicles($data);
            $message = count($users) > 0 ? "Fetch user succesful" : "No Results Found";
            $resp = Utils::buildResponse(200, $users, $message, null);
            echo json_encode($resp);
            break;
        case preg_match('/^POST \/vehicle\.php$/', $resource) == 1:
            $vehicle = $residentService->addVehicle($data);
            $resp = Utils::buildResponse(200, $vehicle, "Vehicle Created", null);
            echo json_encode($resp);
            break;

        case preg_match('/^PATCH \/vehicle\.php$/', $resource) == 1:
            $resident = $residentService->updateVehicles($data);
            $message = count($resident) > 0 ? "Update Successful" : "Unable to Update Vehicle Details.";
            $resp = Utils::buildResponse(200, $resident, $message, null);
            echo json_encode($resp);
            break;

        case preg_match('/^DELETE \/vehicle\.php\?vehicle_id=[0-9]+$/', $resource) == 1:
            $data['vehicle_id'] = $_GET['vehicle_id'];
            $isDeleted = $residentService->deleteVehicle($data);
            $message = $isDeleted ? "Vehicle Deleted Successfully" : "Unable to delete Vehicle";
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

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

require_once '../service/EventService.php';
require_once '../helper/Utils.php';
require_once '../helper/Constants.php';

$request_method = $_SERVER['REQUEST_METHOD'];
$uri = explode('/', $_SERVER['REQUEST_URI']);
$resource = $request_method . ' /' . end($uri);
$resp = new Response();
$eventService = new EventService();
try {
    $data = json_decode(file_get_contents('php://input'), true);
    switch ($resource) {
        case preg_match('/^GET \/residentEvents\.php\?userId=[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $resource) == 1:
            //access id as route param
            $email = isset($_GET['userId']) ? $_GET['userId'] : "";
            $events = $eventService->getResidentEvents($email);
            $message = count($events) > 0 ? "Fetch Events succesful" : "No Results Found";
            $resp = Utils::buildResponse(200, $events, $message, null);
            echo json_encode($resp);
            break;

        case preg_match('/^POST \/residentEvents\.php$/', $resource) == 1:
            $event = $eventService->registerResidentForEvent($data);
            $resp = Utils::buildResponse(200, $event, "Event Successfuly Registered", null);
            echo json_encode($resp);
            break;

        case preg_match('/^DELETE \/residentEvents\.php\?event_id=[0-9]+&userId=[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $resource) == 1:
            $data['event_id'] = $_GET['event_id'];
            $data['userId'] = $_GET['userId'];
            $isDeleted = $eventService->cancelResidentEvent($data);
            $message = $isDeleted != -1 ? "Event Registration Cancelled Successfully" : "Unable to cancel event registration.";
            $resp = Utils::buildResponse(200, [$isDeleted], $message, null);
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

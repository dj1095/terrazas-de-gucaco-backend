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
        case preg_match('/^GET \/events\.php\?visitor_id=[0-9]+$/', $resource) == 1:
            //access id as route param
            $visitor_id = isset($_GET['visitor_id']) ? $_GET['visitor_id'] : "";
            $events = $eventService->getVisitorEvents($visitor_id);
            $message = count($events) > 0 ? "Fetch user succesful" : "No Results Found";
            $resp = Utils::buildResponse(200, $events, $message, null);
            echo json_encode($resp);
            break;

        case preg_match('/^POST \/events\.php$/', $resource) == 1:
            $event = $eventService->registerForEvent($data);
            $resp = Utils::buildResponse(200, $event, "Event Successfuly Registered", null);
            echo json_encode($resp);
            break;

        case preg_match('/^DELETE \/events\.php\?event_id=[0-9]+&visitor_id=[0-9]+$/', $resource) == 1:
            $data['event_id'] = $_GET['event_id'];
            $data['visitor_id'] = $_GET['visitor_id'];
            $isDeleted = $eventService->cancelEvent($data);
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
    var_dump($ex);
    http_response_code(400);
    $errorMessage = $ex->getMessage();
    if ($ex instanceof PDOException) {
        $errorMessage = Utils::handleDBExceptions($ex);
    }
    $resp = Utils::buildResponse(400, [], null, $errorMessage);
    echo json_encode($resp);
}

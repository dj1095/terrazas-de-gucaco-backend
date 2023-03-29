<?php
require_once '../response/Response.php';

class Utils
{

    public static function buildResponse($status_code, $data = array(), $message = "", $error_message = "")
    {
        $resp = new Response();
        $resp->status_code = $status_code;
        $resp->successMessage = $message;
        $resp->errorMessage = $error_message;
        $resp->set_data($data);
        return $resp;
    }

    public static function handleDBExceptions(PDOException $ex)
    {
        if (strpos($ex->getMessage(), "Integrity constraint violation: 1062 Duplicate entry")) {
            return "Email already exists";
        }
    }
}

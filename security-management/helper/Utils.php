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

    public static function buildUpdateQuery($tableName, $allowedColumns, $toBeUpdatedCols, $wherClauses)
    {
        $fields_to_be_updated = array();
        $conditions = array();
        $values = array();
        foreach ($toBeUpdatedCols as $key => $value) {
            if (in_array($key, $allowedColumns)) {
                $fields_to_be_updated[] = sprintf('%s = ?', $key);
                $values[] = $value;
            }
        }
        $partialQuery = count($fields_to_be_updated) == 1 ?  $fields_to_be_updated[0] : implode(',', $fields_to_be_updated);
        foreach ($wherClauses as $key => $value) {
            $conditions[] = sprintf('%s = ?', $key);
            $values[] = $value;
        }
        $whereClause = count($conditions) == 1 ?  $conditions[0] : implode(',', $conditions);
        $query = sprintf('UPDATE ' . $tableName . ' SET %s WHERE %s', $partialQuery, $whereClause);
        return array($query, $values);
    }

    public static function handleDBExceptions(PDOException $ex)
    {
        if (strpos($ex->getMessage(), "Integrity constraint violation: 1062 Duplicate entry")) {
            return "Email already exists";
        }
    }
}

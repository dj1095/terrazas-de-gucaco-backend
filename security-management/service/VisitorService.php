<?php
require_once '../../config/Database.php';
require_once '../helper/Utils.php';
require_once '../service/ManagerService.php';

class VisitorService
{
    private $conn;
    private $visitor_table = "Visitor";
    private $visitor_details_table = "VisitorDetails";
    private $managerService = null;

    public function __construct()
    {
        $this->conn = Database::getDBConnection();
        $this->managerService = new ManagerService();
    }

    public function get()
    {
        $query = 'SELECT 
        v.visitor_id,
        v.first_name,
        v.last_name,
        v.email,
        v.phone_number,
        vd.vehicle_plate,
        v.access_granted
    FROM
        Visitor v
            LEFT JOIN
        VisitorDetails vd ON v.visitor_id = vd.visitor_id';
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $results_arr = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            array_push($results_arr, $row);
        }
        return $results_arr;
    }

    public function getVisitorDetails($visitor_id , $usr_email)
    {

        $managerId = $this->isAuthorized($usr_email);
        if ($managerId == false) {
            throw new Exception("Not Authorized");
        }

        $query = 'SELECT 
        v.visitor_id,
        v.first_name,
        v.last_name,
        v.email,
        v.phone_number,
        vd.vehicle_plate,
        v.access_granted
    FROM
        Visitor v
            LEFT JOIN
        VisitorDetails vd ON v.visitor_id = vd.visitor_id 
        WHERE
    v.visitor_id = :visitor_id;';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':visitor_id', $visitor_id);
        $stmt->execute();
        $users = $stmt->rowCount() == 0 ? array() : array($stmt->fetch(PDO::FETCH_ASSOC));
        return $users;
    }

    public function isAuthorized($mgr_email)
    {
        $managerDetails = $this->managerService->getManagerDetails($mgr_email);
        $managerTitle = "";
        if (count($managerDetails) > 0) {
            $managerTitle = isset($managerDetails[0]["mgr_title"]) ? $managerDetails[0]["mgr_title"] : "";
            if (Constants::BUILDING_MANAGER === $managerTitle || Constants::SECURITY_MANAGER === $managerTitle) {
                return is_numeric($managerDetails[0]["mgr_id"]) ? intval($managerDetails[0]["mgr_id"]) : $managerDetails[0]["mgr_id"];
            }
        }
        return false;
    }

    public function updateVisitorDetails($data)
    {
        //check if manger is building manager or security manager
        $usr_email = isset($data["userId"]) ? $data["userId"] : null;
        //return false if no manager is found else return manager id
        $managerId = $this->isAuthorized($usr_email);
        if ($managerId == false) {
            throw new Exception("Not Authorized");
        }
        if (!isset($data["visitor_id"]) || count($data) <= 1) {
            throw new Exception("Bad Request. Invalid Visitor id or no details given to update");
        }
        $id = htmlspecialchars(strip_tags($data["visitor_id"]));
        if (count($this->getVisitorDetails($id, $usr_email)) > 0) {
            $allowedColums = array('access_granted');
            $whereCols = ["visitor_id" => $data["visitor_id"]];
            $queryValues = Utils::buildUpdateQuery($this->visitor_table, $allowedColums, $data, $whereCols);
            $query = $queryValues[0];
            $values = $queryValues[1];
            $stmt = $this->conn->prepare($query);
            $stmt->execute($values);
            $updatedDetails = $this->getVisitorDetails($id, $usr_email);
            return $updatedDetails;
        }
        return array();
    }


    
}

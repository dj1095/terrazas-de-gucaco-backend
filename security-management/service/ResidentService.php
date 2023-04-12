<?php
require_once '../../config/Database.php';
require_once '../helper/Utils.php';
require_once '../service/ManagerService.php';

class ResidentService
{
    private $conn;
    private $residents_table = "residents";
    private $managerService = null;

    public function __construct()
    {
        $this->conn = Database::getDBConnection();
        $this->managerService = new ManagerService();
    }

    public function get()
    {
        $query = 'SELECT * FROM '. $this->residents_table;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $results_arr = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            array_push($results_arr, $row);
        }
        return $results_arr;
    }

    public function getResidentDetails($resident_id , $usr_email)
    {

        $managerId = $this->isAuthorized($usr_email);
        if ($managerId == false) {
            throw new Exception("Not Authorized");
        }

        $query = 'SELECT * FROM '. $this->residents_table .' WHERE resident_id = :resident_id';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':resident_id', $resident_id);
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

    public function updateResidentDetails($data)
    {
        //check if manger is building manager or security manager
        $usr_email = isset($data["userId"]) ? $data["userId"] : null;
        //return false if no manager is found else return manager id
        $managerId = $this->isAuthorized($usr_email);
        if ($managerId == false) {
            throw new Exception("Not Authorized");
        }
        if (!isset($data["resident_id"]) || count($data) <= 1) {
            throw new Exception("Bad Request. Invalid Resident id or no details given to update");
        }
        $id = htmlspecialchars(strip_tags($data["resident_id"]));
        if (count($this->getResidentDetails($id, $usr_email)) > 0) {
            $allowedColums = array('access_granted');
            $whereCols = ["resident_id" => $data["resident_id"]];
            $queryValues = Utils::buildUpdateQuery($this->residents_table, $allowedColums, $data, $whereCols);
            $query = $queryValues[0];
            $values = $queryValues[1];
            $stmt = $this->conn->prepare($query);
            $stmt->execute($values);
            $updatedDetails = $this->getResidentDetails($id, $usr_email);
            return $updatedDetails;
        }
        return array();
    }


    
}

<?php
require_once '../../config/Database.php';
require_once '../../models/Security.php';
require_once '../helper/Utils.php';
require_once '../helper/Constants.php';
require_once '../service/ManagerService.php';
require_once '../service/SecurityService.php';

class SecurityTimingsService
{
    private $conn;
    private $table = "Security";
    private $securityTimings = "Security_Timings";
    private $managerService = null;
    private $securityService = null;

    public function __construct()
    {
        $this->conn = Database::getDBConnection();
        $this->managerService = new ManagerService();
        $this->securityService = new SecurityService();
    }

    public function deleteShiftDetails($data)
    {
        //check if manger is building manager or security manager
        $usr_email = isset($data["userId"]) ? $data["userId"] : "";
        //return false if no manager is found else return manager id
        $managerId = $this->isAuthorized($usr_email);
        if ($managerId == false) {
            throw new Exception("Not Authorized");
        }
        if (!isset($data["id"])) {
            throw new Exception("Bad Request. Invalid shift id ");
        }
        $id = htmlspecialchars(strip_tags($data["id"]));
        if (count($this->getShiftDetails($id, $usr_email)) > 0) {
            $query = 'DELETE FROM ' . $this->securityTimings . '
            WHERE
            id = :id';
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $data['id']);
            $stmt->execute();
            return  $stmt->rowCount() > 0 ? true : false;
        }
        return array();
    }

    public function getShiftDetails($id, $userId)
    {
        $id = htmlspecialchars(strip_tags($id));
        $userId = htmlspecialchars(strip_tags($userId));
        if (!isset($id)) {
            throw new Exception("Invalid Shift Id");
        }
        //check if manger is building manager or security manager
        //return false if no manager is found else return manager id
        $managerId = $this->isAuthorized($userId);
        if ($managerId == false) {
            throw new Exception("Not Authorized");
        }
        $query = 'SELECT 
        st.id,
        sc.first_name,
        sc.last_name,
        sc.phone_number,
        st.shift_date,
        TIME_FORMAT(st.shift_time, "%H:%i") as shift_time,
        st.place,
        sc.security_id
    FROM
        Security sc,
        Security_Timings st
    WHERE
        sc.security_id = st.security_id
        AND st.id = :id;';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $shift = $stmt->rowCount() == 0 ? array() : array($stmt->fetch(PDO::FETCH_ASSOC));
        return $shift;
    }

    public function createShift($data)
    {
        //validate and sanitize the data
        $securityData = $this->sanitizeData($data);
        $userId = isset($data["userId"]) ? $data["userId"] : null;
        if (!isset($data["shift_date"]) || !isset($data["email"]) || !isset($data["shift_time"]) || !isset($data["place"])) {
            throw new Exception("mandatory fields[email,date,time & place] missing.");
        }
        //return false if no manager is found else return manager id
        $managerId = $this->isAuthorized($userId);
        if ($managerId == false) {
            throw new Exception("Not Authorized");
        }
        //return false if no security with given email is found else return security id
        $securityId = $this->getSecurityId($data["email"]);
        if ($securityId == false) {
            throw new Exception("Security email doesnot not exists");
        }

        $query = 'INSERT INTO ' . $this->securityTimings . '
        SET
        shift_date = :shift_date,
        shift_time = :shift_time,
        place = :place,
        security_id = :security_id';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':shift_date', $securityData['shift_date']);
        $stmt->bindParam(':shift_time', $securityData['shift_time']);
        $stmt->bindParam(':place', $securityData['place']);
        $stmt->bindParam(':security_id', $securityId);
        $stmt->execute();

        $shiftId = $this->conn->lastInsertId();
        return $this->getShiftDetails($shiftId, $userId);
    }



    public function updateShiftDetails($data)
    {
        if (!isset($data["id"]) || count($data) <= 1) {
            throw new Exception("Bad Request. Invalid shift id or no details given to update");
        }
        $id = htmlspecialchars(strip_tags($data["id"]));
        //check if manger is building manager or security manager
        $usr_email = isset($data["userId"]) ? $data["userId"] : null;

        //return false if no manager is found else return manager id
        $managerId = $this->isAuthorized($usr_email);
        if ($managerId == false) {
            throw new Exception("Not Authorized");
        }

        if (count($this->getShiftDetails($id, $usr_email)) > 0) {
            $allowedColums = array('shift_date', 'shift_time', 'place');
            $whereCols = ["id" => $data["id"]];
            $queryValues = Utils::buildUpdateQuery($this->securityTimings, $allowedColums, $data, $whereCols);
            $query = $queryValues[0];
            $values = $queryValues[1];
            $stmt = $this->conn->prepare($query);
            $stmt->execute($values);
            $updatedDetails = $this->getShiftDetails($id, $usr_email);
            return $updatedDetails;
        }
        return array();
    }


    public function get()
    {
        $query =  $query = 'SELECT 
        st.id,
        sc.first_name,
        sc.last_name,
        sc.phone_number,
        st.shift_date,
        TIME_FORMAT(st.shift_time, "%H:%i") as shift_time,
        st.place,
        sc.security_id
    FROM
        Security sc,
        Security_Timings st
    WHERE
        sc.security_id = st.security_id';

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $results_arr = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            array_push($results_arr, $row);
        }
        return $results_arr;
    }

    public function getSecurityId($email)
    {
        $securityData = $this->securityService->getSecurityId($email);
        if (count($securityData) > 0) {
            return isset($securityData[0]["security_id"]) ? intval($securityData[0]["security_id"]) : false;
        }
        return false;
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

    public function sanitizeData($data)
    {
        $email = isset($data["email"]) ? $data["email"] : null;
        $place = isset($data["place"]) ? $data["place"] : null;
        $shift_time = isset($data["shift_time"]) ? $data["shift_time"] : null;
        $shift_date = isset($data["shift_date"]) ? $data["shift_date"] : null;
        $data["email"] = htmlspecialchars(strip_tags($email));
        $data["place"] = htmlspecialchars(strip_tags($place));
        $data["shift_time"] = htmlspecialchars(strip_tags($shift_time));
        $data["shift_date"] = htmlspecialchars(strip_tags($shift_date));
        return $data;
    }
}

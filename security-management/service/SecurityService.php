<?php
require_once '../../config/Database.php';
require_once '../../models/Security.php';
require_once '../helper/Utils.php';
require_once '../helper/Constants.php';
require_once '../service/ManagerService.php';

class SecurityService
{
    private $conn;
    private $table = "Security";
    private $managerService = null;

    public function __construct()
    {
        $this->conn = Database::getDBConnection();
        $this->managerService = new ManagerService();
    }

    public function deleteSecurityDetails($data)
    {
        //check if manger is building manager or security manager
        $managerId = isset($data["mgr_id"]) ? $data["mgr_id"] : "";
        if (!$this->isAuthorized($managerId)) {
            throw new Exception("Not Authorized");
        }
        if (!isset($data["security_id"])) {
            throw new Exception("Bad Request. Invalid security id ");
        }
        $id = htmlspecialchars(strip_tags($data["security_id"]));
        if (count($this->getSecurityDetails($id, $managerId)) > 0) {
            $query = 'DELETE FROM ' . $this->table . '
            WHERE
            security_id = :id';
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $data['security_id']);
            $stmt->execute();
            return  $stmt->rowCount() > 0 ? true : false;
        }
        return array();
    }

    public function updateSecurityDetails($data)
    {
        //check if manger is building manager or security manager
        $managerId = isset($data["mgr_id"]) ? $data["mgr_id"] : null;
        if (!$this->isAuthorized($managerId)) {
            throw new Exception("Not Authorized");
        }
        if (!isset($data["security_id"]) || count($data) <= 1) {
            throw new Exception("Bad Request. Invalid security id or no details given to update");
        }
        $id = htmlspecialchars(strip_tags($data["security_id"]));
        if (count($this->getSecurityDetails($id, $managerId)) > 0) {
            $allowedColums = array('first_name', 'last_name', 'email', 'phone_number', 'timings', 'place');
            $whereCols = ["security_id" => $data["security_id"]];
            $queryValues = Utils::buildUpdateQuery($this->table, $allowedColums, $data, $whereCols);
            $query = $queryValues[0];
            $values = $queryValues[1];
            $stmt = $this->conn->prepare($query);
            $stmt->execute($values);
            $updatedDetails = $this->getSecurityDetails($id, $managerId);
            return $updatedDetails;
        }
        return array();
    }

    public function getSecurityDetails($id, $managerId)
    {
        $id = htmlspecialchars(strip_tags($id));
        if (!isset($id)) {
            throw new Exception("Invalid Security Id");
        }
        //check if manger is building manager or security manager
        if (!$this->isAuthorized($managerId)) {
            throw new Exception("Not Authorized");
        }
        $stmt = $this->conn->prepare('SELECT * FROM ' . $this->table . ' WHERE security_id = :securityId');
        $stmt->bindParam(':securityId', $id);
        $stmt->execute();
        $users = $stmt->rowCount() == 0 ? array() : array($stmt->fetch(PDO::FETCH_ASSOC));
        return $users;
    }

    public function get()
    {
        $query = 'SELECT * FROM ' . $this->table;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $results_arr = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            array_push($results_arr, $row);
        }
        return $results_arr;
    }

    public function createSecurity($data)
    {
        //validate and sanitize the data
        $securityData = $this->sanitizeData($data);
        $managerId = $data["mgr_id"];
        if (!$this->isAuthorized($managerId)) {
            throw new Exception("Not Authorized");
        }
        if (!isset($data["last_name"]) || !isset($data["email"]) || !isset($data["phone_number"])) {
            throw new Exception("mandatory fields[lastname,email,phone_number] missing.");
        }

        $query = 'INSERT INTO ' . $this->table . '
        SET
        first_name = :first_name,
        last_name = :last_name,
        email =:email,
        phone_number = :phone_number,
        timings = :timings,
        place = :place,
        mgr_id = :mgr_id';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':first_name', $securityData['first_name']);
        $stmt->bindParam(':last_name', $securityData['last_name']);
        $stmt->bindParam(':email', $securityData['email']);
        $stmt->bindParam(':phone_number', $securityData['phone_number']);
        $stmt->bindParam(':timings', $securityData['timings']);
        $stmt->bindParam(':place', $securityData['place']);
        $stmt->bindParam(':mgr_id', $securityData['mgr_id']);
        $stmt->execute();

        $securityId = $this->conn->lastInsertId();
        return $this->getSecurityDetails($securityId, $managerId);
    }

    public function isAuthorized($managerId)
    {
        $managerDetails = $this->managerService->getManagerDetails($managerId);
        $managerTitle = "";
        if (count($managerDetails) > 0) {
            $managerTitle = isset($managerDetails[0]["mgr_title"]) ? $managerDetails[0]["mgr_title"] : "";
        }
        return Constants::BUILDING_MANAGER == $managerTitle || Constants::SECURITY_MANAGER == $managerTitle;
    }

    public function sanitizeData($data)
    {
        $firstname = isset($data["first_name"]) ? $data["first_name"] : null;
        $lastname = isset($data["last_name"]) ? $data["last_name"] : null;
        $email = isset($data["email"]) ? $data["email"] : null;
        $phone = isset($data["phone_number"]) ? $data["phone_number"] : null;
        $timings = isset($data["timings"]) ? $data["timings"] : null;
        $place = isset($data["place"]) ? $data["place"] : null;
        $data["first_name"] = htmlspecialchars(strip_tags($firstname));
        $data["last_name"] = htmlspecialchars(strip_tags($lastname));
        $data["email"] = htmlspecialchars(strip_tags($email));
        $data["phone"] = htmlspecialchars(strip_tags($phone));
        $data["timings"] = htmlspecialchars(strip_tags($timings));
        $data["place"] = htmlspecialchars(strip_tags($place));
        return $data;
    }
}

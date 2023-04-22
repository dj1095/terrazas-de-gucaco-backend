<?php
require_once '../../config/Database.php';
require_once '../helper/Utils.php';
require_once '../helper/Constants.php';
require_once '../service/ManagerService.php';

class ResidentService
{
    private $conn;
    private $residents_table = "residents";
    private $users_table = "User";
    private $managerService = null;
    private $RESIDENT_ROLE_ID = 6;
    private $RESIDENT = "resident";

    public function __construct()
    {
        $this->conn = Database::getDBConnection();
        $this->managerService = new ManagerService();
    }

    public function get()
    {
        $query = 'SELECT * FROM ' . $this->residents_table;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $results_arr = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            array_push($results_arr, $row);
        }
        return $results_arr;
    }

    public function getResidentDetails($resident_id, $usr_email)
    {

        /*$managerId = $this->isAuthorized($usr_email);
        if ($managerId == false) {
            throw new Exception("Not Authorized");
        } */

        $query = 'SELECT * FROM ' . $this->residents_table . ' WHERE resident_id = :resident_id';
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
        $resident_id = isset($data["resident_id"]) ? $data["resident_id"] : null;
        $residents = $this->getResidentDetails($resident_id,"");
        if(count($residents) < 1){
            return array();
        }
        try{
            $resident = $residents[0];
            $resident_email = $resident["email"];
            $this->conn->beginTransaction();
            // Update User Table 
            $first_name = isset($data["first_name"]) ? $data["first_name"] : $resident["first_name"];
            $last_name = isset($data["last_name"]) ? $data["last_name"] : $resident["last_name"];
            $email = isset($data["email"]) ? $data["email"] : $resident["email"];
            
            $user_query = "UPDATE " . $this->users_table . "
            SET
            first_name = :first_name,
            last_name = :last_name,
            email =:email
            WHERE email = :resident_email
            ";
            $stmt = $this->conn->prepare($user_query);
            $stmt->bindParam(':last_name', $last_name);
            $stmt->bindParam(':first_name', $first_name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':resident_email', $resident_email);
            $stmt->execute();

            // Upadte Resident Table 
            $phone = isset($data["phone"]) ? $data["phone"] : $resident["phone"];
            $unit_number = isset($data["unit_number"]) ? $data["unit_number"] : $resident["unit_number"];
            $building_id = isset($data["building_id"]) ? $data["building_id"] : $resident["building_id"];
            $access_granted = isset($data["access_granted"]) ? $data["access_granted"] : $resident["access_granted"];
            $DOB = isset($data["DOB"]) ? $data["DOB"] : $resident["DOB"];

            $resident_query = "UPDATE " . $this->residents_table . "
            SET
            first_name = :first_name,
            last_name = :last_name,
            email =:email,
            phone = :phone,
            unit_number = :unit_number,
            building_id = :building_id,
            access_granted = :access_granted,
            DOB = :DOB
            WHERE resident_id = :resident_id
            ";

            $stmt2 = $this->conn->prepare($resident_query);
            $stmt2->bindParam(':last_name', $last_name);
            $stmt2->bindParam(':first_name', $first_name);
            $stmt2->bindParam(':email', $email);
            $stmt2->bindParam(':phone', $phone);
            $stmt2->bindParam(':unit_number', $unit_number);
            $stmt2->bindParam(':building_id', $building_id);
            $stmt2->bindParam(':access_granted', $access_granted);
            $stmt2->bindParam(':DOB', $DOB);
            $stmt2->bindParam(':resident_id', $resident_id);
            $stmt2->execute();

            $this->conn->commit();
            return $this->getResidentDetails($resident_id,"");
        }catch(Exception $ex){
            $this->conn->rollback();
            throw new Exception("Unable To Update Resident ", -1, $ex);
        }
    }

    public function createResident($data)
    {

        $data = $this->sanitizeData($data);
        $userId = isset($data["userId"]) ? $data["userId"] : null;
        if (!isset($data["last_name"]) || !isset($data["email"]) || !isset($data["password"])) {
            throw new Exception("mandatory fields[lastname,email,password] missing.");
        }

        try {
            $this->conn->beginTransaction();
            // Insert into users table to create a resident
            $user_query = "INSERT INTO " . $this->users_table . "
        SET
        first_name = :first_name,
        last_name = :last_name,
        email =:email,
        password = :password,
        role_id = :role_id,
        title = :title
        ";

            $stmt = $this->conn->prepare($user_query);
            $stmt->bindParam(':first_name', $data['first_name']);
            $stmt->bindParam(':last_name', $data['last_name']);
            $stmt->bindParam(':email', $data["email"]);
            $stmt->bindParam(':password', $data['password']);
            $stmt->bindParam(':role_id', $this->RESIDENT_ROLE_ID);
            $stmt->bindParam(':title', $this->RESIDENT);
            $stmt->execute();
            $userId = $this->conn->lastInsertId();


            //Insert into residents table with all resident details
            $resident_query = "INSERT INTO " . $this->residents_table . "
        SET
        first_name = :first_name,
        last_name = :last_name,
        email =:email,
        phone = :phone,
        unit_number = :unit_number,
        building_id = :building_id,
        DOB = :DOB
        ";
            $stmt2 = $this->conn->prepare($resident_query);
            $stmt2->bindParam(':first_name', $data['first_name']);
            $stmt2->bindParam(':last_name', $data['last_name']);
            $stmt2->bindParam(':email', $data["email"]);
            $stmt2->bindParam(':phone', $data['phone']);
            $stmt2->bindParam(':unit_number',  $data['unit_number']);
            $stmt2->bindParam(':building_id',  $data['building_id']);
            $stmt2->bindParam(':DOB',  $data['DOB']);
            $stmt2->execute();
            $resident_id = $this->conn->lastInsertId();
            $this->conn->commit();
            return $this->getResidentDetails($resident_id, $userId);
        } catch (Exception $ex) {
            $this->conn->rollback();
            $message = Utils::handleDBExceptions($ex);
            throw new Exception($message, -1, $ex);
        }
    }



    public function sanitizeData($data)
    {
        $firstname = isset($data["first_name"]) ? $data["first_name"] : null;
        $lastname = isset($data["last_name"]) ? $data["last_name"] : null;
        $email = isset($data["email"]) ? $data["email"] : null;
        $password = isset($data["password"]) ? $data["password"] : Constants::DEFAULT_PASSWORD;
        $phone = isset($data["phone"]) ? $data["phone"] : null;
        $unit_number = isset($data["unit_number"]) ? $data["unit_number"] : "";
        $building_id = isset($data["building_id"]) ? $data["building_id"] : "";
        $DOB = isset($data["DOB"]) ? $data["DOB"] : "";
        $role_id = isset($data["role_id"]) ? $data["role_id"] : null;
        $data["first_name"] = htmlspecialchars(strip_tags($firstname));
        $data["last_name"] = htmlspecialchars(strip_tags($lastname));
        $data["email"] = htmlspecialchars(strip_tags($email));
        $data["password"] = htmlspecialchars(strip_tags($password));
        $data["role_id"] = htmlspecialchars(strip_tags($role_id));
        $data["phone"] = htmlspecialchars(strip_tags($phone));
        $data["unit_number"] = htmlspecialchars(strip_tags($unit_number));
        $data["building_id"] = htmlspecialchars(strip_tags($building_id));
        $data["DOB"] = htmlspecialchars(strip_tags($DOB));
        return $data;
    }

    public function deleteResident($data)
    {
        $email = isset($data["email"]) ? $data["email"] : "";
        try {
            $this->conn->beginTransaction();

            $mgr_delete = 'DELETE FROM '.$this->residents_table .' WHERE email = :email';
            $stmt2 = $this->conn->prepare($mgr_delete);
            $stmt2->bindParam(':email', $email);

            $user_query = 'DELETE FROM '.$this->users_table .' WHERE email = :email';
            $stmt = $this->conn->prepare($user_query);
            $stmt->bindParam(':email', $email);

            $isDeleted =  $stmt2->execute() && $stmt->execute();
            $this->conn->commit();
            return  $isDeleted;
        } catch (PDOException $ex) {
            $this->conn->rollback();
            throw new Exception("Unable to Delete Resident", -1, $ex);
        }
        return false;
    }
}

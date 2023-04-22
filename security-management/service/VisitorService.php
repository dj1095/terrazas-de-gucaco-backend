<?php
require_once '../../config/Database.php';
require_once '../helper/Utils.php';
require_once '../service/ManagerService.php';

class VisitorService
{
    private $conn;
    private $user_table = "User";
    private $visitor_table = "Visitor";
    private $visitor_details_table = "VisitorDetails";
    private $managerService = null;

    public function __construct()
    {
        $this->conn = Database::getDBConnection();
        $this->managerService = new ManagerService();
    }


    public function createVisitor($data)
    {
        //validate and sanitize the data
        $userData = $this->sanitizeData($data);
        if (!isset($data["last_name"]) || !isset($data["email"])) {
            throw new Exception("mandatory fields[lastname,email] missing.");
        }
        try {
            $this->conn->beginTransaction();

            //Insert into Users table
            $query = 'INSERT INTO ' . $this->user_table . '
        SET
        first_name = :first_name,
        last_name = :last_name,
        email =:email,
        password = :password,
        role_id = :role_id,
        title = :title';

            $stmt = $this->conn->prepare($query);
            $email = isset($userData['email']) ? $userData['email'] : "";
            $stmt->bindParam(':first_name', $userData['first_name']);
            $stmt->bindParam(':last_name', $userData['last_name']);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $userData['password']);
            $stmt->bindParam(':role_id', $userData['role_id']);
            $stmt->bindParam(':title', $userData['title']);
            $stmt->execute();
            $userId = $this->conn->lastInsertId();

            // Insert into Visitors table;
            $visitor_details_query = 'INSERT INTO ' . $this->visitor_table . '
            SET
            first_name = :first_name,
            last_name = :last_name,
            email =:email,
            phone_number = :phone_number';

            $stmt2 = $this->conn->prepare($visitor_details_query);
            $stmt2->bindParam(':first_name', $userData['first_name']);
            $stmt2->bindParam(':last_name', $userData['last_name']);
            $stmt2->bindParam(':email', $userData['email']);
            $stmt2->bindParam(':phone_number', $userData['phone_number']);
            $stmt2->execute();
            $visitor_id = $this->conn->lastInsertId();

             // Insert into VisitorDetails table;
             $vehicle_details_query = 'INSERT INTO ' . $this->visitor_details_table . '
             SET
             vehicle_plate = :vehicle_plate,
             dl_number = :dl_number,
             visitor_id = :visitor_id';
             $stmt3 = $this->conn->prepare($vehicle_details_query);
             $stmt3->bindParam(':vehicle_plate', $userData['vehicle_plate']);
             $stmt3->bindParam(':dl_number', $userData['dl_number']);
             $stmt3->bindParam(':visitor_id', $visitor_id);
             $stmt3->execute();
            $this->conn->commit();

            return $this->getVisitorDetails($email);
        } catch (Exception $ex) {
            var_dump($ex);
            $this->conn->rollback();
            throw new Exception("Unable to Create Visitor ", -1, $ex);
        }
        return array();
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
        v.access_granted,
        vd.dl_number
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


    public function getVisitorDetails($visitor_email)
    {

        $query = 'SELECT 
        v.visitor_id,
        v.first_name,
        v.last_name,
        v.email,
        v.phone_number,
        vd.vehicle_plate,
        v.access_granted,
        vd.dl_number
    FROM
        Visitor v
            LEFT JOIN
        VisitorDetails vd ON v.visitor_id = vd.visitor_id 
        WHERE
    v.email = :email;';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $visitor_email);
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
        if (!isset($data["visitor_id"]) || count($data) <= 1) {
            throw new Exception("Bad Request. Invalid Visitor id or no details given to update");
        }
        $email = htmlspecialchars(strip_tags($data["visitor_id"]));
        $visitor_details = $this->getVisitorDetails($email);
        if (count($visitor_details) > 0) {
            try {
                $this->conn->beginTransaction();
                $visitorDetail = $visitor_details[0];
                $visitor_id =  $visitorDetail["visitor_id"];
                $last_name = isset($data["last_name"]) ? $data["last_name"] : $visitorDetail["last_name"];
                $first_name = isset($data["first_name"]) ? $data["first_name"] : $visitorDetail["first_name"];
                $email = isset($data["email"]) ? $data["email"] : $visitorDetail["email"];
                $phone_number = isset($data["phone_number"]) ? $data["phone_number"] : $visitorDetail["phone_number"];
                $access_granted = isset($data["access_granted"]) ? $data["access_granted"] : $visitorDetail["access_granted"];
                $vehicle_plate =  isset($data["vehicle_plate"]) ? $data["vehicle_plate"] : $visitorDetail["vehicle_plate"];
                $dl_number =  isset($data["dl_number"]) ? $data["dl_number"] : $visitorDetail["dl_number"];

                $visitor_update_query = "UPDATE " . $this->visitor_table . '
                SET
                last_name = :last_name,
                first_name = :first_name,
                email = :email,
                phone_number = :phone_number,
                access_granted = :access_granted
                WHERE visitor_id = :visitor_id
                ';

                $stmt = $this->conn->prepare($visitor_update_query);
                $stmt->bindParam(':last_name', $last_name);
                $stmt->bindParam(':first_name', $first_name);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':phone_number', $phone_number);
                $stmt->bindParam(':access_granted', $access_granted);
                $stmt->bindParam(':visitor_id', $visitor_id);
                $stmt->execute();

                $visitor_details_update_query = "UPDATE " . $this->visitor_details_table . '
                SET
                vehicle_plate = :vehicle_plate,
                dl_number = :dl_number
                WHERE visitor_id = :visitor_id
                ';
                $stmt2 = $this->conn->prepare($visitor_details_update_query);
                $stmt2->bindParam(':dl_number', $dl_number);
                $stmt2->bindParam(':vehicle_plate', $vehicle_plate);
                $stmt2->bindParam(':visitor_id', $visitor_id);
                $stmt2->execute();
                $this->conn->commit();
                return $this->getVisitorDetails($email);
            } catch (PDOException $ex) {
                $this->conn->rollback();
                throw new Exception("Unable to Update Visitor Details", -1, $ex);
            }
        }
        return array();
    }
    
    public function deleteVisitor($data)
    {
        $email = isset($data["email"]) ? $data["email"] : "";
        try {
            $this->conn->beginTransaction();

            $visitor_delete = 'DELETE FROM '.$this->visitor_table .' WHERE email = :email';
            $stmt2 = $this->conn->prepare($visitor_delete);
            $stmt2->bindParam(':email', $email);

            $user_query = 'DELETE FROM '.$this->user_table .' WHERE email = :email';
            $stmt = $this->conn->prepare($user_query);
            $stmt->bindParam(':email', $email);

            $isDeleted =  $stmt2->execute() && $stmt->execute();
            $this->conn->commit();
            return  $isDeleted;
        } catch (PDOException $ex) {
            $this->conn->rollback();
            throw new Exception("Unable to Delete Visitor", -1, $ex);
        }
        return false;
    }

    public function sanitizeData($data)
    {
        $firstname = isset($data["first_name"]) ? $data["first_name"] : null;
        $lastname = isset($data["last_name"]) ? $data["last_name"] : null;
        $email = isset($data["email"]) ? $data["email"] : null;
        $password = isset($data["password"]) ? $data["password"] : Constants::DEFAULT_PASSWORD;
        $phone_number = isset($data["phone_number"]) ? $data["phone_number"] : null;
        $vehiclePlate = isset($data["vehicle_plate"]) ? $data["vehicle_plate"] : "";
        $dlNumber = isset($data["dl_number"]) ? $data["dl_number"] : "";
        $role_id = isset($data["role_id"]) ? $data["role_id"] : Constants::VISITOR_ROLE_ID;
        $title = isset($data["title"]) ? $data["title"] : Constants::VISITOR;
        $data["first_name"] = htmlspecialchars(strip_tags($firstname));
        $data["last_name"] = htmlspecialchars(strip_tags($lastname));
        $data["email"] = htmlspecialchars(strip_tags($email));
        $data["password"] = htmlspecialchars(strip_tags($password));
        $data["role_id"] = htmlspecialchars(strip_tags($role_id));
        $data["phone_number"] = htmlspecialchars(strip_tags($phone_number));
        $data["vehicle_plate"] = htmlspecialchars(strip_tags($vehiclePlate));
        $data["dl_number"] = htmlspecialchars(strip_tags($dlNumber));
        $data["title"] = htmlspecialchars(strip_tags($title));
        return $data;
    }
}

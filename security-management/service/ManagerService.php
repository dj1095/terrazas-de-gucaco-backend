<?php
require_once '../../config/Database.php';
require_once '../../models/Security.php';
require_once '../helper/Utils.php';
require_once '../helper/Constants.php';
require_once '../../login/service/UserRoleService.php';

class ManagerService
{
    private $conn;
    private $table = "Manager";
    private $user_table = "User";
    private $roleService = null;
    public function __construct()
    {
        $this->conn = Database::getDBConnection();
        $this->roleService = new UserRoleService();
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

    public function getManagerDetails($manager_email)
    {
        if (!isset($manager_email)) {
            throw new Exception("Invalid Manager Id");
        }
        $email = htmlspecialchars(strip_tags($manager_email));
        $stmt = $this->conn->prepare('SELECT * FROM ' . $this->table . ' WHERE email = :email');
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $managers = $stmt->rowCount() == 0 ? array() : array($stmt->fetch(PDO::FETCH_ASSOC));
        return $managers;
    }

    public function getManagerDetailsWithId($manager_id)
    {
        if (!isset($manager_id)) {
            throw new Exception("Invalid Manager Id");
        }
        $email = htmlspecialchars(strip_tags($manager_id));
        $stmt = $this->conn->prepare('SELECT * FROM ' . $this->table . ' WHERE mgr_id = :manager_id');
        $stmt->bindParam(':manager_id', $manager_id);
        $stmt->execute();
        $managers = $stmt->rowCount() == 0 ? array() : array($stmt->fetch(PDO::FETCH_ASSOC));
        return $managers;
    }

    public function createManager($data)
    {
        //validate and sanitize the data
        if (!isset($data["last_name"]) || !isset($data["email"]) || !isset($data["role_id"])) {
            throw new Exception("mandatory fields[last_name,email, role_id] missing.");
        }
        $userData = $this->sanitizeData($data);
        try {
            $this->conn->beginTransaction();

            //Get Manager Title 

            $role_query = 'SELECT name as mgr_title FROM Role WHERE role_id = :role_id';
            $statement = $this->conn->prepare($role_query);
            $statement->bindParam(':role_id', $userData['role_id']);
            $statement->execute();
            $mgr_title = $statement->fetch(PDO::FETCH_ASSOC)['mgr_title'];


            //Create an entry in User table with Default Credentials
            $user_query = 'INSERT INTO ' . $this->user_table . '
            SET
            first_name = :first_name,
            last_name = :last_name,
            email =:email,
            password = :default_password, 
            role_id = :role_id,
            title = :mgr_title';

            $stmt = $this->conn->prepare($user_query);
            $stmt->bindParam(':first_name', $userData['first_name']);
            $stmt->bindParam(':last_name', $userData['last_name']);
            $stmt->bindParam(':email', $userData['email']);
            $default_password = Constants::DEFAULT_PASSWORD;
            $stmt->bindParam(':default_password', $default_password);
            $stmt->bindParam(':role_id', $userData['role_id']);
            $stmt->bindParam(':mgr_title', $mgr_title);
            $stmt->execute();
            $userId = $this->conn->lastInsertId();

            //Create a Manager in Manager Table

            $query = 'INSERT INTO ' . $this->table . '
            SET
            first_name = :first_name,
            last_name = :last_name,
            email =:email,
            phone_number = :phone_number,
            mgr_title = :mgr_title,
            user_id = :user_id';

            $stmt2 = $this->conn->prepare($query);
            $stmt2->bindParam(':first_name', $userData['first_name']);
            $stmt2->bindParam(':last_name', $userData['last_name']);
            $stmt2->bindParam(':email', $userData['email']);
            $stmt2->bindParam(':phone_number', $userData['phone_number']);
            $stmt2->bindParam(':mgr_title', $mgr_title);
            $stmt2->bindParam(':user_id', $userId);
            $stmt2->execute();
            $this->conn->commit();
            return $this->getManagerDetails($userData['email']);
        } catch (PDOException $ex) {
            $this->conn->rollback();
            $message = Utils::handleDBExceptions($ex);
            throw new Exception($message, -1, $ex);
        }
        return array();
    }

    public function updateManagerDetails($data)
    {
        if (!isset($data["mgr_id"]) || count($data) <= 1) {
            throw new Exception("Bad Request. Invalid manager id or no details given to update");
        }
        try{
            $this->conn->beginTransaction();
            $mgr_id = htmlspecialchars(strip_tags($data["mgr_id"]));
            $managers = $this->getManagerDetailsWithId($mgr_id);
            if(count($managers)< 1){
                return array();
            }
            $manager = $managers[0];
            $mgr_email = $manager["email"];

            //Get Updated Role
            $role_id = isset($data["role_id"]) ? $data["role_id"] : $manager["role_id"];
            $title = $this->roleService->getUserRole($role_id )["name"];
            $data["title"] = $title;

            //Update User Table
            $allowedColums = array('first_name', 'last_name', 'email','role_id','title');
            $whereCols = ["email" => $mgr_email];
            $queryValues = Utils::buildUpdateQuery($this->user_table, $allowedColums, $data, $whereCols);
            $query = $queryValues[0];
            $values = $queryValues[1];
            $stmt = $this->conn->prepare($query);
            $stmt->execute($values);

            //Update Managers Table
            $data["mgr_title"] = $title;
            $allowedColums2 = array('first_name', 'last_name', 'email','phone_number','mgr_title');
            $whereCols2 = ["email" => $mgr_email];
            $queryValues2 = Utils::buildUpdateQuery($this->table, $allowedColums2, $data, $whereCols2);
            $query2 = $queryValues2[0];
            $values2 = $queryValues2[1];
            $stmt2 = $this->conn->prepare($query2);
            $stmt2->execute($values2);

            $this->conn->commit();
            return $this->getManagerDetailsWithId($mgr_id);
        }catch (PDOException $ex) {
            $this->conn->rollback();
            $message = Utils::handleDBExceptions($ex);
            throw new Exception($message, -1, $ex);
        }
        return array();
    }

    public function deleteManager($data)
    {
        $email = isset($data["email"]) ? $data["email"] : "";
        try {
            $this->conn->beginTransaction();

            $mgr_delete = 'DELETE FROM '.$this->table .' WHERE email = :email';
            $stmt2 = $this->conn->prepare($mgr_delete);
            $stmt2->bindParam(':email', $email);

            $user_query = 'DELETE FROM '.$this->user_table .' WHERE email = :email';
            $stmt = $this->conn->prepare($user_query);
            $stmt->bindParam(':email', $email);

            $isDeleted =  $stmt2->execute() && $stmt->execute();
            $this->conn->commit();
            return  $isDeleted;
        } catch (PDOException $ex) {
            $this->conn->rollback();
            throw new Exception("Unable to Delete Manager", -1, $ex);
        }
        return false;
    }



    public function sanitizeData($data)
    {
        $firstname = isset($data["first_name"]) ? $data["first_name"] : "";
        $lastname = isset($data["last_name"]) ? $data["last_name"] : "";
        $email = isset($data["email"]) ? $data["email"] : null;
        $phone_number = isset($data["phone_number"]) ? $data["phone_number"] : null;
        $mgr_title = isset($data["mgr_title"]) ? $data["mgr_title"] : "";
        $role_id = isset($data["role_id"]) ? $data["role_id"] : "";
        $user_id = isset($data["user_id"]) ? $data["user_id"] : null;
        $data["first_name"] = htmlspecialchars(strip_tags($firstname));
        $data["last_name"] = htmlspecialchars(strip_tags($lastname));
        $data["email"] = htmlspecialchars(strip_tags($email));
        $data["phone_number"] = htmlspecialchars(strip_tags($phone_number));
        $data["mgr_title"] = htmlspecialchars(strip_tags($mgr_title));
        $data["user_id"] = htmlspecialchars(strip_tags($user_id));
        $data["role_id"] = htmlspecialchars(strip_tags($role_id));
        return $data;
    }
}

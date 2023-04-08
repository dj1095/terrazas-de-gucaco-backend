<?php
require_once '../../config/Database.php';
require_once '../../models/Security.php';
require_once '../helper/Utils.php';

class ManagerService
{
    private $conn;
    private $table = "Manager";
    public function __construct()
    {
        $this->conn = Database::getDBConnection();
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

    public function createManager($request)
    {
        //validate and sanitize the data
        $userData = $this->sanitizeData($request);
        if (!isset($data["last_name"]) || !isset($data["email"]) || !isset($data["password"]) || !isset($data["role_id"])) {
            throw new Exception("mandatory fields[lastname,email,password, role_id] missing.");
        }

        $query = 'INSERT INTO ' . $this->table . '
        SET
        first_name = :first_name,
        last_name = :last_name,
        email =:email,
        password = :password,
        role_id = :role_id';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':first_name', $userData['first_name']);
        $stmt->bindParam(':last_name', $userData['last_name']);
        $stmt->bindParam(':email', $userData['email']);
        $stmt->bindParam(':password', $userData['password']);
        $stmt->bindParam(':role_id', $userData['role_id']);
        $stmt->execute();

        $userId = $this->conn->lastInsertId();
        return $this->getManagerDetails($userData['email']);
    }

    public function sanitizeData($data)
    {
        $firstname = isset($data["first_name"]) ? $data["first_name"] : null;
        $lastname = isset($data["last_name"]) ? $data["last_name"] : null;
        $email = isset($data["email"]) ? $data["email"] : null;
        $phone_number = isset($data["phone_number"]) ? $data["phone_number"] : null;
        $mgr_title = isset($data["mgr_title"]) ? $data["mgr_title"] : null;
        $role_id = isset($data["role_id"]) ? $data["role_id"] : null;
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

<?php
require_once './config/Database.php';
require_once './models/User.php';
require_once './security-management/helper/Utils.php';
require_once './security-management/helper/Constants.php';

class UserService
{

    private $conn;
    private $table = "User";
    private $managerService = null;

    public function __construct()
    {
        $this->conn = Database::getDBConnection();
    }

    public function createUser($data)
    {
        //validate and sanitize the data
        $userData = $this->sanitizeData($data);
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
        return $this->getUserDetails($userId);
    }

    public function getUserDetails($userId)
    {
        $userId = htmlspecialchars(strip_tags($userId));
        if (!isset($userId)) {
            throw new Exception("Invalid User Id");
        }
        $stmt = $this->conn->prepare('SELECT * FROM ' . $this->table . ' WHERE user_id = :userId');
        $stmt->bindParam(':userId', $userId);
        $stmt->execute();
        $users = $stmt->rowCount() == 0 ? array() : array($stmt->fetch(PDO::FETCH_ASSOC));
        return $users;
    }

    public function sanitizeData($data)
    {
        $firstname = isset($data["first_name"]) ? $data["first_name"] : null;
        $lastname = isset($data["last_name"]) ? $data["last_name"] : null;
        $email = isset($data["email"]) ? $data["email"] : null;
        $password = isset($data["password"]) ? $data["password"] : "1234";
        $role_id = isset($data["role_id"]) ? $data["role_id"] : null;
        $data["first_name"] = htmlspecialchars(strip_tags($firstname));
        $data["last_name"] = htmlspecialchars(strip_tags($lastname));
        $data["email"] = htmlspecialchars(strip_tags($email));
        $data["password"] = htmlspecialchars(strip_tags($password));
        $data["role_id"] = htmlspecialchars(strip_tags($role_id));
        return $data;
    }
}

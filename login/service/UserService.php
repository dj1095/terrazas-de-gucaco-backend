<?php
require_once './config/Database.php';
require_once './models/User.php';
require_once './security-management/helper/Utils.php';
require_once './security-management/helper/Constants.php';
require_once './login/service/UserRoleService.php';

class UserService
{

    private $conn;
    private $table = "User";
    private $userRoleService = null;

    public function __construct()
    {
        $this->conn = Database::getDBConnection();
        $this->userRoleService = new UserRoleService();
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
        $email = isset($userData['email']) ? $userData['email'] : "";
        $stmt->bindParam(':first_name', $userData['first_name']);
        $stmt->bindParam(':last_name', $userData['last_name']);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $userData['password']);
        $stmt->bindParam(':role_id', $userData['role_id']);
        $stmt->execute();
        
        $userId = $this->conn->lastInsertId();
        return $this->getUserDetails($email);
    }

    public function getUserDetails($email)
    {
        $userId = htmlspecialchars(strip_tags($email));
        if (!isset($email)) {
            throw new Exception("Invalid User Id");
        }
        $stmt = $this->conn->prepare('SELECT * FROM ' . $this->table . ' WHERE email = :email');
        $stmt->bindParam(':email', $userId);
        $stmt->execute();
        $users = $stmt->rowCount() == 0 ? array() : array($stmt->fetch(PDO::FETCH_ASSOC));
        return $users;
    }

    public function validateUser($email,$password){
        $users = $this->getUserDetails($email);
        if(count($users) > 0){
            $role = $this->userRoleService->getUserRole($users['role_id']);
            return [
                "first_name" => $users["first_name"],
                "last_name" => $users["last_name"],
                "email" => $users["email"],
                "role" => $role["name"]
            ];
        }
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

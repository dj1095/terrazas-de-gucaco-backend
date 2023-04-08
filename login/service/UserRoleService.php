<?php
require_once './config/Database.php';
require_once './models/User.php';
require_once './security-management/helper/Utils.php';
require_once './security-management/helper/Constants.php';

class UserRoleService{
    private $conn;
    private $table = "Role";
    private $managerService = null;

    public function __construct()
    {
        $this->conn = Database::getDBConnection();
    }

    
    public function getUserRole($role_id)
    {
        $userId = htmlspecialchars(strip_tags($role_id));
        if (!isset($role_id)) {
            throw new Exception("Invalid User Role Id");
        }
        $stmt = $this->conn->prepare('SELECT * FROM ' . $this->table . ' WHERE role_id = :role_id');
        $stmt->bindParam(':role_id', $role_id);
        $stmt->execute();
        return $stmt->rowCount() == 0 ? "" : $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
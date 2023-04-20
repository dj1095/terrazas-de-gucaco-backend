<?php
require_once '../../config/Database.php';

class UserRoleService{
    private $conn;
    private $table = "Role";

    public function __construct()
    {
        $this->conn = Database::getDBConnection();
    }

    
    public function getUserRole($role_id)
    {
        $userId = htmlspecialchars(strip_tags($role_id));
        if (!isset($userId)) {
            throw new Exception("Invalid User Role Id");
        }
        $stmt = $this->conn->prepare('SELECT * FROM ' . $this->table . ' WHERE role_id = :role_id');
        $stmt->bindParam(':role_id', $userId);
        $stmt->execute();
        return $stmt->rowCount() == 0 ? "" : $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
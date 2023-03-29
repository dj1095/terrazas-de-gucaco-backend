<?php
require_once '../../config/Database.php';
require_once '../models/Security.php';

class SecurityService
{
    private $conn;
    private $table = "Security";

    public function __construct()
    {
        $this->conn = Database::getDBConnection();
    }

    public function get()
    {
        $query = 'SELECT * FROM ' . $this->table;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $results_arr = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $security = new Security();
            $security->firstname = $row['first_name'];
            $security->lastname = $row['last_name'];
            $security->email = $row['email'];
            $security->phone = $row['phone'];
            array_push($results_arr, $security);
        }
        return $results_arr;
    }

    public function createSecurity($data)
    {
        //validate and sanitize the data
        $securityData = $this->sanitizeData($data);
        if (!isset($data["lastname"]) || !isset($data["email"]) || !isset($data["phone"])) {
            throw new Exception("mandatory fields[lastname,email,phone] missing.");
        }

        $query = 'INSERT INTO ' . $this->table . '
        SET
        first_name = :firstname,
        last_name = :lastname,
        email =:email,
        phone = :phone';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':firstname', $securityData['firstname']);
        $stmt->bindParam(':lastname', $securityData['lastname']);
        $stmt->bindParam(':email', $securityData['email']);
        $stmt->bindParam(':phone', $securityData['phone']);
        $stmt->execute();

        $securityId = $this->conn->lastInsertId();
        $stmt = $this->conn->prepare('SELECT * FROM ' . $this->table . ' WHERE security_id = :securityId');
        $stmt->bindParam(':securityId', $securityId);
        $stmt->execute();
        $createdUser = $stmt->fetch(PDO::FETCH_ASSOC);
        return $createdUser;
    }

    public function sanitizeData($data)
    {
        $firstname = isset($data["firstname"]) ? $data["firstname"] : null;
        $lastname = isset($data["lastname"]) ? $data["lastname"] : null;
        $email = isset($data["email"]) ? $data["email"] : null;
        $phone = isset($data["phone"]) ? $data["phone"] : null;
        $data["firstname"] = htmlspecialchars(strip_tags($firstname));
        $data["lastname"] = htmlspecialchars(strip_tags($lastname));
        $data["email"] = htmlspecialchars(strip_tags($email));
        $data["phone"] = htmlspecialchars(strip_tags($phone));
        return $data;
    }
}

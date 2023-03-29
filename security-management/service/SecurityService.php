<?php
require_once '../../config/Database.php';
require_once '../models/Security.php';
require_once '../helper/Utils.php';

class SecurityService
{
    private $conn;
    private $table = "Security";

    public function __construct()
    {
        $this->conn = Database::getDBConnection();
    }

    public function deleteSecurityDetails($data)
    {
        if (!isset($data["id"])) {
            throw new Exception("Bad Request. Invalid security id ");
        }
        $id = htmlspecialchars(strip_tags($data["id"]));
        if (count($this->getSecurityDetails($id)) > 0) {
            $query = 'DELETE FROM ' . $this->table . '
            WHERE
            security_id = :id';
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $data['id']);
            $stmt->execute();
            return  $stmt->rowCount() > 0 ? true : false;
        }
        return array();
    }

    public function updateSecurityDetails($data)
    {
        if (!isset($data["id"]) || count($data) <= 1) {
            throw new Exception("Bad Request. Invalid security id or no details given to update");
        }
        $id = htmlspecialchars(strip_tags($data["id"]));
        if (count($this->getSecurityDetails($id)) > 0) {
            $allowedColums = array('first_name', 'last_name', 'email', 'phone');
            $whereCols = ["security_id" => $data["id"]];
            $queryValues = Utils::buildUpdateQuery($this->table, $allowedColums, $data, $whereCols);
            $query = $queryValues[0];
            $values = $queryValues[1];
            $stmt = $this->conn->prepare($query);
            $stmt->execute($values);
            $updatedDetails = $this->getSecurityDetails($id);
            return $updatedDetails;
        }
        return array();
    }

    public function getSecurityDetails($id)
    {
        if (!isset($id)) {
            throw new Exception("Invalid Security Id");
        }
        $id = htmlspecialchars(strip_tags($id));
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
            $security = new Security();
            $security->id = $row['security_id'];
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
        if (!isset($data["last_name"]) || !isset($data["email"]) || !isset($data["phone"])) {
            throw new Exception("mandatory fields[lastname,email,phone] missing.");
        }

        $query = 'INSERT INTO ' . $this->table . '
        SET
        first_name = :first_name,
        last_name = :last_name,
        email =:email,
        phone = :phone';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':first_name', $securityData['first_name']);
        $stmt->bindParam(':last_name', $securityData['last_name']);
        $stmt->bindParam(':email', $securityData['email']);
        $stmt->bindParam(':phone', $securityData['phone']);
        $stmt->execute();

        $securityId = $this->conn->lastInsertId();
        return $this->getSecurityDetails($securityId);
    }

    public function sanitizeData($data)
    {
        $firstname = isset($data["first_name"]) ? $data["first_name"] : null;
        $lastname = isset($data["last_name"]) ? $data["last_name"] : null;
        $email = isset($data["email"]) ? $data["email"] : null;
        $phone = isset($data["phone"]) ? $data["phone"] : null;
        $data["first_name"] = htmlspecialchars(strip_tags($firstname));
        $data["last_name"] = htmlspecialchars(strip_tags($lastname));
        $data["email"] = htmlspecialchars(strip_tags($email));
        $data["phone"] = htmlspecialchars(strip_tags($phone));
        return $data;
    }
}

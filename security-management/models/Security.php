<?php

class Security{
    private $firstname;
    private $lastname;
    private $email;
    private $phone;

    private $conn;
    private $table = "Security";
    
    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    public function createSecurity($securityData){
        $query = 'INSERT INTO '.$this->table. 'VALUES (:firstname, :lastname, :email, :phone)';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':firstname', $securityData['firstname']);
        $stmt->bindParam(':lastname', $securityData['lastname']);
        $stmt->bindParam(':email', $securityData['email']);
        $stmt->bindParam(':phone', $securityData['phone']);
        $stmt->execute();

        $securityId = $this->conn->lastInsertId();
        $stmt = $this->conn->prepare('SELECT * FROM '.$this->table.' WHERE security_id = :securityId');
        $stmt->bindParam(':securityId', $securityId);
        $stmt->execute();
        $createdUser = $stmt->fetch(PDO::FETCH_ASSOC);
        return $createdUser;
    }


}
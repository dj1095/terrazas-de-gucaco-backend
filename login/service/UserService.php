<?php
require_once '../../config/Database.php';
require_once '../../login/service/UserRoleService.php';
require_once '../../security-management/helper/Constants.php';

class UserService
{

    private $conn;
    private $table = "User";
    private $visitor_table = "Visitor";
    private $visitor_details_table = "VisitorDetails";
    private $queries_table = "Queries";
    private $userRoleService = null;

    public function __construct()
    {
        $this->conn = Database::getDBConnection();
        $this->userRoleService = new UserRoleService();
    }

    public function getReports(){
        $query = "SELECT 'security guards' AS name, COUNT(*) AS count FROM Security
        UNION
        SELECT 'Managers' AS Manager, COUNT(*) AS count FROM Manager
        UNION
        SELECT 'Visitors' AS Visitor, COUNT(*) AS count FROM Visitor
        UNION
        SELECT 'Residents' AS residents, COUNT(*) AS count FROM residents";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $results_arr = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            array_push($results_arr, $row);
        }
        return $results_arr;


    }

    public function saveUserQuery($data){

        if (!isset($data["last_name"]) || !isset($data["email"]) || !isset($data["message"])) {
            throw new Exception("mandatory fields[lastname,email,message] missing.");
        }

        $query = 'INSERT INTO ' . $this->queries_table . '
        SET
        first_name = :first_name,
        last_name = :last_name,
        email =:email,
        message = :message';

            $stmt = $this->conn->prepare($query);
            $first_name = isset($data['first_name']) ? $data['first_name'] : "";
            $stmt->bindParam(':first_name', $first_name);
            $stmt->bindParam(':last_name', $data['last_name']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':message', $data['message']);
            $stmt->execute();
            $queryId = $this->conn->lastInsertId();
            return array([
                "query_id" => $queryId,
                "first_name" => $data['first_name'],
                "last_name" => $data['last_name'],
                "email" => $data['email'],
                "message" => $data['message']
            ]);
    }

    public function createUser($data)
    {
        //validate and sanitize the data
        $userData = $this->sanitizeData($data);
        /*if (!isset($data["last_name"]) || !isset($data["email"]) || !isset($data["password"]) || !isset($data["role_id"])) {
            throw new Exception("mandatory fields[lastname,email,password, role_id] missing.");
        }*/
        try {
            $this->conn->beginTransaction();

            //Insert into Users table
            $query = 'INSERT INTO ' . $this->table . '
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

            return $this->getUserDetails($email);
        } catch (Exception $ex) {
            var_dump($ex);
            $this->conn->rollback();
            throw new Exception("Unable to Create Visitor ", -1, $ex);
        }
        return array();
    }

    public function getUserDetails($email)
    {
        $userId = htmlspecialchars(strip_tags($email));
        if (!isset($userId)) {
            throw new Exception("Invalid User Id");
        }
        $stmt = $this->conn->prepare('SELECT * FROM ' . $this->table . ' WHERE email = :email');
        $stmt->bindParam(':email', $userId);
        $stmt->execute();
        $users = $stmt->rowCount() == 0 ? array() : array($stmt->fetch(PDO::FETCH_ASSOC));
        return $users;
    }

    public function validateUser($email, $password)
    {
        $email = htmlspecialchars(strip_tags($email));
        $password = htmlspecialchars(strip_tags($password));
        if (!isset($email) || !isset($password)) {
            return array();
        }
        $users = $this->getUserDetails($email);
        if (count($users) > 0) {
            $user = $users[0];
            $role = $this->userRoleService->getUserRole($user['role_id']);
            if ((strcasecmp($user["email"], $email) == 0) and (strcmp($user['password'], $password) == 0)) {
                return array([
                    "user_id" =>  $user["user_id"],
                    "first_name" => $user["first_name"],
                    "last_name" => $user["last_name"],
                    "email" => $user["email"],
                    "role" => $role["name"]
                ]);
            }
        }
        return array();
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

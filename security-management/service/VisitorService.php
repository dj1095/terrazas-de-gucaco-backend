<?php
require_once '../../config/Database.php';
require_once '../helper/Utils.php';
require_once '../service/ManagerService.php';

class VisitorService
{
    private $conn;
    private $visitor_table = "Visitor";
    private $visitor_details_table = "VisitorDetails";
    private $managerService = null;

    public function __construct()
    {
        $this->conn = Database::getDBConnection();
        $this->managerService = new ManagerService();
    }

    public function get()
    {
        $query = 'SELECT 
        v.visitor_id,
        v.first_name,
        v.last_name,
        v.email,
        v.phone_number,
        vd.vehicle_plate,
        v.access_granted,
        vd.dl_number
    FROM
        Visitor v
            LEFT JOIN
        VisitorDetails vd ON v.visitor_id = vd.visitor_id';

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $results_arr = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            array_push($results_arr, $row);
        }
        return $results_arr;
    }

    public function getVisitorDetails($visitor_id)
    {

        $query = 'SELECT 
        v.visitor_id,
        v.first_name,
        v.last_name,
        v.email,
        v.phone_number,
        vd.vehicle_plate,
        v.access_granted,
        vd.dl_number
    FROM
        Visitor v
            LEFT JOIN
        VisitorDetails vd ON v.visitor_id = vd.visitor_id 
        WHERE
    v.visitor_id = :visitor_id;';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':visitor_id', $visitor_id);
        $stmt->execute();
        $users = $stmt->rowCount() == 0 ? array() : array($stmt->fetch(PDO::FETCH_ASSOC));
        return $users;
    }

    public function isAuthorized($mgr_email)
    {
        $managerDetails = $this->managerService->getManagerDetails($mgr_email);
        $managerTitle = "";
        if (count($managerDetails) > 0) {
            $managerTitle = isset($managerDetails[0]["mgr_title"]) ? $managerDetails[0]["mgr_title"] : "";
            if (Constants::BUILDING_MANAGER === $managerTitle || Constants::SECURITY_MANAGER === $managerTitle) {
                return is_numeric($managerDetails[0]["mgr_id"]) ? intval($managerDetails[0]["mgr_id"]) : $managerDetails[0]["mgr_id"];
            }
        }
        return false;
    }

    public function updateVisitorDetails($data)
    {
        if (!isset($data["visitor_id"]) || count($data) <= 1) {
            throw new Exception("Bad Request. Invalid Visitor id or no details given to update");
        }
        $visitor_id = htmlspecialchars(strip_tags($data["visitor_id"]));
        $visitor_details = $this->getVisitorDetails($visitor_id);
        if (count($visitor_details) > 0) {
            try {
                $this->conn->beginTransaction();

                $visitorDetail = $visitor_details[0];
                $last_name = isset($data["last_name"]) ? $data["last_name"] : $visitorDetail["last_name"];
                $first_name = isset($data["first_name"]) ? $data["first_name"] : $visitorDetail["first_name"];
                $email = isset($data["email"]) ? $data["email"] : $visitorDetail["email"];
                $phone_number = isset($data["phone_number"]) ? $data["phone_number"] : $visitorDetail["phone_number"];
                $access_granted = isset($data["access_granted"]) ? $data["access_granted"] : $visitorDetail["access_granted"];
                $vehicle_plate =  isset($data["vehicle_plate"]) ? $data["vehicle_plate"] : $visitorDetail["vehicle_plate"];
                $dl_number =  isset($data["dl_number"]) ? $data["dl_number"] : $visitorDetail["dl_number"];

                $visitor_update_query = "UPDATE " . $this->visitor_table . '
                SET
                last_name = :last_name,
                first_name = :first_name,
                email = :email,
                phone_number = :phone_number,
                access_granted = :access_granted
                WHERE visitor_id = :visitor_id
                ';

                $stmt = $this->conn->prepare($visitor_update_query);
                $stmt->bindParam(':last_name', $last_name);
                $stmt->bindParam(':first_name', $first_name);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':phone_number', $phone_number);
                $stmt->bindParam(':access_granted', $access_granted);
                $stmt->bindParam(':visitor_id', $visitor_id);
                $stmt->execute();

                $visitor_details_update_query = "UPDATE " . $this->visitor_details_table . '
                SET
                vehicle_plate = :vehicle_plate,
                dl_number = :dl_number
                WHERE visitor_id = :visitor_id
                ';
                $stmt2 = $this->conn->prepare($visitor_details_update_query);
                $stmt2->bindParam(':dl_number', $dl_number);
                $stmt2->bindParam(':vehicle_plate', $vehicle_plate);
                $stmt2->bindParam(':visitor_id', $visitor_id);
                $stmt2->execute();
                $this->conn->commit();
                return $this->getVisitorDetails($visitor_id);
            } catch (PDOException $ex) {
                $this->conn->rollback();
                throw new Exception("Unable to Update Visitor Details", -1, $ex);
            }
        }
        return array();
    }
}

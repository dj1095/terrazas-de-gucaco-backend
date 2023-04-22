<?php
require_once '../../config/Database.php';
require_once '../helper/Utils.php';
require_once '../service/ResidentService.php';

class EventService
{

    private $conn;
    private $events = "Events";
    private $event_registrations = "event_registrations";
    private $PJ_Activities = "PJ_Activities";
    private $PJ_Res_Evnt_Registrations = "PJ_Res_Evnt_Registrations";
    private $residentService = null;

    public function __construct()
    {
        $this->conn = Database::getDBConnection();
        $this->residentService = new ResidentService();
    }

    public function getVisitorEvents($visitor_id)
    {
        $query = 'SELECT e.event_id, e.name, e.category, TIME_FORMAT(e.starts_at, "%H:%i") as starts_at,TIME_FORMAT(e.ends_at, "%H:%i") as ends_at, er.visitor_id
        FROM Events e
        LEFT JOIN event_registrations er ON e.event_id = er.event_id
        AND er.visitor_id = :visitor_id';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':visitor_id', $visitor_id);
        $stmt->execute();

        $results_arr = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            array_push($results_arr, $row);
        }
        return $results_arr;
    }

    public function registerForEvent($request)
    {
        if (!isset($request['event_id']) || !isset($request['visitor_id'])) {
            throw new Exception("Unable to Register. Invalid Event or Visitor Ids");
        }
        $event_id = $request['event_id'];
        $visitor_id = $request['visitor_id'];
        $query = 'INSERT INTO ' . $this->event_registrations . ' VALUES(:visitor_id, :event_id)';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':visitor_id', $visitor_id);
        $stmt->bindParam(':event_id', $event_id);
        $stmt->execute();
        return $this->getVisitorEvents($visitor_id);
    }

    public function cancelEvent($request)
    {
        if (!isset($request['event_id']) || !isset($request['visitor_id'])) {
            throw new Exception("Unable to Register. Invalid Event or Visitor Ids");
        }
        $event_id = $request['event_id'];
        $visitor_id = $request['visitor_id'];
        $query = 'DELETE FROM ' . $this->event_registrations . ' WHERE event_id = :event_id AND visitor_id = :visitor_id';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':visitor_id', $visitor_id);
        $stmt->bindParam(':event_id', $event_id);
        $stmt->execute();
        return  $stmt->rowCount() > 0 ? intval($event_id) : -1;
    }


    public function getResidentEvents($userId)
    {
        $resident_id = $this->residentService->getResidentId($userId);
        $query = 'SELECT e.event_id, e.name, e.category, TIME_FORMAT(e.starts_at, "%H:%i") as starts_at,TIME_FORMAT(e.ends_at, "%H:%i") as ends_at, er.resident_id
        FROM Events e
        LEFT JOIN PJ_Res_Evnt_Registrations er ON e.event_id = er.event_id
        AND er.resident_id = :resident_id';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':resident_id', $resident_id);
        $stmt->execute();

        $results_arr = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            array_push($results_arr, $row);
        }
        return $results_arr;
    }


    public function registerResidentForEvent($request)
    {
        if (!isset($request['event_id']) || !isset($request['userId'])) {
            throw new Exception("Unable to Register. Invalid Event or Resident Ids");
        }
        $event_id = $request['event_id'];
        $resident_id = $this->residentService->getResidentId($request['userId']);
        $query = 'INSERT INTO ' . $this->PJ_Res_Evnt_Registrations . ' VALUES(:resident_id, :event_id)';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':resident_id', $resident_id);
        $stmt->bindParam(':event_id', $event_id);
        $stmt->execute();
        return $this->getResidentEvents($request['userId']);
    }

    public function cancelResidentEvent($request)
    {
        if (!isset($request['event_id']) || !isset($request['userId'])) {
            throw new Exception("Unable to Register. Invalid Event or Resident Ids");
        }
        $event_id = $request['event_id'];
        $resident_id = $this->residentService->getResidentId($request['userId']);
        $query = 'DELETE FROM ' . $this->PJ_Res_Evnt_Registrations . ' WHERE event_id = :event_id AND resident_id = :resident_id';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':resident_id', $resident_id);
        $stmt->bindParam(':event_id', $event_id);
        $stmt->execute();
        return  $stmt->rowCount() > 0 ? intval($event_id) : -1;
    }


    public function getActivities()
    {
        $query = 'SELECT * FROM ' . $this->PJ_Activities;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $results_arr = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            array_push($results_arr, $row);
        }
        return $results_arr;
    }
}

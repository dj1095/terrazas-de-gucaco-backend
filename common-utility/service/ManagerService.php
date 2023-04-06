<?php
class ManagerService
{
    private $conn;
    private $table = "Manager";

    public function __construct()
    {
        $this->conn = Database::getDBConnection();
    }
}

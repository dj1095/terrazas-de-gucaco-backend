<?php

class LoginService
{
    private $conn;
    private $table = "User";

    public function __construct()
    {
        $this->conn = Database::getDBConnection();
    }


    public function signUp($user)
    {

    }

    public function login($user)
    {
        
    }
}

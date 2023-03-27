<?php
    class Database{
        //DB params 
        private $host = null;
        private $db_name = null;
        private $user_name = null;
        private $password = null;
        private $port = null;
        private static $conn = null;

        private function load_db_config()
        { 
            $this->host = getenv('DB_HOST');
            $this->db_name = getenv('DB_DATABASE');
            $this->user_name = getenv('DB_USERNAME');
            $this->password = getenv('DB_PASSWORD');
            $this->port = getenv('DB_PORT');

        }

        private function __construct()
        {
            $this->load_db_config();
        }

        public static function getDBConnection()
        {
            if (self::$conn == null)
            {
                $db = new Database();
                self::$conn = $db->connect();
            }
            return self::$conn;
        }

        public function connect(){
            try{
                echo('mysql:host='. $this->host.';port='.$this->port.';dbname='. $this->db_name);
                $conn = new PDO('mysql:host='. $this->host.';port='.$this->port.';dbname='. $this->db_name, $this->user_name, $this->password);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }catch(PDOException $e){
                echo'Connection Error: '. $e->getMessage();
            }
            return $conn;
        }

    }
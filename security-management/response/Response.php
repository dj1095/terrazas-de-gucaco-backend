<?php

class Response{
       
    public $status_code;
    public $message;
    public $errorMessage;
    public $data = array();

    public function jsonSerialize() {
        return (object) get_object_vars($this);
    }

    public function get_data()
    {
        return $this->data;
    }

    public function set_data($data_arr):void
    {
        foreach($data_arr as $key => $value){
            array_push($this->data,$value);
        }
    }
}
?>
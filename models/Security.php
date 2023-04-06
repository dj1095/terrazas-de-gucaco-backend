<?php
require_once 'User.php';

class Security extends User
{
    public $security_id;
    public $phone_number;
    public $timings;
    public $place;
    public $mgr_id;

    public function jsonSerialize()
    {
        return [
            'security_id' => $this->security_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'timings' => $this->timings,
            'place' => $this->place,
        ];
    }
}

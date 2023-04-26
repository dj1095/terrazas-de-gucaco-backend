<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: *");
header("Access-Control-Allow-Headers:*");
include 'folderPath.php';
$response='';

$server = '51.81.160.154';
$dbname = 'dxj0015_terrazas-de-gucaco-v1';
$user = 'dxj0015_pramodh';
$pass = 'utacloud123';

$conn = new mysqli($server, $user, $pass, $dbname);
$method=$_SERVER['REQUEST_METHOD'];
$URI=$_SERVER['REQUEST_URI'];
if($method==='GET' && $URI===$path."Resident.php/log")
{
    $trp = mysqli_query($conn, "SELECT * from Residentlog");
    $rows = array();
    while($r = mysqli_fetch_assoc($trp)) {
        $rows[] = $r;
    }
    echo json_encode($rows);
}

if($method==='POST' && $URI===$path."Resident.php/log-delete")
{
    $json = file_get_contents('php://input');
    $obj = json_decode($json);
    $log_id=$obj->log_id;

    $sql = "DELETE FROM Residentlog WHERE LogId=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $log_id);
    $stmt->execute(); 
}


if($method==='POST' && $URI===$path."Resident.php/profile")
{

    $json = file_get_contents('php://input');
    $obj = json_decode($json);
    $Email = $obj->Email;
    // $Email="a@gmail.com";
    $stmt = $conn->prepare("SELECT * from residents where email=?");
    $stmt->bind_param('s', $Email);
    $stmt->execute();
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode($results[0]);
}

if($method==='POST' && $URI===$path."Resident.php/membership")
{

    $json = file_get_contents('php://input');
    $obj = json_decode($json);
    $Email = $obj->Email;
    $stmt = $conn->prepare("SELECT * from ResidentMembership where Email=?");
    $stmt->bind_param('s', $Email);
    $stmt->execute();
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode($results[0]);
}

if($method==='POST' && $URI===$path."Resident.php/booking")
{
    $json = file_get_contents('php://input');
    $obj = json_decode($json);
    $Email = $obj->Email;
    $Pool = $obj->Pool;
    $Garden = $obj->Garden;

    $sql = "INSERT INTO ResidentBooking (Email, Pool , Garden)
    values(?,?,?);";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $Email, $Pool, $Garden);
    $stmt->execute(); 
}
if($method==='POST' && $URI===$path."Resident.php/vehicles")
{
    $json = file_get_contents('php://input');
    $obj = json_decode($json);
    $Email = $obj->Email;
    $NumberPlate = $obj->NumberPlate;
    $VehicleMake=$obj->VehicleMake;
    $OwnerName=$obj->OwnerName;
    $APtNumber=$obj->APtNumber;
    // echo('working');

    $sql = "INSERT INTO ResidentVehicle (Email, NumberPlate ,VehicleMake, OwnerName, AptNumber) values(?,?,?,?,?);";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $Email, $NumberPlate, $VehicleMake,$OwnerName,$APtNumber);
    $stmt->execute(); 
    // echo([ $Email,$NumberPlate,$VehicleMake,$OwnerName,$APtNumber]);
}

if($method==='POST' && $URI===$path."Resident.php/vehiclesView")
{
    $json = file_get_contents('php://input');
    $obj = json_decode($json);
    $Email = $obj->Email;
    $stmt = $conn->prepare("SELECT * from ResidentVehicle where Email=?");
    $stmt->bind_param('s', $Email);
    $stmt->execute();
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode($results);
}


if($method==='POST' && $URI===$path."Resident.php/delete-vehicleId")
{
    $json = file_get_contents('php://input');
    $obj = json_decode($json);
    $VehicleId = $obj->VehicleId;
    $sql = "DELETE FROM ResidentVehicle WHERE VehicleId=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $VehicleId);
    $stmt->execute();
    echo($VehicleId);
}
$conn->close();
?>
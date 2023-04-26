<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: *");
header("Access-Control-Allow-Headers:*");
header('Content-Type:application/json; charset=UTF-8');
include "folderPath.php";

$response='';


$server = '51.81.160.154';
$dbname = 'dxj0015_terrazas-de-gucaco-v1';
$user = 'dxj0015_pramodh';
$pass = 'utacloud123';

// $server = 'localhost';
// $dbname = 'wdm';
// $user = 'root';
// $pass = 'root';


$conn = new mysqli($server, $user, $pass, $dbname);
$method=$_SERVER['REQUEST_METHOD'];
$URI=$_SERVER['REQUEST_URI'];
if($method==='GET' && $URI===$path."GardenTiming.php/timings")
{
    $trp = mysqli_query($conn, "SELECT * from gardentiming");
    $rows = array();
    while($r = mysqli_fetch_assoc($trp)) {
        $rows[] = $r;
    }
    echo json_encode($rows);

}

if($method==='POST' && $URI===$path."GardenTiming.php/timings")
{
    
    $json = file_get_contents('php://input');
    $obj = json_decode($json);
    $Day = $obj->day;
    $StartTime = $obj->StartTime;
    $EndTime = $obj->EndTime;

    $sql = "UPDATE gardentiming SET StartTime=?, EndTime=? WHERE day=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $StartTime, $EndTime, $Day);

    // $stmt->bind_param("sss",$StartTime,$EndTime,$Day);
    $stmt->execute(); 
    // echo json_encode();

}


if($method==='GET' && $URI===$path."GardenTiming.php/report")
{
    
    $trp1 = mysqli_query($conn, "SELECT * from visitorlog");
    $trp2 = mysqli_query($conn, "SELECT * from Residentlog");
    $rows = array();
    while($r = mysqli_fetch_assoc($trp1)) {
        $rows[] = $r;
    }
    while($r = mysqli_fetch_assoc($trp2)) {
        $rows[] = $r;
    }
    echo json_encode($rows);

}

?>
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
// http://localhost/terrazas-de-gucaco-backend/Pool.php/timings
if($method==='GET' && $URI==="/terrazas-de-gucaco-backend/Pool.php/timings")
{
    $trp = mysqli_query($conn, "SELECT * from pooltiming");
    $rows = array();
    while($r = mysqli_fetch_assoc($trp)) {
        $rows[] = $r;
    }
    echo json_encode($rows);

}

if($method==='POST' && $URI===$path."Pool.php/timings")
{
    echo('inside poll timing post');
    $json = file_get_contents('php://input');
    $obj = json_decode($json);
    $Day = $obj->day;
    $StartTime = $obj->StartTime;
    $EndTime = $obj->EndTime;

    $sql = "UPDATE pooltiming SET StartTime=?, EndTime=? WHERE Day=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $StartTime, $EndTime, $Day);

    // $stmt->bind_param("sss",$StartTime,$EndTime,$Day);
    $stmt->execute(); 
    // echo json_encode();

}
// $conn->close();
// return $response;


if($method==='GET' && $URI===$path."Pool.php/visitor")
{
    $trp = mysqli_query($conn, "SELECT * from visitorlog");
    $rows = array();
    while($r = mysqli_fetch_assoc($trp)) {
        $rows[] = $r;
    }
    echo json_encode($rows);

}

if($method==='DELETE' && $URI===$path."Pool.php/visitor")
{
    $json = file_get_contents('php://input');
    $obj = json_decode($json);
    $id = $obj->Id;
    $sql = "DELETE FROM visitorlog WHERE LogId=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $id);
    $stmt->execute(); 

}


if($method==='GET' && $URI===$path."Pool.php/resident")
{
    $trp = mysqli_query($conn, "SELECT * from residentlog");
    $rows = array();
    while($r = mysqli_fetch_assoc($trp)) {
        $rows[] = $r;
    }
    echo json_encode($rows);

}


if($method==='DELETE' && $URI===$path."Pool.php/resident")
{
    $json = file_get_contents('php://input');
    $obj = json_decode($json);
    $id = $obj->Id;
    $sql = "DELETE FROM residentlog WHERE LogId=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $id);
    $stmt->execute(); 

}

if($method==='GET' && $URI===$path."Pool.php/log")
{
    $residentlog = mysqli_query($conn, "SELECT * from residentlog");
    $visitorlog = mysqli_query($conn, "SELECT * from visitorlog");
    
    $rows = array();
    while($r = mysqli_fetch_assoc($residentlog)) {
        $rows[] = $r;
    }
    while($r = mysqli_fetch_assoc($visitorlog)) {
        $rows[] = $r;
    }
    echo json_encode($rows); 

}?>
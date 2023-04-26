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


$conn = new mysqli($server, $user, $pass, $dbname);
$method=$_SERVER['REQUEST_METHOD'];
$URI=$_SERVER['REQUEST_URI'];
if($method==='GET' && $URI==="/terrazas-de-gucaco-backend/Visitor.php/log")
{
    $trp = mysqli_query($conn, "SELECT * from visitorlog");
    $rows = array();
    while($r = mysqli_fetch_assoc($trp)) {
        $rows[] = $r;
    }
    echo json_encode($rows);

}

if($method==='POST' && $URI===$path."Visitor.php/log-delete")
{
    
    $json = file_get_contents('php://input');
    $obj = json_decode($json);
    $log_id=$obj->log_id;

    $sql = "DELETE FROM visitorlog WHERE LogId=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $log_id);
    $stmt->execute(); 

}
// $conn->close();
// return $response;
?>
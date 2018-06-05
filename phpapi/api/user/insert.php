<?php 
  
include_once '../../models/User.php';

$response = array();
$objUser = new User();

$response = $objUser->insert(
  json_decode(file_get_contents("php://input"))
);

echo json_encode($response); 
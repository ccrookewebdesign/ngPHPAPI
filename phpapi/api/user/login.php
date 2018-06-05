<?php 

include_once '../../models/User.php';

$objUser = new User();

echo json_encode(
  $objUser->login(
    json_decode(file_get_contents("php://input"))
  )
);
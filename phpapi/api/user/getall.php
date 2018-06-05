<?php 
  
include_once '../../models/User.php';
include_once '../../models/Token.php';

$response = array();
$verify = array();

$objUser = new User();
  
$verify = Token::checkToken();

if ($verify['success']) {

  $response = $objUser->getall();

} else {

  $response = $verify;

}

echo json_encode($response);
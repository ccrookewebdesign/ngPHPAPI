<?php 

include_once '../../models/User.php';
include_once '../../models/Token.php';

$response = array();
$verify = array();

if (isset($_GET['id'])) {

  $verify = Token::checkToken();
  
  if ($verify['success']) {

    $objUser = new User();
    $response = $objUser->get($_GET['id']);
  
  } else {
  
    $response = $verify;
  
  } 

} else {
  
  $response['success'] = false;
  $response['message'] = 'No id provided.';
  $response['errcode'] = 'no-id';

}

echo json_encode($response);
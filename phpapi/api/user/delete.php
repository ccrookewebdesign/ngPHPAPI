<?php 
  
include_once '../../models/User.php';
include_once '../../models/Token.php';

$response = array();
$verify = array();

if (isset($_GET['id'])) {

  $objToken = new Token();
  
  $verify = $objToken->checkToken();

  if ($verify['success']) {

    $objUser = new User();
    
    $response = $objUser->delete($_GET['id']);

  } else {

    $response['success'] = false;
    $response['message'] = verify['message'];
    $response['errcode'] = 'no-token';
    
  }

} else {
  
  $response['success'] = false;
  $response['message'] = 'No id provided';
  $response['errcode'] = 'no-id';

}

echo json_encode($response); 
<?php

include_once '../jwt/jwt.php';
include_once 'User.php';

class Token {

  private static $secret_key = '53@rE*!';

  private function getAuthorizationHeader(){
    
    $headers = null;
    
    if (isset($_SERVER['Authorization'])) {
    
      $headers = trim($_SERVER["Authorization"]);
    
    }
    
    else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { 
    
      $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    
    } elseif (function_exists('apache_request_headers')) {
    
      $requestHeaders = apache_request_headers();
      $requestHeaders = array_combine(
      
        array_map('ucwords', array_keys($requestHeaders)), 
        array_values($requestHeaders)
      
      );
    
      if (isset($requestHeaders['Authorization'])) {
    
        $headers = trim($requestHeaders['Authorization']);
    
      }
    
    }
    
    return $headers;
  
  }

  public static function checkToken() {

    $response = array();
      
    $headers = self::getAuthorizationHeader(); 
    
    $response = JWT::decode($headers, self::$secret_key, ['HS256']);
 
    if($response['success']){
    
      $objUser = new User();
      
      $response = $objUser->checkUserById($response['data']->userId);
    
    } 

    return $response;
  
  }

  public static function setToken($payload) {

    return JWT::encode($payload, self::$secret_key);

  }
  
}
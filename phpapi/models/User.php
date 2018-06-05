<?php 

include_once '../../config/Database.php';
include_once 'Token.php';

class User {
  
  private $conn;
  
  public $id;
  public $firstname;
  public $lastname;
  public $email;
  public $username;
  public $password;
  public $lastlogin;
  public $create_dt;

  public function __construct() {

    $database = new Database();
    $dbConnectResponse = $database->connect();
    $this->conn = $dbConnectResponse['data'];
    
  }

  public function login($user) {

    $response = array();

    $query = '
      SELECT u.id,
        u.firstname, u.lastname, u.email, u.username, 
        u.password, u.lastlogin, u.create_dt    
      FROM users u
      where username = :username and password = :password
    ';
    
    $loginUser = $this->conn->prepare($query);

    $user = $this->cleanValues($user);
    
    $loginUser->bindParam(':username', $user['username']);
    $loginUser->bindParam(':password', $user['password']);

    $loginUser->execute();

    $rowCount = $loginUser->rowCount();

    if($rowCount) {

      $now = date("Y-m-d H:i:s");

      $query = '
        UPDATE users
        set lastlogin = :now
        where username = :username
      ';
      
      $updateUserLastLogin = $this->conn->prepare($query);

      $updateUserLastLogin->bindParam(':now', $now);
      $updateUserLastLogin->bindParam(':username', $user['username']);
      
      $updateUserLastLogin->execute();
      
      $row = $loginUser->fetch(PDO::FETCH_ASSOC);

      $payload = [
        'iat' => time(),
        'iss' => 'localhost',
        'exp' => time() + (15*60),
        'userId' => $row['id']
      ];
      
      $token = Token::setToken($payload);
      
      $returnArray = array(
  
        'id' => $row['id'],
        'firstname' => $row['firstname'],
        'lastname' => $row['lastname'],
        'email' => $row['email'],
        'username' => $row['username'],
        'password' => $row['password'],
        'lastlogin' => $now,
        'create_dt' => $row['create_dt']
  
      );
        
      $response['success'] = true;
      $response['message'] = 'User successfully logged in';
      $response['data'] = $returnArray;
      $response['token'] = $token;
        
    } else {
        
      $response['success'] = false;
      $response['message'] = 'Incorrect login credentials.';
      $response['errcode'] = 'login-fail';
       
    }
      
    return $response;

  }

  public function checkUserById($id) {

    $response = array();

    $query = '
      SELECT u.id,
        u.firstname, u.lastname, u.email, u.username, 
        u.password, u.lastlogin, u.create_dt    
      FROM users u
      where u.id = :id
    ';
    
    $checkUser = $this->conn->prepare($query);

    $checkUser->bindParam(':id', $id);
    
    $checkUser->execute();

    $rowCount = $checkUser->rowCount();

    if($rowCount) {

      $response['success'] = true;
        
    } else {
        
      $response['success'] = false;
       
    }
      
    return $response;

  }

  public function getall() {
    
    $response = array();
    
    $query = '
      SELECT u.id,
        u.firstname, u.lastname, u.email, u.username, 
        u.password, u.lastlogin, u.create_dt    
      FROM users u
      ORDER BY u.lastname DESC
    ';
    
    $getUsers = $this->conn->prepare($query);

    $getUsers->execute();

    $rowCount = $getUsers->rowCount();

    if($rowCount) {

      $returnArray = array();

      while($row = $getUsers->fetch(PDO::FETCH_ASSOC)) {
        
        extract($row);

        $userItem = array(
        
          'id' => $id,
          'firstname' => $firstname,
          'lastname' => $lastname,
          'email' => $email,
          'username' => $username,
          'password' => $password,
          'lastlogin' => $lastlogin,
          'create_dt' => $create_dt
        
        );

        array_push($returnArray, $userItem);

      }
      
      $response['success'] = true;
      $response['message'] = $rowCount . ' user records returned';
      $response['data'] = json_encode($returnArray);

    } else {
      
      $response['success'] = false;
      $response['message'] = 'No users found.';
      $response['errcode'] = 'no-users';

    } 

    return $response;

  }

  public function get($id) {
     
    $response = array();

    $query = '
      SELECT 
        u.firstname, u.lastname, u.email, u.username, 
        u.password, u.lastlogin, u.create_dt    
      FROM users u
      WHERE u.id = :id
      ORDER BY u.lastname DESC        
    ';

    $getUser = $this->conn->prepare($query);

    $getUser->bindParam(':id', $id);

    $getUser->execute();

    $rowCount = $getUser->rowCount();

    if($rowCount) {

      $row = $getUser->fetch(PDO::FETCH_ASSOC);

      $returnArray = array(
  
        'id' => $id,
        'firstname' => $row['firstname'],
        'lastname' => $row['lastname'],
        'email' => $row['email'],
        'username' => $row['username'],
        'password' => $row['password'],
        'lastlogin' => $row['lastlogin'],
        'create_dt' => $row['create_dt']
  
      );

      $response['success'] = true;
      $response['message'] = 'User successfully retrieved.';
      $response['data'] = json_encode($returnArray);
  
    
    } else {
      
      $response['success'] = false;
      $response['message'] = 'No user found.';
      $response['errcode'] = 'no-user';

    }

    return $response;

  }

  public function insert($user) {
    
    $response = array();

    $response = $this->validateUser($user);

    if(!$response['success']) {

      return $response;

    }

    $response = $this->dupCheckUser($user);

    if(!$response['success']) {

      return $response;

    } 
    
    $query = '
      INSERT INTO users
      SET
        firstname = :firstname,
        lastname = :lastname,
        email = :email,
        username = :username,
        password = :password          
    ';
    
    $stmt = $this->conn->prepare($query);
    
    $user = $this->cleanValues($user);

    $stmt->bindParam(':firstname', $user['firstname']);
    $stmt->bindParam(':lastname', $user['lastname']);
    $stmt->bindParam(':email', $user['email']);
    $stmt->bindParam(':username', $user['username']);
    $stmt->bindParam(':password', $user['password']);

    if($stmt->execute()) {

      $id = $this->conn->lastInsertId();

      $returnArray = array(
  
        'id' => $id,
        'firstname' => $user['firstname'],
        'lastname' => $user['lastname'],
        'email' => $user['email'],
        'username' => $user['username'],
        'password' => $user['password'],
      
      );

      $payload = [
        'iat' => time(),
        'iss' => 'localhost',
        'exp' => time() + (15*60),
        'userId' => $id
      ];

      $objToken = new Token();

      $token = $objToken->setToken($payload);

      $response['success'] = true;
      $response['message'] = 'User created successfully';
      $response['data'] = $returnArray;
      $response['token'] = $token;

    } else {

      $response['success'] = false;
      $response['message'] = 'There was an issue inserting the new user into the database';
      $response['errcode'] = 'db-insert';

    }

    return $response;
    
  }

  public function update($user) {

    $response = array();

    $response = $this->validateUser($user);

    if(!$response['success']) {

      return $response;

    }

    $response = $this->dupCheckUser($user);

    if(!$response['success']) {

      return $response;

    }
    
    $query = '
      UPDATE users
      SET
        firstname = :firstname,
        lastname = :lastname,
        email = :email,
        username = :username,
        password = :password
      WHERE
        id = :id
    ';

    $stmt = $this->conn->prepare($query);

    $user = $this->cleanValues($user);

    $stmt->bindParam(':firstname', $user['firstname']);
    $stmt->bindParam(':lastname', $user['lastname']);
    $stmt->bindParam(':email', $user['email']);
    $stmt->bindParam(':username', $user['username']);
    $stmt->bindParam(':password', $user['password']);
    $stmt->bindParam(':id', $user['id']);

    if($stmt->execute()) {
      
      $response['success'] = true;
      $response['message'] = 'User updated successfully';
      $response['data'] = json_encode($user);
    
    } else {

      $response['success'] = false;
      $response['message'] = 'There was an issue updating the user in the database.';
      $response['errcode'] = 'db-update';

    } 

    return $response;
    
  }

  public function delete($id) {
    
    $query = 'DELETE FROM users WHERE id = :id';

    $stmt = $this->conn->prepare($query);

    $id = htmlspecialchars(strip_tags($id));

    $stmt->bindParam(':id', $id);

    if($stmt->execute()) {
      
      $response['success'] = true;
      $response['message'] = 'User deleted successfully';
    
    } else {

      $response['success'] = false;
      $response['message'] = 'There was an issue deleting the user in the database.';
      $response['errcode'] = 'db-delete';

    }

    return $response;

  }

  private function validateUser($user) {

    $response = array();

    $response['success'] = true;
    $response['message'] = 'Validation Succeeded.';
  
    if(
      !strlen($user->firstname) or 
      !strlen($user->lastname)  or 
      !strlen($user->email)  or 
      !strlen($user->username)  or 
      !strlen($user->password)  
    ) { 
        
      $response['success'] = false;
      $response['message'] = 'Please fill in all required fields and provide a valid email address';
      $response['errcode'] = 'invalid-data';
     
    }
  
    return $response;

  }

  private function dupCheckUser($user) {
    
    $response = array();

    $response['success'] = true;
    $response['message'] = 'Validation Succeeded.';

    $query = '
      SELECT u.id,
        u.firstname, u.lastname, u.email, u.username, 
        u.password, u.lastlogin, u.create_dt    
      FROM users u
      WHERE (u.username = :username or u.email = :email)
    ';
    
    if (isset($user->id)) {
      $query = $query . ' and u.id != :id';
    }

    $dupCheck = $this->conn->prepare($query);

    $dupCheck->bindParam(':username', $user->username);
    $dupCheck->bindParam(':email', $user->email);

    if (isset($user->id)) {
      $dupCheck->bindParam(':id', $user->id);
    }

    $dupCheck->execute();

    $rowCount = $dupCheck->rowCount();

    if($rowCount) {
      
      $response['success'] = false;

      $row = $dupCheck->fetch(PDO::FETCH_ASSOC);

      if ($row['email'] === $user->email) {

        $response['message'] = ' - This email already exists in our system. Please login to access your account.';

      } else {

        $response['message'] = 'This username is taken.';

      }      
          
    } else {
      
      $response['success'] = true;
    
    } 

    return $response;

  }

  private function cleanValues($item) {

    $tempArray = array();

    foreach ($item as $i => $v) {

      $tempArray[$i] =  htmlspecialchars(strip_tags(trim($v)));

    }

    return $tempArray;
  
  }
  
} 
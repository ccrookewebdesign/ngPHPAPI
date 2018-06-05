<?php 
  
class Database {

  private $response = array();
  
  private $host = 'localhost';
  private $db_name = 'phpapi';
  private $username = 'root';
  private $password = '';
  private $conn;

  public function connect() {
    
    $this->conn = null;

    try { 
      
      $this->conn = new PDO(
      
        'mysql:host=' . $this->host . ';dbname=' . $this->db_name, 
        $this->username, 
        $this->password
      
      );
      
      $this->conn->setAttribute(
      
        PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION
      
      );

      $response['success'] = true;
      $response['message'] = 'Database successfully connected';
      $response['data'] = $this->conn;
    
    } catch(PDOException $e) {
      
      $response['success'] = false;
      $response['message'] = $e->getMessage();
      $response['errcode'] = 'db-connection';
    
    }

    return $response;
  
  }

}
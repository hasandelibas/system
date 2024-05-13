<?php

/**
 

 */
class DB{

  public $host;
  public $user;
  public $pass;
  public $database;
  public $db;
  public $error = false;

  public function __construct(string $host = null, string $database = null, string $user = null, string $pass = null){
    $this->host = $host;
    $this->user = $user;
    $this->pass = $pass;
    $this->database = $database;
    $this->db = new mysqli($this->host, $this->user, $this->pass, $this->database);
    $this->db->set_charset("utf8mb4");
  }

  private function where($where){
    $_where = "";
    $_values = [];
    // If where type is number
    if(is_numeric($where)){
      $_where = "id = ?";
      $_values = [$where];
    }
    // If where type is array
    if(is_array($where)){
      $_where = "";
      $_isFirst = true;
      foreach($where as $key => $value){
        $_values[] = $value;
        if($_isFirst){
          $_where .= "$key = ? ";
        }else{
          $_where .= "and $key = ? ";
        }
        $_isFirst = false;
      }
    }
    return [$_where, $_values];
  }

  public function remove(string $table, $where=null){
    $query = "DELETE FROM $table";
    $_where_values = $this->where($where);
    $_where = $_where_values[0];
    $_values = $_where_values[1];

    if($_where != ""){
      $query .= " WHERE $_where";
    }
    return  $this->sql($query,$_values);
  }

  public function get(string $table, $where=null){
    $query = "SELECT * FROM $table";
    $_where_values = $this->where($where);
    $_where = $_where_values[0];
    $_values = $_where_values[1];

    if($_where != ""){
      $query .= " WHERE $_where";
    }
    $query .= " LIMIT 1";

    $stmt = $this->db->prepare($query);
    // Check _where is empty
    if($_where != ""){
      $stmt->bind_param("".str_repeat("s", count($_values)), ...$_values);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row;
  }

  public function all(string $table, $where=null){
    $query = "SELECT * FROM $table";
    $_where_values = $this->where($where);
    $_where = $_where_values[0];
    $_values = $_where_values[1];

    if($_where != ""){
      $query .= " WHERE $_where";
    }
    
    $stmt = $this->db->prepare($query);
    // Check _where is empty
    if($_where != ""){
      $stmt->bind_param("".str_repeat("s", count($_values)), ...$_values);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while($row = $result->fetch_assoc()){
      $rows[] = $row;
    }
    $stmt->close();
    return $rows;
  }

  public function sql($sql,$parameters=[]){
    $sql = trim($sql);
    $stmt = $this->db->prepare($sql);
    // If parameters is array
    if(is_array($parameters) && count($parameters) > 0){
      $stmt->bind_param( "".str_repeat("s", count($parameters)), ...$parameters );
    }
    
    mysqli_report(0);

    if ($stmt->execute()) { 
      $this->error = false;
    } else {
      $this->error = $stmt->error;
    }
    
    // If Select Statement
    if(strpos(strtolower(str_replace(["(",")"," "], "", $sql)), "select") === 0 ){
      
      $result = $stmt->get_result();
      $rows = [];
      while($row = $result->fetch_assoc()){
        $rows[] = $row;
      }
      $stmt->close();
      return $rows;  
    }

    // Update or Delete Statement
    if(strpos(strtolower($sql), "update") !== false || strpos(strtolower($sql), "delete") !== false || strpos(strtolower($sql), "replace") !== false){
      $row_counts = $stmt->affected_rows;
      $stmt->close();
      return $row_counts;
    }

    // Insert Statement
    if(strpos(strtolower($sql), "insert") !== false){
      $row_counts = $stmt->affected_rows;
      $stmt->close();
      return $row_counts;
    }

    $stmt->close();
  }

  public function set($table,$parameters){
    $keys = [];
    $values = [];
    $equals = [];
    $marks  = [];
    foreach($parameters as $key => $value){
      $keys[]   = $key;
      $values[] = $value;
      $marks[]  = "?";
      $equals[] = " $key='". addslashes($value) ."' ";
    }
    $equalsSql = "".implode(",", $equals)."";
    $query = "INSERT INTO $table SET " . $equalsSql . " ON DUPLICATE KEY UPDATE " . $equalsSql;
    //$query = "REPLACE INTO $table (" . implode(",", $keys) . ") VALUES (". implode(",", $marks) .")";
    return $this->sql($query);
    // return $this->sql($query,$values);
  }


  public function add($table,$parameters){
    $keys = [];
    $values = [];
    $equals = [];
    foreach($parameters as $key => $value){
      $keys[] = $key;
      $values[] = $value;
      $equals[] = " $key='". addslashes($value) ."' ";
    }
    $equalsSql = "".implode(",", $equals)."";
    $query = "INSERT INTO $table SET " . $equalsSql;
    return $this->sql($query);
  }


  public function tables(){
    return array_map(function($d){
      return $d["name"];
    },$this->sql("SELECT table_name as 'name' FROM information_schema.tables WHERE table_schema = ? ",[$this->database])); 
  }


  public function columns($table){
    return array_map(function($d){
      return $d["name"];
    },$this->sql("SELECT COLUMN_NAME as 'name' FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION",[$this->database,$table]));
  }

  public function beginTransaction(){
    $this->db->begin_transaction();
  }

  public function autocommit(){
    $this->db->autocommit(FALSE);
  }
  
  public function commit(){
    $this->db->commit();
  }

  public function rollback(){
    $this->db->rollback();
  }

  public function save(){
    return true;
  }
}
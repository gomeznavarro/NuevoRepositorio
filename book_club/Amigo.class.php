<?php

require_once "DataObject.class.php";

class Amigo extends DataObject {

  protected $data = array(
    "id" => "",
    "firstName" => "",
    "idmember" => ""
  );


  public static function getAmigos($idmember) {
    $conn = parent::connect();
    $sql = "SELECT * FROM amigos WHERE idmember=$idmember ";
    try {
      $st = $conn->query( $sql );
      $amigos = array();
      foreach ( $st->fetchAll(PDO::FETCH_ASSOC) as $row ) {
        //$amigos[] = new Amigo( $row );
		$amigos[] = $row;
      }
      parent::disconnect( $conn );
      return $amigos;
    } catch ( PDOException $e ) {
      parent::disconnect( $conn );
      die( "Query failed: " . $e->getMessage() );
    }
  }

  public static function getMember( $id ) {
    $conn = parent::connect();
    $sql = "SELECT * FROM " . TBL_MEMBERS . " WHERE id = :id";

    try {
      $st = $conn->prepare( $sql );
      $st->bindValue( ":id", $id, PDO::PARAM_INT );
      $st->execute();
      $row = $st->fetch();
      parent::disconnect( $conn );
      if ( $row ) return new Member( $row );
    } catch ( PDOException $e ) {
      parent::disconnect( $conn );
      die( "Query failed: " . $e->getMessage() );
    }
  }

  public static function getByUsername( $username ) {
    $conn = parent::connect();
    $sql = "SELECT * FROM " . TBL_MEMBERS . " WHERE username = :username";

    try {
      $st = $conn->prepare( $sql );
      $st->bindValue( ":username", $username, PDO::PARAM_STR );
      $st->execute();
      $row = $st->fetch();
      parent::disconnect( $conn );
      if ( $row ) return new Member( $row );
    } catch ( PDOException $e ) {
      parent::disconnect( $conn );
      die( "Query failed: " . $e->getMessage() );
    }
  }

  public static function getByEmailAddress( $emailAddress ) {
    $conn = parent::connect();
    $sql = "SELECT * FROM " . TBL_MEMBERS . " WHERE emailAddress = :emailAddress";

    try {
      $st = $conn->prepare( $sql );
      $st->bindValue( ":emailAddress", $emailAddress, PDO::PARAM_STR );
      $st->execute();
      $row = $st->fetch();
      parent::disconnect( $conn );
      if ( $row ) return new Member( $row );
    } catch ( PDOException $e ) {
      parent::disconnect( $conn );
      die( "Query failed: " . $e->getMessage() );
    }
  }

  public function getGenderString() {
    return ( $this->data["gender"] == "f" ) ? "Female" : "Male";
  }

  public function getFavoriteGenreString() {
    return ( $this->_genres[$this->data["favoriteGenre"]] );
  }

  public function getGenres() {
    return $this->_genres;
  }

  public function insert() {
    $conn = parent::connect();
    $sql = "INSERT INTO " . TBL_MEMBERS . " (
              username,
              password,
              firstName,
              lastName,
              joinDate,
              gender,
              favoriteGenre,
              emailAddress,
              otherInterests
            ) VALUES (
              :username,
              password(:password),
              :firstName,
              :lastName,
              :joinDate,
              :gender,
              :favoriteGenre,
              :emailAddress,
              :otherInterests
            )";

    try {
      $st = $conn->prepare( $sql );
      $st->bindValue( ":username", $this->data["username"], PDO::PARAM_STR );
      $st->bindValue( ":password", $this->data["password"], PDO::PARAM_STR );
      $st->bindValue( ":firstName", $this->data["firstName"], PDO::PARAM_STR );
      $st->bindValue( ":lastName", $this->data["lastName"], PDO::PARAM_STR );
      $st->bindValue( ":joinDate", $this->data["joinDate"], PDO::PARAM_STR );
      $st->bindValue( ":gender", $this->data["gender"], PDO::PARAM_STR );
      $st->bindValue( ":favoriteGenre", $this->data["favoriteGenre"], PDO::PARAM_STR );
      $st->bindValue( ":emailAddress", $this->data["emailAddress"], PDO::PARAM_STR );
      $st->bindValue( ":otherInterests", $this->data["otherInterests"], PDO::PARAM_STR );
      $st->execute();
      parent::disconnect( $conn );
    } catch ( PDOException $e ) {
      parent::disconnect( $conn );
      die( "Query failed: " . $e->getMessage() );
    }
  }

  public function update() {
    $conn = parent::connect();
    $passwordSql = $this->data["password"] ? "password = password(:password)," : "";
    $sql = "UPDATE " . TBL_MEMBERS . " SET
              username = :username,
              $passwordSql
              firstName = :firstName,
              lastName = :lastName,
              joinDate = :joinDate,
              gender = :gender,
              favoriteGenre = :favoriteGenre,
              emailAddress = :emailAddress,
              otherInterests = :otherInterests
            WHERE id = :id";

    try {
      $st = $conn->prepare( $sql );
      $st->bindValue( ":id", $this->data["id"], PDO::PARAM_INT );
      $st->bindValue( ":username", $this->data["username"], PDO::PARAM_STR );
      if ( $this->data["password"] ) $st->bindValue( ":password", $this->data["password"], PDO::PARAM_STR );
      $st->bindValue( ":firstName", $this->data["firstName"], PDO::PARAM_STR );
      $st->bindValue( ":lastName", $this->data["lastName"], PDO::PARAM_STR );
      $st->bindValue( ":joinDate", $this->data["joinDate"], PDO::PARAM_STR );
      $st->bindValue( ":gender", $this->data["gender"], PDO::PARAM_STR );
      $st->bindValue( ":favoriteGenre", $this->data["favoriteGenre"], PDO::PARAM_STR );
      $st->bindValue( ":emailAddress", $this->data["emailAddress"], PDO::PARAM_STR );
      $st->bindValue( ":otherInterests", $this->data["otherInterests"], PDO::PARAM_STR );
      $st->execute();
      parent::disconnect( $conn );
    } catch ( PDOException $e ) {
      parent::disconnect( $conn );
      die( "Query failed: " . $e->getMessage() );
    }
  }
  
  public function delete() {
    $conn = parent::connect();
    $sql = "DELETE FROM " . TBL_MEMBERS . " WHERE id = :id";

    try {
      $st = $conn->prepare( $sql );
      $st->bindValue( ":id", $this->data["id"], PDO::PARAM_INT );
      $st->execute();
      parent::disconnect( $conn );
    } catch ( PDOException $e ) {
      parent::disconnect( $conn );
      die( "Query failed: " . $e->getMessage() );
    }
  }

  public function authenticate() {
    $conn = parent::connect();
    $sql = "SELECT * FROM " . TBL_MEMBERS . " WHERE username = :username AND password = password(:password)";

    try {
      $st = $conn->prepare( $sql );
      $st->bindValue( ":username", $this->data["username"], PDO::PARAM_STR );
      $st->bindValue( ":password", $this->data["password"], PDO::PARAM_STR );
      $st->execute();
      $row = $st->fetch();
      parent::disconnect( $conn );
      if ( $row ) return new Member( $row );
    } catch ( PDOException $e ) {
      parent::disconnect( $conn );
      die( "Query failed: " . $e->getMessage() );
    }
  }

}

?>

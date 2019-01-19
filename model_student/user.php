<?php
namespace Model\User;
use \Db;
use \PDOException;
/**
 * User model
 *
 * This file contains every db action regarding the users
 */
	
	
/**
 * Get a user in db
 * @param id the id of the user in db
 * @return an object containing the attributes of the user or null if error or the user doesn't exist
 */
function get($id) {

	$db = \Db::dbc();
	$sth = $db->prepare("SELECT * FROM UTILISATEUR WHERE IDUSER = :id ");

	try{
		if($sth->execute(array(":id"=>$id))){
			foreach($sth->fetchALL() as $row) {
			return (object) array(
       			"id" => $row['IDUSER'],
       			"username" => $row['NOMUSER'],
       			"name" => $row['PRENOM'],
			"date" => $row['INSCRIDATE'],
        		"password" => $row['PASSWORD'],
        		"email" => $row['EMAIL'],
        		"avatar" => $row['AVATAR'] 
			);
			}
		}else
			return NULL;
	}catch(PDOException $e){
        	echo 'failed：'.$e->getMessage();
        exit;
    	}
}

/**
 * Create a user in db
 * @param username the user's username
 * @param name the user's name
 * @param password the user's password
 * @param email the user's email
 * @param avatar_path the temporary path to the user's avatar
 * @return the id which was assigned to the created user, null if an error occured
 * @warning this function doesn't check whether a user with a similar username exists
 * @warning this function hashes the password
 */
function create($username, $name, $password, $email, $avatar_path) {
	
	$db = \Db::dbc();
	$sql="INSERT INTO UTILISATEUR (NOMUSER,PRENOM, INSCRIDATE,PASSWORD, EMAIL,AVATAR)VALUES(:username,:name,now(),:password,:email,:avatar_path)";
	$sth = $db->prepare($sql);
	try{
		if($sth->execute(array(":username"=>$username,":name"=>$name,":password"=>hash_password($password),":email"=>$email,":avatar_path"=>$avatar_path))){
			return $db->lastInsertId();
		}
		else
			return NULL;
	}catch(PDOException $e){
        	echo 'failed：'.$e->getMessage();
        exit;
    	}


}

/** 
 * Modify a user in db
 * @param uid the user's id to modify
 * @param username the user's username
 * @param name the user's name
 * @param email the user's email
 * @warning this function doesn't check whether a user with a similar username exists
 */
function modify($uid, $username, $name, $email) {
	$db = \Db::dbc();
	$sql="UPDATE UTILISATEUR SET NOMUSER = :username, PRENOM = :name,EMAIL=:email  WHERE IDUSER = :UID";
	$sth=$db->prepare($sql);
	try{
		$sth->execute(array(":UID"=>$uid,":username"=>$username,":name"=>$name,":email"=>$email));
	
	}catch(PDOException $e){
        	echo 'failed：'.$e->getMessage();
        exit;
    	}
}

/**
 * Modify a user in db
 * @param uid the user's id to modify
 * @param new_password the new password
 * @warning this function hashes the password
 */
function change_password($uid, $new_password) {
	$db = \Db::dbc();
	$sql="UPDATE UTILISATEUR SET PASSWORD = :password  WHERE IDUSER = :UID";
	$sth=$db->prepare($sql);
	try{
		$sth->execute(array(":UID"=>$uid,":password"=>hash_password($new_password)));
	
	}catch(PDOException $e){
        	echo 'failed：'.$e->getMessage();
        exit;
    	}
}

/**
 * Modify a user in db
 * @param uid the user's id to modify
 * @param avatar_path the temporary path to the user's avatar
 */
function change_avatar($uid, $avatar_path) {
	$db = \Db::dbc();
	$sql="UPDATE UTILISATEUR SET AVATAR = :avatar_path  WHERE IDUSER = :UID";
	$sth=$db->prepare($sql);
	try{
		$sth->execute(array(":UID"=>$uid,":avatar_path"=>$avatar_path));
	
	}catch(PDOException $e){
        	echo 'failed：'.$e->getMessage();
        exit;
    	}
}

/**
 * Delete a user in db
 * @param id the id of the user to delete
 * @return true if the user has been correctly deleted, false else
 */
function destroy($id) {
	$db = \Db::dbc();
	$sql="DELETE FROM UTILISATEUR WHERE  IDUSER = :UID";
	$sql1="SELECT * FROM TWITTER WHERE  IDUSER = :UID";
	$sql2="DELETE FROM SUIVRE WHERE  IDUSER_SUIVRE = :UID AND IDUSER_ABONNE = :UID ";
	$sth=$db->prepare($sql);
	$sth1=$db->prepare($sql1);
	$sth2=$db->prepare($sql2);
	try{

		$sth1->execute(array(":UID"=>$id));
		$i=0;
		while($row=$sth->fetch()){
			$post[$i]=$row['IDTWEET'];
			Model\Post\destroy($post[$i]);
			$i++;
		}
		$sth2->execute(array(":UID"=>$id));
		if($sth->execute(array(":UID"=>$id))){
			return true;
		}
		else{
			return false;
		}
	}catch(PDOException $e){
        	echo 'failed：'.$e->getMessage();
        exit;
    	}
}

/**
 * Hash a user password
 * @param password the clear password to hash
 * @return the hashed password
 */
function hash_password($password) {
	return md5($password);
    //return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Search a user
 * @param string the string to search in the name or username
 * @return an array of find objects
 */
function search($string) {
	$db = \Db::dbc();
	$sql="SELECT IDUSER FROM UTILISATEUR WHERE NOMUSER like :username OR PRENOM like :name ";
	$sth=$db->prepare($sql);
	try{
		$arr=array();
		$sth->execute(array(":username"=>"%$string%",":name"=>"%$string%"));
			$i=0;
 			while($row=$sth->fetch()) {
    			$id[$i]=$row['IDUSER'];
			$arr[$i]=get($id[$i]);
			$i++;
 	 		}
  		return $arr;

		
	}catch(PDOException $e){
        	echo 'failed：'.$e->getMessage();
        exit;
    	}
    //return [get(1)];
}

/**
 * List users
 * @return an array of the objects of every users
 */
function list_all() {
	$db = \Db::dbc();
	$sql= "SELECT IDUSER FROM UTILISATEUR";
	$sth= $db->prepare($sql);
	try{
		$sth->execute();
		$i=0;
		$arr=array();
 		while($row=$sth->fetch()) {
    			$id[$i]=$row['IDUSER'];
			$arr[$i]=get($id[$i]);
			$i++;
 	 	}
  		return $arr;

		
	}catch(PDOException $e){
        	echo 'failed：'.$e->getMessage();
        exit;
    	}
    	//return [get(1)];
}

/**
 * Get a user from its username
 * @param username the searched user's username
 * @return the user object or null if the user doesn't exist
 */
function get_by_username($username) {
	$db = \Db::dbc();
	$sth = $db->prepare("SELECT * FROM UTILISATEUR WHERE NOMUSER = :NOMUSER ");

	try{
		if($sth->execute(array(":NOMUSER"=>$username))){
			foreach($sth->fetchALL() as $row) {
			return (object) array(
       			"id" => $row['IDUSER'],
       			"username" => $row['NOMUSER'],
       			"name" => $row['PRENOM'],
			"date" => $row['INSCRIDATE'],
        		"password" => $row['PASSWORD'],
        		"email" => $row['EMAIL'],
        		"avatar" => $row['AVATAR']
			);
			}
		}else
			return NULL;
	}catch(PDOException $e){
        	echo 'failed：'.$e->getMessage();
        exit;
    	}

}

/**
 * Get a user's followers
 * @param uid the user's id
 * @return a list of users objects
 */
function get_followers($uid) {
	$db = \Db::dbc();
	$sql="SELECT IDUSER_SUIVRE FROM SUIVRE WHERE IDUSER_ABONNE = :id ";
	$sth=$db->prepare($sql);
	try{
		$sth->execute(array(":id"=>$uid));
			$arr=array();
			$i=0;
 			while($row=$sth->fetch()) {
			if(!$row) return NULL;
    			$id[$i]=$row['IDUSER_SUIVRE'];
			$arr[$i]=get($id[$i]);
			$i++;
 	 		}
  		return $arr;
			
	}catch(PDOException $e){
        	echo 'failed：'.$e->getMessage();
        exit;
    	}

}

/**
 * Get the users our user is following
 * @param uid the user's id
 * @return a list of users objects
 */
function get_followings($uid) {
	$db = \Db::dbc();
	$sql="SELECT IDUSER_ABONNE FROM SUIVRE WHERE IDUSER_SUIVRE = :id ";
	$sth=$db->prepare($sql);
	try{
		$sth->execute(array(":id"=>$uid));
	
			$i=0;
			$arr=array();
 			while($row=$sth->fetch()) {		
    			$id[$i]=$row['IDUSER_ABONNE'];
			$arr[$i]=get($id[$i]);
			$i++;
 	 		}
  			return $arr;
		
	}catch(PDOException $e){
        	echo 'failed：'.$e->getMessage();
        exit;
    	}

    //return [get(2)];
}

/**
 * Get a user's stats
 * @param uid the user's id
 * @return an object which describes the stats
 */
function get_stats($uid) {
	$db = \Db::dbc();
	$sql="SELECT count(*) as total FROM TWITTER WHERE IDUSER = :uid ";
	$sql1="SELECT count(*) as total1 FROM SUIVRE WHERE IDUSER_ABONNE = :uid ";
	$sql2="SELECT count(*) as total2 FROM SUIVRE WHERE IDUSER_SUIVRE = :uid ";
	$sth = $db->prepare($sql);
	$sth1 = $db->prepare($sql1);
	$sth2 = $db->prepare($sql2);	
	try{
		$sth->execute(array(":uid"=>$uid));
    		foreach($sth->fetchALL() as $row){
			$post=$row["total"];
		}
		$sth1->execute(array(":uid"=>$uid));
		foreach($sth1->fetchALL() as $row1){
			$followers=$row1["total1"];
		}
		$sth2->execute(array(":uid"=>$uid));
		foreach($sth2->fetchALL() as $row2){
			$followings=$row2["total2"];
		}
		return (object) array(
        		"nb_posts" => $post,
        		"nb_followers" => $followers,
        		"nb_following" => $followings
    		);
	}catch(PDOException $e){
        	echo 'failed：'.$e->getMessage();
        exit;
    	}

/*
    return (object) array(
        "nb_posts" => 10,
        "nb_followers" => 50,
        "nb_following" => 66
    );*/
}

/**
 * Verify the user authentification
 * @param username the user's username
 * @param password the user's password
 * @return the user object or null if authentification failed
 * @warning this function must perform the password hashing   
 */
function check_auth($username, $password) {

	$db = Db::dbc();
	$query = sprintf("SELECT * FROM UTILISATEUR WHERE NOMUSER = '%s' AND PASSWORD = '%s'",$username, hash_password($password));
	$result = $db->query($query);
	$row= $result->fetch();
	if (!$row) { return NULL;}
	return (object) array(
			"id" => $row['IDUSER'],
       			"username" => $row['NOMUSER'],
       			"name" => $row['PRENOM'],
			"date" => $row['INSCRIDATE'],
        		"password" => $row['PASSWORD'],
        		"email" => $row['EMAIL'],
        		"avatar" => $row['AVATAR']
	);

}

/**
 * Verify the user authentification based on id
 * @param id the user's id
 * @param password the user's password (already hashed)
 * @return the user object or null if authentification failed
 */
function check_auth_id($id, $password) {

	$db = Db::dbc();
	$query = sprintf("SELECT * FROM UTILISATEUR WHERE IDUSER = '%d' AND PASSWORD = '%s'",$id, $password);
	$result = $db->query($query);
	$row= $result->fetch();
	if (!$row) { return NULL;}
	return (object) array(
			"id" => $row['IDUSER'],
       			"username" => $row['NOMUSER'],
       			"name" => $row['PRENOM'],
			"date" => $row['INSCRIDATE'],
        		"password" => $row['PASSWORD'],
        		"email" => $row['EMAIL'],
        		"avatar" => $row['AVATAR']
	);

}

/**
 * Follow another user
 * @param id the current user's id
 * @param id_to_follow the user's id to follow
 */
function follow($id, $id_to_follow) {
	$db = Db::dbc();
	$sql="INSERT INTO SUIVRE (IDUSER_SUIVRE,IDUSER_ABONNE,DATESUIVRE)VALUES(:suivre,:abonne,now())";
	$sth = $db->prepare($sql);
	try{
		$sth->execute(array(":suivre"=>$id,":abonne"=>$id_to_follow));
	}catch(PDOException $e){
        	echo 'failed：'.$e->getMessage();
        exit;
    	}
}

/**
 * Unfollow a user
 * @param id the current user's id
 * @param id_to_follow the user's id to unfollow
 */
function unfollow($id, $id_to_unfollow) {
	$db = Db::dbc();
	$sql="DELETE FROM SUIVRE WHERE IDUSER_SUIVRE = :suivre AND IDUSER_ABONNE =:abonne ";
	$sth = $db->prepare($sql);
	try{
		$sth->execute(array(":suivre"=>$id,":abonne"=>$id_to_unfollow));//:suivre
	}catch(PDOException $e){
        	echo 'failed：'.$e->getMessage();
        exit;
    	}
}


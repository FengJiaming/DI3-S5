<?php
namespace Model\Post;
use \Db;
use \PDOException;
/**
 * Post
 *
 * This file contains every db action regarding the posts
 */

/**
 * Get a post in db
 * @param id the id of the post in db
 * @return an object containing the attributes of the post or false if error
 * @warning the author attribute is a user object
 * @warning the date attribute is a DateTime object
 */
function get($id) {
	$db = \Db::dbc();
	$sth = $db->prepare("SELECT * FROM TWITTER WHERE IDTWEET = :id ");

	try{
		if($sth->execute(array(":id"=>$id))){
			foreach($sth->fetchALL() as $row) {
			return (object) array(
       			"id" => $row['IDTWEET'],
       			"user" => $row['IDUSER'],
       			"text" => $row['TEXT'],
			
        		"date" => new \DateTime($row['PUBLICDATE']),
        		"author" => \Model\User\get($row['IDUSER'])
			);
			}
		}else
			return NULL;
	}catch(PDOException $e){
        	echo 'failed：'.$e->getMessage();
        exit;
    	}
    /*return (object) array(
        "id" => 1337,
        "text" => "Text",
        "date" => new \DateTime('2011-01-01T15:03:01'),
        "author" => \Model\User\get(2)
    );*/
}

/**
 * Get a post with its likes, responses, the hashtags used and the post it was the response of
 * @param id the id of the post in db
 * @return an object containing the attributes of the post or false if error
 * @warning the author attribute is a user object
 * @warning the date attribute is a DateTime object
 * @warning the likes attribute is an array of users objects
 * @warning the hashtags attribute is an of hashtags objects
 * @warning the responds_to attribute is either null (if the post is not a response) or a post object
 */
function get_with_joins($id) {
  	$db = \Db::dbc();
	$sth = $db->prepare("SELECT * FROM TWITTER WHERE IDTWEET = :id ");

	try{
		if($sth->execute(array(":id"=>$id))){
			foreach($sth->fetchALL() as $row) {
			
				return (object) array(
       				"id" => $row['IDTWEET'],
       				"user" => $row['IDUSER'],
       				"text" => $row['TEXT'],
				"likes"=> get_likes($row['IDTWEET']),
				"responds_to"=>get($row['REPONDRE']),
        			"date" => new \DateTime($row['PUBLICDATE']),
        			"author" => \Model\User\get($row['IDUSER'])
				);
		
			}
		}else
			return NULL;
	}catch(PDOException $e){
        	echo 'failed：'.$e->getMessage();
        exit;
    	}


/*return (object) array(
        "id" => 1337,
        "text" => "Ima writing a post !",
        "date" => new \DateTime('2011-01-01T15:03:01'),
        "author" => \Model\User\get(2),
        "likes" => [],
        "hashtags" => [],
        "responds_to" => null
    );*/
}
 
/**
 * Create a post in db
 * @param author_id the author user's id
 * @param text the message
 * @param mentioned_authors the array of ids of users who are mentioned in the post
 * @param response_to the id of the post which the creating post responds to
 * @return the id which was assigned to the created post, null if anything got wrong
 * @warning this function computes the date
 * @warning this function adds the mentions (after checking the users' existence)
 * @warning this function adds the hashtags
 * @warning this function takes care to rollback if one of the queries comes to fail.
 */
function create($author_id, $text, $response_to=null) {
	$db = \Db::dbc();
	$sql="INSERT INTO TWITTER (IDUSER,TEXT, REPONDRE,PUBLICDATE)VALUES(:iduser,:text,:response,now())";
	$sth = $db->prepare($sql);
	try{
	
		if($sth->execute(array(":iduser"=>$author_id,":text"=>$text,":response"=>$response_to))){
				
			$query=$db->prepare('SELECT MAX(IDTWEET) AS id FROM TWITTER');
				$query->execute();
				$pid=$query->fetch();

			$users=extract_mentions($text);
			if($users!=NULL){
				foreach($users as $row){
					$user=\Model\User\get_by_username($row);
					if($user!=NULL){
						mention_user($pid[0],$user->id);
					}
					else{
						echo 'there is no user called'.$row;
					}
				}

			}
			$tags=extract_hashtags($text);
			if($tags!=NULL){

				foreach($tags as $row1){

					\Model\Hashtag\attach($pid[0], $row1);
				
				}
				
			}	
			
		return $pid[0];
			
		}
		else
			return NULL;
		
	}catch(PDOException $e){
        	echo 'failed：'.$e->getMessage();
        exit;
    	}



}

/**
 * Mention a user in a post
 * @param pid the post id
 * @param uid the user id to mention
 */
function mention_user($pid, $uid) {
	$db = \Db::dbc();
	$sql="INSERT INTO MENTION (IDUSER,IDTWEET,DATEMENTION)VALUES(:iduser,:idtweet,now())";
	$sth = $db->prepare($sql);
	try{
		$sth->execute(array(":iduser"=>$uid,":idtweet"=>$pid));

	}catch(PDOException $e){
        	echo 'failed：'.$e->getMessage();
        exit;
    	}
}

/**
 * Get mentioned user in post
 * @param pid the post id
 * @return the array of user objects mentioned
 */
function get_mentioned($pid) {
	$db = \Db::dbc();
	$sql="SELECT IDUSER FROM MENTION WHERE IDTWEET = :pid";
	$sth = $db->prepare($sql);
	try{
		$sth->execute(array(":pid"=>$pid));
		$i=0;
		$arr=array();
 		while($row=$sth->fetch()) {
    			$id[$i]=$row['IDUSER'];
			$arr[$i]=\Model\User\get($id[$i]);
			$i++;
 	 	}
  		return $arr;
	}catch(PDOException $e){
        	echo 'failed：'.$e->getMessage();
        exit;
    	}
	
    //return [];
}

/**
 * Delete a post in db
 * @param id the id of the post to delete
 */
function destroy($id) {
	$db = \Db::dbc();
	$sql="DELETE FROM TWITTER WHERE  IDTWEET = :TID";
	$sql1="DELETE FROM MENTION WHERE  IDTWEET = :TID";
	$sql2="DELETE FROM AIMER WHERE  IDTWEET = :TID";
	$sql3="DELETE FROM CONCERNER WHERE  IDTWEET = :TID";
	$sth=$db->prepare($sql);
	$sth1=$db->prepare($sql1);
	$sth2=$db->prepare($sql2);
	$sth3=$db->prepare($sql3);
	try{
		$sth1->execute(array(":TID"=>$id));
		$sth2->execute(array(":TID"=>$id));
		$sth3->execute(array(":TID"=>$id));
		if($sth->execute(array(":TID"=>$id))){
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
 * Search for posts
 * @param string the string to search in the text
 * @return an array of find objects
 */
function search($string) {
	$db = \Db::dbc();
	$sql="SELECT IDTWEET FROM TWITTER WHERE TEXT like :string ";
	$sth=$db->prepare($sql);
	try{
		$arr=array();
		$sth->execute(array(":string"=>"%$string%"));
			$i=0;
 			while($row=$sth->fetch()) {
    			$id[$i]=$row['IDTWEET'];
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
 * List posts
 * @param date_sorted the type of sorting on date (false if no sorting asked), "DESC" or "ASC" otherwise
 * @return an array of the objects of each post
 */
function list_all($date_sorted=false) {
	$db = \Db::dbc();
	$sql="SELECT IDTWEET FROM TWITTER";
	$sql1= "SELECT IDTWEET FROM TWITTER ORDER BY PUBLICDATE DESC ";
	$sql2= "SELECT IDTWEET FROM TWITTER ORDER BY PUBLICDATE ASC";
	try{
	
		if($date_sorted==false){
			$sth= $db->prepare($sql);
			$sth->execute();
			$k=0;
			$arr=array();
 			while($row=$sth->fetch()) {
    				$id[$k]=$row['IDTWEET'];
				$arr[$k]=get($id[$k]);
				$k++;
 	 		}
  			return $arr;
		}
		else if($date_sorted=="DESC"){
			
			$sth1= $db->prepare($sql1);
			$sth1->execute();
			$i=0;
			$arr1=array();
 			while($row1=$sth1->fetch()) {
    				$id1[$i]=$row1['IDTWEET'];
				$arr1[$i]=get($id1[$i]);
				$i++;
 	 		}
  			return $arr1;
		
		}
		else if($date_sorted=="ASC"){

			$sth2= $db->prepare($sql2);
			$sth2->execute();
			$j=0;
			$arr2=array();
 			while($row2=$sth2->fetch()) {
    				$id2[$j]=$row2['IDTWEET'];
				$arr2[$j]=get($id2[$j]);
				$j++;
 	 		}
  			return $arr2;

		}	
	
	}catch(PDOException $e){
        	echo 'failed：'.$e->getMessage();
       	exit;
    	}
    
}

/**
 * Get a user's posts
 * @param id the user's id
 * @param date_sorted the type of sorting on date (false if no sorting asked), "DESC" or "ASC" otherwise
 * @return the list of posts objects
 */
function list_user_posts($id, $date_sorted="DESC") {
	$db = \Db::dbc();
	$sql="SELECT IDTWEET FROM TWITTER WHERE IDUSER = :id";
	$sql1= "SELECT IDTWEET FROM TWITTER WHERE IDUSER = :id ORDER BY PUBLICDATE DESC ";
	$sql2= "SELECT IDTWEET FROM TWITTER WHERE IDUSER = :id ORDER BY PUBLICDATE ASC";
	try{
		
		if($date_sorted==false){
			$sth= $db->prepare($sql);
			$sth->execute(array(":id"=>$id));
			$k=0;
			$arr=array();
 			while($row=$sth->fetch()) {
    				$id[$k]=$row['IDTWEET'];
				$arr[$k]=get($id[$k]);
				$k++;
 	 		}
  			return $arr;
		}
		else if($date_sorted=="DESC"){
			
			$sth1= $db->prepare($sql1);
			$sth1->execute(array(":id"=>$id));
			$i=0;
			$arr1=array();
 			while($row1=$sth1->fetch()) {
    				$id1[$i]=$row1['IDTWEET'];
				$arr1[$i]=get($id1[$i]);
				$i++;
 	 		}
  			return $arr1;
		
		}
		else if($date_sorted=="ASC"){
			
			$sth2= $db->prepare($sql2);
			$sth2->execute(array(":id"=>$id));
			$j=0;
			$arr2=array();
 			while($row2=$sth2->fetch()) {
    				$id2[$j]=$row2['IDTWEET'];
				$arr2[$j]=get($id2[$j]);
				$j++;
 	 		}
  			return $arr2;

		}	
	
	}catch(PDOException $e){
        	echo 'failed：'.$e->getMessage();
       	exit;
    	}
}

/**
 * Get a post's likes
 * @param pid the post's id
 * @return the users objects who liked the post
 */
function get_likes($pid) {
	$db = \Db::dbc();
	$sql="SELECT IDUSER FROM AIMER WHERE IDTWEET = :pid";
	$sth = $db->prepare($sql);
	try{
		$sth->execute(array(":pid"=>$pid));
		$i=0;
		$arr=array();
 		while($row=$sth->fetch()) {
    			$id[$i]=$row['IDUSER'];
			$arr[$i]=\Model\User\get($id[$i]);
			$i++;
 	 	}
  		return $arr;
	}catch(PDOException $e){
        	echo 'failed：'.$e->getMessage();
        exit;
    	}
 
}

/**
 * Get a post's responses
 * @param pid the post's id
 * @return the posts objects which are a response to the actual post
 */
function get_responses($pid) {
	$db = \Db::dbc();
	$sth = $db->prepare("SELECT IDTWEET FROM TWITTER WHERE REPONDRE = :id ");

	try{
		$sth->execute(array(":id"=>$pid));
			$i=0;
			$arr=array();
			while($row=$sth->fetch()){
			
    			$id[$i]=$row['IDTWEET'];
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
 * Get stats from a post (number of responses and number of likes
 */
function get_stats($pid) {
	$db = \Db::dbc();
	$sql="SELECT count(*) as total FROM AIMER WHERE IDTWEET = :pid ";
	$sql1="SELECT count(*) as total1 FROM TWITTER WHERE REPONDRE = :pid ";
	$sth = $db->prepare($sql);
	$sth1 = $db->prepare($sql1);	
	try{
		$sth->execute(array(":pid"=>$pid));
    		foreach($sth->fetchALL() as $row){
			$like=$row["total"];
		}
		$sth1->execute(array(":pid"=>$pid));
		foreach($sth1->fetchALL() as $row1){
			$repondre=$row1["total1"];
		}
		return (object) array(
        		"nb_likes" => $like,
        		"nb_responses" => $repondre
    		);
	}catch(PDOException $e){
        	echo 'failed：'.$e->getMessage();
        exit;
    	}
	/*return (object) array(
        "nb_likes" => 10,
        "nb_responses" => 40
    );*/
}

/**
 * Like a post
 * @param uid the user's id to like the post
 * @param pid the post's id to be liked
 */
function like($uid, $pid) {
	$db = \Db::dbc();
	$sql="INSERT INTO  AIMER (IDUSER,IDTWEET,DATELIKE)VALUES(:iduser,:idtweet,now()) ";

	$sth = $db->prepare($sql);
	try{
		$sth->execute(array(":iduser"=>$uid,":idtweet"=>$pid));
	}catch(PDOException $e){
        	echo 'failed：'.$e->getMessage();
        exit;
    	}
}

/**
 * Unlike a post
 * @param uid the user's id to unlike the post
 * @param pid the post's id to be unliked
 */
function unlike($uid, $pid) {
	$db = \Db::dbc();
	$sql="DELETE FROM AIMER WHERE IDUSER = :iduser AND IDTWEET = :idtweet";

	$sth = $db->prepare($sql);
	try{
		$sth->execute(array(":iduser"=>$uid,":idtweet"=>$pid));
	}catch(PDOException $e){
        	echo 'failed：'.$e->getMessage();
        exit;
    	}
}


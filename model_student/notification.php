<?php
namespace Model\Notification;
use \Db;
use \PDOException;
/**
 * Notification model
 *
 * This file contains every db action regarding the notifications
 */

/**
 * Get a liked notification in db
 * @param uid the id of the user in db
 * @return a list of objects for each like notification
 * @warning the post attribute is a post object
 * @warning the liked_by attribute is a user object
 * @warning the date attribute is a DateTime object
 * @warning the reading_date attribute is either a DateTime object or null (if it hasn't been read)
 */
function get_liked_notifications($uid) {
	$db = \Db::dbc();
	$sql="SELECT * FROM AIMER WHERE IDTWEET IN (SELECT IDTWEET FROM TWITTER WHERE IDUSER = :uid)";
	$sth= $db->prepare($sql);
	try{
		$sth->execute(array(":uid"=>$uid));
		$arr=array();
		$i=0;
		while($row=$sth->fetch()){
		if($row["DATESEEN"]!=NULL){
		   $arr[$i]=(object) array(
       		   "type" => "liked",
        	   "post" => \Model\Post\get($row["IDTWEET"]),
        	   "liked_by" => \Model\User\get($row["IDUSER"]),
       		   "date" => new \DateTime($row["DATELIKE"]),
        	   "reading_date" =>new \DateTime($row["DATESEEN"])
    		);
		}else{
		$arr[$i]=(object) array(
       		   "type" => "liked",
        	   "post" => \Model\Post\get($row["IDTWEET"]),
        	   "liked_by" => \Model\User\get($row["IDUSER"]),
       		   "date" => new \DateTime($row["DATELIKE"]),
        	   "reading_date" =>NULL
		);
		}
		$i++;
		}
		return $arr;
	}catch(PDOException $e){
        	echo 'failed：'.$e->getMessage();
        exit;
    	}

/*
    return [(object) array(
        "type" => "liked",
        "post" => \Model\Post\get(1),
        "liked_by" => \Model\User\get(3),
        "date" => new \DateTime("NOW"),
        "reading_date" => new \DateTime("NOW")
    )];*/
}

/**
 * Mark a like notification as read (with date of reading)
 * @param pid the post id that has been liked
 * @param uid the user id that has liked the post
 */
function liked_notification_seen($pid, $uid) {
	$db = \Db::dbc();
	$sql="UPDATE AIMER SET DATESEEN = now()  WHERE IDTWEET = :pid AND IDUSER = :uid ";
	$sth= $db->prepare($sql);
	try{
		if($sth->execute(array(":uid"=>$uid,":pid"=>$pid))){
			return true;
		}
		else
			return false;	
		
	}catch(PDOException $e){
        	echo 'failed：'.$e->getMessage();
        exit;
	}
}

/**
 * Get a mentioned notification in db
 * @param uid the id of the user in db
 * @return a list of objects for each like notification
 * @warning the post attribute is a post object
 * @warning the mentioned_by attribute is a user object
 * @warning the reading_date object is either a DateTime object or null (if it hasn't been read)
 */
function get_mentioned_notifications($uid) {
	$db = \Db::dbc();
	$sql="SELECT * FROM MENTION INNER JOIN TWITTER WHERE MENTION.IDUSER = :uid AND MENTION.IDTWEET=TWITTER.IDTWEET";
	$sth= $db->prepare($sql);
	try{
		$sth->execute(array(":uid"=>$uid));
		$arr=array();
		$i=0;
		while($row=$sth->fetch()) {
		if($row["DATESEEN"]!=NULL){
		   $arr[$i]=(object) array(
       		   "type" => "mentioned",
        	   "post" => \Model\Post\get($row["IDTWEET"]),
        	   "mentioned_by" => \Model\User\get($row["IDUSER"]),
       		   "date" => new \DateTime($row["DATEMENTION"]),
        	   "reading_date" =>new \DateTime($row["DATESEEN"])
    		);
		}else{
		
		$arr[$i]=(object) array(
       		   "type" => "mentioned",
        	   "post" => \Model\Post\get($row["IDTWEET"]),
        	   "mentioned_by" => \Model\User\get($row["IDUSER"]),
       		   "date" => new \DateTime($row["DATEMENTION"]),
        	   "reading_date" =>NULL
		);
		}
		$i++;
		}
		return $arr;
	}catch(PDOException $e){
        	echo 'failed：'.$e->getMessage();
        exit;
    	}
   /* return [(object) array(
        "type" => "mentioned",
        "post" => \Model\Post\get(1),
        "mentioned_by" => \Model\User\get(3),
        "date" => new \DateTime("NOW"),
        "reading_date" => null
    )];*/
}

/**
 * Mark a mentioned notification as read (with date of reading)
 * @param uid the user that has been mentioned
 * @param pid the post where the user was mentioned
 */
function mentioned_notification_seen($uid, $pid) {
	$db = \Db::dbc();
	$sql="UPDATE MENTION SET DATESEEN = now()  WHERE IDTWEET = :pid AND IDUSER = :uid ";
	$sth= $db->prepare($sql);
	try{
		if($sth->execute(array(":uid"=>$uid,":pid"=>$pid))){
			return true;
		}
		else
			return false;	
		
	}catch(PDOException $e){
        	echo 'failed：'.$e->getMessage();
        exit;
	}
}

/**
 * Get a followed notification in db
 * @param uid the id of the user in db
 * @return a list of objects for each like notification
 * @warning the user attribute is a user object which corresponds to the user following.
 * @warning the reading_date object is either a DateTime object or null (if it hasn't been read)
 */
function get_followed_notifications($uid) {
	$db = \Db::dbc();
	$sql="SELECT * FROM SUIVRE WHERE IDUSER_ABONNE = :uid";
	$sth= $db->prepare($sql);
	try{
		$sth->execute(array(":uid"=>$uid));
		
		$arr=array();
		$i=0;
		while($row=$sth->fetch()) {
		
		if($row["DATESEEN"]!=NULL){
		   $arr[$i]=(object) array(
       		   "type" => "followed",
        	   "user" => \Model\User\get($row["IDUSER_SUIVRE"]),
       		   "date" => new \DateTime($row["DATESUIVRE"]),
        	   "reading_date" =>new \DateTime($row["DATESEEN"])
    		);
		}else{
		
		$arr[$i]=(object) array(
       		   "type" => "followed",
        	   "user" => \Model\User\get($row["IDUSER_SUIVRE"]),
       		   "date" => new \DateTime($row["DATESUIVRE"]),
        	   "reading_date" =>NULL
		);
		}
		$i++;
		}
		return $arr;
	}catch(PDOException $e){
        	echo 'failed：'.$e->getMessage();
        exit;
    	}
   /* return [(object) array(
        "type" => "followed",
        "user" => \Model\User\get(1),
        "date" => new \DateTime("NOW"),
        "reading_date" => new \DateTime("NOW")
    )];*/
}

/**
 * Mark a followed notification as read (with date of reading)
 * @param followed_id the user id which has been followed
 * @param follower_id the user id that is following  //ID SUIVRE
 */
function followed_notification_seen($followed_id, $follower_id) {
	$db = \Db::dbc();
	$sql="UPDATE SUIVRE SET DATESEEN = now()  WHERE IDUSER_ABONNE = :fid AND IDUSER_SUIVRE = :uid ";
	$sth= $db->prepare($sql);
	try{
		if($sth->execute(array(":fid"=>$followed_id,":uid"=>$follower_id))){
			return true;
		}
		else
			return false;	
	}catch(PDOException $e){
        	echo 'failed：'.$e->getMessage();
        exit;
	}
}

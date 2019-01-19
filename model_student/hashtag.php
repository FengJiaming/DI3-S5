<?php
namespace Model\Hashtag;
use \Db;
use \PDOException;
/**
 * Hashtag model
 *
 * This file contains every db action regarding the hashtags
 */

/**
 * Attach a hashtag to a post
 * @param pid the post id to which attach the hashtag
 * @param hashtag_name the name of the hashtag to attach
 */
function attach($pid, $hashtag_name) {
	$db = \Db::dbc();
	$sql="INSERT INTO CONCERNER (IDTWEET,IDHASHTAG)VALUES(:idtweet,:idhashtag)";
	$sqltag="INSERT INTO HASHTAG (NOMTAG, DATETAG)VALUES(:nametag,now())";
	$sqlid="SELECT IDHASHTAG FROM HASHTAG WHERE NOMTAG = :nametag";
	$sth = $db->prepare($sql);
	$query = $db->prepare($sqltag);
	$same = $db->prepare($sqlid);
	try{
		$same->execute(array(":nametag"=>$hashtag_name));
		$donnees = $same->fetch();
		if($donnees==NULL){
			$query->execute(array(":nametag"=>$hashtag_name));
	
			$sth->execute(array(":idtweet"=>$pid,":idhashtag"=>$db->lastInsertId()/*$donnees[0]*/));       

		}
		else
			$sth->execute(array(":idtweet"=>$pid,":idhashtag"=>$donnees[0]));
	}catch(PDOException $e){
        	echo 'failed：'.$e->getMessage();
        exit;
    	}
}

/**
 * List hashtags
 * @return a list of hashtags names
 */
function list_hashtags() {
	$db = \Db::dbc();
	$sql="SELECT NOMTAG FROM HASHTAG";
	$sth= $db->prepare($sql);
	try{
		$sth->execute();
		$i=0;
		$arr=array();
 		while($row=$sth->fetch()) {
    			$arr[$i]=$row['NOMTAG'];
			$i++;
 	 	}
  		return $arr;

	}catch(PDOException $e){
        	echo 'failed：'.$e->getMessage();
        exit;
    	}
}

/**
 * List hashtags sorted per popularity (number of posts using each)
 * @param length number of hashtags to get at most
 * @return a list of hashtags
 */
function list_popular_hashtags($length) {
	$db = \Db::dbc();
	$sql="SELECT NOMTAG FROM (SELECT NOMTAG,COUNT(*) AS CNT FROM CONCERNER NATURAL JOIN HASHTAG GROUP BY NOMTAG) x ORDER BY  x.CNT DESC";
	$sth= $db->prepare($sql);
	try{
		$sth->execute();
		$i=0;
		$arr=array();
 		while(($row=$sth->fetch())&&($i<$length)) {
    			$arr[$i]=$row['NOMTAG'];
			$i++;
 	 	}
  		return $arr;

	}catch(PDOException $e){
        	echo 'failed：'.$e->getMessage();
        exit;
    	}
}

/**
 * Get posts for a hashtag
 * @param hashtag the hashtag name
 * @return a list of posts objects or null if the hashtag doesn't exist
 */
function get_posts($hashtag_name) {
	$db = \Db::dbc();
	$sql="SELECT IDTWEET FROM CONCERNER NATURAL JOIN HASHTAG WHERE NOMTAG= :nametag";
	$sth= $db->prepare($sql);
	try{
		$sth->execute(array(":nametag"=>$hashtag_name));
		$i=0;
		$arr=array();
 		while($row=$sth->fetch()) {
    			$id[$i]=$row['IDTWEET'];
			$arr[$i]=\Model\Post\get($id[$i]);
			$i++;
 	 	}
  		return $arr;

	}catch(PDOException $e){
        	echo 'failed：'.$e->getMessage();
        exit;
    	}

}

/** Get related hashtags
 * @param hashtag_name the hashtag name
 * @param length the size of the returned list at most
 * @return an array of hashtags names
 */
function get_related_hashtags($hashtag_name, $length) {
	$db = \Db::dbc();
	$sql="SELECT NOMTAG FROM HASHTAG NATURAL JOIN CONCERNER WHERE idtweet IN(SELECT IDTWEET FROM HASHTAG NATURAL JOIN CONCERNER WHERE NOMTAG = :nametag)";
	$sth= $db->prepare($sql);
	try{
		$sth->execute(array(":nametag"=>$hashtag_name));
		$i=0;
		$arr=array();
 		while(($row=$sth->fetch())&&($i<$length)) {
			if($row['NOMTAG']!=$hashtag_name){
    				$arr[$i]=$row['NOMTAG'];
				$i++;
			}
 	 	}
  		return $arr;

	}catch(PDOException $e){
        	echo 'failed：'.$e->getMessage();
        exit;
    	}
    
}

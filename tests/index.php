<?php
	/**
	 *  PHP_MYSQL_CLASS - tests by Mutinda Boniface
	 *   
	 */
	
	// --------------------------------------------------------------------------------------------------------------------------------------
	// lets include our class 
	require "../src/mysql.class.php";
	
	// --------------------------------------------------------------------------------------------------------------------------------------
	// create our object 
	// we use singleton to make sure that we only have one instance of the class in our project 
	$sql = SQLManagerFactory::create();
	
	// --------------------------------------------------------------------------------------------------------------------------------------
	// lets create a connection to the database
	define("HOST", "localhost");
	define("USER", "root");
	define("PASSWORD", "");
	define("DATABASE", "php_mysql_class_db");
	
	define("USERS_TABLE", "`users`");
	
	// since this method throws an exception on failure, we need to catch them 
	try{
		$sql->connect(HOST,USER,PASSWORD,DATABASE);
	}catch(Exception $e){
		die($e->getMessage());
	}
	
	// --------------------------------------------------------------------------------------------------------------------------------------
	// DATA ESCAPE 
	$name = "Mutinda Boniface";
	$escaped_name = $sql->escape($name);
	
	// --------------------------------------------------------------------------------------------------------------------------------------
	// SELECT QUERY
	// 
	// @method -> querySelect($table_name, array("columns") , array("key"=>"value"));
	// @param -> table_name[string] - the table name to run the select query 
	// @param -> columns[array] - the columns to fetch from the table 
	// @param -> where_clause [array] - an associative array(key->value) of where clause of the query. we use = for these parameters
	// @return ->resource - query resource object 
	
	// without where clause 
	$resource = $sql->querySelect( USERS_TABLE, array("*"));
	// with where clause 
	$resource = $sql->querySelect( USERS_TABLE, array("*"), array("first_name"=>"Mutinda"));
	// with select column names 
	$resource = $sql->querySelect( USERS_TABLE, array("date_of_birth","gender"), array("first_name"=>"Mutinda"));
	// with count as a select column e.g. count all users 
	$resource = $sql->querySelect( USERS_TABLE, array("count(`id`) as total_users"));
	// with count and where clause 
	$resource = $sql->querySelect( USERS_TABLE, array("count(`id`) as total_users"), array("status"=>1,"gender"=>"male"));
	
	// to check whether this query worked fine 
	if( $resource ){
		// get the number of returned rows 
		$no_of_rows = $sql->queryNumRows($resource);
		echo "<br/>".$no_of_rows;
		// to loop through the data selected 
		while( $data = $sql->queryArray( $resource )){
			echo $data['total_users'];
		}
	}else{
		echo mysql_error();
	}
	
	// --------------------------------------------------------------------------------------------------------------------------------------
	// UPDATE QUERY
	// 
	// @method -> queryUpdate($table_name, array("key"=>"value","key"=>"value") , array("key"=>"value","key"=>"value"));
	// @param -> table_name[string] - the table name to run the update query 
	// @param -> columns[array] - the columns to update in the table 
	// @param -> where_clause [array] - an associative array(key->value) of where clause of the query. we use = for these parameters
	// @return ->resource - query resource object 
	
	// without where clause 
	$resource = $sql->queryUpdate( USERS_TABLE, array("status"=>0));
	// with where clause 
	$resource = $sql->queryUpdate( USERS_TABLE, array("date_of_birth"=>"1989-09-22","status"=>1), array("first_name"=>"Mutinda"));
	
	// --------------------------------------------------------------------------------------------------------------------------------------
	// DELETE QUERY
	// 
	// @method -> queryDelete($table_name, array("key"=>"value","key"=>"value") , array("key"=>"value","key"=>"value"));
	// @param -> table_name[string] - the table name to run the delete query 
	// @param -> where_clause [array] - an associative array(key->value) of where clause of the query. we use = for these parameters
	// @return ->resource - query resource object 
	
	// without where clause 
	$resource = $sql->queryDelete( USERS_TABLE);
	// with where clause 
	$resource = $sql->queryDelete( USERS_TABLE, array("first_name"=>"Mutinda"));
	
	// --------------------------------------------------------------------------------------------------------------------------------------
	// INSERT QUERY
	// 
	// @method -> queryInsert($table_name, array("key"=>"value","key"=>"value"));
	// @param -> table_name[string] - the table name to run the insert query 
	// @param -> columns [array] - an associative array(key->value) of what to insert and to which column 
	// @context -> the context in which to  run the query - insert or replace - by default its insert but you can pass "replace" if you want unique values insert only
	//		NB: replace query doesn't guarantee any column uniqueness especially when you have a field with current_timestamp which will be returning different value
	//			to enforce this you need to specify which columns should be unique columns in your table  
	// @return ->resource - query resource object 
	
	// without context 
	$resource = $sql->queryInsert( USERS_TABLE, array("first_name"=>"Mutinda", "middle_name"=>'Daudi', "last_name"=>'Boniface', "gender"=>'male'));
	$resource = $sql->queryInsert( USERS_TABLE, array("first_name"=>"Stella", "middle_name"=>'Boniface', "last_name"=>'Kimanzi', "gender"=>'female'));
	
	// with context 
	$resource = $sql->queryInsert( USERS_TABLE, array("first_name"=>"Mutinda", "middle_name"=>'Daudi', "last_name"=>'Boniface', "gender"=>'male'), "REPLACE");
	
	
	// --------------------------------------------------------------------------------------------------------------------------------------
	// CUSTOM CREATED QUERY
	// 
	// @method -> execute($query));
	// @param -> query[string] - the sql query to execute  
	// @return ->resource - query resource object 
	
	$query = "SELECT * FROM ".USERS_TABLE." WHERE status = 1 ";
	$resource = $sql->execute( $query );	
	echo $sql->queryNumRows($resource);
	
	
	/**
	 *  Happy coding... report any bug and feel free to contribute to the project 
	 *  
	 */
	
?>
<?php
	/**
	 *  PHP_MYSQL_CLASS
	 *  
	 *  @Author: 	Mutinda Boniface
	 *  @Email: 	boniface.info@gmail.com
	 *  @Website: 	http://mutinda.rebmos.net
	 *  
	 *  @Facebook:  https://www.facebook.com/mutinda.boniface
	 *  @Twitter:   https://twitter.com/webgeeker
	 *  @Google+:   https://plus.google.com/+MutindaBoniface/posts
	 *  
	 *  A php class to allow you to create a connection to mysql database and run queries with ease 
	 *  
	 *  At a glance - The core methods :
	 *  	1. Create a connection using ->connect method 
	 *  	2. Run select queries using ->querySelect method
	 *  	3. Run delete queries using ->queryDelete method
	 *  	4. Run update queries using ->queryUpdate method
	 *  
	 *  --- check the documentation and tests files for more methods usage -----------------
	 *  
	 *  DISCLAIMER: This class is free to use in any sort of project from open source to commercial ones 
	 *  LICENCE: Its free to use and modify so long as the accreditation part provided at the top is left intact. 
	 */

	interface ArrayHelper{
		public static function  isArray( $value );
		public static function arrayToString( $array = null, $inner_glue = '=', $outer_glue = ' AND ',$recurse=true );		
	}
	interface StringHelper{
		public static function isString( $value );		
	}
	
	/*---------------------------------------------------------------
	 * Helper class definition and Interface implementation...
	 *--------------------------------------------------------------- */
	 
	class Helper implements ArrayHelper, StringHelper{
		
		function __construct( $options = null ){
			// do nothing				
		}
		
		/**
		 * array interface implementations
		 */ 
		// determines whether a passed value is an array
		public static function isArray( $value ){
			return (!empty($value) and $value!="") ? is_array( $value ) : false;
		}
		// covert an array to string...
		public static function arrayToString( $array = null, $inner_glue = '=', $outer_glue = ' AND ',$recurse=true ){
			$output = array();
			if( Helper::isArray( $array ) ){
				foreach ($array as $key => $item){
					if (is_array ($item) && $recurse){
					$output[] = "{$key} IN (".$this->implode(",",$item).") ";
					}
					else{
						$output[] = " {$key} {$inner_glue} ".Helper::checkQuotes($item)." ";
					}
				}
			}
			return implode( $outer_glue, $output);
		}
		// determines whether a passed value is a string
		public static function isString( $value ){
			return (!empty($value) and $value!="") ? is_string( $value ) : false;
		}
		
		public static function checkQuotes( $value ){
			if ( Helper::isString($value)) {
				$value = mysql_real_escape_string($value);
				$value = "'".$value."'";
				
			}
			return $value;
		}
	}
	
	/*---------------------------------------------------------------
	 * Helper class factory
	 *---------------------------------------------------------------
	 */
	 
	class HelperFactory{

		public static function create( $options = null ){
			return new Helper( $options );
		}
	}
	
	// this class contains SQL QueryManager helper methods and properties
	class SQLManager{
	
		public  $query = null;
		private $helper = null;
	
		public function __construct( $options = null ){
			$this->helper = HelperFactory::create();
		}
		
		public function connect( $host, $user, $pass, $db  ){
			$con = mysql_connect( $host, $user, $pass );
			if( $con ){
				if ( ! mysql_select_db(  $db  )){
					throw new Exception( "Database Connection failed->Database not selected " );
				}
			}else{
				throw new Exception( "Database Connection failed ->Server refused connection" );
			}
		}

		/*
		 * check if not empty array and check quotes 
		 */
		  
		public function implode($glue,$array) {
			if ( Helper::isArray($array) && !empty($glue)) {
				$quoted_array = array_map(array($this,'check_quotes'),$array);
				return implode($glue,$quoted_array);
			}else{
				return $array;
			}
		}
		
		/**
		 * Method to escape some chars on a string...sql secure
		 * @param <string> $value to apply quotes
		 * return quoted string
		 */
		public function checkQuotes( $value ){
			if ( Helper::isString($value)) {
				$value = mysql_real_escape_string($value);
				$value = "'".$value."'";
				
			}
			return $value;
		}
		
		/**
		 * Method to run an insert sql query
		 * @param <string> $table	: db table to update records
		 * @param <array> $data		: array of data to be loaded
		 */
		public function queryInsert( $table, $data = array(), $context = "INSERT "){
			$keys = "";
			$values = null;
			$continue = false;
			
			if( $table and $table!=""){
				$continue = true;				
			}
			
			if( $continue ){
				
				foreach( $data as $data_key=>$data_value ){
					$keys.=$data_key.",";
						
					if( helper::isString( $data_value )){
						$data_value = trim( $data_value );
					}
					if( $data_value=="now()"){
						$values.= $data_value.",";
					}else{
						$values.= $this->checkQuotes($data_value).",";
					}
				}
				
				$keys = substr($keys, 0, -1);
				$values = substr($values, 0, -1);
				
				$this->query = sprintf("%s INTO %s (%s) VALUES (%s)",$context, $table, $keys, $values );
				
				return $this->execute( $this->query );
			}else{
				return false;
			}
		}
		
		/**
		 * Method to run a delete sql query
		 * @param <string> $table	: db table to delete records
		 * @param <array> $what		: array of data to be updated
		 * @param <array> $condition: an array representing the where clause data
		 * Return
		 */
		public function queryDelete( $table, $condition = null ){
			$continue = false;
			$where = "";
			if( !empty($table) and helper::isString( $table ) ){
				$continue  = true;
			}
			if( $continue){
				if(!empty( $condition )){
					$where = "WHERE ".(is_array($condition) ? helper::arrayToString($condition) : strval(mysql_real_escape_string( $condition) ));
				}
			}
			$this->query = sprintf("DELETE FROM %s %s", $table, $where);
			return $this->execute( $this->query);
		}
		
		/**
		 * Method to run a select sql query
		 * @param <string> $table database table to execute the query on
		 * @param <array> $what the attributes to extract from the table
		 * @param <array> $condition array of query filter conditions..
		 */
		public function querySelect( $table, $what = array(), $condition = array(), $orderByClause = null ){
			$continue = false;
			
			$continue = isset($table) and $table!="" ?  true: false;
			
			if( $continue ){
				$what_data_count = count( $what );
				
				$sql_query = " SELECT ";
				
				for( $i=0; $i< $what_data_count; $i++){
					if(trim($what[$i]) !="*"){
						$sql_query.="{$what[$i]}".",";	
					}else{
						$sql_query.=" * ";
					}					
				}
				
				$sql_query = substr( $sql_query, 0, -1);
				$sql_query = $sql_query." FROM {$table} ";
				
				if( isset( $condition ) and count($condition)!=0 ){
					
					$sql_query.=" WHERE ";
					$condition_data_count = count( $condition );
					
					foreach( $condition as $key=>$value ){
						$secure_key = mysql_real_escape_string( $key );
						$secure_value = mysql_real_escape_string( $value );
						$sql_query.=" {$secure_key} = '{$secure_value}'"." AND ";
					}
					
					$sql_query = substr( $sql_query, 0, -5);				
				}
				
				if( ! empty( $orderByClause )){
					$sql_query.=" ".$orderByClause;
				}
				
				return $this->execute( $sql_query );
			}
		}
		
		/**
		 * QueryManager::queryUpdate()
		 *
		 * @param <string> $table	: db table to update records
		 * @param <array> $what		 : array of what to be updated
		 * @param <array> $condition : sql query where clauses
		 * Return
		 */
		public function queryUpdate( $table, $what = array(), $condition = array()){
			$continue = false;
			
			$continue = isset($table) and $table!="" ?  true: false;
			
			if( $continue ){
				$what_keys = array_keys( $what ); $what_key_count = count( $what_keys );
				$what_values = array_values( $what ); 
				
				$sql_query = " UPDATE {$table} SET ";
				
				for( $i=0; $i< $what_key_count; $i++){
					$_value = mysql_real_escape_string( $what_values[$i] );
					$sql_query.="`{$what_keys[$i]}` = '{$_value}'".",";				
				}
				$sql_query = substr( $sql_query, 0, -1);
				
				if( isset( $condition ) and count( $condition ) >0 ){
					//
					
					$sql_query.=" WHERE ";
					$condition_data_count = count( $condition );
					
					foreach( $condition as $key=>$value ){
						$secure_key = mysql_real_escape_string( $key );
						$secure_value = mysql_real_escape_string( $value );
						$sql_query.=" {$secure_key} = '{$secure_value}'"." AND ";
					}
					
					$sql_query = substr( $sql_query, 0, -5);	
					
				}
				return $this->execute( $sql_query );
			}			
		}
		
		/**
		 *  @param <resource> $query_resource
		 */
		public function queryArray( $query_resource ){
			$row = mysql_fetch_array($query_resource, MYSQL_ASSOC);
			return $row;
		}
		
		/**
		 * Method to run a sql query with like params
		 */
		public function queryLike( $table, $what=array(), $condition = array() ){
			$continue = false;
			
			$continue = isset($table) and $table!="" ?  true: false;
			
			if( $continue ){
				$what_data_count = count( $what );
				
				$sql_query = " SELECT ";
				
				for( $i=0; $i< $what_data_count; $i++){
					if(trim($what[$i]) !="*"){
						$sql_query.="`{$what[$i]}`".",";	
					}else{
						$sql_query.=" * ";
					}					
				}
				
				$sql_query = substr( $sql_query, 0, -1);
				$sql_query = $sql_query." FROM {$table} ";
				
				if( isset( $condition ) and count($condition)!=0 ){
					$condition_keys = array_keys( $condition);
					$condition_values = array_values( $condition );
					$condition_key_count = count( $condition_keys );
					
					$sql_query.=" WHERE ";
					
					for( $i=0; $i< $condition_key_count; $i++){
						$sql_query.=" {$condition_keys[$i]} LIKE '%{$condition_values[$i]}%'".",";				
					}
				}
				$sql_query = substr( $sql_query, 0, -1);
				
				return $this->execute( $sql_query."limit 5 " );
			}		
		}
		
		/**
		 * QueryManager::queryAssoc()
		 *
		 * @param $query_resource
		 */
		public function queryAssoc( $query_resource ){
			return ( $query_resource and $query_resource!="")? mysql_fetch_assoc($query_resource, MYSQL_ASSOC): false;
		}
		/**
		 * QueryManager::queryObject()
		 *
		 * @param $query_resource
		 */
		public function queryObject( $query_resource ){
			return ( $query_resource and $query_resource!="")? mysql_fetch_object($query_resource ): false;
		}
		
		/**
		 * QueryManager::queryFreeData()
		 *
		 * @param $query_resource
		 * Return bool
		 */
		public function queryFreeData( $query_resource ){
			$status = false;
			if( $query_resource  and $query_resource!="" ){
				$status =  mysql_free_result( $query_resource );
			}
			return $status;
		}
		
		/**
		 * QueryManager::queryArrayImplode()
		 *
		 * @param <array> $data : array of data to implode
		 * @param <array> $separator: what do i want to use as values separator..
		 *
		 */
		public function queryArrayImplode( $data = array(), $separator ){
			$fields = Helper::arrayKeys( $data );
			$values = array_values( array_map('mysql_real_escape_string', $data));
			$i = 0;
			$string = "";
			
			while( @$fields[$i] ){
				if ($i > 0) $string .= $separator;
				$string .= sprintf("%s = '%s'", $fields[$i], $values[$i]);
				$i++;			
			}
			return $string;
		}
		
		public function fetchColumnData( $table, $columns = array(), $condition = array() ){
			$final_query = null;
			$query_columns = null;
			$query_where   = null;
			
			if( $table!="" ){
				if( count($columns)!=0 ){
					$columns_length = count( $columns );
					for( $i=0 ; $i<$columns_length; $i++ ){
						$query_columns.=$columns[$i].",";
					}
				}
				// get rid of the last comma
				$query_columns = substr($query_columns, 0, -1);
				
				if( count($condition)!=0 ){
					$query_where = " WHERE ";
					$condition_count = count( $condition );
					$counter = 0;
					foreach( $condition as $key=>$value ){
						$counter++;
						$query_where.=$key."=".$value;
						if( $counter != $condition_count ){
							$query_where.=" AND ";
						}
					}
				}
				
				$final_query = "SELECT ".$query_columns." FROM {$table}";
				if(!empty( $query_where )){
					$final_query.=$query_where;
				}	
				
				return $this->execute( $final_query );
				
			}else{ return false;}
		}
		
		public function execute( $query ){
			// query must be a string
			if( helper::isString( $query )){
				if( $query and strlen( trim( $query ))>10){
					return mysql_query( $query );
				}
			}else {
				return null;
			}
		}
		
		/* 
		 * return num of rows of the specified query resource
		 */
		public function queryNumRows( $query ){
			return ($query and $query!=null) ? mysql_num_rows( $query ): false;
		}
		
		/**
		 * QueryManager::getQueryAssociated()
		 *
		 * Depreciated method - new method QueryManager::queryAssoc()
		 */
		public function getQueryAssociated( $query_resource ){
			return $this->queryAssoc( $query_resource);
		}
		
		public function escape( $value, $params = null ){
			return mysql_real_escape_string( $value );
		}

		public function __toString(){
			return "sql";
		}
	}
	
	/**
	 * Factory class for QueryManager
	 */ 
	class SQLManagerFactory{
		public static function create( $options=null ){
			return new SQLManager( $options = null);
		}
	
	}	
?>
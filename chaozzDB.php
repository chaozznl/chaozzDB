<?php
	// flat file database engine
	// by E. Wenners - www.chaozz.nl - 2008-2019
	// version 2.4
	
	// chaozzDB settings
	$chaozzdb_delimiter 	= "\t"; // tab
	$chaozzdb_location 		= "./db/"; // location of tsv files (tables)
	$chaozzdb_extension 	= ".tsv"; // table file extension
	$chaozzdb_salt			= "3UHCws4dCN7qk9gX"; // you should probably change this
	$chaozzdb_max_records 	= 999; // max number of records returned by a SELECT
	$chaozzdb_last_error 	= ""; // holds the value of the last occured error
	
	Function chaozzdb_password ($unhashed_password)
	{
		global $chaozzdb_salt;
		return sha1($unhashed_password.$chaozzdb_salt);
	}
	
	Function chaozzdb_error ($query_action, $error)
	{
		global $chaozzdb_last_error;
		$chaozzdb_last_error = $error;
		
		switch ($query_action) 
		{
			case "SELECT":
				return Array(); // return an empty array
				break;
			case "INSERT":
				return 0; // return 0 as the new id
				break;
			case "DELETE":
				return false;
				break;
			case "UPDATE":
				return false;
				break;
		}
		return false; // still here? lets return false
	}
	
	Function chaozzdb_query ($query)
	{
		global $chaozzdb_delimiter;
		global $chaozzdb_location;
		global $chaozzdb_extension;
		global $chaozzdb_max_records;
		global $chaozzdb_last_error;
		
		$chaozzdb_last_error = "";
		
		// preset some things
		if ($query == "") return chaozzdb_error("", "Invalid or empty query: $query"); // no query? exit
		
		$query_limit = -1; // no limit for all queries. in SELECT this can be changed in the query by LIMIT command
		$query_where = null;
		
		$query_action = substr ($query, 0, 6); // filter the action out of the query
		$called_from_index_actions = Array ("SELECT", "DELETE", "INSERT", "UPDATE");
		if (!in_array($query_action, $called_from_index_actions)) return chaozzdb_error ($query_action, "Illegal action $query_action");
		
		// ------------------------
		// DELETE FROM user
		// DELETE FROM user WHERE name ~= Pe
		// ------------------------
		if ($query_action == "DELETE")
		{
			// split the query
			$query_part = preg_split ('/DELETE FROM|WHERE/', $query, -1, PREG_SPLIT_NO_EMPTY); // split query on the commands
			$query_part = array_map ('trim', $query_part); // trim the array values

			// extract the info we need
			$query_table 	= $query_part[0];
			$query_select 	= "*"; // all fields (you don't specify fields in this statement)
			if (count($query_part) == 2) 
				$query_where 	= $query_part[1]; // optional
			if (!isset($query_where)) $query_where = "id > 0"; // if there is no WHERE part, it means all records.			
			
			// OPEN TABLE
			$db_filename = $chaozzdb_location.$query_table.$chaozzdb_extension; // open the table
			@unlink ($db_filename."_");					// it might exist if a previous deletion failed (@ surpresses any errors)
			rename ($db_filename, $db_filename."_");		// lock the table
			
			$db_file = fopen($db_filename."_",'r'); 	// cursor in beginning of file, read-only
		}

		// ------------------------
		// INSERT INTO user VALUES (richard, password123, 3)
		// DO NOT SPECIFY FIELDS. INSTEAD EVERY INSERT MUST CONTAIN EVERY FIELD EXCEPT ID, ID WILL AUTO INCREMENT
		// YOU THE FIELD ORDER AS DEFINED ON THE FIRST LINE OF THE TABLE.
		// ------------------------
		if ($query_action == "INSERT")
		{
			// split the query
			$query_part = preg_split ('/INSERT INTO|VALUES/', $query, -1, PREG_SPLIT_NO_EMPTY); // split query on the commands
			$query_part = array_map ('trim', $query_part); // trim the array values

			// extract the info we need
			$query_table 	= $query_part[0];
			$query_values	= trim($query_part[1], "()");
			$query_insert 	= array_map('trim', explode(',', $query_values));
			$query_select 	= "*"; // all fields (you don't specify fields in this statement)
			
			// OPEN TABLE
			$db_filename = $chaozzdb_location.$query_table.$chaozzdb_extension; // open the table
			@unlink ($db_filename."_");					// it might exist if a previous deletion failed (@ surpresses any errors)
			rename ($db_filename, $db_filename."_");		// lock the table
			
			$db_file = fopen($db_filename."_",'r'); 	// cursor in beginning of file, read-only
		}	
		
		// ------------------------
		// UPDATE user SET name = Pete WHERE id = 3
		// ------------------------
		if ($query_action == "UPDATE")
		{
			// split the query
			$query_part = preg_split ('/UPDATE|SET|WHERE/', $query, -1, PREG_SPLIT_NO_EMPTY); // split query on the commands
			$query_part = array_map ('trim', $query_part); // trim the array values

			// extract the info we need
			$query_table 	= $query_part[0];
			$query_update	= array_map('trim', explode(',', $query_part[1]));
			foreach ($query_update as $query_update_part)
			{
				$temp_part = array_map('trim', explode('=', $query_update_part));
				$query_update_list[] = array($temp_part[0] => $temp_part[1]);
			}
			if (count($query_part) == 3) 
				$query_where 	= $query_part[2]; // optional
			if (!isset($query_where)) $query_where = "id > 0";  // if there is no WHERE part, it means all records.		
			
			$query_select	= "*"; // unused in this query
			
			// OPEN TABLE
			$db_filename = $chaozzdb_location.$query_table.$chaozzdb_extension; // open the table
			@unlink ($db_filename."_");					// it might exist if a previous deletion failed (@ surpresses any errors)
			rename ($db_filename, $db_filename."_");		// lock the table
			
			$db_file = fopen($db_filename."_",'r'); 	// cursor in beginning of file, read-only			
		}
		
		// ------------------------
		// SELECT * FROM user
		// SELECT id, name FROM user WHERE name ~= er
		// SELECT id, name FROM user WHERE name != er ORDER BY id ASC
		// SELECT * FROM user LIMIT 10
		// SELECT * FROM user LIMIT 10,20
		// ------------------------
		if ($query_action == "SELECT")
		{
			// split the query
			$query_part = preg_split ('/SELECT|FROM|WHERE|ORDER BY|LIMIT/', $query, -1, PREG_SPLIT_NO_EMPTY); // split query on the commands
			$query_part = array_map ('trim', $query_part); // trim the array values
			
			// mandetory parts
			$query_select 	= $query_part[0];
			$query_table 	= $query_part[1];
			
			// optional parts
			if (strpos($query, "WHERE") !== false)
			{
				$query_part = preg_split('/WHERE|ORDER BY|LIMIT/', $query, -1, PREG_SPLIT_NO_EMPTY);
				$query_part = array_map ('trim', $query_part); // trim the array values
				$query_where = $query_part[1];
			}
			if (strpos($query, "ORDER BY") !== false)
			{
				$query_part = preg_split('/ORDER BY|LIMIT/', $query, -1, PREG_SPLIT_NO_EMPTY);
				$query_part = array_map ('trim', $query_part); // trim the array values
				$query_order = $query_part[1];
			}
			if (strpos($query, "LIMIT") !== false)
			{
				$query_part = preg_split('/LIMIT/', $query, -1, PREG_SPLIT_NO_EMPTY);
				$query_part = array_map ('trim', $query_part); // trim the array values
				$query_limit = $query_part[1];
			
				if (strpos($query_limit, ",") !== false)
				{
					$limit = explode(",", $query_limit);
					$limit = array_map ('trim', $limit); // trim the array values
					$query_limit_start = $limit[0]; // start at record x
					$query_limit_num = $limit[1]; // return x records
				}
				else
				{
					$query_limit_start = 0; // start at the beginning
					$query_limit_num = $query_limit;
				}
			}
			
			if (!isset($query_where)) $query_where = "id > 0"; // if there is no WHERE part, it means all records.
			if (!isset($query_order)) $query_order = "id ASC"; // if there is no ORDER BY part then sort by id ASCENDING
			if (!isset($query_limit_start)) $query_limit_start = 0; // start at the beginning
			if (!isset($query_limit_num)) $query_limit_num = $chaozzdb_max_records; // return the max of x records
			
			// OPEN TABLE
			$db_filename = $chaozzdb_location.$query_table.$chaozzdb_extension; // open the table
			$db_file = fopen ($db_filename, "r"); // cursor in beginning of file, read-write
		}
		
		// FAILED TO OPEN TABLE!!! EXIT
		if (!$db_file) return chaozzdb_error ($query_action, "Table $db_filename not found");

		// ------------------------
		// READ FIELDS
		// ------------------------
		$header = fgets ($db_file); 			// first line lists all table fields
		//$header = str_replace(PHP_EOL,"", $header); // remove End Of Line (EOL)
		$header = preg_replace("/[\n\r]/","",$header); // remove End Of Line (EOL)
		$field_name = explode ($chaozzdb_delimiter, $header);	// all fields in an array
		$field_name = array_map('trim', $field_name); // trim the array values

		// ------------------------
		// READ ENTIRE TABLE INTO ARRAY
		// ------------------------
		$records_read = 0;
		$record_array = Array();
		
		while(! feof($db_file))
		{
			$line = fgets ($db_file); // read a line from the file
			if ($line == "") continue; // skip if it's empty
			//$line = str_replace(PHP_EOL,"", $line); // remove End Of Line (EOL)
			$line = preg_replace("/[\n\r]/","",$line);
			$values = explode($chaozzdb_delimiter, $line); // split on the delimiter
			$record_array[] = array_combine($field_name, $values); // create a multi dimensional array of key's and values of this record
			$records_read++; // count read records
		}	
		fclose($db_file);
		
		if (($query_action == "SELECT" || $query_action == "UPDATE" || $query_action == "DELETE") && $records_read == 0) return chaozzdb_error($query_action, "");
		
		// get the auto increment ID for the INSERT query action
		if ($query_action == "INSERT")
		{
			if (count($record_array) > 0)
			{
				// SORT THE ARRAY BY ID DESCENDING BEFORE WRITING IT TO THE TABLE. NEWEST RECORD ON TOP. THIS IS IDEAL FOR AUTO INCREMENTS
				$sort_by = array_column($record_array, 'id');
				array_multisort($sort_by, SORT_DESC, SORT_NATURAL|SORT_FLAG_CASE, $record_array);
				$query_id = $record_array[0]['id']+1;
			}
			else $query_id = 1;
			
			array_unshift ($query_insert, $query_id); // now add this id to the array of values we want to add to the table
			$record_array[] = array_combine($field_name, $query_insert); // add this new array to the records array
		}
		
		// if the table is empty then return false for most query actions
		if (count($record_array) == 0 && ($query_action == "SELECT" || $query_action == "DELETE" || $query_action == "UPDATE")) return chaozzdb_error ($query_action);
		
		// ------------------------
		// SELECT FIELDS: SELECT field1, field2 ... or SELECT *
		// ------------------------
		if ($query_select == "*")
			$select_field = $field_name; // all fields (already explode()d)
		else
			$select_field = explode (",", $query_select); // split fields in query by comma's
		
		$select_field = array_map('trim', $select_field); // trim the array values
		$remove_field = array_diff ($field_name, $select_field); // which fields to remove from the result, because they are not in the SELECT part

		// ------------------------
		// WHERE CONDITION? WHERE field1=value1 ... or field1 != value1  .. or field1 > value1   .. or field1 < value1
		// ------------------------
		if ($query_where !== null) 
		{
			$where_part = preg_split('/=|!=|>|<|~=/', $query_where); // split the WHERE part of the query on the condition, which is either = , != , < , > OR ~=
			$where_part = array_map('trim', $where_part); // trim the result
			$where_field = $where_part[0]; // field1
			$where_value = trim($where_part[1], "'"); // value1
			
			$dummy = preg_match('/=|!=|>|<|~=/', $query_where, $matches); // search the condition
			$where_condition = trim($matches[0]); // trim the result
		}
		else
		{
			$where_field = "id";
			$where_value = 0;
			$where_condition = ">";
		}
		
		// ------------------------
		// LOOP THROUGH THE MULTI DIMENSIONAL ARRAY THAT CONTAINS THE ENTIRE TABLE
		// ------------------------
		if ($query_action != "INSERT")
		{
			$record_count = count($record_array); // since we're removing subarrays during the loop, we can must register the count before the loop
			for ($a = 0; $a < $record_count; $a++)
			{
				// SEE IF WE MATCH THE WHERE PART OF THE QUERY, IF ANY
				if ($query_where != null) 
				{
					$matches_where_condition = false; // lets presume we don't have a match on the WHERE field
					for ($i = 0; $i < count($record_array[$a]); $i++)
					{
						switch ($where_condition) 
						{
							case "=":
								if ($record_array[$a][$where_field] == $where_value) $matches_where_condition = true;
								break;
							case "!=":
								if ($record_array[$a][$where_field] != $where_value) $matches_where_condition = true;
								break;
							case "<":
								if ($record_array[$a][$where_field] < $where_value) $matches_where_condition = true;
								break;
							case ">":
								if ($record_array[$a][$where_field] > $where_value) $matches_where_condition = true;
								break;
							case "~=":
								if (stripos($record_array[$a][$where_field], $where_value) !== false) $matches_where_condition = true;
								break;
						}
					}
				}
				else
					$matches_where_condition = true;
				
				
				// if the record matches the WHERE part of the query, return the values supplied in SELECT part
				if (!$matches_where_condition && $query_action == "SELECT")
				{
					unset($record_array[$a]); // remove the current record from the result
					continue; // stop processing this record and continue to the next
				}
				
				// if the record does not match the WHERE query part, write this record to the new table
				if ($matches_where_condition && $query_action == "DELETE")
				{
					unset($record_array[$a]); // remove the current record from the result
					continue; // stop processing this record and continue to the next
				}
				
				// if the record matches the WHERE part, update the values defined in update
				if ($matches_where_condition && $query_action == "UPDATE")
				{
					foreach ($query_update_list as $update_pair)
						foreach ($update_pair as $field => $value)
							$record_array[$a][$field] = $value;
				}
				
			}
		}
		
		// IF SELECT WAS DONE, CHECK THE ORDER BY
		if ($query_action == "SELECT")
		{
			$orderby = explode(" ", $query_order); // $query_order is for example: id ASC   ... or  ...   name DESC
			$sort_by = array_column($record_array, $orderby[0]); // split on the space
			if (strtoupper($orderby[1]) == "ASC") array_multisort($sort_by, SORT_ASC, SORT_NATURAL|SORT_FLAG_CASE, $record_array); // sort ascending
			else array_multisort($sort_by, SORT_DESC, SORT_NATURAL|SORT_FLAG_CASE, $record_array); // sort descending
			
			// remove fields from the array that are not in the SELECT part. we do this here, because you can sort on a field that is not in the select.
			for ($i = 0; $i < count ($record_array); $i ++)
				foreach ($remove_field as $remove_this_field)
					unset($record_array[$i][$remove_this_field]); // remove the keys that are unwanted
					
			// LIMIT START
			$num_records = count($record_array); // store in a variable as we're going to remove records, which will adjust this number
			if ($query_limit_start > 0)
				for ($i = 0; $i < $query_limit_start; $i++)
					unset ($record_array[$i]); // remove records until we reach limit_start
				
			// LIMIT NUMBER	
			$num_records = count($record_array); // store in a variable as we're going to remove records, which will adjust this number
			if ($num_records > $query_limit_num) 
				for ($i = $query_limit_num; $i < $num_records; $i++)
					unset ($record_array[$i]); // remove records until we have a number of records that matches limit_num

		}
		
		
		
		
		// LETS WRITE THE CHANGED TABLE
		if ($query_action == "DELETE" || $query_action == "INSERT" || $query_action == "UPDATE") 
		{
			$db_file = fopen ($db_filename, "w");
			fwrite($db_file, $header."\r\n"); // write header, containing field names. no need for PHP_EOL because we didn't strip it from the original header we read
			
			// SORT THE ARRAY BY ID DESCENDING BEFORE WRITING IT TO THE TABLE. NEWEST RECORD ON TOP. THIS IS IDEAL FOR AUTO INCREMENTS
			$sort_by = array_column($record_array, 'id');
			array_multisort($sort_by, SORT_DESC, SORT_NATURAL|SORT_FLAG_CASE, $record_array);
			
			// FLATTEN THE MULTIDIMENSIONAL ARRAY BACK TO VALUES ONE PER LINE SEPERATED BY TABs
			foreach ($record_array as $this_record)
			{
				foreach ($this_record as $field => $value)
					$flat_record_value[] = $value; // read the value out of the field=>value pair
					
				$flat_record = implode($chaozzdb_delimiter, $flat_record_value); // flatten the array to a delimiter seperated record
				fwrite($db_file, $flat_record."\r\n"); // write the flat record back to the table
				unset($flat_record_value); // clear the array
			}
			
			fclose($db_file);
			unlink ($db_filename."_");			// delete lock table
		}
		
		// RETURN VALUE
		switch ($query_action) 
		{
			case "SELECT":
				return $record_array;
				break;
			case "INSERT":
				return $query_id;
				break;
			case "DELETE":
				return true;
				break;
			case "UPDATE":
				return true;
				break;
		}
	}
?>
<?php
namespace PhpMvc\Core;

class MySqliClient {
	private $_mysqli=null;
	private $_errorCallback=null;
	private $_debugMode=false;

	function __construct($sqlserver, $sqluser, $sqlpassword, $database, $charset='utf-8', $errorCallback = null) {
		$this->_errorCallback=$errorCallback;
		$this->_mysqli = new \mysqli($sqlserver, $sqluser, $sqlpassword, $database);
		if (mysqli_connect_errno()) {
			$this->_error(sprintf("Connect failed: %s", mysqli_connect_error()), true);
		}

		/* change character set to utf8 */
		if (!$this->_mysqli->set_charset($charset)) {
			$this->_error(sprintf("Can not set connection encoding to %s!", $charset), true);
		}
	}

	function __destruct() {
		$this->_mysqli->close();
	}

	private function _error($message, $throwException) {
		if(!$throwException && $this->_errorCallback) {
			//must throw and catch an exception to build the stack trace and send information
			try {
				throw new \Exception($message);
			}
			catch(\Exception $ex) {
				if(is_callable($this->_errorCallback))
					call_user_func_array($this->_errorCallback, array($message, $ex));
				return null;
			}
		}
		else {
			//just throw the exception
			throw new \Exception($message);
		}
	}

	/**
	 * Turns on Debug Mode. In this mode, queries are not executed, but are being sent to output, with the parameters.
	 */
	public function TurnOnDebugMode() {
		$this->_debugMode = true;
	}

	/**
	 * Turns off Debug Mode. When it's off, queries are executed.
	 */
	public function TurnOffDebugMode() {
		$this->_debugMode = false;
	}

	/**
	 * Turns off autocommit, enabling transaction-based operation. When autocommit is off, you must call Commit() or Rollback() after finishing modifications
	 */
	public function TurnOffAutocommit() {
		$this->_mysqli->autocommit(false);
	}

	/**
	 * Turns autocommit back on. On is the default state. Call this method when autocommit has been turned off before
	 */
	public function TurnOnAutocommit() {
		$this->_mysqli->autocommit(true);
	}

	/**
	 * Commit latest changes, if autocommit is off
	 * @return bool
	 */
	public function Commit() {
		return $this->_mysqli->commit();
	}

	/**
	 * Rollback latest uncommitted changes (when autocommit is off)
	 * @return bool
	 */
	public function Rollback() {
		return $this->_mysqli->rollback();
	}

	/**
	 * @param $stmt \Mysqli_stmt
	 * @param $return string
	 * @param $dyn_fields array
	 * @param $fields array
	 * @return array|mixed|null
	 */
	private function Fetch(\Mysqli_stmt $stmt, $return, &$dyn_fields, $fields)
	{
		if($return=='result')
		{
			$ret=array();
			while ($stmt->fetch())
			{
				$index=0;
				$row=array();
				foreach($fields as $fieldName)
				{
					$row[$fieldName]=$dyn_fields[$index];
					$index++;
				}
				$ret[]=$row;
			}
			return $ret;
		}
		elseif($return=='rows_modified')
		{
			return $stmt->affected_rows;
		}
		elseif($return=='autoincrement')
		{
			return $this->_mysqli->insert_id;
		}
		return null;
	}

	/**
	 * Performs a query and returns a result as specified
	 * @param $query string Query text
	 * @param $params array Parameters
	 * @param $return string Specifies a result type from: "result", "rows_modified", "autoincrement"
	 * @param $throwException bool Whether to throw the exception or just log it
	 * @return array|mixed|null result
	 */
	private function Query($query, $params, $return, $throwException)
	{
		if($this->_debugMode) {
			echo "<pre>\nDebug Mode is on, query (with return type '$return') not executed:\n\n";
			echo $query."\n\n";
			echo "With params:\n\n";
			var_dump($params);
			echo "\n\n\n</pre>";
			return false;
		}
		if(!is_array($params)) {
			$params = array($params);
		}
		//here be more dragons
		if ($stmt = $this->_mysqli->prepare($query))
		{
			if($return=="result")
			{
				$meta = $stmt->result_metadata();
				$fields=array();
				while ($columnName = $meta->fetch_field())
					$fields[] = $columnName->name;
				$dyn_fields=array();
				$tmp_fields=array();

				for($index=0;$index<count($fields);$index++) {
					$tmp_fields[$index]="";
					$dyn_fields[$index]=&$tmp_fields[$index];
				}
				call_user_func_array(array($stmt, 'bind_result'), $dyn_fields);

				$meta->close();
			}
			else {
				$fields = null;
			}

			$result=array();
			if(is_array($params) && !empty($params))
			{
				//check if multiple parameters set
				if(!empty($params) && is_array($params[0]))
				{
					$params_group=$params[0];
					$isMultiple=true;
				}
				else
				{
					$params_group=$params;
					$isMultiple=false;
				}

				$params_arr=array();
				$params_type='';
				foreach($params_group as &$param)
				{
					switch(gettype($param))
					{
						case 'boolean':
							$type='i';
							$param=$param?1:0;
							break;
						case 'integer':
							$type='i';
							$param=intval($param);
							break;
						case 'double':
							$type='d';
							$param=doubleval($param);
							break;
						case 'string':
							$type='s';
							break;
						default:
							$type='s';
					}
					$params_arr[]=&$param;
					$params_type.=$type;
				}
				$dyn_params=array_merge(array($params_type),$params_arr);
				call_user_func_array(array($stmt, 'bind_param'), $dyn_params);

				$paramIndex=0;
				do
				{
					//bind new params
					if($isMultiple)
					{
						$paramRow=$params[$paramIndex];
						$numParams = count($paramRow);
						for($i=0; $i<$numParams; $i++)
						{
							$dyn_params[$i+1]=$paramRow[$i];
						}
					}

					if($stmt->execute()!==true)
					{
						$this->_error($stmt->error . ' || Query: ' . print_r($query,1) . '  || \PARAMS: ' . print_r($params,1), $throwException);
						return null;
					}
					if($isMultiple)
						$result[] = $this->Fetch($stmt, $return, $dyn_fields, $fields);
					else
						$result = $this->Fetch($stmt, $return, $dyn_fields, $fields);
					$paramIndex++;
				} while($isMultiple && $paramIndex<count($params));
			}
			else
			{
				/* execute query */
				if($stmt->execute()!==true)
				{
					$this->_error($stmt->error, $throwException);
					return null;
				}
				$result=$this->Fetch($stmt, $return, $dyn_fields, $fields);
			}
			$stmt->close();
			return $result;
		}
		$this->_error($this->_mysqli->error, $throwException);
		return null;
	}

	/**
	 * Runs a query and returns a single value, from the first column of the first fetched row
	 * @param string $query
	 * @param array $params
	 * @param $throwException bool Whether to throw the exception or not. Not throwing just logs it.
	 * @return mixed
	 */
	public function GetOne($query, $params=array(), $throwException=false)
	{
		$results = $this->Query($query,$params,'result', $throwException);
		if(is_array($results) && !empty($results))
		{
			//it executes once
			foreach($results[0] as $col=>$val)
				return $val;
		}
		return null;
	}

	/**
	 * Runs a query and returns an associative array representing a row
	 * @param string $query
	 * @param array $params
	 * @param $throwException bool Whether to throw the exception or not. Not throwing just logs it.
	 * @return array|null
	 */
	public function GetRow($query, $params=array(), $throwException=false)
	{
		$results = $this->Query($query,$params,'result', $throwException);
		if(is_array($results) && !empty($results))
		{
			return $results[0];
		}
		return null;
	}

	/**
	 * Runs a query and returns an array of associative arrays representing rows
	 * @param $query
	 * @param array $params
	 * @param $throwException bool Whether to throw the exception or not. Not throwing just logs it.
	 * @return array|null
	 */
	public function GetAll($query, $params=array(), $throwException=false)
	{
		$results = $this->Query($query,$params,'result', $throwException);
		return $results;
	}

	/**
	 * Runs a query
	 * @param string $query
	 * @param array $params
	 * @param $throwException bool Whether to throw the exception or not. Not throwing just logs it.
	 */
	public function Execute($query, $params=array(), $throwException=false)
	{
		$this->Query($query,$params,'none', $throwException);
	}

	/**
	 * Runs a query and returns last inserted auto_incremet value
	 * @param string $query
	 * @param array $params
	 * @param $throwException bool Whether to throw the exception or not. Not throwing just logs it.
	 * @return int|null
	 */
	public function Insert($query, $params=array(), $throwException=false)
	{
		$results = $this->Query($query,$params,'autoincrement', $throwException);
		return $results;
	}

	/**
	 * Runs a query and returns number of affected rows
	 * @param string $query
	 * @param array $params
	 * @param $throwException bool Whether to throw the exception or not. Not throwing just logs it.
	 * @return int|null
	 */
	public function Modify($query, $params=array(), $throwException=false)
	{
		$results = $this->Query($query,$params,'rows_modified', $throwException);
		return $results;
	}

	private function isAssoc($arr) {
		$result = array_diff(array_keys($arr), range(0, count($arr) - 1));
		$result = !empty($result);
		return $result;
	}


	private function getFieldSetInfo($fields) {
		$info = array(
			'level'=>0,
			'rows'=>1,
			'columns'=>1
		);
		if(is_array($fields)) {
			if($this->isAssoc($fields)) {
				$info['columns']=count($fields);
				$info['level']=1;
			}
			else {
				$info = $this->getFieldSetInfo($fields[0]);
				$info['level']=$info['level']+1;
				$info['rows']=count($fields);
			}
		}
		return $info;
	}

	private function getRowsToUpdate($whereFieldsRow, $whereValuesRow, $throwException) {
		$rowsToUpdate = array();
		$rowToUpdate = array();
		if(count($whereFieldsRow)>1 && count($whereValuesRow)!=count($whereFieldsRow)) {
			return $this->_error("Incompatible 'Where' field list with value list", $throwException);
		}
		$index=0;
		foreach($whereValuesRow as $whereValue) {
			if($index>count($whereFieldsRow)-1) {
				$index=0;
				$rowsToUpdate[]=$rowToUpdate;
				$rowToUpdate=array();
			}
			if($index==0) {
				$rowToUpdate=array();
			}
			$rowToUpdate[$whereFieldsRow[$index]]=$whereValue;
			$index++;
		}
		$rowsToUpdate[]=$rowToUpdate;
		return $rowsToUpdate;
	}

	private function prepUpdateConditions($whereFields, $whereValues, $throwException) {
		if(!is_array($whereFields))
			$whereFields = array($whereFields);
		if(!is_array($whereValues))
			$whereValues = array($whereValues);

		$rowsToUpdate = array();
		if(is_array($whereValues[0])) {
			foreach($whereValues as $whereValuesRow) {
				$newRowsToUpdate = $this->getRowsToUpdate($whereFields, $whereValuesRow, $throwException);
				if($newRowsToUpdate==null) {
					return null;
				}
				$rowsToUpdate = array_merge($rowsToUpdate, $newRowsToUpdate);
			}
		}
		else {
			$newRowsToUpdate = $this->getRowsToUpdate($whereFields, $whereValues, $throwException);
			if($newRowsToUpdate==null) {
				return null;
			}
			$rowsToUpdate = array_merge($rowsToUpdate, $newRowsToUpdate);
		}
		return $rowsToUpdate;
	}

	/**
	 * Updates a row with new values
	 * @param $tableName string Table to update
	 * @param $whereFields array|string Field or list of field to be checked
	 * @param $whereValues array|mixed Value or list of values, or arrays of lists of values to test the fields against
	 * @param $updatedRow array Values (assoc array) or array of values (array of assoc arrays) to update
	 * @param $throwException bool Whether to throw the exception or not. Not throwing just logs it.
	 * @return int|null
	 */
	public function UpdateRows($tableName, $whereFields, $whereValues, $updatedRow, $throwException=false) {
		$query="UPDATE `$tableName` SET ";
		$params=array();

		$rowsToUpdate = $this->prepUpdateConditions($whereFields, $whereValues, $throwException);
		if($rowsToUpdate===null) {
			return null;
		}

		//$whereFieldsInfo = $this->getFieldSetInfo($whereFields);
		//$whereValuesInfo = $this->getFieldSetInfo($whereValues);
		$updatedRowInfo = $this->getFieldSetInfo($updatedRow);
		if($updatedRowInfo['rows']>1 && count($rowsToUpdate)!=count($whereValues)) {
			return $this->_error("Could not parse 'where' values list, or incompatible with update list", $throwException);
		}

		$isSIMD = $updatedRowInfo['level']>1 || (count($rowsToUpdate)>1);

		// MULTIPLE ROWS TO UPDATE
		//multiple updatedRow rows with multiple whereValues rows
		if($isSIMD && $updatedRowInfo['level']>1) {
			foreach($updatedRow as $rowIndex=>$row) {
				$params[$rowIndex]=array();
				foreach($row as $key=>$value) {
					//only do it once
					if($rowIndex==0) {
						$query.="`$key`=?,";
					}
					$params[$rowIndex][]=$value;
				}
			}
		}
		//single updatedRow, with multiple whereValues rows
		elseif($isSIMD) {
			for($rowIndex=0;$rowIndex<count($rowsToUpdate);$rowIndex++) {
				$params[$rowIndex]=array();
				foreach($updatedRow as $key=>$value) {
					//only do it once
					if($rowIndex==0) {
						$query.="`$key`=?,";
					}
					$params[$rowIndex][]=$value;
				}
			}
		}
		// END MULTIPLE ROWS TO UPDATE
		// SINGLE ROW TO UPDATE
		else {
			$params[0]=array();
			foreach($updatedRow as $key=>$value) {
				$query.="`$key`=?,";
				$params[0][]=$value;
			}
		}
		// END SINGLE ROW TO UPDATE
		$query=rtrim($query, ',');

		$query.=" WHERE ";

		// FINISH CONSTRUCTING QUERY
		$updateParts = array();
		foreach($rowsToUpdate[0] as $key=>$value) {
			$updateParts[]="`$key`=?";
		}
		$query.=implode(" AND ", $updateParts);
		// END FINISH CONSTRUCTING QUERY

		// ADD WHERE PARAMS
		foreach($rowsToUpdate as $index=>$row) {
			foreach($row as $key=>$value) {
				$params[$index][]=$value;
			}
		}
		// END ADD WHERE PARAMS

		$results = $this->Modify($query, $params, $throwException);
		if(is_array($results) && count($params)==1) {
			$results = $results[0];
		}
		return $results;
	}

	/**
	 * Inserts one or more rows
	 * @param $tableName
	 * @param $insertRow
	 * @param $throwException bool Whether to throw the exception or not. Not throwing just logs it.
	 * @return int|null
	 */
	public function InsertRows($tableName, $insertRow, $throwException=false) {
		$query="INSERT INTO `%s` (%s) VALUES (%s)";
		if(isset($insertRow[0]) && is_array($insertRow[0])) {
			$fields=array_keys($insertRow[0]);
			$values=array();
			foreach($insertRow as $row) {
				$values[]=array_values($row);
			}
		}
		else {
			$fields=array_keys($insertRow);
			$values=array_values($insertRow);
		}
		$query=sprintf($query, $tableName, '`'.implode('`,`', $fields).'`', rtrim(str_repeat('?,', count($fields)), ','));

		$results = $this->Insert($query, $values, $throwException);
		return $results;
	}
}
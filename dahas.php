<?php
// error_reporting(E_ALL);
// ini_set("display_errors", 1);
class dahas {
	function __construct(){
		$this->prefix = "";
		$this->_reset();
		$this->query = false;
	}
	
	function _reset(){
		$this->sql = array();
		$this->flags = array();
		$this->flags["field"]=false;
		$this->flags["value"]=false;
		$this->flags["call"]=false;
		$this->joining = false;
		$this->order = array();
		$this->random = false;
		$this->limit = false;
	}
	
	function __get($name){
		switch($name){
			case "run":
			case "return_row":
			case "result_row":
			case "return_array":
			case "result_array":
			case "result":
			case "return":
				return $this->_run($name);
			break;
		}
	}
	
	function _run($name){
		//SELECT
		if($this->flags["field"]){
			$return = $this->_select($name);
		} else if($this->flags["field"]==false AND $this->flags["value"]==true) {
			$return = $this->_insert();
		} else {
			echo "else";
		}	
		$this->_reset();
		return $return;
	}
	
	public function __call($name, $arguments){
		if(!method_exists($this, $name)){
			if(!is_array($arguments[0])){
				$arg = $arguments;
			} else {
				$arg = $arguments[0];
			}
			
			if(!$this->flags["call"]){
				$this->sql = array();
				$this->table = $name;
				$this->flags["call"]=$name;			
				foreach($arg as $id => $val){
					if(is_numeric($id)){
						$this->flags["field"]=true;
						$this->sql[$name][] = array(
							"row" => $val,
							"val" => ""
						);
					} else {
						$this->flags["value"]=true;
						$this->sql[$name][] = array(
							"row" => $id,
							"val" => $val
						);
					}
				}
			} else {
				$utolso = "";
				foreach($this->sql as $id => $val){
					foreach($val as $id2 => $val2){
						if($val2["val"]==""){
							$utolso = $val2["row"];
						}	
					}
				}
				// $utolso = end($this->sql[$this->flags["call"]]);
				$mit = false;
				$this->joi = array();
				foreach($arg as $id => $val){
					//Field
					if(is_numeric($id)){
						if(!$mit){
							$mit = $val;
						} else {
							
							$this->joi[] = $this->prefix.$name.".".$val;
						}
					//Value
					} else {
						$this->joi[$this->prefix.$name.".".$id] = $val;
					}
				}
				$this->joining = " INNER JOIN ".$this->prefix.$name." ON ".$this->prefix.$name.".".$mit."=".$this->prefix.$this->table.".".$utolso;
			}
		}
		return $this;
	}
	
	function _insert(){
		$query = "";
		foreach($this->sql as $table => $val){
			$query.=" INSERT INTO ".$this->prefix.$table." SET ";
			$rows = array();
			foreach($val as $id => $row){
				$rows[]="".$this->prefix.$table.".".$row["row"]."='".$row["val"]."'";
			}
			$query.=implode(", ",$rows);
		}
		$this->query = $query;
		return $this->_result();
	}
	
	function update($where=""){
		$query = "";
		foreach($this->sql as $table => $val){
			$query.="UPDATE ".$this->prefix.$table." SET ";
			$rows = array();
			foreach($val as $id => $row){
				$rows[]="".$this->prefix.$table.".".$row["row"]."='".$row["val"]."'";
			}
			$query.=implode(", ",$rows);
			if(!empty($where)){
				$query.=" WHERE ".$where;
			}
		}
		$this->query = $query;
		$this->_reset();
		return $this->_result();
	}
	
	function delete(){
		$query = "";
		foreach($this->sql as $table => $val){
			$query.="DELETE FROM ".$this->prefix.$table." WHERE ";
			$rows = array();
			foreach($val as $id => $row){
				$rows[]="".$this->prefix.$table.".".$row["row"]."='".$row["val"]."'";
			}
			$query.=implode(", ",$rows);
		}
		$this->query = $query;
		$this->_reset();
		return $this->_result();
	}
	
	function num($num="num"){
		$query = "";
		foreach($this->sql as $table => $val){
			$query.="SELECT COUNT(*) as ".$num." FROM ".$this->prefix.$table." WHERE ";
			$rows = array();
						
			foreach($val as $id => $row){
				
				$last_one = substr($row["row"],-1,1);
				$last_two = substr($row["row"],-2,2);
				$operator = "=";
				
				switch($last_one){
					case ">": $operator = ""; $break;
					case "<": $operator = ""; $break;
				}
				
				switch($last_two){
					case "<=": $operator = ""; $break;
					case ">=": $operator = ""; $break;
				}
				
				$rows[]="".$this->prefix.$table.".".$row["row"].$operator."'".$row["val"]."'";
			}
			$query.=implode(" AND ",$rows);
		}
		$this->query = $query;
		$this->_reset();
		return $this->_return_row();
	}
	
	function random(){
		$this->random = true;
		return $this;
	}
	
	function orderby($id,$direction="ASC"){
		$this->order["id"] = $id;
		$this->order["direction"] = $direction;
		return $this;
	}
	
	function limit($limit){
		$this->limit = $limit;
		return $this;
	}
	
	function _select($name){
		
		$query = "";

		foreach($this->sql as $table => $val){
				
			//Selected items
			$selected = array();
			$where = array();
			
			foreach ($val as $id => $item) {
				
				if($item["val"]==""){
					$selected[]=$this->prefix.$table.".".$item["row"];
				} else {
					$last_one = substr($item["row"],-1,1);
					$last_two = substr($item["row"],-2,2);
					$operator = "=";
					
					switch($last_one){
						case ">": $operator = ""; $break;
						case "<": $operator = ""; $break;
					}
					
					switch($last_two){
						case "<=": $operator = ""; $break;
						case ">=": $operator = ""; $break;
					}
					$where[]=$this->prefix.$table.".".$item["row"].$operator."'".$item["val"]."'";
				}
			}
			
			$selected = implode(", ", $selected);
			
			if($this->joi){
				$join_select = array();
				$join_where = "";
				foreach($this->joi as $jid => $jval){
					if(is_numeric($jid)){
						$join_select[]=$jval;
					} else {
						$join_where.=" ".$jid."='".$jval."'";
					}
				}
				$selected.=", ".implode(", ",$join_select);
			}
			
			$query.= "SELECT ".$selected." FROM ".$this->prefix.$table."";
			if(isset($this->joining)){
				$query.=$this->joining;
			}
			if(!empty($where)){
				$query.=" WHERE ".implode(" AND ", $where);
			}
			
			if(isset($join_where) AND !empty($join_where)){
				$query.=$join_where;
			}
			
			if(isset($this->order) and !empty($this->order)){
				$query.=" ORDER BY ".$this->prefix.$table.".".$this->order["id"]." ".$this->order["direction"];
			} else if($this->random){
				$query.=" ORDER BY RAND()";
			}
			
			if(isset($this->limit) and !empty($this->limit)){
				$query.=" LIMIT ".$this->limit;
			}
		}
		
		$this->query = $query;
		$this->_reset();
		if($name=="return_row"){
			return $this->_return_row();
		} else if($name=="return_array") {
			return $this->_return_array();
		} else if($name=="return"){
			return $this->_result();
		}
	}
	
	function _return_array(){
		if($this->query){
			$datas = mysql_query($this->query);
			$results = array();
			while($data = mysql_fetch_array($datas)){
				$results[]=$data;
			}
			return $results;
		}
	}
	
	function _return_row(){
		if($this->query){
			return mysql_fetch_array(mysql_query($this->query));
		}
	}
	
	function _result(){
		if($this->query){
			return mysql_query($this->query);
		}
	}
}
?>
<?php
  /*
    Copyright 2018 - Thomas Pearce
    Part of [REDACTED]
    
    Instance: singleton
    Initialization: $var = DataScrub::instanceGen();
    Usage: $var->sanitize($input);
    Accepts: $input - string, arrays, associative arrays, multi-dimensional arrays, and JSON objects
    Returns: $input - escaped, and ready for insertion into your database
		
  */
    
  class DataScrub extends Unique {
      
    private $_result;
    
    public function sanitize($data) {
      $this->_sanitize($data);
      return $this->_result;
    }
    
    // for redundancy - new code will reflect new structure, old code will be updated
    public function scrub($data) {
      $this->sanitize($data);
      return $this->_result;
    }
    
    // for redundancy - get rid of this NAO!
    public function getResults() {
      return $this->_result;
    }
    
    public function isArray($data) {
      
     // RECURSIVE JSON ARRAY  REGEX 
      $regexString = '"([^"\\\\]*|\\\\["\\\\bfnrt\/]|\\\\u[0-9a-f]{4})*"';
      $regexNumber = '-?(?=[1-9]|0(?!\d))\d+(\.\d+)?([eE][+-]?\d+)?';
      $regexBoolean= 'true|false|null'; 
      $regex = '/\A('.$regexString.'|'.$regexNumber.'|'.$regexBoolean.'|';    //string, number, boolean
      $regex.= '\[(?:(?1)(?:,(?1))*)?\s*\]|'; //arrays
      $regex.= '\{(?:\s*'.$regexString.'\s*:(?1)(?:,\s*'.$regexString.'\s*:(?1))*)?\s*\}';    //objects
      $regex.= ')\Z/is'; 

      if(is_array($data) && $this->_is_assoc($data)) // ASSOCIATIVE ARRAY
          return 4;
      else if(is_array($data) && $this->_is_multi($data)) // MULTIDIMENSIONAL ARRAY
          return 2;
      else if(is_array($data)) // ARRAY
          return 1;
      else if(preg_match($regex, (string)$data) == 1) // JSON DATA
          return 3;
      else
          return 0; // STRING
    }
    
    private function _sanitize($data) {
        
      switch($this->isArray($data)) {
          
        case 0:
           $this->_prepSingle($data);
        break;
        
        case 1:
        case 2:
          $this->_prepArray($data);
        break;
        
        case 3: // JSON data - integers require different parsing
          $data = strval(json_decode($data, true));
              
          if(is_numeric(strval($data)))  { // numeric value
            $data = addslashes(strval($data));
            $val = implode("",explode("\\", $data));
            $val = stripslashes(trim($val));
            $val = htmlentities($val, ENT_QUOTES);
            $val = htmlspecialchars($val, ENT_QUOTES);
            $val = strip_tags(trim($val));
            $this->_result =  $val;
            unset($val);
            unset($data);
          } else { $this->_sanitize($data); }
        break;
          
         default:
           $this->_prepSingle($data);
         break;
         
      }
    }
    
    private function _prepSingle($data) {
       
      if(!$this->isArray($data)) {
        $val = addslashes($data);
        $val = implode("", explode("\\", $val));
        $val = stripslashes(trim($val));
        $val = htmlentities($val, ENT_QUOTES);
        $val = htmlspecialchars($val, ENT_QUOTES);
        $val = strip_tags(trim($val));
        $this->_result = $val;
        unset($val);
        unset($data);
      } else { $this->_result = $this->_sanitize($data); }
          
    }

    private function _is_assoc(array $array) {
      return count(array_filter(array_keys($array), 'is_string')) > 0;
    }
    
    private function _is_multi($data) {
      foreach ($data as $v) {
        if (is_array($v)) return true;
      }
      return false;
    }
    
    private function _prepArray($data) {
      
      if($this->isArray($data) > 0) {
        
        foreach($data as $key=>$value) {
          if($this->isArray($value)) { 
            $this->_sanitize($key);
            $key = $this->_result;
            $this->_sanitize($value);
            $value = $this->_result;
          } else {
            $this->_prepSingle($key);
            $key = $this->_result;
            $this->_prepSingle($value);
            $value = $this->_result;
          }
        }
      }
      $this->_result = $data;
    }
   
  }
?>
<?
  /*
    Copyright 2018 - Thomas Pearce
    Part of [Redacted]
    
    Singleton initialization for classes included in [REDACTED].
    Passes any parameters along as well.
    
    Usage: class X inherits Unique { }
    Initialize: X::instanceGen();
  */
  class Singleton { 
    private static $instances = array();

    private function __construct() {}
    private function __clone() {}
    private function __sleep() {}
    public function __destruct() { 
      if(isset(self::$instances[get_called_class()]))
        unset(self::$instances[get_called_class()]); 
    }

    private function __wakeup() {
      throw new Exception("Cannot unserialize singleton");
    }

    public static function instanceGen($params = '') {
      $cls = get_called_class(); // late-static-bound class name
      if (!isset(self::$instances[$cls])) {
        if(!isset($params) || $params == '')
          self::$instances[$cls] = new static;
        else 
          self::$instances[$cls] = new static($params);
      }
      return self::$instances[$cls];
    }
    
  }
  
  class Unique extends Singleton {
    protected $label;
    
    public function setLabel($data) {
      $this->label = $data;
    }
    
    public function getLabel() { return $this->label; }
    
  }
?>
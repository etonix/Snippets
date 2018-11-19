# Snippets
Classes I have written for various projects.

maid.php
 -- A data sanitization class for insertion via MySQL/MySQLi wrapper.

filesecure.php
 -- A file encryption class utilizing OpenSSL
 
unique.php
 -- A singleton initialization class which all of the above classes inherit.
 
 
Examples:

 maid.php
 
    require_once("unique.php");
    require_once("maid.php");
    $var = json_encode(array("key"=>"value", "key2"=>array("level")));
    $maid = DataSecure::instanceGen();
  
    $var = $maid->scrub($var); // now escaped and ready for database insertion!

    -------------------------   
 
 filesecure.php

    require_once("unique.php");
    require_once("maid.php");
    $secure = FileSecure::instanceGen();
    // encrypt!
    $secure->encrypt("path/to/file");
    // decrypt!
    $secure->decrypt("path/to/file");
      
    ------------------------- 

  
  
  
  

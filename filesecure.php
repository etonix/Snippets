<?php
  /*
    Copyright 2018 - Thomas Pearce
    Part of [Redacted]
    
    Instance: singleton
    Initialization: $var = FileSecure::instanceGen();
    
    To encrypt:
    Usage: $var->encrypt($file_location);
    Returns: $file_location -- the original file is now a link to an encrypted file
		
    To decrypt:
    Usage: $var->decrypt($file_location);
    Returns: $file_location -- the original file has been decrypted and restored
  */

  class FileSecure extends Unique {
    
    public function encrypt($source) {
      $key = $this->_genHash(32);
      $ini = $this->_genHash(16);
      $iv  = $this->_genHash(16);
      $tiv = $iv;
      $err = false;
      $dir = dirname($source)."/";
      $fileName = basename($source);
      $fileExt = $this->_getExtension($source);
      $lock = TRUE;
      if(!rename($source, $dir."tkf.".$fileName)) {
          $err = true; // 101
          throw new Exception("Unable to create new file. Encryption failed.");
      }
      $fileInfo = openssl_encrypt($iv, 'AES-256-CBC', $key, OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING, $ini);
      if($fileFake = fopen($dir.$fileName, "w+")) {
          flock($fileFake, LOCK_EX, $lock);
          ftruncate($fileFake, 0);
          fwrite($fileFake, $key.$fileInfo);
          fflush($fileFake);
          flock($fileFake, LOCK_UN, $lock);
          fclose($fileFake);
      } else {
          $err = true; //102
          throw new Exception("Unable to create new file. Encryption failed.");
      }
      if($fileOut = fopen($dir.$key.".kef", "w")) {
          if($fileIn = fopen($dir."tkf.".$fileName, "rb")) {
              flock($fileOut, LOCK_EX, $lock);
              ftruncate($fileOut, 0);
              while(!feof($fileIn)) {
                  $dataIn = fread($fileIn, 16 * 10000);
                  $dataIn = $this->_pad($dataIn, 16);
                  $dataEn = openssl_encrypt($dataIn, 'AES-256-CBC', $key, OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING, $iv);
                  $iv = substr($dataEn, 0, 16);
                  fwrite($fileOut, $dataEn);
              }
              fflush($fileOut);
              flock($fileOut, LOCK_UN, $lock);
              fclose($fileIn);
              unlink($dir."tkf.".$fileName);
          } else {
              $err = true; // 103
              throw new Exception("Unable to read plain file. Encryption failed.");
          }
          fclose($fileOut);
          $stream = file_get_contents($dir.$key.".kef");
          file_put_contents($dir.$key.".kef", $ini.$stream);
      } else {
          $err = true; // 104
          throw new Exception("Unable to create new file. Encryption failed.");
      }
      return $err ? false : $source;
    }
  
    public function decrypt($source) {
      $lock = TRUE;
      if($fileFake = fopen($source, "rb")) {
          $data = fread($fileFake, filesize($source));
          $key = substr($data, 0, 32);
          $info = substr($data, 32, strlen($data));
          $dir = dirname($source)."/";
          fclose($fileFake);
      } else {
          $err = true; // 201
          throw new Exception("Unable to read plain file. Decryption failed.");
      }
      if(!rename($source, $source.".tkf")) {
          $err = true; // 202
          throw new Exception("Unable to write new file. Decryption failed.");
      } else if(!file_exists($source.".tkf")) {
          $err = true; // 203
          throw new Exception("Unable to write new file. Decryption failed.");
      }
      if($fileOut = fopen($source, "w")) {
          if($fileIn = fopen($dir.$key.".kef", "rb")) {
              $ini = fread($fileIn, 16);
              fclose($fileIn);
          } else {
              $err = true; // 204
              throw new Exception("Cannot open encrypted file. Decryption failed.");
          }
          $stream = file_get_contents($dir.$key.".kef");
          file_put_contents($dir.$key.".kef", str_replace($ini, "", $stream));
          if($fileIn = fopen($dir.$key.".kef", "rb")) { 
              $tmp = openssl_decrypt($info, 'AES-256-CBC', $key, OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING, $ini);
              $iv = $tmp;
              flock($fileOut, LOCK_EX, $lock);
              while(!feof($fileIn)) {
                  $dataEn = fread($fileIn, 16 * (10000 + 1));
                  $dataOut = openssl_decrypt($dataEn, 'AES-256-CBC', $key, OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING, $iv);
                  $dataOut = $this->_unpad($dataOut);
                  $iv = substr($dataEn, 0, 16);
                  fwrite($fileOut, $dataOut);
              }            
              fclose($fileIn);
              flock($fileOut, LOCK_UN, $lock);
              unlink($dir.$key.".kef");
          } else {
              $err = true; // 205
              rename($source.".tkf", $source);
              throw new Exception("Unable to read plain file. Decryption failed.");
          }
          fclose($fileOut);
          unlink($source.".tkf");
      } else {
          $err = true; // 206
          rename($source.".tkf", $source);
          throw new Exception("Unable to write to new file. Decryption failed.");
      }
      return isset($err) ? false : $source;
    }
    
    private function _getExtension($source) {
        $i = strrpos($source, '.');
        return substr($source, $i+1);
    }

    private function _genHash($length) {
      if(version_compare(PHP_VERSION, '7.1.0', '>=') {
        if(!$length || $length == "") return bin2hex(random_bytes(32));
        else return bin2hex(random_bytes($length));
      } else {
        if(!$length || $length == "") return substr(preg_replace('/([^A-Za-z0-9])/i', '', base64_encode(mcrypt_create_iv(32, MCRYPT_DEV_URANDOM))), 0, 32);
        else return substr(preg_replace('/([^A-Za-z0-9])/i', '', base64_encode(mcrypt_create_iv($length, MCRYPT_DEV_URANDOM))), 0, $length);
      }
    }
    
    private function _pad($data, $block_size) {
      $padding = $block_size - (strlen($data) % $block_size);
      $pattern = chr($padding);        
      return $data . str_repeat($pattern, $padding);
    }
    
    private function _unpad($data) {
      $pattern = substr($data, -1);
      $length = ord($pattern);
      $padding = str_repeat($pattern, $length);
      $pattern_pos = strlen($data) - $length;
      
      if(substr($data, $pattern_pos) == $padding)
        return substr($data, 0, $pattern_pos);
      
      return $data;
    }
  }
?>
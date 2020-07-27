<?php
  
  require_once 'cloudflare.php';
  
  $obj = new CloudFlare();
  $obj->removeCache(array('https://site.com/page1.html','https://site.com/page2.html')); 
    
?>

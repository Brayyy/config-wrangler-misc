<?php

include_once('./configWrangler.php');

$config = configWrangler(array(
  'etcdNameSpace' => 'cfg/flash-service/',
  'envNameSpace' => 'FLASH'
));

print_r($config);

?>
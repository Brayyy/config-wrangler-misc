<?php

include_once('./configWrangler.php');

$config = configWrangler(array(
  'etcdNameSpace' => 'cfg/flash-service/',
  'envNameSpace' => 'FLASH',
  'requiredKeys' => array('port', 'serverName', 'maxConnects')
));

print_r($config);

?>
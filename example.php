<?php
include_once('./configEEC.php');
$config = configEEC(array(
  'etcdNameSpace' => 'cfg/flash-service/',
  'envNameSpace' => 'FLASH'
));
print_r($config);
?>
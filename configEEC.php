<?php
// Show all errors (used during development)
// error_reporting(E_ALL);

/**
 * Import configuration values from Etcd, environment then CLI arguments, in that order. Vars are
 * overridden if they are redefined, allowing special config and developer control. The Etcd HTTP
 * API is configurable using the environment variable ETCD_CONN.
 *
 * @author Bray Almini <bray@coreforge.com>
 * @param mConfig['etcdNameSpace'] String of etcd namespace prefix to scan for. ex: 'cfg/vast-service/'
 * @param mConfig['envNameSpace'] String of ENV namespace prefix to scan for. ex: 'VAST'
 */
function configEEC ($mConfig) {
  $foundVars = array();
  /**
   * Convert a string to lowerCamelCase, optionally strip off a prefix
   * @param str String to be converted
   * @param prefix Prefix to strip from str
   */
  function stripLowerCamel ($str, $prefix) {
    // Optionally strip off prefix before conversion
    if ($prefix) $str = str_replace($prefix, '', $str);
    // Lowercase everything, replace all non 0-9a-z with a space,
    // ..uppercase words, lowercase first word, strip spaces.
    return str_replace(' ', '', lcfirst(ucwords(preg_replace("/[^0-9a-z]/", " ", strtolower($str)))));
  }

  // Etcd Vars
  if (array_key_exists('etcdNameSpace', $mConfig)) {
    // Http host+port to find etcd API
    $etcdConn = (array_key_exists('ETCD_CONN', $_ENV) ? $_ENV['ETCD_CONN'] : 'http://localhost:2379');
    $etcdPath = (array_key_exists('etcdApiPath', $mConfig) ? $mConfig['etcdApiPath'] : 'v3alpha');
    // Additional payload to go out with file_get_contents
    $context = stream_context_create(array(
      'http' => array(
        'header' => "Content-type: application/x-www-form-urlencoded\r\n",
        'method' => 'POST',
        'content' => json_encode(array(
          'key' => base64_encode($mConfig['etcdNameSpace']),
          'range_end' => base64_encode($mConfig['etcdNameSpace'] . 'zzz')
        ))
      )
    ));
    // Make range request with added context
    $result = file_get_contents("$etcdConn/$etcdPath/kv/range", false, $context);
    if ($result === FALSE) exit("ERROR: Unable to get config. Failed file_get_contents()\n");
    $decoded = json_decode($result, true);
    // Iterate over all found kvs, base64 decoding the key/value, then standardize the key
    if (array_key_exists('kvs', $decoded) && count($decoded['kvs']) > 0) {
      foreach ($decoded['kvs'] as $kv) {
        $key = stripLowerCamel(base64_decode($kv['key']), $mConfig['etcdNameSpace']);
        $foundVars[$key] = base64_decode($kv['value']);
      }
    }
  }

  // Environment Vars
  if (array_key_exists('envNameSpace', $mConfig)) {
    foreach ($_ENV as $envK => $envV) {
      // Skip env keys which don't start with prefix
      if (substr($envK, 0, strlen($mConfig['envNameSpace'])) !== $mConfig['envNameSpace']) continue;
      // Standardize the key
      $key = stripLowerCamel($envK, $mConfig['envNameSpace'] . '_');
      $foundVars[$key] = $envV;
    }
  }

  // Command line argument Vars
  global $argc, $argv;
  foreach ($argv as $arg) {
    // Skip args that don't start with "--"
    if (substr($arg, 0, 2) !== '--') continue;
    // Break apart key/value
    $argParts = explode('=', str_replace('--', '', $arg));
    // Standardize the key
    $key = stripLowerCamel($argParts[0], false);
    if ($arg[1]) $foundVars[$key] = $argParts[1];
  }

  return $foundVars;
}
?>
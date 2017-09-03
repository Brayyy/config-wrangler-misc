import os
import sys
import requests
import json
import base64
import re


# Import configuration values from Etcd, environment then CLI arguments, in
# that order. Vars are overridden if they are redefined, allowing special
# config and developer control. The Etcd HTTP API is configurable using the
# environment variable ETCD_CONN.
def configEEC(mConfig):
    '''Import configuration values from Etcd, environment then CLI arguments'''
    foundVars = {}

    def stripLowerCamel(string, prefix):
        '''Convert a string to lowerCamelCase, optionally strip off a prefix'''
        # Optionally strip off prefix before conversion
        if prefix != '':
            string = string.replace(prefix, '')
        # Lowercase everything, replace all non 0-9a-z with a space,
        # ..uppercase words, lowercase first word, strip spaces.
        string = string.lower()
        string = re.sub(r'[^0-9a-z]', ' ', string)
        string = string.title()
        string = string[0].lower() + string[1:]
        string = string.replace(' ', '')
        return string

    # Etcd Vars
    if 'etcdNameSpace' in mConfig:
        # Http host+port to find etcd API
        etcdConn = 'http://localhost:2379'
        if 'ETCD_CONN' in os.environ:
            etcdConn = os.environ['ETCD_CONN']
        etcdPath = 'v3alpha'
        if 'etcdApiPath' in mConfig:
            etcdPath = mConfig['etcdApiPath']
        # Craft URL, payload and headers
        url = etcdConn + "/" + etcdPath + "/kv/range"
        payload = {
            'key': base64.b64encode(mConfig['etcdNameSpace']),
            'range_end': base64.b64encode(mConfig['etcdNameSpace'] + 'zzzzz')
        }
        headers = {
            'Content-type': 'application/x-www-form-urlencoded'
        }
        #  Make range request with added context
        r = requests.post(url, data=json.dumps(payload), headers=headers)
        if r.status_code != 200:
            print "ERROR: Unable to get config."
            sys.exit(0)
        decoded = json.loads(r.text)
        # Iterate over all found kvs, base64 decoding the key/value
        # ...then standardize the key
        if 'kvs' in decoded and len(decoded['kvs']) > 0:
            for kv in decoded['kvs']:
                key = stripLowerCamel(
                    base64.b64decode(kv['key']),
                    mConfig['etcdNameSpace']
                )
                foundVars[key] = base64.b64decode(kv['value'])

    # Environment Vars
    if 'envNameSpace' in mConfig:
        for envK in os.environ:
            # Skip env keys which don't start with prefix
            envPrefix = mConfig['envNameSpace'] + '_'
            if not envK.startswith(envPrefix):
                continue
            # Standardize the key
            newKey = stripLowerCamel(envK, envPrefix)
            foundVars[newKey] = os.environ[envK]

    # Command line argument Vars
    for arg in sys.argv:
        # Skip args that don't start with "--"
        if not arg.startswith('--'):
            continue
        # Break apart key/value
        argParts = arg.replace('--', '').split('=')
        # Standardize the key
        newKey = stripLowerCamel(argParts[0], '')
        if len(argParts) == 2:
            foundVars[newKey] = argParts[1]

    return foundVars

# Config Wrangler Scripts
#### Load Etcd v3 keys, environment vars, and command line arguments in a predictable, standardized way.

I was searching for a simple, lightweight and standardized way to import config into projects, and to my surprise I couldn't find anything like that. So, I'm working on creating a collection of modules or includes for various languages I work in, in order to standardize the way that things are done.

Main goals:
- Load variables from Etcd v3, environment variables, command line arguments.
- Be extremely light weight, and have few or no dependencies.
- Simplify origin of keys in Etcd and Env. Each project should have it's own namespace, nothing shared.
- Predictable outcome between each language Config Wrangler is written in.
- Ability to reload sources by calling load function again.
- If the language supports async, have a watch function notify when a change occurs.

By using this module, variables are read from these sources in this order:
1. Etcd v3 keys
2. Environment variables
3. Command line arguments

Variables are overridden if they are redefined during that order. Meaning that if variable "port" is defined in Etcd, it can be overridden by ENV, and both Etcd and ENV can be overridden by command line argument. The key format is standardized, as to further reduce guesswork. Etcd keys are pretended by namespace, and are "lower-kebab-case". Environment variables are are prepended by namespace, and are "UPPER_SNAKE_CASE". Command line arguments are "lower-kebab-case", starting with "--". The config-wrangler module returns all keys as "camelCase", normalizing how config appears in code.

## Examples
Hypothetical web project is configured as so in Python or PHP:
```python
# Python...
from configWrangler import configWrangler
config = configWrangler({
    'etcdNameSpace': 'cfg/web-service/',
    'envNameSpace': 'WEBSVC',
    'requiredKeys': ['port', 'serverName', 'maxConnects']
})
```
```php
// PHP...
include_once('./configWrangler.php');
$config = configWrangler(array(
    'etcdNameSpace' => 'cfg/web-service/',
    'envNameSpace' => 'WEBSVC',
    'requiredKeys' => array('port', 'serverName', 'maxConnects')
));
```

Config is now available to project from three different sources:

| Etcd key | Env key | CLI key | Code result |
| - | - | - | - |
| cfg/web-service/port | WEBSVC_PORT | --port | config['port'] |
| cfg/web-service/server-name | WEBSVC_SERVER_NAME | --server-name | config['serverName'] |
| cfg/web-service/max-connects | WEBSVC_MAX_CONNECTS | --max-connects | config['maxConnects'] |
| cfg/web-service/time-out-ms | WEBSVC_TIME_OUT_MS | --time-out-ms | config['timeOutMs'] |

```bash
# Assuming Etcd has all of the above keys configured,
# they can be overridden by ENV by doing:
export WEBSVC_MAX_CONNECTS=100
export WEBSVC_SERVER_NAME="New staging server"
python someScript.py

# Or as a one-time env set
WEBSVC_PORT=8080 python someScript.py

# And they can be overridden again by using CLI arguments:
python someScript.py --max-connects=50 --server-name="Test server"
```

The configuration is now agnostic of the language of the script/service. The example above could have been PHP, Python or Node.js, being configured the same way.

## Config object options
| Key | Required | Description |
| - | - | - |
| etcdNameSpace | No | Namespace/prefix to search for keys in Etcd |
| envNameSpace | No | Namespace/prefix to search for keys in local environment |
| etcdApiPath | No | Etcd V3 JSON proxy is currently at "/v3alpha". Option is here if it changes |

## Other languages
Config Wrangler is available for the following languages:
- [Python](https://github.com/Brayyy/config-wrangler-misc) _(this project)_
- [PHP](https://github.com/Brayyy/config-wrangler-misc) _(this project)_
- [Node.js/JavaScript](https://github.com/Brayyy/config-wrangler-js)

## Notes
- The Etcd server can be overridden by using the environment variable `ETCD_CONN`, defaulting to `http://localhost:2379`.
- These scripts all make use of Etcd v3 gRPC JSON proxy. Currently, the gRPC proxy has a prefix of /v3alpha, which I don't expect to be dropped any time soon, but I'm making it configurable as well.
- I could have used gRPC without the JSON proxy, however that adds additional weight to the modules, and in some cases far outweighs the project I'm trying to augment.
- If a CLI variable is passed in with no value (--test), what should happen? Set to TRUE? be an empty string? Currently discards in Python and is empty string in PHP
- CLI arguments with a equal sign in the value don't parse correctly.
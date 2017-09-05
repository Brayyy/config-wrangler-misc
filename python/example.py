
from configWrangler import configWrangler

config = configWrangler({
    'etcdNameSpace': 'cfg/flash-service/',
    'envNameSpace': 'FLASH',
    'requiredKeys': ['port', 'serverName', 'maxConnects']
})

print config

# Proxy Mode Feature

## Status - not implemented

### Description

A proxy mode is required which will ignore the moodle ports in recipes. Instead, on upping an mchef instance, or on instantiating from the recipe, it will allocate a port that isn't currently being used or registered (starting from 8100). This port will be registered as an additional field in the registry.txt file in the global mchef config directory (should be final field, fields are delimited by pipe symbol). When not in proxy mode, registering an instance will set the port field as empty. E.g. of registry.txt file with entries in proxy mode:

67efc5cd86e4a|/Users/guy/Development/mchef_ally/moodle-recipe.json|ally|8100
68975349nkngf|/Users/guy/Development/mchef_xund/moodle-recipe.json|xund|8101

E.g. of registry.txt file when entries are added not in proxy mode:

67efc5cd86e4a|/Users/guy/Development/mchef_ally/moodle-recipe.json|ally|
68975349nkngf|/Users/guy/Development/mchef_xund/moodle-recipe.json|xund|

The RegistryInstance model will be updated to store the port in a proxyModePort

A new docker container (proxy container) will need to be run running nginx which implements the proxy. A file will need to be generated with virtual hosts corresponding to the site names in the recipes, and ports in registry.txt (recipe location is recipePath of RecipePath model). This file should be stored in the global config directory AND it should be volume mounted into the proxy container. Everytime the virtual hosts file for the proxy server is modified via an mchef command, the proxy server should be restarted and the cli should show a "restarting proxy server", "proxy server restarted" status.
The proxy container will run on port 80. It's purpose is to allow developers to run multiple ally instances on localhost without having to worry about port numbers.

Already implemented and working:
The proxy mode feature is enabled via the global config. This can already be done via the command line by running mchef config proxy.
The user then selects Y/N to enable / disable proxy mode. All this does is set the config.

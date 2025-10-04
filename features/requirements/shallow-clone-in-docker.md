# Proxy Mode Feature

## Status - Not started

## Github issue number - #34

## Description

Currently, volume mounts are being used to enable developers to work on code locally with instant updates. However, in addition to this, the dockerFile should ALWAYS be created so that it performs its own shallow (depth=1) clones of the plugins directly into the moodle directory. This means that if "cloneRepoPlugins" is set to false in the recipe, the docker image will still include the plugins.

The recipe "cloneRepoPlugins" should be renamed to avoid confusion - it should be replaced with "mountPlugins" to avoid confusion. The "cloneRepoPlugins" should be marked as deprecated and should show a warning if it is used. "mountPlugins" should work in exactly the same way "cloneRepoPlugins" worked.

Where to start - 

src/Model/Recipe.php:
cloneRepoPlugins is defined here and should be marked deprecated. Also, mountPlugins should be added here.

src/Service/Plugins.php:
This will need to spit out the deprecation warning for cloneRepoPlugins and will also have to respect the new mountPlugins.

templates/docker/main.dockerfile.twig:
This will need new code to do the shallow clones.

src/Service/Main.hp
This will need to get the list of plugins from the plugin service and then pass them into the dockerfile template. I.e. before the code below is reached, dockerData will need the plugin types and repo locations so that the dockerfile can do appropriate shallow clones.

        try {
            $dockerFileContents = $this->twig->render('@docker/main.dockerfile.twig', (array) $dockerData);
        } catch (\Exception $e) {
            throw new Exception('Failed to parse main.dockerfile template: '.$e->getMessage());
        }
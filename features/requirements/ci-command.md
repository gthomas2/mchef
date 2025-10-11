# Publish Feature

## Status - Not started

## Description

A new "CI" command is required which will initially have 1 argument "--publish"

E.g. mchef ci --publish=v1.5.0

The publish argument's value is the tag that will be applied to the build. It will do a build in the same way that the main service currently does EXCEPT it will override the following recipe fields

cloneRepoPlugins: false
mountPlugins: false
developer: false
includePhpUnit: false
includeBehat: false
includeXdebug: false

Once completed, it will tag this build with the argument value and publish it to a docker hub or github packages.
It will use environment variables for the type of repo, credentials, etc. The idea is that you will set these variables in a github repository and then you can use the ci command as part of a github workflow.
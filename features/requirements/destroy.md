# Destroy Feature

## Status - Not started

## Description

A new "destroy" command is required which will:

Require an argument of the instance name of the mchef project you will like to destroy. Regardless of the active instance (set by use, etc), you must supply the instance name.
It will prompt you with "All associated containers / data will be destroyed, are you sure Y/N"

On selecting yes it will destroy all containers for the selected instance (moodle, db). It will also destroy any volumes. 
Finally, it will deregister the selected instance.
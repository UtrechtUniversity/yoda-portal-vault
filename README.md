yoda-portal-research
====================

NAME
----

yoda-portal-research - Research module for Yoda.

DESCRIPTION
---
This research module was created for the Institutions I-lab project, but can be configured to allow the module to work for other projects that wish to make use of Yoda.

The module allows defining a schema in XML format, that generates a highly versatile meta data editor form for online editing of iRODS metadata. 

The module provides a detailed file overview of all projects a user is a member of inside the Yoda environment.

The allows for creating versions; direct copies of a datapackage which is safely stored in the vault, where it can no longer be changed, but can always be read or copied.

INSTALLATION
------
You can use the Yoda install script to add modules to the Yoda environment.
In the following example, $home is used for the root of the Yoda portal (where the directories `controllers`, `models`, etc. are)
```sh
$ /bin/bash $home/tools/add-module.sh https://github.com/UtrechtUniversity/yoda-portal-research research
```
The module will be installed in `$home/modules/research`.

### Installing with a different name
In the above instructions, it is assumed the module should be called _research_. In case this name should be different, replace the argument `research` in the above example call to `add-module.sh` to the name you wish to use. It is best practive to use lower case letters and lower dashes (`_`) only.

Next, create the file `module_local.php` in the `config` directory of the module, and copy the contents from `module.php` to this file. Change the `name` key to be the same value you used in your call to `add-module.sh` and pick any name you want for the `label`.

LICENSE
-------
Copyright (c) 2015-2018, Utrecht University. All rights reserved.

This project is licensed under the GPLv3 license.

The full license text can be found in [LICENSE](LICENSE).


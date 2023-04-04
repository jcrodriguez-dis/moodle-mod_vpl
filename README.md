# VPL - Virtual Programming Lab for Moodle

![VPL Logo](https://vpl.dis.ulpgc.es/images/logo2.png)

VPL is the easy way to manage programming assignments in Moodle.

Its features of editing, running and evaluation of programs makes learning process
for students, and the evaluation task for teachers, easier than ever.

It's free and its code is available at GitHub. To see VPL in action visite our demo site.
This software is distributed under the terms of the GNU General
Public License version 3 (see http://www.gnu.org/licenses/gpl.txt for details)

This software is provided "AS IS" without a warranty of any kind.

For more details about VPL, visit the [VPL home page](http://vpl.dis.ulpgc.es) or
the [VPL plugin page at Moodle](http://www.moodle.org/plugins/mod_vpl).

# Using Docker

1. Build vpl-jail image with [VPL Jail](https://github.com/jcrodriguez-dis/vpl-xmlrpc-jail) with any desired variable:
```
docker build -t vpl-jail .
```
2. Build composition and wait for installation with any desired variable:
```
docker-compose up --build
```
3. Start docker, finish installation of vpl and then change jail configuration. By default, you need to put:
  * Execution servers list:
  ```
    http://localhost:81/secret
  ```
  * proxy:
  ```
    jail:81/secret
  ```
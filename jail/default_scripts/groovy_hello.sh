#!/bin/bash
# This file is part of VPL for Moodle
# Groovy language hello source code
# Copyright 2021 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

cat >Groovy_hello.groovy <<'END_OF_FILE'
import groovy_message.Show
def message = System.in.newReader().readLine()
Show.print(message)
END_OF_FILE
mkdir groovy_message
cat >groovy_message/Show.groovy <<'END_OF_FILE'
package groovy_message
class Show {
    static def print(String text) {
        println text
    }
}
END_OF_FILE

export VPL_SUBFILE0=Groovy_hello.groovy
export VPL_SUBFILE1=groovy_message/Show.groovy
export VPL_SUBFILES="Groovy_hello.groovy groovy_message/Show.groovy"
export INPUT_TEXT="Hello from the Groovy language!"

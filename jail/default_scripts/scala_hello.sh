#!/bin/bash
# This file is part of VPL for Moodle
# Scala language Hello source code
# Copyright 2015 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

cat >vpl_hello_scala.scala <<END_OF_FILE
object vpl_hello_scala {
    def main(args: Array[String]) {
        println("Hello from the Scala language!")
    }
}
END_OF_FILE
export VPL_SUBFILE0=vpl_hello_scala.scala

#!/bin/bash
# This file is part of VPL for Moodle
# Go language Hello source code
# Copyright 2015 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

cat >vpl_hello.go <<'END_OF_FILE'
package main
import "fmt"
func main() {
    fmt.Println("Hello from the Go language!")
}
END_OF_FILE
export VPL_SUBFILE0=vpl_hello.go

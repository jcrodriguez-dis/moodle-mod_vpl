#!/bin/bash
# This file is part of VPL for Moodle
# Go language Hello source code
# Copyright 2015 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

cat > "vpl hello.go" <<'END_OF_FILE'
package main
import (
	"bufio"
	"fmt"
	"os"
)
func Hello() {
	reader := bufio.NewReader(os.Stdin)
    text, _ := reader.ReadString('\n')
    fmt.Print(text)
}
func main() {
    Hello()
}
END_OF_FILE

export VPL_SUBFILE0="vpl hello.go"
export INPUT_TEXT="Hello from the Go language!"

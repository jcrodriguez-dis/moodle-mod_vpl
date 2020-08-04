#!/bin/bash
# This file is part of VPL for Moodle
# Go language Hello source code
# Copyright 2015 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

mkdir "src" 2> /dev/null
mkdir "src/message" 2> /dev/null

cat > "src/vpl hello.go" <<'END_OF_FILE'
package main
import "message"
func main() {
    message.Hello()
}
END_OF_FILE

cat > "src/message/hello.go" <<'END_OF_FILE'
package message
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
END_OF_FILE
export VPL_SUBFILE0="src/vpl hello.go"
export INPUT_TEXT="Hello from the Go language!"

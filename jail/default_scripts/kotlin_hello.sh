#!/bin/bash
# This file is part of VPL for Moodle
# Kotlin language Hello source code
# Copyright 2021 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

cat >vpl_hello.kt <<'END_OF_FILE'
fun main() {
    hello()
}
END_OF_FILE

mkdir "hello kotlin" 2> /dev/null
cat > "hello kotlin/Message.kt" <<'END_OF_FILE'
fun hello() {
    println(readLine())
}
END_OF_FILE
export VPL_SUBFILE0="vpl_hello.kt"
export VPL_SUBFILE1="hello kotlin/Message.kt"
export INPUT_TEXT="Hello from the Kotlin language!"

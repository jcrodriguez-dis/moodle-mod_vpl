#!/bin/bash
# This file is part of VPL for Moodle
# Haskell language hello source code
# Copyright 2015 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

mkdir Message 2>/dev/null
cat >"vpl hello.hs" <<'END_OF_FILE'
import Message.Hello
main = Message.Hello.hello

END_OF_FILE

cat >"Message/Hello.hs" <<'END_OF_FILE'
module Message.Hello (hello) where
hello = do
    text <- getLine
    putStrLn text
END_OF_FILE

export VPL_SUBFILE0="vpl hello.hs"
export INPUT_TEXT="Hello from the Haskell language!"
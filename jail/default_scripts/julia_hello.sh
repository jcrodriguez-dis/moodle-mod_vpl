#!/bin/bash
# This file is part of VPL for Moodle
# Julia language Hello source code
# Copyright 2022 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

cat >vpl_hello.jl <<'END_OF_FILE'
include("hello julia/Message.jl")
message = readline()
hello(message)
END_OF_FILE

mkdir "hello julia" 2> /dev/null
cat > "hello julia/Message.jl" <<'END_OF_FILE'
function hello(x)
    println(x)
end
END_OF_FILE
export VPL_SUBFILE0="vpl_hello.jl"
export VPL_SUBFILE1="hello kotlin/Message.jl"
export INPUT_TEXT="Hello from the Julia language!"

#!/bin/bash
# This file is part of VPL for Moodle
# Erlang language hello source code
# Copyright (C) Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# Note: Erlang do not support module file names with spaces,
#       but can include code from file names with spaces.

mkdir "test erlang" 2> /dev/null

cat > "test erlang/vpl_hello.erl" <<'END_OF_FILE'
-module(vpl_hello).
-export([main/1]).
-include("vpl message.erl").
main([]) ->
    hello().
END_OF_FILE

cat > "test erlang/vpl message.erl" <<'END_OF_FILE'
hello() ->
    io:format(io:get_line("")).
END_OF_FILE

export VPL_SUBFILE0="test erlang/vpl_hello.erl"
export VPL_SUBFILE1="test erlang/vpl message.erl"
export INPUT_TEXT="Hello from the Erlang language!"

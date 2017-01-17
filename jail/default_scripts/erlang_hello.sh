#!/bin/bash
# This file is part of VPL for Moodle
# Erlang language hello source code
# Copyright (C) Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

cat >vpl_hello.erl <<'END_OF_FILE'
-module(vpl_hello).
-export([main/1]).
main([]) ->
    io:format("Hello from the Erlang language!\n").
END_OF_FILE
export VPL_SUBFILE0=vpl_hello.erl

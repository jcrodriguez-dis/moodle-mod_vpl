#!/bin/bash
# This file is part of VPL for Moodle
# VHDL language Hello source code
# Copyright 2023 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

cat >vpl_hello.vhdl <<'END_OF_FILE'
entity vpl_hello is
end entity;

library STD;
use STD.textio.all;

architecture message of vpl_hello is
begin
    process is
    variable hello : line;
    begin
        write(hello, string'("Hello from VHDL language!"));
        writeline(output, hello);
        wait;
    end process;
end message;
END_OF_FILE
# Notes from gvhdl documentation
# The first VHDL file name determines the name of the simulator executable.
# The object files as well as the simulator will be created in the current directory.
export VPL_SUBFILE0=vpl_hello.vhdl


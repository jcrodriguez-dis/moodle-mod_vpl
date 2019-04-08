#!/bin/bash
# This file is part of VPL for Moodle
# VHDL language Hello source code
# Copyright 2018 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

cat >vpl_hello.vhdl <<'END_OF_FILE'
entity vpl_hello is
end entity;
 
architecture sim of vpl_hello is
begin
    process is
    begin
        report "Hello from VHDL!";
        wait;
    end process;
end architecture;

END_OF_FILE
export VPL_SUBFILE0=vpl_hello.vhdl
export VPL_SUBFILES=vpl_hello.vhdl

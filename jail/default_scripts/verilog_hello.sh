#!/bin/bash
# This file is part of VPL for Moodle
# Verilog language Hello source code
# Copyright 2016 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

cat >vpl_hello.v <<'END_OF_FILE'
module vpl_hello;
  initial
  begin
     $display("Hello from the Verilog language!");
  end
endmodule

END_OF_FILE
export VPL_SUBFILE0=vpl_hello.v
export VPL_SUBFILES=vpl_hello.v

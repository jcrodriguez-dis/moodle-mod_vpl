#!/bin/bash
# This file is part of VPL for Moodle
# Verilog language Hello source code
# Copyright 2016 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

cat >"vpl hello.v" <<'END_OF_FILE'
module vpl_hello;
    message hello();
endmodule
END_OF_FILE
mkdir "test verilog" 2>/dev/null
cat >"test verilog/message.v" <<'END_OF_FILE'
module message;
  initial $display("Hello from the Verilog language!");
endmodule
END_OF_FILE
export VPL_SUBFILE0="vpl hello.v"
export VPL_SUBFILE1="test verilog/message.v"

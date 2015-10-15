#!/bin/bash
# $Id: vhdl_run.sh,v 1.3 2012-07-25 19:02:19 juanca Exp $
# Default VHDL language run script for VPL

cat >vpl_hello.vhdl << END_OF_FILE
use std.textio.all;

entity vpl_hello is
end vpl_hello;

architecture Main of vpl_hello is
begin
       p : process
       variable l:line;
       begin
               write(l, String'("Hello from the VHDL language!"));
               writeline(output, l);
               wait;
       end process;
end Main;



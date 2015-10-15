#!/bin/bash
# $Id: ada_run.sh,v 1.3 2012-07-25 19:02:20 juanca Exp $
# ADA language hello source code

cat >vpl_hello.adb <<END_OF_FILE
with ada.text_io;
use ada.text_iO;
procedure vpl_hello is
begin
    put_line("Hello from the Ada language!");
end vpl_hello;
END_OF_FILE

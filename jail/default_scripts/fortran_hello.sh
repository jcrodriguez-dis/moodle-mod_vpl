#!/bin/bash
# $Id: ada_run.sh,v 1.3 2012-07-25 19:02:20 juanca Exp $
# Fortran language hello source code

cat >vpl_hello.f <<END_OF_FILE
       PRINT *, "Hello from the Fortran language!"
       END
END_OF_FILE

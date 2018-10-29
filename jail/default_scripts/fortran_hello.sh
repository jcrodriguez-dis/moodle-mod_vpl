#!/bin/bash
# This file is part of VPL for Moodle
# Fortran language hello source code
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

mkdir "test fortran" 2> /dev/null

cat > "test fortran/vpl hello.f90" <<'END_OF_FILE'
program vpl_hello
	call hello()
end program vpl_hello
END_OF_FILE

cat > "test fortran/vpl message.f90" <<'END_OF_FILE'
subroutine hello()
	PRINT *, "Hello from the Fortran language!"
end subroutine hello
END_OF_FILE

export VPL_SUBFILE0="test fortran/vpl hello.f90"
export VPL_SUBFILE1="test fortran/vpl message.f90"

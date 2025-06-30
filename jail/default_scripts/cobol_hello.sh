#!/bin/bash
# This file is part of VPL for Moodle
# C++ Hello
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

mkdir "test_cobol" 2> /dev/null

cat > "test_cobol/hello_message.cbl" <<'END_OF_FILE'
       IDENTIFICATION DIVISION.
       PROGRAM-ID. Message.

       DATA DIVISION.
       WORKING-STORAGE SECTION.
       01 WS-INPUT-LINE PIC X(70).

       PROCEDURE DIVISION.
           ACCEPT WS-INPUT-LINE.
           DISPLAY WS-INPUT-LINE.
           EXIT PROGRAM.
END_OF_FILE

cat > "test_cobol/vpl_hello.cbl" <<'END_OF_FILE'
       IDENTIFICATION DIVISION.
       PROGRAM-ID. HelloProgram.

       PROCEDURE DIVISION.
           CALL 'Message'.
           STOP RUN.
END_OF_FILE

export VPL_SUBFILE0="test_cobol/vpl_hello.cbl"
export VPL_SUBFILE1="test_cobol/hello_message.cbl"
export INPUT_TEXT="Hello from the Cobol language!"

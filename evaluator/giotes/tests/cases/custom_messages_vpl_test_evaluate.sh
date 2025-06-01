#!/bin/bash
if [ -s "$VPLTESTERRORS" ] ; then
    exit 1
fi

# Correct output
assertOutput "-Prueba_1_de_6_CORRECTOUTPUT_BIEN"
assertOutput "Correcto_5↵-16↵-16↵"

# Wrong output
assertOutput "-Prueba_2_de_6_OUTPUTFAIL_MAL"
assertOutput "^>3$"
assertOutput "^output_expected:"
assertOutput "^>55$"
assertOutput "^MAL-BIEN-TIMEOUT-ERROR$"

# Wrong output inline
assertOutput "-Prueba_3_de_6_OUTPUTFAILINLINE_MAL"
assertOutput "^i:1↵-66↵-4↵$"
assertOutput "^3_OUTPUTFAILINLINE_MAL_MAL_BIEN"
assertOutput "ERROR_TIMEOUT_-1_0_3"
assertOutput "_3---OUTPUTFAILINLINE----_"

# Wrong exit code
assertOutput "-Prueba_4_de_6_EXITCODEFAIL_MAL"
assertOutput "fail_exit_code_4_4_5"
assertOutput "^EXITCODEFAIL--1↵-O↵--2↵$"

# Timeout
assertOutput "-Prueba_5_de_6_TIMEOUTFAIL_TIMEOUT"
assertOutput "Fin tiempo: esperando 0.33 segundos"

# Execution error
assertOutput "-Prueba_6_de_6_ERRORFAIL_ERROR"
assertOutput "no_existe"

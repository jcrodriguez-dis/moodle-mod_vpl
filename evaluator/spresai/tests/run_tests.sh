#!/bin/bash
# This file is part of VPL for Moodle
# Script for test GIOTES for VPL
# Copyright (C) 2025 onwards Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
# This script is used to test the GIOTES plugin for VPL
# It runs the test cases and evaluates the results

CHECK_MARK="✅";
X_MARK="❌";
function writeHeading {
	echo -e "\e[37;42m RUN \e[39;49m \e[34m$1\e[39m"
}
function writeInfo {
	echo -e $3 "\e[42m$1\e[0m$2"
}
function writeError {
	echo -e "\e[31m$1\e[0m$2"
}
function write {
	echo -e "$1"
}

export -f writeHeading
export -f writeInfo
export -f writeError
export -f write

function assertOutput {
	grep -e "$1" "$VPLTESTOUTPUT" >/dev/null
	[ $? -eq 0 ] && return 0
	write
	writeError "Not found: " "\"$1\" not found in output result"
	exit 1
}

function assertOutputFalse {
	grep -e "$1" "$VPLTESTOUTPUT" >/dev/null
	[ $? -ne 0 ] && return 0
	write
	writeError "Found: " "\"$1\" found in output result"
	exit 1
}

function assertErrors {
	grep -e "$1" "$VPLTESTERRORS" >/dev/null
	[ $? -eq 0 ] && return 0
	write
	writeError "Not found: " "\"$1\" not found in output errors (stderr)"
	exit 1
}

writeHeading "Testing SPRESAI: plugin for evaluating student's submissions on VPL for Moodle"
writeHeading "with AI models using LiteLLM"

python3 spresai_test.py

#!/bin/bash
# This file is part of VPL for Moodle
# C Hello
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

mkdir "test c" 2> /dev/null

cat > "test c/vpl hello.c" <<'END_OF_FILE'
void hello();
int main(){
	hello();
	return 0;
}

END_OF_FILE
cat > "test c/hello.c" <<'END_OF_FILE'
#include <stdio.h>
void hello(){
	char text[256];
	fgets(text, 255, stdin);
	printf("%s", text);
}

END_OF_FILE
export VPL_SUBFILE0="test c/vpl hello.c"
export VPL_SUBFILE1="test c/hello.c"
export INPUT_TEXT="Hello from the C language!"

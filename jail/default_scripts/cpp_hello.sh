#!/bin/bash
# This file is part of VPL for Moodle
# C++ Hello
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

mkdir "test cpp" 2> /dev/null

cat > "test cpp/vpl hello.cpp" <<'END_OF_FILE'
void hello();
int main(){
	hello();
	return 0;
}
END_OF_FILE

cat > "test cpp/hello.cpp" <<'END_OF_FILE'
#include <iostream>
#include <string>
using namespace std;
void hello(){
	string text;
	getline(cin, text);
	std::cout << text  << std::endl;
}
END_OF_FILE

export VPL_SUBFILE0="test cpp/vpl hello.cpp"
export INPUT_TEXT="Hello from the C++ language!"

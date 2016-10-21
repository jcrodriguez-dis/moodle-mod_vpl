#!/bin/bash
# This file is part of VPL for Moodle
# C++ Hello
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>


cat >vpl_hello.cpp <<'END_OF_FILE'
#include <iostream>
int main(){
	std::cout << "Hello from the C++ language!" << std::endl;
	return 0;
}
END_OF_FILE
export VPL_SUBFILE0=vpl_hello.cpp

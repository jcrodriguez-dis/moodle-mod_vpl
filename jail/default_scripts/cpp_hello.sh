#!/bin/bash
# $Id: ada_run.sh,v 1.3 2012-07-25 19:02:20 juanca Exp $
# C++ language hello source code

cat >vpl_hello.cpp <<END_OF_FILE
#include <iostream>
int main(){
	std::cout << "Hello from the C++ language!";
	return 0;
}
END_OF_FILE

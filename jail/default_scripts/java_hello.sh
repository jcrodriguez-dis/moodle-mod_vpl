#!/bin/bash
# $Id: ada_run.sh,v 1.3 2012-07-25 19:02:20 juanca Exp $
# Java language hello source code

cat >vpl_hello.java <<END_OF_FILE
public class vpl_hello {
    public static void main(String[] args) {
        System.out.println("Hello from the Java language!");
    }
}
END_OF_FILE

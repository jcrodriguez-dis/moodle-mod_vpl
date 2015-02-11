#!/bin/bash
# Copyright (C) 2011 onwards Juan Carlos Rodríguez-del-Pino
# Java language hello source code

cat >vpl_hello.java <<END_OF_FILE
public class vpl_hello {
    public static void main(String[] args) {
        System.out.println("Hello from the Java language!");
    }
}
END_OF_FILE

#!/bin/bash
# This file is part of VPL for Moodle
# Java language hello source code
# Copyright 2015 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

if [ "$1" == "gui" ] ; then
cat >vpl_hello.java <<'END_OF_FILE'
import javax.swing.JOptionPane;
public class vpl_hello {
    public static void main(String[] args) {
        JOptionPane.showMessageDialog(null, "Hello from the Java language!"
                                     , "VPL", JOptionPane.INFORMATION_MESSAGE);
    }
}
END_OF_FILE
else
cat >vpl_hello.java <<'END_OF_FILE'
public class vpl_hello {
    public static void main(String[] args) {
        System.out.println("Hello from the Java language!");
    }
}
END_OF_FILE
fi
export VPL_SUBFILE0=vpl_hello.java

#!/bin/bash
# This file is part of VPL for Moodle
# Java language hello source code
# Copyright 2015 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

cat >Vpl_hello.java <<'END_OF_FILE'
import message.Hello;
public class Vpl_hello {
    public static void main(String[] args) throws Exception {
    	Hello.hello();
    }
}
END_OF_FILE

if [ "$1" == "gui" ] ; then
	
mkdir "message" 2> /dev/null
cat >message/Hello.java <<'END_OF_FILE'
package message;
import javax.swing.JOptionPane;
public class Hello {
    public static void hello() {
        JOptionPane.showMessageDialog(null, "Hello from the Java language!"
                                     , "VPL", JOptionPane.INFORMATION_MESSAGE);
    }
}
END_OF_FILE

else
	
mkdir "message" 2> /dev/null
cat >message/Hello.java <<'END_OF_FILE'
package message;
public class Hello {
    public static void hello() throws Exception {
    	java.util.Scanner reader = new java.util.Scanner(System.in);
        System.out.println(reader.nextLine());
    }
}
END_OF_FILE

fi

export VPL_SUBFILE0=Vpl_hello.java
export VPL_SUBFILE1=message/Hello.java
export VPL_SUBFILES="Vpl_hello.java message/Hello.java"
export INPUT_TEXT="Hello from the Java language!"

#!/bin/bash
# This file is part of VPL for Moodle
# Scala language Hello source code
# Copyright 2015 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

cat >vpl_hello.scala <<'END_OF_FILE'
import test_scala.Message.hello
object vpl_hello {
    def main(args: Array[String]) {
    	hello();
    }
}
END_OF_FILE

mkdir "test_scala" 2> /dev/null

if [ "$1" == "gui" ] ; then
cat > "test_scala/Message.scala" <<'END_OF_FILE'
package test_scala
import javax.swing.JOptionPane
object Message {
    def hello() {
    	JOptionPane.showMessageDialog(null, "Hello from the Scala language!"
                                     , "VPL", JOptionPane.INFORMATION_MESSAGE);
    }
}
END_OF_FILE
else
cat >"test_scala/Message.scala" <<'END_OF_FILE'
package test_scala
object Message {
    def hello() {
    	println(scala.io.StdIn.readLine())
    }
}
END_OF_FILE
fi
export VPL_SUBFILE0="vpl_hello.scala"
export VPL_SUBFILE1="test_scala/Message.scala"
export INPUT_TEXT="Hello from the Scala language!"
#!/bin/bash
# This file is part of VPL for Moodle
# C# Hello
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

mkdir "test_visualbasic" 2> /dev/null

cat > "test_visualbasic/main.vb" <<'END_OF_FILE'
Imports System
Module Program
    Sub Main(args As String())
        Dim helloMessage As String = Console.ReadLine()
        Message.show(helloMessage)
    End Sub
End Module

END_OF_FILE
	
cat > "test_visualbasic/message.vb" <<'END_OF_FILE'
Imports System
Module Message
    Sub Show(text As String)
        Console.WriteLine(text)
    End Sub
End Module
END_OF_FILE

export INPUT_TEXT="Hello from the VisualBasic language!"

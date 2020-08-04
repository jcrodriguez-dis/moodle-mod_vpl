#!/bin/bash
# This file is part of VPL for Moodle
# C# Hello
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

mkdir "test csharp" 2> /dev/null

cat > "test csharp/vpl hello.cs" <<'END_OF_FILE'
using static Message;
public class vpl_hello
{
   public static void Main()
   {
      hello();
   }
}
END_OF_FILE

if [ "$1" == "gui" ] ; then

cat > "test csharp/a Message.cs" <<'END_OF_FILE'
using System.Windows.Forms;
public class Message
{
   public static void hello()
   {
     MessageBox.Show("Hello from the C# language!","VPL"
      ,MessageBoxButtons.OK, MessageBoxIcon.Information);
   }
}
END_OF_FILE

else
	
cat > "test csharp/a Message.cs" <<'END_OF_FILE'
public class Message
{
   public static void hello()
   {
      System.Console.WriteLine( System.Console.ReadLine() );
   }
}
END_OF_FILE

fi

export VPL_SUBFILE0="test csharp/vpl hello.cs"
export VPL_SUBFILE1="test csharp/a Message.cs"
export INPUT_TEXT="Hello from the C# language!"

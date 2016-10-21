#!/bin/bash
# This file is part of VPL for Moodle
# C# Hello
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

if [ "$1" == "gui" ] ; then
cat >vpl_hello.cs <<'END_OF_FILE'
using System.Windows.Forms;
public class vpl_hello
{
   public static void Main()
   {
      MessageBox.Show("Hello from the C# language!","VPL"
      ,MessageBoxButtons.OK, MessageBoxIcon.Information);
   }
}
END_OF_FILE
else
cat >vpl_hello.cs <<'END_OF_FILE'
public class vpl_hello
{
   public static void Main()
   {
      System.Console.WriteLine("Hello from the C# language!");
   }
}
END_OF_FILE
fi
export VPL_SUBFILE0=vpl_hello.cs

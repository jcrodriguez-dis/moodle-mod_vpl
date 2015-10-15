#!/bin/bash
# $Id: ada_run.sh,v 1.3 2012-07-25 19:02:20 juanca Exp $
# C# language hello source code

cat >vpl_hello.cs <<END_OF_FILE
public class vpl_hello
{
   public static void Main()
   {
      System.Console.WriteLine("Hello from the C# language!");
   }
}
END_OF_FILE

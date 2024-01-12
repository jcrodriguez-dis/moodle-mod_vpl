#!/bin/bash
# This file is part of VPL for Moodle
# C# Hello
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

mkdir "test_fsharp" 2> /dev/null

cat > "test_fsharp/main.fs" <<'END_OF_FILE'
open System

[<EntryPoint>]
let main argv =
    let inputLine = Console.ReadLine()
    Message.show inputLine
    0 // Return an integer exit code
END_OF_FILE
	
cat > "test_fsharp/message.fs" <<'END_OF_FILE'
module Message
    open System
    let show(m:string) =
        Console.WriteLine m
END_OF_FILE
export DOTNET_CLI_TELEMETRY_OPTOUT=1
export DOTNET_RUNNING_IN_CONTAINER=1
export DOTNET_EnableWriteXorExecute=0
export DOTNET_NOLOGO=1
DOTNET_VERSION=$(dotnet --version | grep -o "^...")

cat > "test_fsharp/hello_vpl.fsproj" <<END_OF_FILE
<Project Sdk="Microsoft.NET.Sdk">
	<PropertyGroup>
		<OutputType>Exe</OutputType>
		<TargetFramework>net$DOTNET_VERSION</TargetFramework>
	</PropertyGroup>
	<ItemGroup>
        <Compile Include="message.fs" />
        <Compile Include="main.fs" />
	</ItemGroup>
</Project>
END_OF_FILE

export INPUT_TEXT="Hello from the F# language!"

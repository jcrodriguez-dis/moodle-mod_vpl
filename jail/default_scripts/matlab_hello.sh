#!/bin/bash
# This file is part of VPL for Moodle
# Matlab/Octave language hello source code
# Copyright 2015 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

if [ "$1" == "gui" ] ; then
cat >vpl_hello.m <<'END_OF_FILE'
x = -10:0.1:10;
plot(x,cos(x));
title("VPL running Matlab/Octave");
figure;
input("Continue");
exit();
END_OF_FILE
else
cat >vpl_hello.m <<'END_OF_FILE'
fprintf("Hello from Matlab/Octave!\n");
END_OF_FILE
fi
export VPL_SUBFILE0=vpl_hello.m

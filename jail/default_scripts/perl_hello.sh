#!/bin/bash
# This file is part of VPL for Moodle
# Perl language hello source code
# Copyright 2015 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

cat >"vpl hello.perl" <<'END_OF_FILE'
use lib '.';
use Perl_test::Message;
Perl_test::Message::hello();
END_OF_FILE

mkdir "Perl_test" 2> /dev/null

cat >"Perl_test/Message.pm" <<'END_OF_FILE'
package Perl_test::Message;

sub hello {
   $text = readline(STDIN);
   print $text;
}

1;
END_OF_FILE

export VPL_SUBFILE0="vpl hello.perl"
export VPL_SUBFILE1="Perl_test/Message.pm"
export INPUT_TEXT="Hello from the Perl language!"
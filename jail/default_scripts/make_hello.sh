#!/bin/bash
# This file is part of VPL for Moodle
# Makefile Hello
# Copyright (C) 2012 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

cat > "Makefile" <<'END_OF_FILE'
# Define the target executable
TARGET = hello
hello:
	@echo '#!/bin/bash' > hello
	@echo 'make -f Makefile_hello' >> hello
	@chmod +x hello

END_OF_FILE

cat > "Makefile_hello" <<'END_OF_FILE'
# Define default target
TARGET = hello
all: $(TARGET)
$(TARGET):
	@(read message; echo $$message )
	@touch hello

END_OF_FILE

export INPUT_TEXT="Hello from make builder!"

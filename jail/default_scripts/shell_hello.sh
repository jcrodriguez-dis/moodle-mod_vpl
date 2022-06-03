#!/bin/bash
# This file is part of VPL for Moodle
# Shell (Bash) language Hello source code
# Copyright 2018 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

cat >"vpl hello.sh" <<'END_OF_FILE'
#!/bin/bash
exec "test shell/vpl message.sh"
END_OF_FILE
chmod +x "vpl hello.sh"

mkdir "test shell"

cat >"test shell/vpl message.sh" <<'END_OF_FILE'
#!/bin/bash
read TEXT
echo $TEXT
END_OF_FILE

chmod +x "test shell/vpl message.sh"

export VPL_SUBFILE0="vpl hello.sh"
export VPL_SUBFILE1="test shell/vpl message.sh"
export INPUT_TEXT="Hello from the Shell (Bash) language!"

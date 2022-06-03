#!/bin/bash
# This file is part of VPL for Moodle
# Clojure language Hello source code
# Copyright 2015 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

mkdir "clojure test" 2> /dev/null

cat > "clojure test/vpl hello.clj" <<'END_OF_FILE'
(load-file "clojure test/a message.clj" )
(hello)
END_OF_FILE

cat > "clojure test/a message.clj" <<'END_OF_FILE'
(defn hello []
    (println (read-line))
)
END_OF_FILE

export VPL_SUBFILE0="clojure test/vpl hello.clj"
export VPL_SUBFILE1="clojure test/a message.clj"
export INPUT_TEXT="Hello from the Clojure language!"

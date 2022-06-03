#!/bin/bash
# Default JavaScript language run script for VPL
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author  Juan Carlos Rodriguez-del-pino
#load common script and check programs

cat >"vpl_tshello.ts" <<'END_OF_FILE'
import { message } from "./typescript test/message";
message('Hello from the TypeScript language!');
END_OF_FILE

mkdir "typescript test" 2>/dev/null

cat >"typescript test/message.ts" <<'END_OF_FILE'
export function message(text :string) {
    console.log(text);
}

END_OF_FILE

export VPL_SUBFILE0="vpl_tshello.ts"
export VPL_SUBFILE1="typescript test/message.ts"

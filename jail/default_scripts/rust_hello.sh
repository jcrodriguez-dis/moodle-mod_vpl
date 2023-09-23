#!/bin/bash
# This file is part of VPL for Moodle
# Rust Hello program code
# Copyright (C) 2023 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

mkdir "test rust"
cat > "test rust/vpl_hello.rs" <<'END_OF_FILE'
mod vpllib;
fn main(){
	vpllib::hello();
}
END_OF_FILE
cat > "test rust/vpllib.rs" <<'END_OF_FILE'
use std::io::stdin;
pub fn hello(){
    let mut message = String::new();
    stdin().read_line(&mut message).expect("Error reading hello");
    message = message.trim_end().to_string();
    println!("{}", message);
}

END_OF_FILE
export VPL_SUBFILE0="test rust/vpl_hello.rs"
export VPL_SUBFILE1="test rust/hello.rs"
export INPUT_TEXT="Hello from the Rust language!"

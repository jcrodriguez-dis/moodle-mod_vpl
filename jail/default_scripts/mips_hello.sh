#!/bin/bash
# This file is part of VPL for Moodle
# MIPS R2000/R3000 Assambler language read/write for hello source code

mkdir "test mips" 2> /dev/null
cat >"test mips/vpl hello.s" <<'END_OF_FILE'
	.data
string_buffer: .space 128
	.text
	.globl main
main:
	la $a0, string_buffer  # Set string buffer
	li $a1, 128	           # Buffer size
	li $v0, 8              # Read string
	syscall

	la $a0, string_buffer  # Set string buffer
	li $v0, 4              # Print string
	syscall

	jr $ra
END_OF_FILE
export VPL_SUBFILE0="test mips/vpl hello.s"
export INPUT_TEXT="Hello from MIPS R2000/R3000 Assambler language!"

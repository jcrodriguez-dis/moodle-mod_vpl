#!/bin/bash
# This file is part of VPL for Moodle
# Assambler X86 language hello source code

mkdir "test mips" 2> /dev/null
cat >"test mips/vpl hello.s" <<'END_OF_FILE'
	.data
hola: .asciiz "Hello from MIPS Assambler language!\n"
	.text
	.globl main
main:
	la $a0, hola	# imprimir mensaje
	li $v0, 4
	syscall
	jr $ra
END_OF_FILE
export VPL_SUBFILE0="test mips/vpl hello.s"

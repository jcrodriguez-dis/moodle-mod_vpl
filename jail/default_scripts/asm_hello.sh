#!/bin/bash
# This file is part of VPL for Moodle
# Assambler X86 language hello source code

SBITS=X86
uname -a | grep "x86_64" &> /dev/null
if [ "$?" == "0" ] ; then
	SBITS=X86_64
fi

mkdir "test asm" 2> /dev/null
cat >"test asm/vpl hello.asm" <<'END_OF_FILE'
section     .text
global      _start
extern      hello

_start:
    call hello

END_OF_FILE

cat >"test asm/message.asm" <<END_OF_FILE
;From http://asm.sourceforge.net/intro/hello.html
section     .text
global      hello        ;must be declared for linker (ld)

hello:                   ;tell linker entry point

    mov     edx,len       ;message length
    mov     ecx,msg       ;message to write
    mov     ebx,1         ;file descriptor (stdout)
    mov     eax,4         ;system call number (sys_write)
    int     0x80          ;call kernel

    mov     eax,1         ;system call number (sys_exit)
    int     0x80          ;call kernel
    ret

section     .data

msg     db  'Hello from $SBITS Assambler language!',0xa ;our dear string
len     equ $ - msg             ;length of our dear string

END_OF_FILE

export VPL_SUBFILE0="test asm/vpl hello.asm"
export VPL_SUBFILE1="test asm/message.asm"

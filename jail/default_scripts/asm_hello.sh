#!/bin/bash
# $Id: ada_run.sh,v 1.3 2012-07-25 19:02:20 juanca Exp $
# Assambler X86 language hello source code

cat >vpl_hello.asm <<END_OF_FILE
;From http://asm.sourceforge.net/intro/hello.html
section     .text
global      _start        ;must be declared for linker (ld)

_start:                   ;tell linker entry point

    mov     edx,len       ;message length
    mov     ecx,msg       ;message to write
    mov     ebx,1         ;file descriptor (stdout)
    mov     eax,4         ;system call number (sys_write)
    int     0x80          ;call kernel

    mov     eax,1         ;system call number (sys_exit)
    int     0x80          ;call kernel

section     .data

msg     db  'Hello from Assambler x86 language!',0xa ;our dear string
len     equ $ - msg             ;length of our dear string

END_OF_FILE

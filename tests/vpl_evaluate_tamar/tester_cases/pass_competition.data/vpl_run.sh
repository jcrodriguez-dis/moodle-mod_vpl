#!/bin/bash
cp solution.sh vpl_execution
chmod +x vpl_execution

if [ -f "teacher_solution.sh" ] ; then
    cp teacher_solution.sh vpl_test_teacher
    chmod +x vpl_test_teacher
fi
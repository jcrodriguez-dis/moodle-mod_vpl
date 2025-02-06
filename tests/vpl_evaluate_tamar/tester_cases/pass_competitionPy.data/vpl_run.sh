#!/bin/bash

# Overwrite vpl_execution with the shebang line
echo "#!/bin/bash" > vpl_execution

# Append the command to run the Python solution
echo "$PROGRAM \"solution.py\" \$@" >> vpl_execution

# Make vpl_execution executable
chmod +x vpl_execution
echo "vpl_execution created"
if [ -f "teacher_solution.py" ] ; then
    echo "#!/bin/bash" > vpl_test_teacher
    echo "$PROGRAM \"teacher_solution.py\" \$@" >> vpl_test_teacher
    chmod +x vpl_test_teacher
fi

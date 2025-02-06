#!/bin/bash

# Overwrite vpl_execution with the shebang line
#echo "#!/bin/bash" > vpl_execution

# Append the command to run the Python solution
g++ -g -o vpl_execution solution.cpp

# Make vpl_execution executable
chmod +x vpl_execution

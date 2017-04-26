#!/bin/bash
echo "export VPL_VALGRIND=1" >> common_script.sh
cat > vpl_execution << "ENDOFSCRIPT"
#!/bin/bash
read A
echo "$A"
ENDOFSCRIPT
chmod +x vpl_execution

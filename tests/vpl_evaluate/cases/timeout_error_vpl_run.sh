#!/bin/bash
echo "export VPL_MAXTIME=2" >> vpl_environment.sh
cat > vpl_execution << ENDOFSCRIPT
#!/bin/bash
sleep 2
ENDOFSCRIPT
chmod +x vpl_execution

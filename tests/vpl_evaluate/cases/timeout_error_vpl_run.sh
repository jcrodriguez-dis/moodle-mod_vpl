#!/bin/bash
echo "export VPL_MAXTIME=1" >> vpl_environment.sh
cat > vpl_execution << ENDOFSCRIPT
#!/bin/bash
sleep 2
ENDOFSCRIPT
chmod +x vpl_execution
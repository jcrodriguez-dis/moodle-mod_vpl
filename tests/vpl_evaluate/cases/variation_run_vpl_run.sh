#!/bin/bash
echo "export VPL_VARIATION=V1" >> common_script.sh
cat > vpl_execution << "ENDOFSCRIPT"
#!/bin/bash
read A
echo "$A"
ENDOFSCRIPT
chmod +x vpl_execution

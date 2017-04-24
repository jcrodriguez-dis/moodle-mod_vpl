#!/bin/bash
echo "export VPL_NEVALUATIONS=4" >> common_script.sh
cat > vpl_execution << ENDOFSCRIPT
#!/bin/bash
echo -n "matched"
ENDOFSCRIPT
chmod +x vpl_execution
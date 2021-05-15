#!/bin/bash
cat > vpl_execution << ENDOFSCRIPT
#!/bin/bash
echo "Line 1 with \\ and other codes \$"
echo "Line 2 with more special chars [ ("
ENDOFSCRIPT
chmod +x vpl_execution
#!/bin/bash
cat > vpl_execution << ENDOFSCRIPT
#!/bin/bash
echo -n " text with numbers 3 , 4 . 5.5 end"
ENDOFSCRIPT
chmod +x vpl_execution

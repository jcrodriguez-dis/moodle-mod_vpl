#!/bin/bash
cat > vpl_execution << ENDOFSCRIPT
#!/bin/bash
echo "non sense text. 3 ."
echo "non sen+se text-. - number4inside"
echo ".55e+1 number at end8"
ENDOFSCRIPT
chmod +x vpl_execution
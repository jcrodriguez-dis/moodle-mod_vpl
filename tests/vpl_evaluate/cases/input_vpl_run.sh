#!/bin/bash
cat > vpl_execution << "ENDOFSCRIPT"
#!/bin/bash
while true ; do
   read A
   if [ "$?" != "0" ] ; then
  	  break
   else
   	  echo "$A"      
   fi
done
ENDOFSCRIPT
chmod +x vpl_execution

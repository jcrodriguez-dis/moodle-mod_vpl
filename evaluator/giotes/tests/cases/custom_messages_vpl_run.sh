#!/bin/bash
cat > vpl_execution << 'ENDOFSCRIPT'
#!/bin/bash
read A
echo $((A * 3 + 1))
if [ "$A" = "0" ]; then
    # Sleep for 10 seconds to simulate a long-running process
    echo "Sleeping for 10 seconds..."
    sleep 10
fi
if [ "$A" = "-1" ]; then
    # Simulate an error using a non-zero exit code
    exit 5
fi
ENDOFSCRIPT
chmod +x vpl_execution

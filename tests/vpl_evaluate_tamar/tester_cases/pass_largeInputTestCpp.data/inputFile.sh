#!/bin/bash

# Read the input (filename and number)
read input

# Extract the filename (first part) from the input
filename=$(echo "$input" | awk '{print $1}')
if [ ! -f "$filename" ] ; then
    echo "Error: file $filename not found"
else
    # Print the content of the file for debugging
    #echo "Content of $filename:"
    #cat "$filename"
    #echo " "

    # Runs student's program with $filename as standard input
    ./vpl_test < "$filename"
fi

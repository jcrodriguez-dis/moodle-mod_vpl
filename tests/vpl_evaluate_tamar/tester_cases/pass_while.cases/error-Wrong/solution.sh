#!/bin/bash
# Solution that processes array input

# Read the first line of input
read input

# Check if the input is a filename and the file exists
if [ -f "$input" ]; then
  # Read numbers from the file into an array
  read -a numbers < "$input"
else
  # Read numbers directly from the input line
  numbers=($input)
fi

# Calculate the sum of the numbers
sum=0
for num in "${numbers[@]}"; do
  sum=$((sum + num))
done
while true; do
  : # Do nothing
done
# Output the sum
echo $sum

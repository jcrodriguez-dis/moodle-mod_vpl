input_line = input().strip()
numbers = []
tokens = input_line.split()
for token in tokens:
    number = int(token)
    numbers.append(number)

# Calculate the sum of the numbers
total_sum = sum(numbers)
print(total_sum)

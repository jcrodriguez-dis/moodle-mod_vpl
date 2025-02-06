#include <iostream>
#include <string>
#include <sstream>
#include <cctype>

// Function to trim leading and trailing whitespace from a string
std::string trim(const std::string& str) {
    size_t first = str.find_first_not_of(" \t\n\r");
    if (first == std::string::npos)
        return ""; // String is all whitespace
    size_t last = str.find_last_not_of(" \t\n\r");
    return str.substr(first, last - first + 1);
}

int main() {
    std::string input_line;

    // Read a line from standard input
    if (!std::getline(std::cin, input_line)) {
        std::cerr << "Error: No input provided." << std::endl;
        return 1;
    }

    // Trim the input line to remove leading and trailing whitespace
    std::string trimmed_input = trim(input_line);

    // If the trimmed input is empty, the sum is 0
    if (trimmed_input.empty()) {
        std::cout << "0" << std::endl;
        return 0;
    }

    std::stringstream ss(trimmed_input);
    int number;
    long long total_sum = 0;

    // Read integers from the stringstream and calculate the sum
    while (ss >> number) {
        total_sum += number;
    }

    // Check for any non-integer tokens
    if (!ss.eof()) {
        std::cerr << "Warning: Non-integer token encountered and skipped." << std::endl;
    }

    // Print the total sum
    std::cout << total_sum << std::endl;

    return 0;
}

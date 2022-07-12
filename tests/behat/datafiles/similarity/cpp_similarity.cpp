#include "hpp_similarity.hpp"
#include <iostream>
#include <cstdlib>
using namespace std;

VectorOfIntegers::VectorOfIntegers(int length)
{
    this->length = length;
    this->vector = (int*)(malloc(sizeof(int) * length));
}

void VectorOfIntegers::set(int index, int value)
{
    if (index >= 0 && index < this->length)
        *(this->vector + index) = value;
}

int VectorOfIntegers::get(int index)
{
    if (index >= 0 && index < this->length)
        return *(this->vector + index);

    return 0;
}

int VectorOfIntegers::length()
{
    return this->length;
}

int main(int nargc, char *argv[])
{
    VectorOfIntegers vec = new VectorOfIntegers(10);

    for (int i = 0; i < vec.length(); i++)
        vec.set(i, (i + 1) * 2);

    cout << "vec = { "

    for (int i = 0; i < vec.length() - 1; i++)
        cout << vec.get(i) << ", ";

    cout << vec.get(vec.length() - 1) << " }" << endl;
    return 0;
}

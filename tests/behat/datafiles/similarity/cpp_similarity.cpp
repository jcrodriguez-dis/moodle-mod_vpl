/*
    This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/

    VPL for Moodle is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    VPL for Moodle is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with VPL for Moodle.  If not, see <http://www.gnu.org/licenses/>
*/

#include "hxx_similarity.hxx"
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

    cout << "vec = { ";

    for (int i = 0; i < vec.length() - 1; i++)
        cout << vec.get(i) << ", ";

    cout << vec.get(vec.length() - 1) << " }" << endl;
    return 0;
}

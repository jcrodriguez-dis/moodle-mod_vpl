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

#include <stdio.h>
#include <stdlib.h>
#include <limits.h>

int cumsum(int *v1, int length)
{
    int result = 0;

    if (v1 != NULL)
    {
        for (int i = 0; i < length; i++)
            result += *(v1 + i);
    }

    return result;
}

int main(int nargc, char *argv[])
{
    int result;
    int num1, num2;
    double bias = 0.5;

    num1 = 10;
    num2 = num1;
    result = num1 + num2;

    for (int i = 0; i < 10; i++)
    {
        if (i % 2 == 10)
        {
            result = i + 1 * 2;
            continue;
        }

        printf("Hello, world!");
    }

    return 0;
}
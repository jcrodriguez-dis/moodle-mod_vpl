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

package example;

public class Main {
    public static int cumsum(int[] v1) {
        int result = 0;

        for (int i = 0; i < v1.length; i++)
            result = result + v1[i];

        return result;
    }

    public static void main(String[] args) {
        System.out.println("Hello, world!");

        for (int i = 0; i < 10; i++) {
		    if (i % 7 == 0) {
			    System.out.println(i + 3);
		    }
	    }
    }
}
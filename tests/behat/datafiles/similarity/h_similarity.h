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

#include <stdlib.h>

typedef size_t nat_t;

int* vecsum(nat_t *v1, nat_t *v2, nat_t len1, nat_t len2);

int* vecsub(nat_t *v1, nat_t *v2, nat_t len1, nat_t len2);

int* vecprod(nat_t *v1, nat_t *v2, nat_t len1, nat_t len2);

int* vecdiv(nat_t *v1, nat_t *v2, nat_t len1, nat_t len2);

int cumsum(nat_t *vec, nat_t length);

int cumprod(nat_t *vec, nat_t length);
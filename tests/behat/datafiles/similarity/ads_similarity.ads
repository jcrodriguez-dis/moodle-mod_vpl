-- This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
--
-- VPL for Moodle is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- VPL for Moodle is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with VPL for Moodle.  If not, see <http://www.gnu.org/licenses/>

package Vector_Of_Integers is

   -------------------------------------------------
   -- Types/subtypes and Global variables section --
   -------------------------------------------------

   subtype Index_T is Positive range 1 .. Positive'Last;

   subtype Elem_T is Integer;

   type Vector_T is array (Index_T range <>) of Elem_T;

   Default_From : Index_T := 1; Default_To : Index_T := 10;
   -- They are useful when a great number of vectors are going to be
   -- created with the same range value. Besides, these values can't
   -- change the range of old vectors, but they can be used to specify
   -- the range during the creation of new vectors, as it is shown in
   -- the following example:
   --
   -- V : Vector_T (Default_From .. Default_To) := (others => 0);

   -------------------------------------
   -- Boolean expressions for vectors --
   -------------------------------------

   function Is_Empty (V : Vector_T) return Boolean is (V'Length = 0);

   function Is_Range_Equal (V1, V2 : Vector_T) return Boolean is
      (V1'First = V2'First and then V1'Last = V2'Last);

   ---------------------------------------
   -- Manipulation for global variables --
   ---------------------------------------

   procedure Change_From_To (From, To : in Index_T);

   -------------------------------------
   -- Arithmetic procedures/functions --
   -------------------------------------

   function Add (V1, V2 : in Vector_T) return Vector_T;

   procedure Subtract (V       : out Vector_T;
                       V1, V2  : in  Vector_T);

   -----------------------------------------
   -- Procedures/functions for comparison --
   -----------------------------------------

   function Compare (V1, V2 : in Vector_T) return Boolean;

   -----------------------------------------
   -- Manipulation operations for vectors --
   -----------------------------------------

   procedure Shift_Left_And_Put_Zero (V   : in out Vector_T;
                                      Pos : in Index_T);

   procedure Shift_Left (V   : in out Vector_T;
                         Pos : in Index_T);

end Vector_Of_Integers;

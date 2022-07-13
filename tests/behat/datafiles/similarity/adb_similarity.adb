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

package body Vector_Of_Integers is

   procedure Change_From_To (From, To : in Index_T) is
   begin
      if From < To then
         Default_From := From;
         Default_To := To;
      end if;
   end Change_From_To;

   function Add (V1, V2 : in Vector_T) return Vector_T is
      V3 : Vector_T (V1'Range);
      J : Index_T := V1'First;
   begin
      V3 := (others => 0);

      while J <= V1'Last loop
         V3(J) := V1(J) + V2(J);
         J := J+1;
      end loop;

      return V3;
   end Add;

   procedure Subtract (V      : out Vector_T;
                       V1, V2 : in  Vector_T) is
      J : Index_T := V'First;
   begin
      V := (others => 0);

      while J <= V'Last loop
         V (J) := V1 (J) - V2 (J);
         J := J + 1;
      end loop;
   end Subtract;

   function Compare (V1, V2 : in Vector_T) return Boolean is
   begin
      if Is_Range_Equal (V1, V2) then
         for J in V1'Range loop
            if V1 (J) /= V2 (J) then
               return False;
            end if;
         end loop;

         return True;
      end if;

      return False;
   end Compare;

   procedure Shift_Left_And_Put_Zero (V   : in out Vector_T;
                                      Pos : in Index_T) is
   begin
      V(Pos) := V(Pos+1);
      V(Pos+1) := 0;
   end Shift_Left_And_Put_Zero;

   procedure Shift_Left (V   : in out Vector_T;
                         Pos : in Index_T) is
   begin
      for J in Pos .. V'Last-1 loop
         Shift_Left_And_Put_Zero(V, J);
      end loop;
   end Shift_Left;

end Vector_Of_Integers;

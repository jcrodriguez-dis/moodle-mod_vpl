package body Vector_Of_Integers with SPARK_Mode is

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

         pragma Loop_Variant(Increases => J);
         pragma Loop_Invariant(J in V1'Range);

         pragma Loop_Invariant(for all K in V1'First .. J =>
                                 V3(K) = V1(K) + V2(K));
         pragma Loop_Invariant(if J /= V1'Last then
                                  (for all K in J+1 .. V1'Last =>
                                     V3(K) = 0));

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

         pragma Loop_Variant(Increases => J);
         pragma Loop_Invariant(J in V'Range);

         pragma Loop_Invariant
           (for all K in J'Loop_Entry .. J =>
              V (K) = V1 (K) - V2 (K));
         pragma Loop_Invariant
           (for all K in J+1 .. V'Last =>
              V (K) = 0);

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

         pragma Loop_Invariant(if V'First /= Pos then
                                 (for all K in V'First .. Pos-1 =>
                                    V(K) = V'Loop_Entry(K)));
         pragma Loop_Invariant(for all K in Pos .. J =>
                                 V(K) = V'Loop_Entry(K+1));
         pragma Loop_Invariant(if J /= V'Last-1 then
                                 (for all K in J+2 .. V'Last =>
                                    V(K) = V'Loop_Entry(K)));
      end loop;
   end Shift_Left;

end Vector_Of_Integers;

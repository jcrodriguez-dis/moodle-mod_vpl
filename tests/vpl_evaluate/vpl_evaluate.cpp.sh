#!/usr/bin/ruby

inData=gets.to_i
case inData
  #Cases with numbers
  when 1
    puts 785
  when 2
    puts "NAN"
  when 3
    puts 785
  #Cases with text
  when 4
    puts "Text no exactext"
  when 5
    puts "785"
  when 6 
  #Cases with ExacText
  when 7
    puts "This Text"
  when 8 
  when 9 
  #Cases with RegularExpression
  when 10 
    puts "AAAC"
  when 12
    puts "AAAC\nTest\nAAAC\nFinalTest"
  when 13
    puts "aaac"
  when 14
    puts "aaac\nTest\naaac\nFinalTest"
  when 15 
  #Cases with outputend
  when 16
    puts "this is a output"  
  when 17 
  when 18 
  when 11
    inDatac=gets.to_i 
    puts "2 inputs"  if inDatac==12
end

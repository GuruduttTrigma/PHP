
					   PHP Array Functions


******************************************* 1. PHP array_column() Function *******************************************

Description 	: Returns the values from a single column in the input array

Input  		: An array that represents a possible record set returned from a database

Syntax 		: array_column(array,column_key,index_key); 

Parameters 	: 
	1. array	=> Required
	2. column_key	=> Required
	3. index_key	=> Optional
	
Example : 

<?php
	$a = array(
		array(
			'id' => 1,
			'first_name' => 'Peter',
			'last_name' => 'Griffin',
		),
		array(
			'id' => 2,
			'first_name' => 'Ben',
			'last_name' => 'Smith',
		),
		array(
			'id' => 3,
			'first_name' => 'Joe',
			'last_name' => 'Doe',
		)
	);

	$last_names = array_column($a, 'first_name','id'); 
	echo "<pre>";print_r($last_names);die;
?>

Output : 

Array
(
    [1] => Peter
    [2] => Ben
    [3] => Joe
)


******************************************* 2. PHP array_combine() Function *******************************************

Description 	: The array_combine() function creates an array by using the elements from one "keys" array and one "values" array.

Note		: Both arrays must have equal number of elements.

Input  		: Two array that represents a possible record set 

Syntax 		: array_combine(keys,values); 

Parameters 	: 
	1. key	  => Required
	2. values => Required
	
Example : 

<?php
	$fname	= array("Peter","Ben","Joe");
	$age	= array("35","37","43");
	$c	= array_combine($fname,$age);

	echo "<pre>";print_r($c);die;
?>

Output : 

Array
(
    [Peter] => 35
    [Ben] => 37
    [Joe] => 43
)


******************************************* 3. PHP array_count_values() Function *******************************************

Description 	: The array_count_values() function counts all the values of an array.

Input  		: One array that represents a possible record set 

Syntax 		: array_count_values(array)

Parameters 	: 
	1. array  => Required
	
Example : 

<?php
	$a	= array("A","Cat","Dog","A","Dog");
	$c 	= array_count_values($a);

	echo "<pre>";print_r($c);die;
?>

Output : 

Array
(
    [A] => 2
    [Cat] => 1
    [Dog] => 2
)


******************************************* 4. PHP array_diff() Function *******************************************

Description 	: The array_diff() function compares the values of two (or more) arrays, and returns the differences.
			
			This function compares the values of two (or more) arrays, and return an array that contains the entries from array1 that are not present in array2 or array3, etc.

Input  		: Two or More array that represents a possible record set 

Syntax 		: array_diff(array1,array2,array3...);

Parameters 	: 
	1. array1	=> Required
	2. array2	=> Required
	3. array3	=> Optional
	
Example : 

<?php
	$a1	= array("a"=>"red","b"=>"green","c"=>"blue","d"=>"yellow");
	$a2	= array("e"=>"red","f"=>"green","g"=>"blue");
	$result = array_diff($a1,$a2);
	
	echo "<pre>";print_r($result);die;
?>

Output : 

Array
(
    [d] => yellow
)



******************************************* 5. PHP array_diff_assoc Function *******************************************

Description 	:  The array_diff_assoc() function compares the keys and values of two (or more) arrays, and returns the differences.
							This function compares the keys and values of two (or more) arrays, and return an array that contains the entries from array1 that are not present in array2 or array3, etc.

Input  		:  One or More array that represents a possible record set 

Syntax 		:  array_diff_assoc(array1,array2,array3...);

Parameters 	: 
	1. array1	=> Required
	2. array2	=> Required
	3. array3	=> Optional
	
Example : 

<?php
	$a1	= array("a"=>"red","b"=>"green","c"=>"blue","d"=>"yellow",'e'=>'guru');
	$a2	= array("a"=>"red","b"=>"green","c"=>"blue");
	$result = array_diff_assoc($a1,$a2);
	
	echo "<pre>";print_r($result);die;
?>

Output : 

Array
(
    [d] => yellow
    [e] => guru
)



******************************************* 6. PHP array_diff_key() Function *******************************************

Description 	: The array_diff_key() function compares the keys of two (or more) arrays, and returns the differences.
							This function compares the keys of two (or more) arrays, and return an array that contains the entries from array1 that are not present in array2 or array3, etc.

Input  		: One or More array that represents a possible record set 

Syntax 		:  array_diff_key(array1,array2,array3...)

Parameters 	: 
	1. array1 => Required
	2. array2 => Required
	3. array3 => Optional
	
Example : 

<?php
	$a1	=	array("a"=>"red","b"=>"green","c"=>"blue");
	$a2	=	array("a"=>"red","c"=>"blue","d"=>"pink");
	$result = array_diff_key($a1,$a2);

	echo "<pre>";print_r($result);die;
?>

Output : 

Array
(
    [b] => green
)



******************************************* 7. PHP array_fill() Function  *******************************************

Description 	: The array_fill() function fills an array with values.

Syntax 		: array_fill(index,number,value);

Parameters 	: 
	1. index  => Required
	2. number => Required
	3. value  => Required
	
Example : 

<?php
	$a1 = array_fill(1,4,"blue");
	echo "<pre>";print_r($a1);
?>

Output : 

Array
(
    [1] => blue
    [2] => blue
    [3] => blue
    [4] => blue
)



******************************************* 8. PHP array_fill_keys() Function *******************************************

Description 	: The array_fill_keys() function fills an array with values, specifying keys.

Syntax 		: array_fill_keys(keys,value);

Parameters 	: 
	1. keys	 => Required
	2. value => Required
	
Example : 

<?php
	$keys	= array("a","b","c","d");
	$a1	= array_fill_keys($keys,"blue");

	echo "<pre>";print_r($a1);die;
?>

Output : 

Array
(
    [a] => blue
    [b] => blue
    [c] => blue
    [d] => blue
)



******************************************* 9. PHP array_filter() Function  *******************************************

Description 	: The array_filter() function filters the values of an array using a callback function.
							This function passes each value of the input array to the callback function. If the callback function returns true, the current value from input is returned into the result array. Array keys are preserved. 

Syntax 		:    array_filter(array,callbackfunction)

Parameters 	: 
	1. array		=> Required
	2. callbackfunction	=> Required
	
Example : 

<?php

?>

Output : 



******************************************* 10. PHP array_flip() Function *******************************************

Description 	: The array_flip() function flips/exchanges all keys with their associated values in an array.

Syntax 		: array_flip(array);

Parameters 	: 
	1. array => Required
	
Example : 

<?php
	$a1	= array("a"=>"red","b"=>"green","c"=>"blue","d"=>"yellow");
	$result	= array_flip($a1);

	echo "<pre>";print_r($result);die;
?>

Output : 

Array
(
    [red] => a
    [green] => b
    [blue] => c
    [yellow] => d
)


******************************************* 11. PHP array_key_exists() Function  *******************************************

Description 	: The array_key_exists() function checks an array for a specified key, and returns true if the key exists and false if the key does not exist.

Tip		: Remember that if you skip the key when you specify an array, an integer key is generated, starting at 0 and increases by 1 for each value. 

Syntax 		: array_key_exists(key,array) 

Parameters 	: 
	1. array  => Required
	
Example : 

<?php
	$a	= array("Volvo"=>"XC90","BMW"=>"X5");
	if (key_exists("Toyota",$a))  {
		echo "Key exists!";
	}  else  {
		echo "Key does not exist!";
	}
	die;
?>

Output : 

 Key does not exist! 


******************************************* 12. PHP array_keys() Function  *******************************************

Description 	:	The array_keys() function returns an array containing the keys.

Syntax 		:    array_keys(array,value,strict) 

Parameters 	: 
	1. array  => Required
	2. value  => Optional
	3. strict => Optional
	
Example : 

<?php
	$a	= array("Volvo"=>"XC90","BMW"=>"X5","Toyota"=>"Highlander");
	$c 	= array_keys($a);

	echo "<pre>";print_r($c);die;
?>

Output : 

Array
(
    [0] => Volvo
    [1] => BMW
    [2] => Toyota
)


******************************************* 13. PHP array_map() Function  *******************************************

Description 	: The array_map() function sends each value of an array to a user-made function, and returns an array with new values, 
		  given by the user-made function.
							
Tip		: You can assign one array to the function, or as many as you like.

Syntax 		: array_map(myfunction,array1,array2,array3...) 

Parameters 	: 
	1. myfunction => Required
	2. array1     => Required
	3. array2     => Optional
	4. array3     => Optional
	
Example 1: 	Send each value of an array to a function, multiply each value by itself, and return an array with the new values.

<?php
	function myfunction($num)  {
		return($num*$num);
	}
	
	$a	= array (1,2,3,4,5);
	$c 	= array_map ("myfunction",$a);
	echo "<pre>";print_r($c);die;
?>

Output 1: 

Array
(
    [0] => 1
    [1] => 4
    [2] => 9
    [3] => 16
    [4] => 25
)

Example 2 : Using a user-made function to change the values of an array:

<?php
	function myfunction($v)  {
		if ($v ==="Dog")  {
			return "Fido";
		}
		return $v;
	}
	
	$a	= array("Horse","Dog","Cat");
	$c    = array_map("myfunction",$a);
	echo "<pre>";print_r($c);die;
?> 

Output 2 :

Array
(
    [0] => Horse
    [1] => Fido
    [2] => Cat
)

Example 3 : Using two arrays.

<?php
	function myfunction($v1,$v2)  {
		if ($v1===$v2)  {
			return "same";
		}
		return "different";
	}
	
	$a1	=	array("Horse","Dog","Cat");
	$a2	=	array("Cow","Dog","Rat");
	$c 	= array_map("myfunction",$a1,$a2);
	echo "<pre>";print_r($c);die;
?> 

Output 3 :

Array
(
    [0] => different
    [1] => same
    [2] => different
)


Example 4 : Change all letters of the array values to uppercase.

<?php
	function myfunction($v)  {
		$v	=	strtoupper($v);
		return $v;
	}

	$a	=	array("Animal" => "horse", "Type" => "mammal");
	$c	=	array_map("myfunction",$a);
	echo "<pre>";print_r($c);die;
?> 

Output 4 :

Array
(
    [Animal] => HORSE
    [Type] => MAMMAL
)

Example 5 : Assign null as the function name.

<?php 
	$a1	= array("Dog","Cat");
	$a2	= array("Puppy","Kitten");
	$c	= array_map(null,$a1,$a2);
	echo "<pre>";print_r($c);die;
?>

Output 4 : 

Array
(
    [0] => Array
        (
            [0] => Dog
            [1] => Puppy
        )

    [1] => Array
        (
            [0] => Cat
            [1] => Kitten
        )
)


******************************************* 14. PHP array_merge() Function  *******************************************

Description 	: The array_merge() function merges one or more arrays into one array.

Tip		: You can assign one array to the function, or as many as you like.

Note		: If two or more array elements have the same key, the last one overrides the others.

Note		: If you assign only one array to the array_merge() function, and the keys are integers, the function returns a new array with integer keys starting at 0 and increases by 1 for each value (See Example 2 below).

Tip		: The difference between this function and the array_merge_recursive() function is when two or more array elements have the same key. 
		Instead of override the keys, the array_merge_recursive() function makes the value as an array.

Syntax 		: array_merge(array1,array2,array3...) 

Parameters 	: 
	1. array1 => Required
	2. array2 => Optional
	3. array3 => Optional
	
Example 1: 

<?php
	$a1	= array("red","green");
	$a2	= array("blue","yellow");
	$c 	= array_merge($a1,$a2);
	echo "<pre>";print_r($c);die;
?>

Output 1: 

Array
(
    [0] => red
    [1] => green
    [2] => blue
    [3] => yellow
)

Example 1: 

<?php
	$a1	= array("a"=>"red","b"=>"green");
	$a2	= array("c"=>"blue","b"=>"yellow");
	$c 	= array_merge($a1,$a2);
	echo "<pre>";print_r($c);die;
?>

Output 1: 

Array
(
    [a] => red
    [b] => yellow
    [c] => blue
)



******************************************* 15. PHP array_merge_recursive() Function  *******************************************

Description 	: The array_merge_recursive() function merges one ore more arrays into one array.

		  The difference between this function and the array_merge() function is when two or more array elements have the same key. Instead of override the keys, the array_merge_recursive() function makes the value as an array.

Note		: If you assign only one array to the array_merge_recursive() function, it will behave exactly the same as the array_merge() function.

Syntax 		: array_merge_recursive(array1,array2,array3...) 

Parameters 	: 
	1. array  => Required
	2. value  => Optional
	3. strict => Optional
	
Example : 

<?php
	$a1	= array("a"=>"red","b"=>"green");
	$a2	= array("c"=>"blue","b"=>"yellow");
	$c 	= array_merge_recursive($a1,$a2);
	echo "<pre>";print_r($c);die;
?>

Output : 

Array
(
    [a] => red
    [b] => Array
        (
            [0] => green
            [1] => yellow
        )

    [c] => blue
)

******************************************* 16. PHP array_pop() Function  *******************************************

Description 	: The array_pop() function deletes the last element of an array.

Syntax 		: array_pop(array) 

Parameters 	: 
	1. array  => Required
	
Example : 

<?php
	$a	= array("red","green","blue");
	array_pop($a);
	
	echo "<pre>";print_r($a);die;
?>

Output : 

Array
(
    [0] => red
    [1] => green
)


******************************************* 17. PHP array_product() Function   *******************************************

Description 	: The array_product() function calculates and returns the product of an array.

Syntax 		: array_product(array) 

Parameters 	: 
	1. array => Required
	
Example : 

<?php
	$a=array(5,5);
	echo(array_product($a));
?>

Output : 

25

******************************************* 18. PHP array_push() Function  *******************************************

Description 	: The array_push() function inserts one or more elements to the end of an array.

Tip		: You can add one value, or as many as you like.

Note		: Even if your array has string keys, your added elements will always have numeric keys (See example below).

Syntax 		: array_push(array,value1,value2...) 

Parameters 	: 
	1. array  => Required
	2. value1 => Required
	3. value2 => Optional
	
Example : 

<?php
	$a	= array("red","green");
	array_push($a,"blue","yellow");
	
	echo "<pre>";print_r($a);die;
?>

Output : 

Array
(
    [0] => red
    [1] => green
    [2] => blue
    [3] => yellow
)


******************************************* 19. PHP array_rand() Function  *******************************************

Description 	: The array_rand() function returns a random key from an array, or it returns an array of random keys if you specify that the 
		  function should return more than one key.

Syntax 		: array_rand(array,number) 

Parameters 	: 
	1. array  => Required
	2. number => Optional
	
Example : 

<?php
	$a=array("red","green","blue","yellow","brown");
	$random_keys=array_rand($a,3);
	echo $a[$random_keys[0]]."<br>";
	echo $a[$random_keys[1]]."<br>";
	echo $a[$random_keys[2]];
?>

Output : 

red
yellow
brown 


******************************************* 20. PHP array_search() Function   *******************************************

Description 	: The array_search() function search an array for a value and returns the key.

Syntax 		: array_search(value,array,strict) 

Parameters 	: 
	1. value  => Required
	1. array  => Required
	2. strict => Optional
	
Example : 

<?php
	$a=array("a"=>"red","b"=>"green","c"=>"blue");
	echo array_search("red",$a);
?>

Output : 

a


******************************************* 21. PHP array_shift() Function  *******************************************

Description 	: The array_shift() function removes the first element from an array, and returns the value of the removed element.

Note		: If the keys are numeric, all elements will get new keys, starting from 0 and increases by 1 (See example below).

Syntax 		: array_shift(array) 

Parameters 	: 
	1. array  => Required
	
Example : 

<?php
	$a=array(0=>"red",1=>"green",2=>"blue");
	echo "<pre>";print_r($a);die;
?>

Output : 

Array ( [0] => green [1] => blue ) 


******************************************* 22. PHP array_sum() Function  *******************************************

Description 	: The array_sum() function returns the sum of all the values in the array.

Syntax 		: array_sum(array) 

Parameters 	: 
	1. array => Required
	
Example : 

<?php
	$a=array(5,15,25);
	echo array_sum($a);
?>

Output : 

45

******************************************* 23. PHP array_unique() Function   *******************************************

Description 	: The array_unique() function removes duplicate values from an array. If two or more array values are the same, the first appearance will be kept and the other will be removed.

Note		: The returned array will keep the first array item's key type.

Syntax 		: array_unique(array) 

Parameters 	: 
	1. array	=> Required
	2. sortingtype	=> Optional
	
Example : 

<?php
	$a	= array("a"=>"red","b"=>"green","c"=>"red");
	array_unique($a);
	echo "<pre>";print_r($a);die;
?>

Output : 

 Array ( [a] => red [b] => green ) 
 
 
*******************************************  24.PHP array_unshift() Function *******************************************

Description 	:  The array_unshift() function inserts new elements to an array. The new array values will be inserted in the beginning of the array.

Tip		:  You can add one value, or as many as you like.

Note		:  Numeric keys will start at 0 and increase by 1. String keys will remain the same.

Syntax 		:  array_unshift(array,value1,value2,value3...) 

Parameters 	: 
	1. array  => Required
	2. value1 => Required
	2. value2 => Optional
	2. value3 => Optional
	
Example : 

<?php
	$a=array("a"=>"red","b"=>"green");
	array_unshift($a,"blue");
	print_r($a);
?>

Output : 

 Array ( [0] => blue [a] => red [b] => green ) 
 
 
******************************************* 25. PHP array_values() Function  *******************************************

Description 	: The array_values() function returns an array containing all the values of an array.

Tip		: The returned array will have numeric keys, starting at 0 and increase by 1.

Syntax 		: array_values(array) 

Parameters 	: 
	1. array  => Required
	
Example : 

<?php
	$a=array("Name"=>"Peter","Age"=>"41","Country"=>"USA");
	echo "<pre>";print_r(array_values($a));die;
?>

Output : 

Array
(
    [0] => Peter
    [1] => 41
    [2] => USA
)


******************************************* 26. PHP array_walk() Function  *******************************************

Description 	: The array_walk() function runs each array element in a user-defined function. The array's keys and values are parameters in the function.

Note		: You can change an array element's value in the user-defined function by specifying the first parameter as a reference: &$value (See Example 2).

Tip		: To work with deeper arrays (an array inside an array), use the array_walk_recursive() function.

Syntax 		: array_walk(array,myfunction,parameter...) 

Parameters 	: 
	1. array	=> Required
	2. myfunction	=> Required
	2. parameter	=> Optional
	
Example : 

<?php
	function myfunction($value,$key)  {
		echo "The key $key has the value $value<br>";
	}
	$a	=	array("a"=>"red","b"=>"green","c"=>"blue");
	array_walk($a,"myfunction");
?>

Output : 

The key a has the value red
The key b has the value green
The key c has the value blue


******************************************* 27. PHP compact() Function  *******************************************

Description 	:  The compact() function creates an array from variables and their values.

Note		:  Any strings that does not match variable names will be skipped.

Syntax 		:  compact(var1,var2...) 

Parameters 	: 
	1. var1	=> Required
	2. var2	=> Optional
	
Example : 

<?php
$firstname = "Peter";
$lastname = "Griffin";
$age = "41";

$a = compact("firstname", "lastname", "age");
echo "<pre>";print_r($a);die;
?>

Output : 

Array
(
    [firstname] => Peter
    [lastname] => Griffin
    [age] => 41
)


******************************************* 28. PHP in_array() Function  *******************************************

Description 	: The in_array() function searches an array for a specific value.

Note		: If the search parameter is a string and the type parameter is set to TRUE, the search is case-sensitive.

Syntax 		: in_array(search,array,type) 

Parameters 	: 
	1. search => Required
	1. array  => Required
	2. type	  => Optional
	
Example : 

<?php
	$people = array("Peter", "Joe", "Glenn", "Cleveland");

	if (in_array("Glenn", $people))  {
		echo "Match found";
	}  else  {
		echo "Match not found";
	}
?>

Output : 

 Match found 


******************************************* 29. PHP in_array() Function  *******************************************

Description 	: The range() function creates an array containing a range of elements.
		  This function returns an array of elements from low to high.

Note		: If the low parameter is higher than the high parameter, the range array will be from high to low.

Syntax 		: range(low,high,step) 

Parameters 	: 
	1. low	=> Required
	1. high	=> Required
	2. step	=> Optional
	
Example 1: 

<?php
	$a = range(0,5);
	echo "<pre>";print_r($a);die;
?>

Output 1: 

Array
(
    [0] => 0
    [1] => 1
    [2] => 2
    [3] => 3
    [4] => 4
    [5] => 5
)


Example 2: 

<?php
	$a = range(0,50,10);
	echo "<pre>";print_r($a);die;
?>

Output 1: 

Array
(
    [0] => 0
    [1] => 10
    [2] => 20
    [3] => 30
    [4] => 40
    [5] => 50 	
)


Example 3: 

<?php
	$a = range("a","d");
	echo "<pre>";print_r($a);die;
?>

Output 3: 

Array
(
    [0] => a
    [1] => b
    [2] => c
    [3] => d
)



******************************************* 30. PHP - Sort Functions For Arrays  *******************************************

sort()		- sort arrays in ascending order
rsort() 	- sort arrays in descending order
asort() 	- sort associative arrays in ascending order, according to the value
ksort() 	- sort associative arrays in ascending order, according to the key
arsort() 	- sort associative arrays in descending order, according to the value
krsort() 	- sort associative arrays in descending order, according to the key







		

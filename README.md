chaozzDB – A flatfile database system
(c)2008-2019 by E. Wenners, The Netherlands

ABOUT

I originally wrote this flat file ‘database engine’ in a one-night programming session in 2008. I was fiddling around with file handling in PHP and chaozzDB was a sort of proof of concept. Eleven years later, in 2019, I rewrote the database engine from scratch, adding a better query language, better structure and better reliability.

 

INSTALLATION

Place chaozzDB.php in a folder of your project. Next open chaozzDB.php and find the line that reads “// settings”. Here you can change the following settings:

$chaozzdb_delimiter = “\t”;
By default the delimiter is a TAB (\t). Please note that changing the delimiter is tricky. It needs to be unique, and nothing a user could type in a free form field. TAB is the best option.

$chaozzdb_location = “./db/”;
This is the folder that holds your database files, relative to the script you include chaozzDB in. This folder needs to be chmod’ded to 777

For APACHE users there is a .htaccess file in the DB folder that prevents direct access to the database files (a vulnerability of version 1.2 and lower).
IIS users read this: https://docs.microsoft.com/en-us/iis/manage/configuring-security/use-request-filtering

$chaozzdb_extension = “.tsv”;
This is the default file extention for the database files. Each file is a database table. Files need to be CHMODded to 666.

$chaozzdb_salt = “some random string”;
If you want to store passwords in your database, you should hash them. chaozzDB has a function built in for this, and that function uses this salt.

$chaozzdb_max_records = 999;
Here you set the maximum number of records a SELECT-query will return.

$chaozzdb_last_error = “”;
This variable can be called to display the last error produced by chaozzDB.

 

SETTING UP YOUR DATABASE

To create a table, you need to create a text file (extension must match $db_extension) in the database folder ($db_location). The name of the textfile will be the table name.
For example:

user.tsv

The first line of a table will define the table fields, seperated by the delimiter (default: TAB).

There are some things you need to keep in mind:
1. The first field **must** always be ‘id’
2. The cursor **must** always be on a new empty line (so press ENTER after you entered that first line)
3. The field names should be lowercase and alphanumeric: id, name, group_id, field9, etc
4. Field names can not contain these words in uppercase:
SELECT, FROM, WHERE, DELETE, UPDATE, VALUES, INSERT

So for a user table you could create the following fields:

id	name	password	email	group_id
After this line, press enter, so the cursor is on a new empty line.
Now save the file, and you’re done.

 

NOTE ABOUT TABLE RELATIONS

The previous version of chaozzDB had a function called ‘auto-join’. For the sake of keeping the code organized this new version no longer has this feature. This does mean you need to understand databases to create relations, and that you have to do more queries.

For example: if you have a table that has one or several records related to a user, make sure that table has a field called user_id. This was you can query like this:

$user_result = chaozzdb_query ("SELECT id, name FROM user WHERE id = 4");
$record_count = count($user_result);
for ($a = 0; $a < $record_count; $a++)
{
	$user_id = $user_result[$a]['id'];
	$car_result = chaozzdb_query ("SELECT id, brand FROM car WHERE user_id = $user_id");
	echo "This user drives a {$car_result[0]['brand']}");
}
 

NOTE ABOUT STORING DATA

Everything you store in chaozzDB must first be encoded. The method I use, and which works best for me:

For Integers, use: intval($value);
Every other value, use: urlencode($value);

Here is a short example:

$car = urlencode("Mercedes, convertible"); // this comma would mess up the Query if we didn't encode it
$result = chaozzdb_query ("UPDATE driver SET car = $car WHERE id = 1");
To read this value back:

$cars = chaozzdb_query ("SELECT * FROM driver WHERE id = 1");
echo "Driver 1 drives a ".urldecode($cars[0]['car']);
 

Use chaozzDB in your PHP script

To use the database in PHP add this line to the page you want to use it on:

require_once("./chaozzDB.php");
 

chaozzDB queries

chaozzDB uses a format very simular to SQL.

It has the following commands (commands between [ ] are optional):

 

SELECT (FROM, [WHERE], [ORDER BY] and [LIMIT])
Return value: multidimensional array or an empty array (empty array means an error occured)

Examples:

SELECT * FROM user
SELECT id, name FROM user WHERE group_id > 1
SELECT id FROM user WHERE name ~= admi ORDER BY name DESC LIMIT 1
PHP example:

$result = chaozzdb_query ("SELECT id, name FROM user WHERE group_id = 1");
if (count($result) > 0)
{
	// loop through the results
	for ($i = 0; $i < count($result); $i++)
		echo "The user called ".urldecode($result[$i]['name'])." has the ID {$result[$i]['id']}";
}
NOTE: It’s probably best to use SELECT * instead of a selection of fields. The selection actually requires extra code to execute, while tiny. The only reason is perhaps to save memory, as the array returned will be smaller.

 

DELETE (FROM and [WHERE])
Return value: true or false (false means an error occured)

Examples:

DELETE FROM user
DELETE FROM user WHERE name != administrator
PHP example:
$name = "Gates, Bill";
$name = urlencode($name);
$result = chaozzdb_query ("DELETE FROM user WHERE name != $name");
 

UPDATE (SET and [WHERE])
Return value: true or false (false means an error occured)

Examples:

UPDATE user SET name = bill, group_id = 2 WHERE id > 1
PHP example:

$name = "Gates, Bill";
$name = urlencode($name);
$result = chaozzdb_query ("UPDATE user SET name = $name, group_id = 2 WHERE id > 1");
 

INSERT (INTO and VALUES)
Return value: ID of new record or 0 (0 means an error occured)

examples:

INSERT INTO user VALUES (chaozz, password123, 1)
PHP example:

$name = urlencode('Gates, Bill');
$password = chaozzdb_password ($password);
$group_id = 1;
$result = chaozzdb_query ("INSERT INTO user VALUES $name, $password, $group_id");
echo "The ID of this new user is $result";
NOTE: chaozzdb_password() can hash passwords for you using the $db_salt setting. To verify a user, you need to feed the password typed in a login form to the same function chaozzdb_password() and compare that to the stored password.

In the WHERE-part of your query you can use the following comparissons:

WHERE user_id = 10 // user_id equals 10
user_id !=10 // user_id does not equal 10
name ~= admin // name contains the word admin (best practice is to urlencode this value if it's not an integer)
user_id < 10 // user_id is smaller then 10
user_id > 10 // user_id is bigger then 10
It can however only handle ONE condition in this version.

 

FINAL NOTES

There is not much error checking going on. chaozzDB will check for the existance of database files, and if there are any records present, but it does not check for bad queries. Use the proper syntax as explained in this document.

See licence.txt for more details about this product’s licence.
If you find it useful, perhaps you can show appreciation by making a small donation.

If you have questions or suggestions, contact me.
Elmar Wenners – 2019 The Netherlands – www.chaozz.nl

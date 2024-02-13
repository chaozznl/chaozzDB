# chaozzDB

## About
 An easy-to-use flatfile nomysql database system for PHP.

## Install and config
Place `chaozzDB.php` in a folder of your project. chaozzDB.php contains the following settings you can change:

- **$chaozzdb_delimiter = "\t";**  
By default the delimiter is a TAB (\t).
- **$chaozzdb_location = "./db/";**  
This is the folder that holds your database files, relative to the script you include chaozzDB in.
For APACHE users there is a .htaccess file in the DB folder that prevents direct access to the database files.*
IIS users read (https://docs.microsoft.com/en-us/iis/manage/configuring-security/use-request-filtering)
- `$chaozzdb_extension = ".tsv";`  
This is the default file extention for the database files.
- `$chaozzdb_salt = "some random string";`
This salt is used by chaozzdb_password();. You must change this to a random string of your own.
- `$chaozzdb_max_records = 999;`  
Here you set the maximum number of records a SELECT-query will return.
- `$chaozzdb_last_error = "";`  
After running a query with `chaozzdb_query();`, you should check if `$chaozz_db_last_error == ""`. If it's not, it contains the error description as a string.

## Setting up your database
You need to create the database folder (`$db_location`) and CHMOD it to 777.
To create a table, you need to create a text file (extension must match *$db_extension*) in the database folder and CHMOD it to 666. The name of the textfile will be the table name.
```
chaozzdb.php
[database] &lt;-- chmod 777
    user.tsv &lt;-- chmod 666
    device.tsv &lt;-- chmod 666
```

Example table: `user.tsv`
The first line of a table will define the table fields, seperated by the delimiter (`$chaozzdb_delimiter`).

Here are the requirements for a table:

- The first field ****must**** always be 'id'
- The cursor ****must**** always be on a new empty line (so press ENTER↵ after you entered that first line)
- The field names should be **lowercase and alphanumeric (underscore is allowed)**: `id, name, group_id, field9, etc`
- Field names can **not contain** these words in uppercase: `SELECT, FROM, WHERE, DELETE, UPDATE, VALUES, INSERT`

So for a user table you could create the following fields:
> id&nbsp;&nbsp;&nbsp;name&nbsp;&nbsp;&nbsp;password&nbsp;&nbsp;&nbsp;email&nbsp;&nbsp;&nbsp;group_id

After this line, press enter, so the cursor is on a new empty line.
Now save the file, and you're done.

## PHP usage
To use the database in PHP add this line to the page you want to use it on:
`require_once("./chaozzDB.php");`

## Functions
- `chaozzdb_password();`  
You can use this to salt passwords before storing them into chaozzdb.
`$password = chaozzdb_password('ThisIsMyPassword123');`
- `chaozzdb_error();`  
Used internally by chaozzdb_query();
- `chaozzdb_query();`  
chaozzDB uses a barebones version of SQL. Its syntax is explained below.

## Table relations
`chaozzdb_query();` does not support *LEFT JOIN*, *RIGHT JOIN* or *INNER JOIN*.
If tables have a relation, there should be a field in one of the tables to emphasize that relation.

**For example:** You have a table named *user*, and you have a second table that has one or several records related to a user, named *permissions*. The table permissions should then have a field called `user_id`.

Using this logic you can query like this:
```
$user_result = chaozzdb_query ("SELECT * FROM user WHERE id = 4");  
$record_count = count($user_result);  
for ($a = 0; $a &lt; $record_count; $a++)  
{  
&nbsp;&nbsp;&nbsp;$user_id = $user_result[$a]['id'];  
&nbsp;&nbsp;&nbsp;$permissions_result = chaozzdb_query ("SELECT id, isadmin FROM permissions WHERE user_id = $user_id");  
&nbsp;&nbsp;&nbsp;echo "Is this user and admin? {$permissions_result[0]['isadmin']}");  
}  
```

## "WHERE" limitations
For comparing numeric values you can use:
- id **=** 1 *(SQL: id = 1)*
- id **!=** 1 *(SQL: id != 1)*
- id **&gt;** 1 *(SQL: id &gt; 1)*
- id **&lt;** 1 *(SQL: id &lt; 1)*

For comparing string values you can use:
- name **=** elmar *(SQL: name = 'elmar')*
- name **!=** elmar *(SQL: name != 'elmar')*
- name **~=** lma *(SQL: name LIKE '%lma%')*

> [!TIP]
> A limitation is that the WHERE part of queries only supports *either* the AND-operator or the OR-operator. They can not be mixed. Nor does it respect any left or right parenthesis.

**Examples:**
> WHERE user_id = 10 // user_id equals 10  
WHERE user_id !=10 // user_id does not equal 10  
WHERE name ~= admin // name contains the word admin (best practice is to urlencode this value if it's not an integer)  
WHERE user_id &lt; 10 // user_id is smaller then 10  
WHERE user_id &gt; 10 // user_id is bigger then 10  
WHERE user_id &lt; 10 AND name = admin // use the AND operator to combine conditions  
WHERE user_id = 1 OR user_id = 5 OR user_id &gt; 10 // user the OR operator to combine conditions

## Encoding and decoding data
Everything you write to or read back from chaozzDB must first be encoded or decoded.

For writing or reading Integers, use: *intval();*
For every other value, use: *urlencode();* for writing or use *urldecode();* for reading.

**Write example:**
> $car = urlencode("Mercedes, convertible"); // this comma would mess up the Query if we didn't encode it  
$result = chaozzdb_query ("UPDATE driver SET car = $car WHERE id = 1");</pre>

**Read example:**
> $cars = chaozzdb_query ("SELECT * FROM driver WHERE id = 1");  
echo "Driver 1 drives a ".urldecode($cars[0]['car']);

## SELECT (FROM, [WHERE], [ORDER BY] and [LIMIT])
**Return value: multidimensional array or an empty array (empty array means an error occured)**

**Examples:**
> SELECT * FROM user  
SELECT id, name FROM user WHERE group_id &gt; 1  
SELECT id FROM user WHERE name ~= admi ORDER BY name DESC LIMIT 1  
SELECT id FROM user WHERE id &gt; 1 AND id &lt; 10  
SELECT id FROM user WHERE name = Bill OR name = Gates

**PHP example:**
> $result = chaozzdb_query ("SELECT id, name FROM user WHERE group_id = 1");  
if (count($result) > 0)  
{  
&nbsp;&nbsp;&nbsp;// loop through the results  
&nbsp;&nbsp;&nbsp;for ($i = 0; $i &lt; count($result); $i++)  
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;echo "The user called ".urldecode($result[$i]['name'])." has the ID {$result[$i]['id']}";  
}

> [!TIP]
> SELECT * is faster than SELECT field1, field2 because it executes less code. It does however return a bigger array, thus is less memory efficient.

## DELETE (FROM and [WHERE])
**Return value: true or false (false means an error occured)**

**Examples:**
> DELETE FROM user  
DELETE FROM user WHERE name != administrator  
DELETE FROM user WHERE id &gt; 1 AND id &lt; 10  
DELETE FROM user WHERE name = Bill OR name = Gates

**PHP example:**
> $name = "Gates, Bill";  
$name = urlencode($name);  
$result = chaozzdb_query ("DELETE FROM user WHERE name != $name");

## UPDATE (SET and [WHERE])
*Return value: true or false (false means an error occured)*

**Examples:**
> UPDATE user SET name = bill, group_id = 2 WHERE id &gt; 1  
UPDATE user SET name = bill, group_id = 3 WHERE id &gt; 1 AND name = Hank  
UPDATE user SET name = Bill Gates WHERE name = Bill OR name = Gates

**PHP example:**
> $name = "Gates, Bill";  
$name = urlencode($name);  
$result = chaozzdb_query ("UPDATE user SET name = $name, group_id = 2 WHERE id &gt; 1");

## INSERT (INTO and VALUES)
***Return value: ID of new record or 0 (0 means an error occured)*

**examples:**
> INSERT INTO user VALUES (chaozz, password123, 1)</pre>

**PHP example:**
> $name = urlencode('Gates, Bill');  
$password = chaozzdb_password ($password);  
$group_id = 1;  
$result = chaozzdb_query ("INSERT INTO user VALUES $name, $password, $group_id");  
echo "The ID of this new user is $result";

## Error checking
There is basic error checking in chaozzDB; it will check for the existence of database files, and if there are any records present, but it does not check for bad queries. Use the proper syntax as explained in this document.

If you want to see the last error that was thrown by chaozzDB, check the variable *$chaozzdb_last_error*.
If *$chaozzdb_last_error* is an empty string then the last query was succesful.

**Examples:**
> if ($chaozzdb_last_error != "")  
{  
&nbsp;&nbsp;&nbsp;echo "An error occured: $chaozzdb_last_error");  
&nbsp;&nbsp;&nbsp;// panic here  
}

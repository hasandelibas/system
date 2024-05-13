
# DF
```php
$df->refGet($path, $default=false)
$df->refSet($path, $value)
$df->refRemove($path)
$df->add("users",["name"=>"John"])           // ["id"=>1, "name"=>"John"]
$df->get("users",1)                          // return first id=1 item
$df->get("users",["name"=>"John"])           // return first name=John item
$df->all("users")                            // return all items
$df->all("users", ["name"=>"John"])          // return all name=John items
$df->set("users",["id"=>1, "name"=>"Adam"])
$df->remove("users",true)                    // Removes all items
$df->remove("users",1)                       // Remove id=1 item
$df->remove("users",["name"=>"Adam"])        // Remove name=Adam
```

# DB

```php
$db->tables()          // selected database table list
$db->columns($table)   // list of table's columns
```

# request.php

```php
upload($param,$folder, $name=false) -> return file_name, if $name pass, create a new file name
get   ($param,$default=false)
post  ($param,$default=false)
all   ($param,$default=false)
alls  ($params,$func)
alls  (["name","surname","age"] , "trim" ) 
// ["name"=>"John","surname"=>"Doe","age"=>"26"]
alls  (["name"=>"DEFAULT_NAME","surname"=>"","age"=>25]) // with default value

session($key,$default=false)
cookie($key,$value,$expires)

get()                      // is method GET
post()                     // is method POST
json()                     // get posted json data
json(["success"=>"true"])  // json response
success($data,$message="") // ["status"=>"success","data"=>$data, "message"=>$message]
error($data,$message="")   // ["status"=>"error","data"=>$data, "message"=>$message]

redirect() -> refresh page
redirect("/app") -> redirect "/app"
redirect("/app", true)  -> moved save to chache

message("Save Data") -> save message to session
message()            -> get message from session
```

## login
```php
login  ( $id          , "role"=null )
logout ( "role" = null)
id     ( "role" = null)         // false or $id
```


## env

```php
domain()    -> return domain
url($path)  -> return full url
```


## router
```php
if(router("profile")){
  write("Profile")
})

if(router("news/:slug-:id","param_")){
  write("news");
  write($param_slug);
  write($param_id);

  write( get("param_id") );
})

```


# functions

```php 
slugify($text);
write($obj); 
startsWith($text,$search);
endsWith($text,$search);
csv_parse($text);
csv_stringify($array);

```

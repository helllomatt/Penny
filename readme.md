# Penny

## Installing
It's easiest to start out using the quick-start.

```
$ git clone https://git.helllomatt.com/matt/penny-quickstart.git
    [cloning]
$ cd penny-quickstart
$ composer install
```

### Initializing Penny
There's a command to help you get started right away, after cloning and letting composer run.

```
$ php penny init
What is the name of your first theme? (no spaces) <- this is the folder name of your theme, and how you will refer to it in configuration files
    Downloading theme shell...
    ...
What is the name of your first website? (no spaces) <- this is the folder name of your site, and how it will be addressed throughout the runtime
    Downloading site shell...
    ...
What URL should your website respond to? (eg: localhost/default) <- when you go to this URL, this site will answer
```

After initializing you can find your site in `sites/<site name you defined above>`, and your theme in `themes/<theme name you defined above>`

## Creating a new site

```
$ php penny new-site
What is the name of your website? (no spaces)
What URL should your website respond to? (eg: localhost/default)
What theme are you going to use? <- folder name of the theme
```

## Creating a new theme

```
$php penny new-theme
What is the name of your theme? (no spaces)
```

## Complete `./config.json` Example

```
{
    "siteRootFolder": "sites/",
    "apiRootFolder": "apis/",
    "themeRootFolder": "themes/",
    "apiIdentity": "api",
    "globalFolder": "themes/global",
    "globalScripts": [
        "global/test-global.js"
    ],
    "globalStyles": [
        "global/main-global.css"
    ]
    "default": {
        "folder": "default",
        "theme": {
            "folder": "default"
        },
        "domain": "localhost/default"
    },
}
```

|key|value|description
|---|---|---
|siteRootfolder|string|The folder that holds all of the sites
|apiRootFolder|string|The folder that holds all of the apis
|themeRootFolder|string|The folder that holds all of the themes
|globalFolder|string|The folder that holds global resource files (js, css)
|globalScripts|string[]|Array of file paths to scripts that will be added to every website
|globalStyles|string[]|Array of file paths to stylesheets that will be added to every website
|{sitename}|object|A site that has been registered with Penny
|{sitename}.folder|string|Folder to find the site views in (siteRootPath/<sitename>/<sitename>.folder/)
|{sitename}.theme.folder|string|Name of the folder that the theme to be loaded for this site lives in
|{sitename}.domain|string|Name of the domain URL that the site should respond to


## Complete `./sites/{sitename}/config.json` Example

```
{
    "routes": {
        "/": {
            "view": "home.view.php",
            "vars": {
                "title": "Home"
            }
        },
        "/about": {
            "view": "about.view.php",
            "theme": "secondary.php",
            "vars": {
                "title": "About"
            }
        },
        "/data/{name}": {
            "view": "data-processing.view.php",
            "vars": {
                "title": "Data"
            },
            "autoload": {
                "Test\\": "test/"
            },
            "variables": {
                "name": {
                    "required": true
                }
            }
        }
    }
}
```

|key|value|description
|---|---|---
|routes|object|Object of all routes, key being the route, value being the data for the rout
|routes.{route}.view|string|File path of the view to be loaded when the route matches
|routes.{route}.vars|object|Key value pairs of variables that can be accessed by the theme
|routes.{route}.theme|string|File name of the theme template to use

### Getting variables in the route path

Variables are defined in curly braces `{}` and can be accessed in the view file.

#### Example:
```
Route: localhost/default/{name}
URL: localhost/default/Matt
View access: $route->variables()['name']
```

#### Variable Validation

You can validate the variables before the page is loaded, right from the route data

```
"variables": {
    "name": {
        "required": true,
        "match": //todo
        "errors": {
            "missing:   "This will show up when the variable is missing",
            "notstring":"This will show when the variable is not a string",
            "tooshort": "This will show when the variable is too short",
            "toolong":  "This will show when the variable is too long",
            "mismatch": "This will show then the variable doesn't match the regex",
            "bademail": "This will show when the variable is not a valid email",
            "notnumber":"This will show when the variable is not a valid number",
            "baddate":  "This will show when the variable is not a valid date format",
            "badname":  "This will show if the variable is not a valid name (first[space]last, match)",
            "notbool":  "This will show if the variable is not a boolean"
        }
    }
}
```

## How to make an API
An API is just a class with static functions. You can always expand to use objects from autoloaded classes (defined in the config.json), but the basics are static class functions.

Lets make a simple math API.

Firstly, we have to create a folder in our `apiEndpoint` folder that will house our math namespace and classes. You defined the apiEndpoint in the root `config.json` file, but if you didn't it's just the `apis/` folder.

Our API will live in the namespace `Math` and the class files will live in the folder `apis/math`.

### `apis/math/math.class.php`
Just a simple Math class.

```
<?php

namespace Math;

use Penny\JSON;

class Calculations {
    public static function add($a, $b) {
        JSON::add("result", $a + b);
    }
}
```

Now that we have our math class, we need to set up an endpoint to look at it. We are asking for two variables so that's something we need to keep in mind as well.

### `./apis/config.json`

```
{
    "autoload": {
        "Math\\": "math/"
    },
    "routes": {
        "/math/add/{a}/{b}":
            "action": "Math\\Calculations::add",
            "variables": {
                "a": {"required": true},
                "b": {"required": true}
            }
    }
}
```

Now when someone goes to `localhost/apis/math/add/1/2/` then will get a response of `{"result": 3}`.

## Complete `./apis/config.json` Example

```
{
    "autoload": {
        "Test\\": "test/"
    },
    "routes": {
        "/hello": {
            "action": "Test\\Greeting::say_hello"
        },
        "/hello-person/{name}/{age}": {
            "action": "Test\\Greeting::say_hello_name",
            "variables": {
                "name": {
                    "required": true
                },
                "age": {}
            }
        },
        "say_hello": {
            "action": "Test\\Greeting::say_hello_echo",
            "cli-options": {
                "name": "required",
                "age": "optional"
            }
        }
    }
}
```

|key|value|description
|---|---|---
|autoload|object|Key value pairs of files to autoload, keys being namespaces, values being source directories
|routes|object|Key value pairs of routes to answer to
|routes.{route}.action|string|Static function that will be called when the route is resolved
|routes.{route}.variables|object|Key value pairs of variables that will be injected into the action function (in order!)
|routes.{route}.cli-options|object|Key value pairs of variables given through the CLI that will be injected into the action function (in order!)

### Example

```
Basic:
User goes to: localhost/{config.apiEndpoint}/hello
Action to run: Test\\Greeting::say_hello
Output: {"greeting": "Hello!"}

With Variable:
User goes to: localhost/{config.apiEndpoint}/hello-person/Matt
Action to run: Test\\Greeting::say_hello_name
Output: {"greeting": "Hello Matt!"}

From CLI:
User types: php penny say_hello --name Matt
Action to run: Test\\Greeting::say_hello_echo
Output: "Hello, Matt!"
```

### Example API class

```
<?php

namespace Test;

use Penny\JSON;

class Greeting {
    public static function say_hello() {
        JSON::add("greeting", "Hello!");
    }

    public static function say_hello_name($name, $age = null) {
        $greeting = "Hello, ".$name."!";
        if ($age != null) $greeting .= " You are ".$age." years old!";
        JSON::add("greeting", $greeting)
    }

    public static function say_hello_name_echo($name, $age = null) {
        $greeting = "Hello, ".$name."!";
        if ($age != null) $greeting .= " You are ".$age." years old!";
        echo $greeting;
    }
}
```

## Theme Example and Functions

```
<!doctype html>
<html>
    <head>
        <meta charset='utf-8' />
        <?php echo $view->baseHref(); // BIG TIME REQUIRED ?>
        <title>
            <?php echo $view->variable('title'); // variable got from the site config.json file?>
        </title>
        <?php echo Penny\ViewResponse::getGlobalStyles(); ?>
    </head>

    <body>
        <?php
        $view->includeThemeFile('header.php'); // includes a file from the same theme folder into the template
        $view->contents(); // output of the view file
        ?>

        <?php echo Penny\ViewResponse::getGlobalScripts(); ?>
    </body>
</html>

```

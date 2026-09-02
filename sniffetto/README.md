# Micron - PHP API REST Framework
A small and usefull PHP Api REST framework.

## Table of contents
* [General info](#general-info)
* [Technologies](#technologies)
* [Setup](#setup)
* [Utilization](#utilization)
* [API](#api)
* [Sniffetto endpoints](#sniffetto-endpoints)
* [Demos](#demos)


## General info

This framework allow you to build **API Rest using PHP** in a very easy way. It also provides usefull helper class, like DataHelper or Database that allow you to connect and make prepared query to MySql database in a very fast and simple way. There is also a library for user authentication with **JWT Token**. **All the framework classes throws php exceptions on error, so is strongly recommended using "try-catch" block for wrap your code.** Micron has an internal PHP routing class and this provides an easy way for **build readble URI**. It supports all HTTP method (following **REST guidelines**) and provides all the **responses** cases (according to HTTP Code) in **JSON** format thanks to Response Class. Routing uses anonymous function for execute your code, so is required to put your code in a function to be run.

## Technologies
* PHP 8.
* PDO for Database interaction.
* OOP.
* Functional Programming.

## Setup
* clone the repo.
* In `.htaccess` file put your "index file" location. I recommend keeping the default setting.
* In `config.php` file put your database information.
* In `config.php` put your jwt secret key.

## Utilization
Be inspierd by `api` folder, when you clone the repo this folder contains a working database-less example for auth, GET and POST request.
Micron is very easy to use, follow this simple steps:

* Create a resource's php file. To access all Micron functions you only need to require `Micron.php`. In this file put all your resource's functions.
* Defining a resource function you need to add a funciton parameter of type's Request. In this function parameter you will find all request informations like the request body or the query params.
* Create a response object from Response class, this provides all methods for JSON responses.
* Wrap your code with a `try-catch` block, this will help you to manage errors. Every exception throw by the helper classes containts the relative HTTP code, so (just see the example file) in the `catch` section put this code `$response->response($e->getMessage(), array(), false, $e->getCode() );` this will send the exception relative JSON response.
* in the `try` section put your resource code, make your database code and don't forget to take the token if required (`$token = DataHelper::getToken();`) and verify it (`JWT::verify($token)`);
* in `index.php` write your route: create an object from Route class (already done in the code) and use his methods to define the route. All methods accept 5 parameters:
  1. `string $route` -> URI of the resources, can accept multiple parameters in bracket (example `product/{id}` in the `$params` array you will find a key `id` with         the correct value: `product/1` -> $params["id"] will contain `1`.
  2. `$callback` -> this parameter has to be an anonymous function, and will be a function defined in php files in `api` folder. If you want to access Request data don't forget to pass the $request paramater.
  3. `array $header` put here your headers. There are preconfigured array but don't worry, you can use what header you prefer. The form has to be "header" => "value"
      for example : `Access-Control-Allow-Origin : *` in the array will be `"Access-Control-Allow-Origin" => "*"`.
  4. `array $allowedQueryParams`, in this array you have to put all the allowed keys passed like variables in the URI.
  5. `array $middlewareSettings`, you can define 2 keys for this array : TOKEN_CONTROL -> is a boolean value, if true the Middleware will check for the token; TOKEN_AUTH -> is an array, in this array you have to put some keys existent in the token body with the expected value.
* Now define the route according to the desired parameters, (check the example in source code, is very clear). `Route class` has a method for every HTTP method, and you can repeat the same URI with a different method: `$route->get("example", function(){ myGETFunction();});` and `$route->post("example", function(){myPOSTFunction();});` will be two different paths!
* Is strongly reccomended to follow the REST guidelines in the routes definition:
  1. `$route->post()` -> create resource.
  2. `$route->get()` -> read resource.
  3. `$route->put()` -> update resource.
  4. `$route->delete()` -> delete resource.
  
* **Enjoy Micron and check the API section for other useful method!**

## API

### DataHelper
Provides useful methods for retriving data.

| Name | Parameters | Description | Return value |
| ---- | ---------- | ----------- | ---------------- |
| `__construct()` | `none` | Create a new object of DataHelper | `DataHelper` |
| `postGetBody()` | `none` | read the data upcoming in the body from the php input | `array` |
| `getToken()` | `none` | read the token from the upcoming headers | `string` |
| `checkParameters()` | `array<ParamKey> $keys` => this array must contains all the body key to check, every array's item is an instance of `ParamKey Class` <br /> `array $requestBody` => the request body (take this with `postGetBody()` | Check if the incoming parameters key is present and if respect the setted constraints | `bool` |
| `convertAdjacencyListToNestedObject(array $adjacency_list, int $index = 0, string $id_key = "id", string $parent_id_key = "parent_id")` | `array $adjacency_list` => an associative array that models an adjacency list <br /> `int $index = 0` => Starting index, default value is strongly raccomanded <br /> `string $id_key = "id" `=> the id key in the data <br /> `string $parent_id_key = "parent_id"` => the parent id in the data | Make a nested object (tree) starting from an Adjacency List Array | `Node` |
|`checkIfSomeParametersInBody(array $keys, array $requestBody)` | `array<String> $keys` => set of expected keys <br /> `array $requestBody` => array (usually the request body) where to check if has some $keys in it's keys.  | Check if some items of the $keys array is a keys of the $requestBody array. | `bool` |
| `rrmdir(string $path_to_remove)` | `string $path_to_remove` => a path (on the server's filesystem). | Recursively remove the given path from the server. This function delete both files and folders. | `void` |
| `log(string $text)` | `string $text` => the text to log. | This function log into a text file the given text. The function will add date and time. The log folder is `/micron-logs` and will be automatically created. | `void` |
| `fromSecondsToTime(int $seconds)` | `int $seconds` => seconds to convert. | Convert the given seconds in the 'h m s' format. | `string` |

### Route
Provides routing method, use this for build your paths.

| Name | Prameters | Description | Return value |
| ---- | --------- | ----------- | ---------------- |
| `__construct(array $defaultMiddlewareConfig = ['TOKEN_CONTROL' => true])` | `array $defaultMiddlewareConfig` => this array setup the default behavior of the middlewere. Uses 2 keys : `TOKEN_CONTROL` => is a boolean, if true make the middleware check for the token; `TOKEN_AUTH` => is an array, the keys must be token body keys and the values are the expected value for that key. | Instantiate a Route object and set up some default Middleware rules | `Route` |
| `get()` | `string $route` => path for reach the resource <br />  `function $callback(Request $request)` => function to be executed, admit a parameter of Request class type. <br /> `array $header` => headers setted by resource in form of `"header" => "value"` <br /> `array $middlewareSettings` => this array setup the middleware for the specific call. Uses 2 keys : `TOKEN_CONTROL` => is a boolean, if true make the middleware check for the token; `TOKEN_AUTH` => is an array, the keys must be token body keys and the values are the expected value for that key. <br /> `array $allowedQueryParams` => this array can contains the allowed URI variables (e.g. http://test.com?tvariable=test&v2=t2, in this array you have to put "tvariable" and "v2" other variables will be ignored.  | define a route with GET HTTP method. | `void` |
| `post()` | `string $route` => path for reach the resource <br />  `function $callback(Request $request)` => function to be executed, admit a parameter of Request class type. <br /> `array $header` => headers setted by resource in form of `"header" => "value"` <br /> `array $middlewareSettings` => this array setup the middleware for the specific call. Uses 2 keys : `TOKEN_CONTROL` => is a boolean, if true make the middleware check for the token; `TOKEN_AUTH` => is an array, the keys must be token body keys and the values are the expected value for that key. <br /> `array $allowedQueryParams` => this array can contains the allowed URI variables (e.g. http://test.com?tvariable=test&v2=t2, in this array you have to put "tvariable" and "v2" other variables will be ignored. | define a route with POST HTTP method. | `void` |
| `put()` | `string $route` => path for reach the resource <br />  `function $callback(Request $request)` => function to be executed, admit a parameter of Request class type. <br /> `array $header` => headers setted by resource in form of `"header" => "value"` <br /> `array $middlewareSettings` => this array setup the middleware for the specific call. Uses 2 keys : `TOKEN_CONTROL` => is a boolean, if true make the middleware check for the token; `TOKEN_AUTH` => is an array, the keys must be token body keys and the values are the expected value for that key. <br /> `array $allowedQueryParams` => this array can contains the allowed URI variables (e.g. http://test.com?tvariable=test&v2=t2, in this array you have to put "tvariable" and "v2" other variables will be ignored. | define a route with PUT HTTP method. | `void` |
| `delete()` | `string $route` => path for reach the resource <br />  `function $callback(Request $request)` => function to be executed, admit a parameter of Request class type. <br /> `array $header` => headers setted by resource in form of `"header" => "value"` <br /> `array $middlewareSettings` => this array setup the middleware for the specific call. Uses 2 keys : `TOKEN_CONTROL` => is a boolean, if true make the middleware check for the token; `TOKEN_AUTH` => is an array, the keys must be token body keys and the values are the expected value for that key. <br /> `array $allowedQueryParams` => this array can contains the allowed URI variables (e.g. http://test.com?tvariable=test&v2=t2, in this array you have to put "tvariable" and "v2" other variables will be ignored. | define a route with DELETE HTTP method. | `void` |
| `notFound()` | `string $path` => path of the file to be included | attach a file that manage the "resource not found" case. | `void` |
| `enableCORS()` | `string $allowedOrigin = "*"` => Parameter for set allowed origin. "*" By default. | Is used for manage the Preflight CORS request. | `void` |

### Request
Provides all the information of a Micron Request. And object of this class will be passed to the callback function in the various routing functions. Usually this class is instantiated by the Micron's Middleware, so you should not create an object of this class, you have only to use it in your callback functions.

| Name | Type | Description |
| ---- | ---- | ----------- |
| `Uri` | `string` | Uri of the incoming request |
| `method` | `string` | Http method of the incoming request |
| `URIparams` | `array` | This array will contains the uri params with the values. E.g. `request/{id}/{param}`, the URIparams array will be ['id' => idvalue, 'param' => paramValue] |
| `requestBody` | `array` | The body of the incoming request. This array contains all possible body, so Raw body, `$_FILES` and the query params |
| `authTokenBody` | `array` | The body of the token, if present and if validate. |

### Response
Provides an useful set of JSON responses with preconfigured HTTP code or completely configurable JSON response.

| Name | Prameters | Description | Return value |
| ---- | --------- | ----------- | ---------------- |
| `success()` | `string $message` => message that will be displayed in response JSON <br /> `array\|object\|null $data` => data that will be returned in response JSON | display a JSON response and set the HTTP Code 200 | `void` |
| `created()` | `string $message` => message that will be displayed in response JSON <br /> `array\|object\|null $data` => data that will be returned in response JSON | display a JSON response and set the HTTP Code 201 | `void` |
| `updated()` | `string $message` => message that will be displayed in response JSON <br /> `array\|object\|null $data` => data that will be returned in response JSON | display a JSON response and set the HTTP Code 204 | `void` |
| `badRequest()` | `string $message` => message that will be displayed in response JSON | display a JSON response and set the HTTP Code 400, set "state key" in response to `false` | `void` |
| `unhatorized()` | `string $message` => message that will be displayed in response JSON | display a JSON response and set the HTTP Code 401, set "state key" in response to `false` | `void` |
| `forbidden()` | `string $message` => message that will be displayed in response JSON | display a JSON response and set the HTTP Code 403, set "state key" in response to `false` | `void` |
| `notFound()` | `string $message` => message that will be displayed in response JSON | display a JSON response and set the HTTP Code 404, set "state key" in response to `false` | `void` |
| `internalServerError()` | `string $message` => message that will be displayed in response JSON | display a JSON response and set the HTTP Code 500, set "state key" in response to `false` | `void` |
| `notImplemented()` | `string $message` => message that will be displayed in response JSON | display a JSON response and set the HTTP Code 501, set "state key" in response to `false` | `void` |
| `response()` | `string $message` => message that will be displayed in response JSON <br /> `array $data` => data that will be returned in response JSON <br /> `bool $state` => boolean rappresentation of call success <br /> `int $http_code` => HTTP code to be setted | display a JSON response configured with given parameters | `void` |
| `responseAndContinueScript(string $text_for_response, bool $response_state = true, int $response_http_code = 200)` | `string $text_for_response` => message that will be displayed in response JSON <br />  `bool $response_state` => boolean rappresentation of call success <br /> `int $response_http_code` => HTTP code to be setted | send a JSON response without ending the script. The connection with the client will be closed but the script execution will continue. | `void` |


### JWT (JSON Web Token)
Class that manage JWT tokens. All the methods throw Exceptions on errors. The exception code will be the HTTP Code relative to the error occured.

| Name | Prameters | Description | Return value |
| ---- | --------- | ----------- | ---------------- |
| `JWT()` | `array $body` => body of the token, usually filled with user informations <br/> `string $secret_key` => optional, by default the value putted in `config.php` <br/> `int $hours_before_expire = 24` => optional, 24 hours by default, this paramters will set the token expiration | Constructor of JWT class, this will create and configure a JWT Bearer Token. | `JWT Object` |
| `getToken()` | `none` | Return the JWT Token created by the constructor. | `string` |
| `getBody()` | `none` | Return the body of the token. | `array` |
| `decode()` | `string $token` => a JWT Token to verify and decode <br /> `string $secret_key = JWT_SECRET` => optional, set the secret key for decode the token | Verify and decode the given JWT Token and return the body. | `array` |
| `verify()` | `string $token` => a JWT Token to verify and decode <br /> `string $secret_key = JWT_SECRET` => optional, set the secret key for decode the token | Verify the given JWT Token. | `bool` |

### Database
Manage MySQL Database connection and interaction, extends PDO and you can access all PDO's functions.

| Name | Prameters | Description | Return value |
| ---- | --------- | ----------- | ---------------- |
| `__construct()` | `none` | Create new Database object | `Database` |
| `ExecQuery(string $query, array $params = [])` | `string $query` => an SQL query. You can put inside params (`:param` notation) and the function automatically prepare the query with value passed in second argument. <br /> `array $params` => params for query preparation. This is an associative array, the key is the param name and the value is the value to be binded in the query. | Execute a query on database. The query can be prepared or not. Throw exception on error. | `PDO Statement` |
| `Database::SExecQuery(string $query_string, array $params = [])` | `string $query_string` => an SQL query. You can put inside params (`:param` notation) and the function automatically prepare the query with value passed in second argument. <br /> `array $params` => params for query preparation. This is an associative array, the key is the param name and the value is the value to be binded in the query. | This is the static version of ExecQuery, can be run without create a Database object. This function open and close the connection so use only for one or two consecutive queries. | `PDO Statement` |
| `getTableScheme(string $tableName)` | `string $tableName` | Return the fields of the given table if exist in the Database. Throw exception for non valid table. | `array` |
| `Table(string $tableName)` | `string $tableName` | Return a model of the table by the table name. If the table not exist will throw an exception | `DBTable` |

### DBTable
Manage MySQL Database connection and interaction, extends PDO and you can access all PDO's functions.

| Name | Prameters | Description | Return value |
| ---- | --------- | ----------- | ---------------- |
| `__construct()` | `Database $database` </br> `string $tableName` | Create new Table object | `DBTable` |

## Sniffetto endpoints

This is the concrete set of routes registered in this app's `index.php` on top of Micron. Every response shares the same JSON envelope produced by `Response::success()`:

```json
{
  "result": { "state": true, "description": "<message>" },
  "data": { }
}
```

`result.state` is `false` and `data` is `[]` on error (unmatched route → `404.php`; thrown exception → caught in `index.php` and returned with `$th->getCode()` as HTTP status). **No auth**: token control is disabled app-wide (`tokenControl: false`), and CORS is open to any origin (`enableCORS()`), so none of these routes require a token or restrict callers.

### `GET /`
Dashboard page, the only route that does **not** return JSON. It overrides Micron's default `Content-Type: application/json` header (set via `header()` before echoing) and renders a single self-contained HTML page — inline `<style>`, no external assets/includes — showing the same data as `/v1/vona/{vulcano}` and `/v1/terremoti` (it calls `getLastVONA()`/`getLastTerremoto()` directly, no HTTP round-trip) as two stacked cards: VONA bulletin on top (with a colored badge for `current_color`), latest earthquake below. Each card falls back to a "no data yet, run `/sniff`" message when its table is empty. Layout is a single centered column that switches its field grid to one column under 420px width, and follows `prefers-color-scheme` for light/dark.

### `GET /status`
Health-check / hello-world route. `data` is always `[]`.
```json
{ "result": { "state": true, "description": "Sniffetto 1.0" }, "data": [] }
```

### `GET /sniff`
The ingestion endpoint — meant to be hit by an external cron (`cron-job.org`), not by the firmware. It runs both scrapers (`lib\VONA::sniff()` for Etna, `lib\terremoti::sniff()`) and tries to persist a new row for each. It does **not** return the scraped bulletin/earthquake data itself — only a status summary of the ingestion attempt:
```json
{
  "result": { "state": true, "description": "Operazione completata!" },
  "data": {
    "VONA": { "topic": "VONA", "update": true, "error": false, "errorMessage": "" },
    "terremoti": { "topic": "terremoti", "update": false, "error": false, "errorMessage": "" }
  }
}
```
- `update: true` means a new record was actually inserted (i.e. the source had a `notice_number`/`event_id` not already in the DB — see `updateVONA()`/`updateTerremoti()` in `dbfunctions.php`); `update: false` means nothing new was found, so no row was written.
- `error: true` means the scraper itself failed (e.g. INGV page/PDF unreachable, or the FDSN webservice down); `errorMessage` carries the detail. A scraper error does not stop the other scraper from running.

### `GET /v1/vona/{vulcano}`
Read endpoint polled by the firmware for the current alert color. `data` is the latest `vona` DB row, flattened (RedBean returns every column as a string, including `id`):
```json
{
  "result": { "state": true, "description": "Operazione completata!" },
  "data": {
    "id": "12",
    "created": "2026-07-01 10:15:03",
    "disclaimer": "Scollo, S., Prestifilippo, M., ... (2019) ... Corradini, S., ... (2018)",
    "source": "https://www.ct.ingv.it/sezioniesterne/Comunicati/ComunicatiVonaN.php",
    "issued": "20260701/0800Z",
    "volcano": "ETNA",
    "current_color": "ORANGE",
    "previous_color": "YELLOW",
    "notice_number": "2026042",
    "activity_summary": "Explosive activity ..."
  }
}
```
- `current_color`/`previous_color` are free text taken verbatim from bulletin fields (4)/(5) — expected values are `GREEN`/`YELLOW`/`ORANGE`/`RED`, matching the colors the firmware's `analogWrite` logic understands (anything else lights solid white).
- **`{vulcano}` is accepted but ignored**: `getLastVONA()` always returns the single most recent `vona` row regardless of its value. It doesn't even need to be present in the request — Micron's path-param matching (`Route::navigate()`) only checks that the route's static prefix (`v1/vona`) matches; a missing trailing segment still matches the route with an empty/unset param. This is why the firmware calls the bare `http://www.noexit.it/sniffetto/v1/vona` (no volcano segment at all) and still gets a response.
- If the `vona` table is empty (nothing ever sniffed successfully), `data` is `[]` — the firmware's raw substring search for `"current_color"` then finds nothing and falls into its unrecognized-color branch (solid white).

### `GET /v1/terremoti`
Read endpoint for the latest nearby earthquake. `data` is the latest `terremoti` DB row, flattened:
```json
{
  "result": { "state": true, "description": "Operazione completata!" },
  "data": {
    "id": "7",
    "created": "2026-07-01 09:00:12",
    "disclaimer": "",
    "source": "http://webservices.ingv.it/fdsnws/event/1/query?format=text&lat=37.53&lon=14.97&maxradiuskm=200&limit=1",
    "event_id": "12345678",
    "event_time": "2026-07-01T08:55:30",
    "latitude": "37.75",
    "longitude": "15.00",
    "depth": "2.3",
    "magnitude": "1.8",
    "location": "Etna area"
  }
}
```
- No path or query params — always the single latest event within 200km of the hardcoded Etna coordinates (`lat=37.53, lon=14.97`).
- `disclaimer` is always `""` here (`lib\terremoti::sniff()` never sets one, unlike the VONA scraper).
- No client in this repo currently consumes this route (the firmware only polls `/v1/vona`).

## Demos
A rich collection of code's examples.


## Inspiration 
The `navigate` private function is inspired by a source code read on [Help in coding](https://helpincoding.com), i have modified it and passed from "inlcuding file" to "anonymous functions".  

## About me
I'm an Italian Full-stack Web Developer since 2019 with **TacoSoft s.r.l**. and a student of **Politecnico di Torino**, in this moment i'm having fun with **ReactJS**, but i started with **PHP** and I can't leave him. Follow me on my [Linked-In page](https://www.linkedin.com/in/girolamo-dario-pisano-375aa514b/) to be updated with some other awesome project!

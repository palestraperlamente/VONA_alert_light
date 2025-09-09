<?php
// basato sul framework di https://github.com/gpisano97/Micron
// API "sniff" chiamata da https://console.cron-job.org/jobs

use core\DataHelper\DataHelper;
use core\DataHelper\ParamKey;
use core\Response;
use core\MiddlewareConfiguration;
use RedBeanPHP\R as R;
use lib\VONA;
use lib\terremoti;

require_once "micron/Micron.php";
require "vendor/autoload.php";
require "lib/VONA.php";
require "lib/terremoti.php";

// include le funzioni di lettura/scrittura al db
include_once "dbfunctions.php";

// apre la connessione al database
try {
    if (array_key_exists('HTTP_HOST', $_SERVER) && $_SERVER['HTTP_HOST'] == 'www.noexit.it') {
        R::setup( 'mysql:host=mysql.netsons.com;dbname=ykqtppjf_sniffetto', 'ykqtppjf_dbuser', '$niff3tt0!DB' );
    } else {
        R::setup( 'mysql:host=localhost;dbname=sniffetto', 'root', 'root' );
    }
    //R::fancyDebug(TRUE);
} catch (Exception $exc) {
    echo $exc->getTraceAsString();
    die;
}

//this class start the router, this is the core of micron!
$route = new Route(defaultMiddlewareConfig: MiddlewareConfiguration::getConfiguration(tokenControl: false)); //here we are defining a general middleware behavior. The JWT Token Control is enabled by default but for this example is useless!
                                                                                                            //so we are turning it off
$route->enableCORS();

try {

    //this is a simple API REST hello world

    //this function listen for a GET request with '/' URL.
    //The $request parameter will contain informations about the request 
    $route->get("/", function(Request $request){
        //this class provide a set of predefined response with the relative HTTP Code, the response content type is always application/json
        $response = new Response();
        //this response set the http code to 200 and return a message. Can also return an array (or object)
        $response->success("Sniffetto 1.0"); //all the Response class methods stop the process... so use it knowing that they call an "exit"!
    }); //by default the JWT token control is enabled

    /*
    $route->post("/", function(Request $request){
        $response = new Response();
        //an attribute of the $request object is the requestBody, here you can find all the request data, like raw body, $_POST keys etc.
        $body = $request->requestBody;

        //the DataHelper class provide some usefull fuction for handling data, and some utilities like textual logs on the server.
        $expectedBodyParams = [
            new ParamKey("field1"),
            new ParamKey("field2")
        ]; // here we define the incoming data. 
        if(DataHelper::checkParameters($expectedBodyParams, $body)){ //check if all the required data is sent in the body. This will automatically ignore the params differt from field1 and field2
            $response->created("Very well, the data is in!!");
        }
        $response->badRequest("Missing body data.");
    }); //by default this request accepts application/json content, you can change this in the middlewareSettings.


    //this call provide an example of different body checking operation.
    $route->post("dontmatterwhichparams", function(Request $request){
        $body = $request->requestBody;

        $expectedBodyParams = ["optionalField1", "optionalField2"];

        if(DataHelper::checkIfSomeParametersInBody($expectedBodyParams, $body)){ //this function check if some of the params is in the body, you have to manually ignore the possible unwanted params
            $foundParams = [];
            if(isset($body["optionalField1"])){
                array_push($foundParams, "optionalField1");
            }
            if(isset($body["optionalField2"])){
                array_push($foundParams, "optionalField2");
            }

            $foundParams = implode(", ",$foundParams);

            Response::instance()->success("Here is the body: $foundParams ...");
        }
        Response::instance()->badRequest("Send me some data please...");
    });

    //let's use the path params!
    //you can define complex and dynamic paths with the use of path params
    //this request will make an array of numbers from 0 to the loop_limit param.
    //You will find the path params in the $request object
    //in the Request object the path Params are called "URIparams"
    //the type of the path params can be only "numeric" and "string"
    $route->get("count/to/{loop_limit:numeric}", function(Request $request){
        $numbers = [];
        for($i = 0; $i <= $request->URIparams["loop_limit"]; $i++){
            array_push($numbers, $i);
        }

        Response::instance()->success("Here is your count!!", $numbers); //this static function provide a Response object to access to all it's functions.
    });

    //Let's works now with the query params!!
    //The query params are additional data placed at the end of the URI... after the question mark!
    //Micron handle this additional info with.... The Request object!!! When i write "all the request data are in the request object" is true!
    //In addition you have to specifiy the query params allowed (and the type that can be numeric or string) in order to use them.
    $route->get("cities", function(Request $request){
        $database = ["Italy" => ["Milan", "Rome", "Turin"], "France" => ["Paris", "Marseille", "Nice"], "Spain" => ["Madrid", "Barcelona", "Seville"]];
        $nations = ["Italy", "France", "Spain"];

        if(isset($request->queryParams["country"])){ //in the queryParams associative array you will find all your allowed query params.
            if(in_array($request->queryParams["country"], $nations)){
                Response::instance()->success("Here is the cities of {$request->queryParams["country"]}!", $database[$request->queryParams["country"]]);
            }
            else{
                Response::instance()->badRequest("You are searching for a non supported country");
            }
        }

        Response::instance()->success("Here is the cities!", $database);
    }, allowedQueryParams: ["country" => "string"]);
    */

    $route->get("/sniff", function(Request $request){

        $buffer = [];

        // VONA
        $buffer['VONA'] = [
            'topic' => 'VONA',
            'update' => false,
            'error' => false,
            'errorMessage' => ''
        ];
        $vona = new VONA('etna');
        $data = $vona->sniff();
        if (!$data['error']) {
            if (updateVONA($data)) {
                $buffer['VONA']['update'] = true;
            }
        } else {
            $buffer['VONA']['error'] = true;
            $buffer['VONA']['errorMessage'] = $data['errorMessage'];
        }

        // terremoti
        $buffer['terremoti'] = [
            'topic' => 'terremoti',
            'update' => false,
            'error' => false,
            'errorMessage' => ''
        ];
        $hq = new terremoti();
        $data = $hq->sniff();
        if (!$data['error']) {
            if (updateTerremoti($data)) {
                $buffer['terremoti']['update'] = true;
            }
        } else {
            $buffer['terremoti']['error'] = true;
            $buffer['terremoti']['errorMessage'] = $data['errorMessage'];
        }

        $response = new Response();
        $response->success("Operazione completata!", $buffer);
    });

    $route->get("/v1/vona/{vulcano:string}", function(Request $request){
        $response = new Response();
        $response->success("Operazione completata!", getLastVONA());
    });

    $route->get("/v1/terremoti", function(Request $request){
        $response = new Response();
        $response->success("Operazione completata!", getLastTerremoto());
    });

    $route->notFound("404.php");
} catch (\Throwable $th) {
    $response = new Response();
    $response->response($th->getMessage(), [], false, $th->getCode());
    exit;
}

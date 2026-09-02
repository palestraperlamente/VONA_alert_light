<?php
// avviare UniServer Generic 82, MYSQL e APACHE, http://localhost/sniffetto/, http://localhost/sniffetto/sniff
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
    if (array_key_exists('HTTP_HOST', $_SERVER) && str_ends_with($_SERVER['HTTP_HOST'], 'noexit.it')) {
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

    // dashboard HTML one-page: mostra gli ultimi dati di /v1/vona e /v1/terremoti in due card
    $route->get("/", function (Request $request) {
        header('Content-Type: text/html; charset=UTF-8');

        $field = function (array $data, string $key) {
            return (isset($data[$key]) && $data[$key] !== '') ? htmlspecialchars((string) $data[$key]) : '—';
        };

        $vona = getLastVONA();
        $terremoto = getLastTerremoto();

        $alertColors = [
            'GREEN'  => '#2e7d32',
            'YELLOW' => '#f9a825',
            'ORANGE' => '#ef6c00',
            'RED'    => '#c62828',
        ];
        $badgeColor = $alertColors[strtoupper($vona['current_color'] ?? '')] ?? '#78909c';

        if (empty($vona)) {
            $vonaBody = '<p class="empty">Nessun comunicato VONA disponibile. Esegui <code>/sniff</code> per avviare la raccolta dati.</p>';
        } else {
            $vCurrentColor  = $field($vona, 'current_color');
            $vVolcano       = $field($vona, 'volcano');
            $vPreviousColor = $field($vona, 'previous_color');
            $vNoticeNumber  = $field($vona, 'notice_number');
            $vIssued        = $field($vona, 'issued');
            $vActivity      = $field($vona, 'activity_summary');
            $vCreated       = $field($vona, 'created');

            $vonaBody = <<<HTML
                <span class="badge" style="background:{$badgeColor}">{$vCurrentColor}</span>
                <dl>
                    <dt>Vulcano</dt><dd>{$vVolcano}</dd>
                    <dt>Colore precedente</dt><dd>{$vPreviousColor}</dd>
                    <dt>Bollettino n.</dt><dd>{$vNoticeNumber}</dd>
                    <dt>Emesso il</dt><dd>{$vIssued}</dd>
                    <dt>Riepilogo attività</dt><dd>{$vActivity}</dd>
                    <dt>Rilevato da Sniffetto il</dt><dd>{$vCreated}</dd>
                </dl>
                HTML;
        }

        if (empty($terremoto)) {
            $terremotoBody = '<p class="empty">Nessun terremoto disponibile. Esegui <code>/sniff</code> per avviare la raccolta dati.</p>';
        } else {
            $tLocation  = $field($terremoto, 'location');
            $tMagnitude = $field($terremoto, 'magnitude');
            $tDepth     = $field($terremoto, 'depth');
            $tLatitude  = $field($terremoto, 'latitude');
            $tLongitude = $field($terremoto, 'longitude');
            $tEventTime = $field($terremoto, 'event_time');
            $tCreated   = $field($terremoto, 'created');

            $terremotoBody = <<<HTML
                <dl>
                    <dt>Località</dt><dd>{$tLocation}</dd>
                    <dt>Magnitudo</dt><dd>{$tMagnitude}</dd>
                    <dt>Profondità</dt><dd>{$tDepth} km</dd>
                    <dt>Coordinate</dt><dd>{$tLatitude}, {$tLongitude}</dd>
                    <dt>Orario evento</dt><dd>{$tEventTime}</dd>
                    <dt>Rilevato da Sniffetto il</dt><dd>{$tCreated}</dd>
                </dl>
                HTML;
        }

        echo <<<HTML
            <!doctype html>
            <html lang="it">
            <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Sniffetto — Stato VONA &amp; Terremoti</title>
            <style>
                :root {
                    color-scheme: light dark;
                    --bg: #f2f4f7;
                    --card-bg: #ffffff;
                    --text: #1c1e21;
                    --muted: #5f6570;
                    --border: #e3e6ea;
                }
                @media (prefers-color-scheme: dark) {
                    :root {
                        --bg: #14171a;
                        --card-bg: #1f2327;
                        --text: #f0f1f3;
                        --muted: #9aa1ac;
                        --border: #2c3138;
                    }
                }
                * { box-sizing: border-box; }
                body {
                    margin: 0;
                    padding: 24px 16px 48px;
                    background: var(--bg);
                    color: var(--text);
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                }
                main {
                    max-width: 640px;
                    margin: 0 auto;
                }
                h1 {
                    font-size: 1.5rem;
                    margin: 0 0 4px;
                }
                .subtitle {
                    color: var(--muted);
                    margin: 0 0 24px;
                    font-size: 0.95rem;
                }
                .card {
                    background: var(--card-bg);
                    border: 1px solid var(--border);
                    border-radius: 14px;
                    padding: 20px 24px;
                    margin-bottom: 20px;
                    box-shadow: 0 1px 3px rgba(0,0,0,.06);
                }
                .card h2 {
                    margin: 0 0 12px;
                    font-size: 1.15rem;
                }
                .badge {
                    display: inline-block;
                    color: #fff;
                    font-weight: 600;
                    font-size: 0.85rem;
                    letter-spacing: .02em;
                    padding: 4px 12px;
                    border-radius: 999px;
                    margin-bottom: 14px;
                }
                dl {
                    margin: 0;
                    display: grid;
                    grid-template-columns: minmax(120px, auto) 1fr;
                    gap: 8px 16px;
                }
                dt {
                    color: var(--muted);
                    font-size: 0.85rem;
                }
                dd {
                    margin: 0;
                    font-size: 0.95rem;
                    word-break: break-word;
                }
                .empty {
                    color: var(--muted);
                    font-size: 0.95rem;
                    margin: 0;
                }
                .empty code {
                    background: var(--border);
                    padding: 2px 6px;
                    border-radius: 6px;
                }
                footer {
                    text-align: center;
                    color: var(--muted);
                    font-size: 0.8rem;
                    margin-top: 8px;
                }
                @media (max-width: 420px) {
                    dl { grid-template-columns: 1fr; }
                    dt { margin-top: 8px; }
                }
            </style>
            </head>
            <body>
            <main>
                <h1>🌋 Sniffetto</h1>
                <p class="subtitle">Stato allerta Etna e ultimo terremoto rilevato</p>

                <section class="card">
                    <h2>Comunicato VONA — Etna</h2>
                    {$vonaBody}
                </section>

                <section class="card">
                    <h2>Ultimo terremoto</h2>
                    {$terremotoBody}
                </section>

                <footer>Dati INGV · aggiornati tramite <code>/sniff</code></footer>
            </main>
            </body>
            </html>
            HTML;
    });

    //this is a simple API REST hello world

    //this function listen for a GET request with '/status' URL.
    //The $request parameter will contain informations about the request
    $route->get("/status", function(Request $request){
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

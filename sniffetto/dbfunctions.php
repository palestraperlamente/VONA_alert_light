<?php
use RedBeanPHP\R as R;

// funzioni di comodo
function debug($obj, $die = false): void
{
    print_r(json_encode($obj));
    $die && die();
}
function updateVONA($data): bool {

    // controlla se il dato esiste già...
    $t = R::findOne('vona', 'notice_number = ?', [$data['data'][7]['value']]);
    if (!$t) {
        // aggiunge il dato al db
        $t = R::dispense('vona');
        $t->created = date("Y-m-d H:i:s");
        $t->disclaimer = $data['disclaimer'];
        $t->source = $data['source'];
        $t->issued = $data['data'][2]['value'];
        $t->volcano = $data['data'][3]['value'];
        $t->current_color = $data['data'][4]['value'];
        $t->previous_color = $data['data'][5]['value'];
        $t->notice_number = $data['data'][7]['value'];
        $t->activity_summary = $data['data'][11]['value'];
        $id = R::store($t);
        return true;
    }
    return false;
}

function updateTerremoti($data): bool {

    // controlla se il dato esiste già...
    $t = R::findOne('terremoti', 'event_id = ?', [$data['data']['#EventID']['value']]);
    if (!$t) {
        // aggiunge il dato al db
        $t = R::dispense('terremoti');
        $t->created = date("Y-m-d H:i:s");
        $t->disclaimer = $data['disclaimer'];
        $t->source = $data['source'];
        $t->event_id = $data['data']['#EventID']['value'];
        $t->event_time = $data['data']['Time']['value'];
        $t->latitude = $data['data']['Latitude']['value'];
        $t->longitude = $data['data']['Longitude']['value'];
        $t->depth = $data['data']['Depth/Km']['value'];
        $t->magnitude = $data['data']['Magnitude']['value'];
        $t->location = $data['data']['EventLocationName']['value'];
        $id = R::store($t);
        return true;
    }
    return false;
}

function getLastVONA(): array {
    $res = R::findAll( 'vona' , 'ORDER BY issued DESC LIMIT 1');
    $res = R::beansToArray($res);
    if (sizeof($res) > 0)
        return $res[0];
    else
        return [];
}

function getLastTerremoto(): array {
    $res = R::findAll( 'terremoti' , 'ORDER BY event_time DESC LIMIT 1');
    $res = R::beansToArray($res);
    if (sizeof($res) > 0)
        return $res[0];
    else
        return [];
}
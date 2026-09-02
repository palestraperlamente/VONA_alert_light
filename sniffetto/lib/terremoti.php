<?php

namespace lib;

class terremoti
{
    public function __construct()
    {
    }

    public function sniff(): array
    {
        $result = [
            'disclaimer' => '',
            'source' => '',
            'error' => false,
            'errorMessage' => null,
            'data' => []
        ];

        $source = "http://webservices.ingv.it/fdsnws/event/1/query?format=text&lat=37.53&lon=14.97&maxradiuskm=200&limit=1";
        $result['source'] = $source;

        // estrae i dati e li spezza
        $buff = file_get_contents($source);
        $buff = explode("\n", $buff);
        $head = explode("|", $buff[0]);
        $data = isset($buff[1]) ? explode("|", $buff[1]) : [];

        // il servizio FDSN restituisce solo l'header quando non ci sono terremoti che soddisfano i criteri di ricerca
        if (count($data) < count($head)) {
            $result['error'] = true;
            $result['errorMessage'] = 'Nessun terremoto trovato dal servizio INGV.';
            return $result;
        }

        for ($i = 0; $i < sizeof($head); $i++) {
            $result['data'][$head[$i]] = [
                'key' => $head[$i],
                'value' => $data[$i]
            ];
        }

        return $result;
    }
}

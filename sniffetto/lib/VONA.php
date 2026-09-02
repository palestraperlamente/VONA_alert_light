<?php

namespace lib;

use DOMDocument;
use Smalot\PdfParser\Parser;

class VONA
{
    private $vulcano;

    public function __construct($vulcano)
    {
        $this->vulcano = $vulcano;
    }

    private function sniffEtna(): array
    {
        $result = [
            'disclaimer' => '',
            'source' => '',
            'error' => false,
            'errorMessage' => null,
            'data' => []
        ];

        $source = "https://www.ct.ingv.it/sezioniesterne/Comunicati/ComunicatiVonaN.php";
        $result['source'] = $source;

        // fissa il disclaimer
        $disclaimer = "Scollo, S., Prestifilippo, M., Bonadonna, C., Cioni, R., Corradini, S., Degruyter, W., Rossi, E., Silvestri, M., Biale, E., Carparelli, G., Cassisi, C., Merucci, L., Musacchio, M., Pecora, E. (2019). Near-Real-Time Tephra Fallout Assessment at Mt. Etna, Italy, Remote Sensing , 11(24), 2987 (2019), https://doi.org/10.3390/rs11242987";
        $disclaimer .= PHP_EOL . "Corradini, S., Guerrieri, L., Lombardo, V., Merucci, L., Musacchio, M., Prestifilippo, M., Scollo, S., Silvestri, M., Spata, G., Stelitano, D (2018). Proximal monitoring of the 2011-2015 Etna lava fountains using MSG-SEVIRI data. Geosciences 2018, 8, 140";
        $result['disclaimer'] = $disclaimer;

        // apre la pagina con i dati
        $doc = new DOMDocument();
        $doc->loadHTMLFile($source, LIBXML_NOWARNING | LIBXML_NOERROR);
        $table = $doc->getElementsByTagName('table')->item(0);

        // scorre le righe alla ricerca del bollettino ETNA (salta STROMBOLI e altri eventuali)
        // partendo dalla seconda riga della tabella (saltando la testata)
        $href = '';
        for ($i = 1; $i <= $table->getElementsByTagName('tr')->count(); $i++) {
            // la prima colonna contiene il nome del vulcano
            $tr = $table->getElementsByTagName('tr')->item($i);
            $td = $tr->childNodes->item(0);
            if ($td->nodeValue == 'ETNA') {
                $td = $tr->childNodes->item(3);
                $a = $td->childNodes->item(0);
                $href = $a->getAttribute('href');
                $href = str_replace('../../', 'https://www.ct.ingv.it/', $href);
                break;
            }
        }

        // apre il PDF
        if ($href) {
            $content = file_get_contents($href);

            // estrae il testo
            $parser = new Parser();
            $pdf = $parser->parseContent($content);
            $text = $pdf->getPages()[0]->getText();

            //$dataTm = $pdf->getPages()[$i]->getDataTm();
            $rows = explode("\n", $text);
            $firstRow = true;

            // cicla le righe cercando quelle con la sintassi "(n)"
            foreach ($rows as $row) {
                $row = trim($row);

                // rimpiazza nella prima riga "(1)" con "(1) Title:" per renderlo funzionante alla regexp
                if ($firstRow) {
                    $row = str_replace("(1)", "(1) Title:", $row);
                    $firstRow = false;
                }

                //preg_match("/\((\d+)\)\s*(.*?):\s*(.*)/", $row, $matches);
                preg_match("/\((\d+)\)\s*(.*?):\s*(.*)/", $row, $matches);

                if (sizeof($matches) >= 4) {
                    // riga con identificatore numerico
                    $index = $matches[1];
                    $key = $matches[2];
                    $value = $matches[3];

                    $result['data'][$index] = [
                        'index' => $index,
                        'key' => $key,
                        'value' => $value
                    ];
                } else {
                    // riga di testo appartenente all'indice precedente
                    $result['data'][$index]['value'] .= ($result['data'][$index]['value'] ? ' ' : '') . $row;
                }
            }
        }

        return $result;
    }
    public function sniff(): array
    {
        $ret = true;

        switch ($this->vulcano) {
            case 'etna':
                $ret = $this->sniffEtna();
                break;
        }
        return $ret;
    }
}

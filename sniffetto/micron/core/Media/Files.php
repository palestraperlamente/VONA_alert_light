<?php

namespace core\Media;
class Files
{

    const BASE_PATH_MEDIA =  $_SERVER["DOCUMENT_ROOT"] ."/media/";

    private function splitIdInPathLike(string $id)
    {
        $path = "";
        for ($i = 0; $i < strlen($id); $i++) {
            $path .= $id[$i] . "/";
        }
    
        return $path;
    }

    public static function Move(string $source, string $destination){

    }

    public static function Upload(int $id, string $temp, string $targetFolder = ""){

        /* $path = Files::BASE_PATH_MEDIA."" */
/*         if (!is_dir($path)) {
            mkdir($path);
        }
        for ($i = 0; $i < strlen($fileId); $i++) {
            if (!is_dir($path . $fileId[$i])) {
                mkdir($path . $fileId[$i]);
            }
            $path .= $fileId[$i] . "/";
        }
        $path .= "{$fileId}.{$extension}";
        if (move_uploaded_file($tempName, $path)) {
            $response->created("Upload eseguito correttamente.");
        } */
    }



}

<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SirenController extends AbstractController
{
  /**
  * @Route("siren/{siren_num}", name="siren")
  */
  public function index(string $siren_num)
  {
    $url = 'http://files.data.gouv.fr/sirene/sirene_2018088_E_Q.zip';
    $SIREN_COL = 0;
    $ORGA_COL = 36;

    // recup zip file
    $hostfile = fopen($url, 'r');
    $fh = fopen('siren.zip', 'w');

    while (!feof($hostfile)) {
        $output = fread($hostfile, 8192);
        fwrite($fh, $output);
    }

    fclose($hostfile);
    fclose($fh);

    // unzip
    $zip = new \ZipArchive;
    $res = $zip->open('siren.zip');
    if ($res === TRUE) {
        $zip->extractTo('.');
        $zip_name = $zip->getNameIndex(0);
        $zip->close();
        unlink('siren.zip');
    }

    if ($zip_name) {
      // store data in array
      $csv = array_map('str_getcsv', file($zip_name));

      // find siren number
      foreach ($csv as $data) {
        $tmp = explode(';', $data[0]);
        if ($tmp[$SIREN_COL] == $siren_num && $tmp[$ORGA_COL]) {
          return new JsonResponse(['status' => '200', 'data' => [
            'siren_num' => $siren_num,
            'orga_name' => str_replace('"', '', $tmp[$ORGA_COL]),
          ]]);
        }
      }
    }
    
    return new JsonResponse(['status' => '404', 'message' => 'SIREN Not Found']);
  }
}
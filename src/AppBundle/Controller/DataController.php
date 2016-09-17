<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class DataController extends Controller
{



    public function getData(Request $request)
    {


        $_locale = 'en';

        $inputFileName = $this->get('kernel')->getRootDir() . '/../Tableau hottes de cuisine'. (($_locale == 'en')?'_en':'').'.xls';

        /* Load $inputFileName to a PHPExcel Object  */
        $objPHPExcel = \PHPExcel_IOFactory::load($inputFileName);
        $myWorksheetListInfo = $this->_getWorksheetListInfo($inputFileName);

        $objPHPExcel->setActiveSheetIndex(0);
        $row = $objPHPExcel->getActiveSheet()->getHighestRow() + 1;

        $objWorksheet = $objPHPExcel->getActiveSheet();
        $maxRow = 1;
        $maxCol = 11;
        $total = $myWorksheetListInfo[0]['totalRows'] - 1; // Removing the header row.
        $output = "total=" . urlencode($total); //First echo of all variables
        $error = "non";
        $col = 0;
        $data = array();





        for ($row = 0; $row <= $total; $row++) {


            $data [] = array(
                'type' => urlencode($objWorksheet->getCellByColumnAndRow($col, $row + 2)->getValue()),
                'installation' => urlencode($objWorksheet->getCellByColumnAndRow($col +1, $row + 2)->getValue()),
                'largeur' => urlencode($objWorksheet->getCellByColumnAndRow($col + 3, $row + 2)->getValue()),
                'afficheur' => urlencode($objWorksheet->getCellByColumnAndRow($col + 4, $row + 2)->getValue()),
                'largeur' => urlencode($objWorksheet->getCellByColumnAndRow($col + 5, $row + 2)->getValue()),
                'chapeau' => urlencode($objWorksheet->getCellByColumnAndRow($col + 6, $row + 2)->getValue()),
                'moteur' => urlencode($objWorksheet->getCellByColumnAndRow($col + 7, $row + 2)->getValue()),
                'capacite' => urlencode($objWorksheet->getCellByColumnAndRow($col + 8, $row + 2)->getValue()),
                'photos' => urlencode($objWorksheet->getCellByColumnAndRow($col + 10, $row + 2)->getValue()),
            );
        }



        // replace this example code with whatever you need
        return new Response("Goucou from Service");
    }

}

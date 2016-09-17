<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use AppBundle\Controller\DataController;

class DefaultController extends Controller
{

    public $available_fields = array(
        'type',
        'installation',
        'nbr_moteurs',
        'largeur',
        'afficheur',
        'largeur_cheminee',
        'chapeau',
        'moteur',
        'capacite',
        'ref_moteur',
        'photos'
    );


    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', array(
            'base_dir' => realpath($this->container->getParameter('kernel.root_dir') . '/..'),
        ));
    }

    /**
     * @Route("/xls", name="xls")
     */
    public function xlsAction(Request $request)
    {
        // replace this example code with whatever you need

        $service = $this->container->get('raw_json_data');

        return new Response($service->getData($request));
    }

    public function allAction(Request $request, $_locale)
    {

        $data = $this->_loadRawData($request, $_locale);

        /*
         *
         *  Filter results by Values
         *
         * */

        if ($request->query->get('filter')) {

            $filters = json_decode($request->query->get('filter'), true);

            $data = array_filter($data, function ($el) use ($filters) {

                foreach ($filters as $filterName => $filterVal) {

                    if ($el[$filterName] != $filterVal) {
                        return false;
                    }

                }

                //the element passed all the filters => we keep it.
                return true;


            });

        }


        /*
         *
         *  Sort Params
         *
         * */
        if ($request->query->get('sort')) {
            $field = $request->query->get('sort');

            if (count(explode('-', $field)) == 1) {
                //1   Ascending
                $sort_direction = SORT_ASC;
            } else {
                //2  "-"  is there
                $sort_direction = SORT_DESC;
                $field = explode('-', $field)[1];
            }

            $row_array = array();
            foreach ($data as $key => $row) {
                $row_array[$key] = $row[$field];
            }
            array_multisort($row_array, $sort_direction, $data);

        }


        $json = array('meta' => array('total' => count($data)),
            'data' => $data);

        return new JsonResponse($json);
    }

    public function distinctAction(Request $request, $_locale, $field)
    {


        if (!in_array($field, $this->available_fields)) {

            throw new \Exception("[Arouatek] the field: '$field' is not available ", 1);
        }


        $data = $this->_loadRawData($request, $_locale);

        $distinctValues = $this->_unique_multidim_array($data, $field);


        $distinctValues_reduced = array_reduce($distinctValues, function ($carry, $item) use ($field) {
            if ($item[$field]) {
                $carry [] = $item[$field];
            }
            return $carry;

        }, []);


        $json = array('meta' => array('total' => count($distinctValues_reduced)),
            'data' => $distinctValues_reduced);

        return new JsonResponse($json);


    }


    function _getWorksheetListInfo($datafile)
    {
        if (file_exists($datafile) && is_readable($datafile)) {

            try {
                $FileType = \PHPExcel_IOFactory::identify($datafile);

                $SpreadsheetReaderObj = \PHPExcel_IOFactory::createReader($FileType);
                switch ($FileType) {
                    case 'Excel5':
                    case 'Excel2003XML':
                    case 'Excel2007':
                    case 'OOCalc':
                    case 'SYLK':
                        break;
                    case 'CSV':
                        $SpreadsheetReaderObj->setDelimiter(',');
                        $SpreadsheetReaderObj->setEnclosure('"');
                        $SpreadsheetReaderObj->setLineEnding('\r\n');
                        $SpreadsheetReaderObj->setInputEncoding('UTF-8');
                        break;
                }

                // Get worksheet information.
                $WorksheetListInfo = $SpreadsheetReaderObj->listWorksheetInfo($datafile);
            } catch (Exception $ExceptionObj) {
                echo $ExceptionObj->getMessage();
            }

            return $WorksheetListInfo;
        }
    }

    public function _unique_multidim_array($array, $key)
    {
        $temp_array = array();
        $i = 0;
        $key_array = array();

        foreach ($array as $val) {
            if (!in_array($val[$key], $key_array)) {
                $key_array[$i] = $val[$key];
                $temp_array[$i] = $val;
            }
            $i++;
        }
        return $temp_array;
    }

    public function _compare_fullname($a, $b, $field)
    {
        return strnatcmp($a[$field], $b[$field]);
    }

    public function _loadRawData($request, $_locale)
    {


        $inputFileName = $this->get('kernel')->getRootDir() . '/../Tableau hottes de cuisine' . (($_locale == 'en') ? '_en' : '') . '.xls';

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


        /*
         *  Query Params
         *
         * */

        if ($request->query->get('limit') > 0) {
            $total = $request->query->get('limit');
        }


        for ($row = 0; $row <= $total; $row++) {


            $data [] = array(
                'type' => urlencode($objWorksheet->getCellByColumnAndRow($col, $row + 2)->getValue()),
                'installation' => urlencode($objWorksheet->getCellByColumnAndRow($col + 1, $row + 2)->getValue()),
                'nbr_moteurs' => urlencode($objWorksheet->getCellByColumnAndRow($col + 2, $row + 2)->getValue()),
                'largeur' => urlencode($objWorksheet->getCellByColumnAndRow($col + 3, $row + 2)->getValue()),
                'afficheur' => urlencode($objWorksheet->getCellByColumnAndRow($col + 4, $row + 2)->getValue()),
                'largeur_cheminee' => urlencode($objWorksheet->getCellByColumnAndRow($col + 5, $row + 2)->getValue()),
                'chapeau' => urlencode($objWorksheet->getCellByColumnAndRow($col + 6, $row + 2)->getValue()),
                'moteur' => urlencode($objWorksheet->getCellByColumnAndRow($col + 7, $row + 2)->getValue()),
                'capacite' => urlencode($objWorksheet->getCellByColumnAndRow($col + 8, $row + 2)->getValue()),
                'ref_moteur' => urlencode($objWorksheet->getCellByColumnAndRow($col + 9, $row + 2)->getValue()),
                'photos' => urlencode($objWorksheet->getCellByColumnAndRow($col + 10, $row + 2)->getValue()),
            );
        }

        return $data;

    }


}

<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class DefaultController extends Controller
{
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
        return new Response("Goucou");
    }

    public function allAction($_locale)
    {

        $inputFileName = $this->get('kernel')->getRootDir() . '/../Tableau hottes de cuisine.xls';

        /* Load $inputFileName to a PHPExcel Object  */
        $objPHPExcel = \PHPExcel_IOFactory::load($inputFileName);
        $myWorksheetListInfo = $this->_getWorksheetListInfo($inputFileName);



        $objPHPExcel->setActiveSheetIndex(0);
        $row = $objPHPExcel->getActiveSheet()->getHighestRow() + 1;



        $objWorksheet =$objPHPExcel->getActiveSheet();
        $maxRow = 1;
        $maxCol = 11;
        $total = $myWorksheetListInfo[0]['totalRows'] - 1; // Removing the header row.
        $output = "total=".urlencode($total); //First echo of all variables
        $error="non";
//echo $objPHPExcel->getActiveSheet()->getCell('B8')->getValue();
//echo 'doooo='. $objPHPExcel->getActiveSheet()->getCellByColumnAndRow(9,6)->getValue(). 'oood';
        $col = 0;
        $data = array();

        for ($row = 0; $row <= $total; $row++) {


            $data [] = array (
                'type' => urlencode($objWorksheet->getCellByColumnAndRow($col, $row+2)->getValue()),
                'installation' => urlencode($objWorksheet->getCellByColumnAndRow($col, $row+2)->getValue()),
                'largeur' => urlencode($objWorksheet->getCellByColumnAndRow($col+1, $row + 2)->getValue()),
                'afficheur' => urlencode($objWorksheet->getCellByColumnAndRow($col +4, $row + 2)->getValue()),
                'largeur' => urlencode($objWorksheet->getCellByColumnAndRow($col +5, $row + 2)->getValue()),
                'chapeau' => urlencode($objWorksheet->getCellByColumnAndRow($col +6, $row + 2)->getValue()),
                'moteur' => urlencode($objWorksheet->getCellByColumnAndRow($col +7, $row + 2)->getValue()),
                'capacite' => urlencode($objWorksheet->getCellByColumnAndRow($col +8, $row + 2)->getValue()),
                'photos' => urlencode($objWorksheet->getCellByColumnAndRow($col +10, $row + 2)->getValue()),
            );

        }





        $json = array('meta' => array('total' => 100000),
            'data' => $data);

        return new JsonResponse($json);
    }


    /**
     * @Route("/fake", name="fake")
     */
    public function fakeAction(Request $request)
    {

        // ask the service for a Excel5
        $phpExcelObject = $this->get('phpexcel')->createPHPExcelObject();

        $phpExcelObject->getProperties()->setCreator("liuggio")
            ->setLastModifiedBy("Giulio De Donato")
            ->setTitle("Office 2005 XLSX Test Document")
            ->setSubject("Office 2005 XLSX Test Document")
            ->setDescription("Test document for Office 2005 XLSX, generated using PHP classes.")
            ->setKeywords("office 2005 openxml php")
            ->setCategory("Test result file");
        $phpExcelObject->setActiveSheetIndex(0)
            ->setCellValue('A1', 'Hello')
            ->setCellValue('B2', 'world!');
        $phpExcelObject->getActiveSheet()->setTitle('Simple');
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $phpExcelObject->setActiveSheetIndex(0);

        // create the writer
        $writer = $this->get('phpexcel')->createWriter($phpExcelObject, 'Excel5');
        // create the response
        $response = $this->get('phpexcel')->createStreamedResponse($writer);
        // adding headers
        $dispositionHeader = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'stream-file.xls'
        );
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        $response->headers->set('Content-Disposition', $dispositionHeader);

        return $response;
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

}

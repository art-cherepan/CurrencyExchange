<?php


namespace App\Controller;

use App\Entity\Employee;
use App\Entity\EmployeeSchedule;
use App\Entity\Exchange;
use App\Repository\EmployeeRepository;
use App\Repository\ScheduleRepository;

use App\services\CurrencyExchangeService;
use App\services\ScheduleService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use phpDocumentor\Reflection\Types\Integer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends BaseController
{

    /**
     * @Route("/", name="home")
     */
    public function index()
    {
        $forRender = parent::renderDefault();
        return $this->render('main/index.html.twig', $forRender);
    }

    /**
     * @param Request $request
     * @return Response
     * @Route("/exchange-rate", name="show_exchange_rate")
     */
    public function showExchangeRate(ManagerRegistry $doctrine, Request $request): Response
    {
        if (empty($request->query->get('from'))) {
            return new Response(
                'Parameter "from" is empty',
                Response::HTTP_OK
            );
        } elseif (empty($request->query->get('to'))) {
            return new Response(
                'Parameter "to" is empty',
                Response::HTTP_OK
            );
        }
        $from = $request->query->get('from');
        $to = $request->query->get('to');

        $date = '';
        if (!empty($request->query->get('date'))) {
            $date = $request->query->get('date');
        } else {
            $date = date('Y-m-d');
        }

        $getDataByDb = $doctrine->getRepository(Exchange::class)->getExchangeRateByDb($date, $from , $to);
        //есть данные в базе
        if (false !== $getDataByDb) {
            return new Response(
                json_encode($getDataByDb),
                Response::HTTP_OK
            );
        } else { //проверяем по API
            $getDataByApi = $doctrine->getRepository(Exchange::class)->getExchangeRateByApi($date, $from, $to);
            if (false !== $getDataByApi) {
                return new Response(
                    json_encode($getDataByApi),
                    Response::HTTP_OK
                );
            } else { //данные не найдены ни в базе, ни по API
                return new Response(
                    'Error: information for currency exchange from ' . $from . ' to ' . $to .' on date ' . $date . ' not found',
                    Response::HTTP_OK
                );
            }
        }
    }


    /**
     * @param Request $request
     * @return Response
     * @Route("/exchange-rates", name="show_exchange_rates")
     */
    public function showExchangeRates(ManagerRegistry $doctrine, Request $request): Response
    {
        if (empty($request->query->get('from'))) {
            return new Response(
                'Parameter "from" is empty',
                Response::HTTP_OK
            );
        } elseif (empty($request->query->get('to'))) {
            return new Response(
                'Parameter "to" is empty',
                Response::HTTP_OK
            );
        } elseif (empty($request->query->get('datePeriodFrom'))) {
            return new Response(
                'Parameter "datePeriodFrom" is empty',
                Response::HTTP_OK
            );
        } elseif (empty($request->query->get('datePeriodTo'))) {
            return new Response(
                'Parameter "datePeriodTo" is empty',
                Response::HTTP_OK
            );
        }

        $from = $request->query->get('from');
        $to = $request->query->get('to');
        $datePeriodFrom = $request->query->get('datePeriodFrom');
        $datePeriodTo = $request->query->get('datePeriodTo');
        if ($datePeriodTo < $datePeriodFrom) {
            return new Response(
                'Error: The end date cannot be greater than the start date.',
                Response::HTTP_OK
            );
        }
        $format = '';
        if (!empty($request->query->get('format'))) {
            $format = $request->query->get('format');
        }

        $resultData = ['from' => $from, 'to' => $to];

        $rates = [];
        if ('csv' == $format) {
            $rate = [0 => 'rate', 1 => 'date'];
            $rates[] = $rate;
        }
        $currDate = $datePeriodFrom;
        //получаем информацию по каждому дню из данного периода
        while (date($currDate) <= date($datePeriodTo)) {
            $getDataByDb = $doctrine->getRepository(Exchange::class)->getExchangeRateByDb($currDate, $from , $to);
            if (false !== $getDataByDb) {
                $rate = ['rate' => $getDataByDb['rate'], 'date' => $getDataByDb['date']];
                $rates[] = $rate;
            } else {
                $getDataByApi = $doctrine->getRepository(Exchange::class)->getExchangeRateByApi($currDate, $from, $to);
                if (false !== $getDataByApi) {
                    $rate = ['rate' => $getDataByApi['rate'], 'date' => $getDataByApi['date']];
                    $rates[] = $rate;
                } else {
                    $rate = ['error' => 'Error: information for currency exchange from ' . $from . ' to ' . $to .' on date ' . $currDate . ' not found',];
                    $rates[] = $rate;
                }
            }
            $currDateTime = new \DateTime($currDate);
            $currDateTime->add(new \DateInterval('P1D'));
            $currDate = (string)$currDateTime->format('Y-m-d');
        }
        $resultData['rate'] = $rates;

        $result = '';
        //отображаем результат в формате json
        if ('' == $format || 'json' == $format) {
            $result = json_encode($resultData);
        } elseif ('jsonp' == $format) {
            //отображаем результат в формате jsonp
            $result = 'func(' . json_encode($resultData) . ');';
        } elseif ('csv' == $format) {
            //отображаем результат в формате csv
            header("Content-Type: text/csv");
            header("Content-Disposition: attachment; filename=file.csv");
            $output = fopen("php://output", "wb");
            foreach ($resultData['rate'] as $row) {
                fputcsv($output, $row);
            }
            fclose($output);
        } elseif ('xml' == $format) {
            //отображаем результат в формате xml
            $currencyExchangeService = new CurrencyExchangeService();
            $xmlData = new \SimpleXMLElement('<?xml version="1.0"?><data></data>');
            $currencyExchangeService->arrayToXml($resultData, $xmlData);
            $xmlData->asXML(__DIR__ . '/../../files/save.xml');
        }
        return new Response(
            $result,
            Response::HTTP_OK
        );
    }
}
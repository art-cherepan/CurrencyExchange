<?php

namespace App\Repository;

use App\Entity\Exchange;
use App\services\CurrencyExchangeService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use phpDocumentor\Reflection\Types\Mixed_;
use Symfony\Component\HttpClient\HttpClient;

/**
 * @method Exchange|null find($id, $lockMode = null, $lockVersion = null)
 * @method Exchange|null findOneBy(array $criteria, array $orderBy = null)
 * @method Exchange[]    findAll()
 * @method Exchange[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExchangeRepository extends ServiceEntityRepository
{
    protected const URL = 'http://api.exchangeratesapi.io/v1/';
    protected const apiKey = '0d6ff676dcdea48c0a82000e596ca313';


    /**
     * ExchangeRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Exchange::class);
    }

    /**
     * @param $date
     * @param $from
     * @param $to
     * @return array|bool
     * @throws \Doctrine\DBAL\Exception
     */
    public function getExchangeRateByDb($date, $from , $to)
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT rates FROM exchange WHERE date = :exchange_date';
        $stmt = $conn->prepare($sql);
        $resultSet = $stmt->executeQuery(['exchange_date' => $date])->fetchAllAssociative();
        if (count($resultSet) > 0) {
            //если записи в БД есть
            $currencyExchangeService = new CurrencyExchangeService($from, $to, $resultSet[0]['rates'], $date);
            //получаем курс по интересующей валюте
            $data = $currencyExchangeService->exchangeRateForCurrency();
            //если такой валюты в базе нет, возвращаем false
            return $data;
        } else {
            return false;
        }
    }

    /**
     * @param $date
     * @param $from
     * @param $to
     * @return array|bool
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function getExchangeRateByApi($date, $from, $to)
    {
        $client = HttpClient::create();
        $response = $client->request('GET', self::URL . $date . '?access_key=' . self::apiKey . '&symbols=' . $from . ',' . $to);
        if (200 == $response->getStatusCode()) {
            $currencyExchangeService = new CurrencyExchangeService($from, $to, json_encode($response->toArray()), $date);
            return $currencyExchangeService->exchangeRateForCurrency();
        } else {
            return false;
        }
    }



    // /**
    //  * @return Exchange[] Returns an array of Exchange objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Exchange
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

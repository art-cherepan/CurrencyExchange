<?php


namespace App\services;


use Symfony\Component\HttpClient\HttpClient;

class CurrencyExchangeService
{
    protected $from;
    protected $to;
    protected $exchangeRates;
    protected $date;

    /**
     * CurrencyExchangeService constructor.
     * @param string $from
     * @param string $to
     * @param string $exchangeRates
     * @param string $date
     */
    public function __construct(string $from, string $to, string $exchangeRates = '', string $date = '')
    {
        $this->from = $from;
        $this->to = $to;
        $this->exchangeRates = json_decode($exchangeRates, true);
        $this->date = $date;
    }

    /**
     * @return array|bool
     */
    public function exchangeRateForCurrency()
    {
        if (!empty($this->exchangeRates['rates']) && count($this->exchangeRates['rates']) == 2) {
            $currencyFrom = (float)($this->exchangeRates['rates'][$this->from]);
            $currencyTo = (float)($this->exchangeRates['rates'][$this->to]);
            $rate = number_format(number_format($currencyTo, 4) / number_format($currencyFrom, 4), 4);
            return ['from' => $currencyFrom, 'to' => $currencyTo, 'date' => $this->date, 'rate' => $rate];
        } else {
            return false;
        }
    }

    public function arrayToXml($data, &$xmlData) {
        foreach( $data as $key => $value ) {
            if( is_array($value) ) {
                if( is_numeric($key) ){
                    $key = 'item'.$key;
                }
                $subnode = $xmlData->addChild($key);
                $this->arrayToXml($value, $subnode);
            } else {
                $xmlData->addChild("$key",htmlspecialchars("$value"));
            }
        }
    }
}
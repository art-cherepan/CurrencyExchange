<?php


namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class BaseController extends AbstractController
{
    /**
     * @return string[]
     */
    public function renderDefault()
    {
        return [
            'title' => 'Hello, world!!!!'
        ];

    }
}
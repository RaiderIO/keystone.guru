<?php


namespace App\Service\Dashboard;


interface StatisticsServiceInterface
{
    function getByDay();
    function getByMonth();
    function getMonthLabels();
}
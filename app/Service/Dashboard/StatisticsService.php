<?php

namespace App\Service\Dashboard;

use Illuminate\Support\Facades\DB;

/**
 *
 * @package App\Service
 * @author Wouter
 * @since 13/06/2019
 */
class StatisticsService implements StatisticsServiceInterface
{
    private const MONTHS = 6;

    protected $table = '';

    /**
     * @return array
     */
    function getByDay()
    {
        // Select all users over the past 6 months and group them by day
        $dataByDayResult = DB::select("
            SELECT DATE_FORMAT(created_at, '%Y-%m-%d') date, COUNT(`id`) as count
            FROM " . $this->table . "
            WHERE created_at > NOW() - interval " . StatisticsService::MONTHS . " month 
            GROUP BY date
            ");

        // Convert to key => value
        $dataByDay = [];
        $cumulative = 0;
        foreach ($dataByDayResult as $row) {
            // Ever increasing
            $dataByDay[] = ['t' => $row->date, 'y' => $row->count + $cumulative];
            $cumulative += $row->count;
        }

        return $dataByDay;
    }

    /**
     * @return mixed
     */
    function getByMonth()
    {
        // Select all users over the past 6 months and group them by day
        $dataByMonthResult = DB::select("
            SELECT DATE_FORMAT(created_at, '%b') month, COUNT(`id`) as count
            FROM " . $this->table . "
            WHERE created_at > NOW() - interval " . StatisticsService::MONTHS . " month 
            GROUP BY month
            ORDER BY created_at asc
            ");

        // Get the labels we're supposed to conform to
        $labels = $this->getMonthLabels();

        // Convert to key => value
        $dataByMonth = [];
        foreach ($labels as $month) {
            $found = false;

            // Match the labels with the months as selected above
            foreach ($dataByMonthResult as $row) {
                // If found (maybe there's a month with 0 registered users)
                if ($row->month === $month) {
                    // Add it to the list
                    $dataByMonth[] = $row->count;
                    $found = true;
                    break;
                }
            }

            // If it was not found, just add a zero
            if (!$found) {
                $dataByMonth[] = 0;
            }
        }

        return $dataByMonth;
    }

    /**
     * Get the labels that should be presented for the last X months.
     * @return array
     */
    function getMonthLabels()
    {
        // Start with $months months ago
        $date = \Carbon\Carbon::now()->subMonths(StatisticsService::MONTHS);
        $labels = [];

        // Increase by $months months
        for ($i = 0; $i < StatisticsService::MONTHS; $i++) {
            // Get the 3 char month
            $labels[] = $date->format('M');

            // Next month!
            $date = $date->addMonth();
        }

        return $labels;
    }
}
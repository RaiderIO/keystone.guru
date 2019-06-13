<?php

namespace App\Http\Controllers;

use App\Service\Dashboard\DashboardService;
use App\Service\Dashboard\DungeonRoutesStatisticsService;
use App\Service\Dashboard\PageViewStatisticsService;
use App\Service\Dashboard\StatisticsServiceInterface;
use App\Service\Dashboard\TeamsStatisticsService;
use App\Service\Dashboard\UsersStatisticsService;

class DashboardController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return redirect()->route('dashboard.users');
    }

    /**
     * Get the parameters that should be passed to the dashboard view for proper display.
     * @param DashboardService $dashboardService
     * @param StatisticsServiceInterface $statisticsService
     * @param array $titles
     * @return array
     */
    private function _getDashboardParams(DashboardService $dashboardService, StatisticsServiceInterface $statisticsService, array $titles)
    {
        return array_merge($dashboardService->getTopCardsData(), [
            'lineChartTopTitle' => isset($titles['lineChartTopTitle']) ? $titles['lineChartTopTitle'] : __('Overview'),
            'lineChartBottomTitle' => isset($titles['lineChartBottomTitle']) ? $titles['lineChartBottomTitle'] : __('Model'),
            'barChartTopTitle' => isset($titles['barChartTopTitle']) ? $titles['barChartTopTitle'] : __('Performance'),
            'barChartBottomTitle' => isset($titles['barChartBottomTitle']) ? $titles['barChartBottomTitle'] : __('By month'),
            'options' => [
                'data' => [
                    'datasets' => [
                        [
                            'data' => $statisticsService->getByDay()
                        ]
                    ]
                ]
            ],
            'optionsByMonth' => [
                'data' => [
                    'datasets' => [
                        [
                            'data' => $statisticsService->getByMonth()
                        ]
                    ],
                    'labels' => $statisticsService->getMonthLabels()
                ]
            ]
        ]);
    }

    /**
     * @param DashboardService $dashboardService
     * @param TeamsStatisticsService $statisticsService
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function teams(DashboardService $dashboardService, TeamsStatisticsService $statisticsService)
    {
        // Return in a fancy data set
        return view('admin.dashboard.dashboard',
            $this->_getDashboardParams($dashboardService, $statisticsService, ['lineChartBottomTitle' => __('Teams')])
        );
    }

    /**
     * @param DashboardService $dashboardService
     * @param UsersStatisticsService $statisticsService
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function users(DashboardService $dashboardService, UsersStatisticsService $statisticsService)
    {
        // Return in a fancy data set
        return view('admin.dashboard.dashboard',
            $this->_getDashboardParams($dashboardService, $statisticsService, ['lineChartBottomTitle' => __('Registered users')])
        );
    }

    /**
     * @param DashboardService $dashboardService
     * @param DungeonRoutesStatisticsService $statisticsService
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function dungeonroutes(DashboardService $dashboardService, DungeonRoutesStatisticsService $statisticsService)
    {
        // @TODO Multiple datasets for visible, unpublished and unlisted?
        // Return in a fancy data set
        return view('admin.dashboard.dashboard',
            $this->_getDashboardParams($dashboardService, $statisticsService, ['lineChartBottomTitle' => __('Routes')])
        );
    }

    /**
     * @param DashboardService $dashboardService
     * @param PageViewStatisticsService $statisticsService
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function pageviews(DashboardService $dashboardService, PageViewStatisticsService $statisticsService)
    {
        // Return in a fancy data set
        return view('admin.dashboard.dashboard',
            $this->_getDashboardParams($dashboardService, $statisticsService, ['lineChartBottomTitle' => __('Page views')])
        );
    }
}

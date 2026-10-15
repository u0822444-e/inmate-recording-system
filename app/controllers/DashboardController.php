<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Response;
use App\Models\Dashboard;
use App\Services\Auth;
use mysqli;

class DashboardController
{
    private Dashboard $model;

    public function __construct(mysqli $db)
    {
        $this->model = new Dashboard($db);
    }

    public function handle(string $action): void
    {
        if (!Auth::isLoggedIn()) {
            Response::json(false, 'Access denied.');
        }

        switch ($action) {
            case 'stats':     $this->stats();     break;
            case 'activity':  $this->activity();  break;
            case 'upcoming':  $this->upcoming();  break;
            case 'trend':     $this->trend();     break;
            case 'analytics': $this->analytics(); break;  // NEW
            default:          Response::json(false, 'Unsupported request.');
        }
    }

    private function stats(): void
    {
        $isAdmin = Auth::isAdmin();
        $userId  = (int) Auth::user()['id'];

        if ($isAdmin) {
            $data = [
                'scope'     => 'admin',
                'pdl'       => $this->safeCount(fn() => $this->model->totalPdl()),
                'visitors'  => $this->safeCount(fn() => $this->model->visitorsToday()),
                'incidents' => $this->safeCount(fn() => $this->model->openIncidents()),
                'users'     => $this->safeCount(fn() => $this->model->activeUsers()),
            ];
        } else {
            $data = [
                'scope'        => 'staff',
                'my_entries'   => $this->safeCount(fn() => $this->model->myEntriesToday($userId)),
                'my_headcount' => $this->safeCount(fn() => $this->model->myPendingHeadcounts($userId)),
                'pdl'          => $this->safeCount(fn() => $this->model->totalPdl()),
                'visitors'     => $this->safeCount(fn() => $this->model->visitorsToday()),
            ];
        }

        Response::json(true, 'OK', ['stats' => $data]);
    }

    private function activity(): void
    {
        $limit  = max(1, min(20, (int) ($_GET['limit'] ?? 5)));
        $userId = (int) Auth::user()['id'];

        $items = [];
        try {
            if (Auth::isAdmin()) {
                $items = $this->model->recentActivity($limit);
            } else {
                $items = $this->model->myRecentActivity($userId, $limit);
            }
        } catch (\Throwable $e) {
            error_log('activity fail: ' . $e->getMessage());
        }

        Response::json(true, 'OK', ['activity' => $items]);
    }

    private function upcoming(): void
    {
        $limit = max(1, min(10, (int) ($_GET['limit'] ?? 3)));
        Response::json(true, 'OK', [
            'events' => $this->model->upcomingEvents($limit),
        ]);
    }

    private function trend(): void
    {
        $days  = max(7, min(365, (int) ($_GET['days'] ?? 7)));
        $items = [];
        try {
            $items = $this->model->populationTrend($days);
        } catch (\Throwable $e) {
            error_log('trend fail: ' . $e->getMessage());
        }
        Response::json(true, 'OK', ['trend' => $items]);
    }

    /* ============================================================
       NEW: analytics — one-shot call for the whole dashboard
       ============================================================ */

    private function analytics(): void
    {
        // FIX: hard admin gate on the server side
        if (!Auth::isAdmin()) {
            Response::json(false, 'Analytics are restricted to administrators.');
        }

        $days      = max(7, min(90, (int) ($_GET['days'] ?? 30)));
        $visitDays = max(7, min(60, (int) ($_GET['visitors_days'] ?? 14)));

        $data = [
            'population_trend'     => $this->safeList(fn() => $this->model->populationTrend($days)),
            'admissions_trend'     => $this->safeList(fn() => $this->model->admissionsTrend($days)),
            'classification'       => $this->safeList(fn() => $this->model->classificationBreakdown()),
            'custody'              => $this->safeList(fn() => $this->model->custodyBreakdown()),
            'top_offenses'         => $this->safeList(fn() => $this->model->topOffenses(8)),
            'visitors_per_day'     => $this->safeList(fn() => $this->model->visitorsPerDay($visitDays)),
            'incidents_by_type'    => $this->safeList(fn() => $this->model->incidentsByType()),
            'incidents_by_severity'=> $this->safeList(fn() => $this->model->incidentsBySeverity()),
            'age_distribution'     => $this->safeList(fn() => $this->model->ageDistribution()),
            'inmates_by_unit'      => $this->safeList(fn() => $this->model->inmatesByUnit()),
            'releases_this_month'  => $this->safeList(fn() => $this->model->releasesThisMonth()),
            'drug_cases'           => $this->safeList(fn() => $this->model->drugCaseBreakdown()),
            'headcount_compliance' => $this->safeList(fn() => $this->model->headcountComplianceToday()),
            'admissions_by_month'  => $this->safeList(fn() => $this->model->admissionsByMonth(6)),
        ];

        Response::json(true, 'OK', ['analytics' => $data]);
    }

    private function safeCount(callable $fn): int
    {
        try {
            return (int) $fn();
        } catch (\Throwable $e) {
            error_log('dashboard stat fail: ' . $e->getMessage());
            return 0;
        }
    }

    private function safeList(callable $fn): array
    {
        try {
            return (array) $fn();
        } catch (\Throwable $e) {
            error_log('dashboard analytics fail: ' . $e->getMessage());
            return [];
        }
    }
}
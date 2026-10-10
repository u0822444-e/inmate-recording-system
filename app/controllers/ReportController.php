<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Response;
use App\Models\Report;
use App\Services\Auth;
use mysqli;

class ReportController
{
    private Report $model;

    public function __construct(mysqli $db)
    {
        $this->model = new Report($db);
    }

    public function handle(string $action): void
    {
        if (!Auth::isLoggedIn()) {
            Response::json(false, 'Access denied.');
        }

        // Reports are administrator-only.
        if (!Auth::isAdmin()) {
            Response::json(false, 'Reports are restricted to administrators.');
        }

        switch ($action) {
            case 'population':  $this->population();  break;
            case 'flow':        $this->flow();        break;
            case 'drug-cases':  $this->drugCases();   break;
            case 'export':      $this->export();      break;
            default:            Response::json(false, 'Unsupported request.');
        }
    }

    /* ============================================================
       POPULATION SNAPSHOT
       ============================================================ */

    private function population(): void
    {
        $rows = $this->model->populationSnapshot();
        $pct  = $this->model->classificationPercentages();

        $totals = ['male' => 0, 'female' => 0, 'total' => 0];
        foreach ($rows as $r) {
            $totals['male']   += $r['male'];
            $totals['female'] += $r['female'];
            $totals['total']  += $r['total'];
        }

        Response::json(true, 'OK', [
            'report'   => 'population',
            'meta'     => [
                'title'     => 'ACTUAL JAIL POPULATION DATA',
                'facility'  => 'Ipil District Jail',
                'subtitle'  => 'As of ' . date('F j, Y'),
                'generated' => date('M j, Y g:i A'),
            ],
            'rows'     => $rows,
            'totals'   => $totals,
            'percents' => $pct,
        ]);
    }

    /* ============================================================
       FLOW — Committed vs. Released
       ============================================================ */

    private function flow(): void
    {
        $month = $this->readMonth();
        $rows  = $this->model->flow($month);

        $totals = [
            'committed_male'   => 0,
            'committed_female' => 0,
            'committed_total'  => 0,
            'released_male'    => 0,
            'released_female'  => 0,
            'released_total'   => 0,
        ];
        foreach ($rows as $r) {
            $totals['committed_male']   += $r['committed_male'];
            $totals['committed_female'] += $r['committed_female'];
            $totals['committed_total']  += $r['committed_total'];
            $totals['released_male']    += $r['released_male'];
            $totals['released_female']  += $r['released_female'];
            $totals['released_total']   += $r['released_total'];
        }

        Response::json(true, 'OK', [
            'report' => 'flow',
            'month'  => $month,
            'meta'   => [
                'title'     => 'NUMBER OF PDL COMMITTED AND RELEASED',
                'facility'  => 'Ipil District Jail',
                'subtitle'  => 'For the month of ' . date('F j, Y', strtotime($month . '-01')),
                'generated' => date('M j, Y g:i A'),
            ],
            'rows'   => $rows,
            'totals' => $totals,
        ]);
    }

    /* ============================================================
       DRUG CASES
       ============================================================ */

    private function drugCases(): void
    {
        $rows = $this->model->drugCases();

        $totals = ['male' => 0, 'female' => 0, 'total' => 0];
        foreach ($rows as $r) {
            $totals['male']   += $r['male'];
            $totals['female'] += $r['female'];
            $totals['total']  += $r['total'];
        }

        Response::json(true, 'OK', [
            'report' => 'drug-cases',
            'meta'   => [
                'title'     => 'DATA ON PDL WITH DRUG CASES BY CLASSIFICATION',
                'facility'  => 'Ipil District Jail',
                'subtitle'  => 'As of ' . date('F j, Y'),
                'generated' => date('M j, Y g:i A'),
            ],
            'rows'   => $rows,
            'totals' => $totals,
        ]);
    }

    /* ============================================================
       CSV EXPORT
       ============================================================ */

    private function export(): void
    {
        $type  = trim((string) ($_GET['report'] ?? 'population'));
        $month = $this->readMonth();

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="bjmp-report-' . $type . '-' . date('Ymd-His') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");

        $delimiter = ',';
        $enclosure = '"';
        $escape    = '';

        if ($type === 'population') {
            $this->exportPopulation($out, $delimiter, $enclosure, $escape);
        } elseif ($type === 'flow') {
            $this->exportFlow($out, $month, $delimiter, $enclosure, $escape);
        } elseif ($type === 'drug-cases') {
            $this->exportDrugCases($out, $delimiter, $enclosure, $escape);
        } else {
            fputcsv($out, ['Invalid report type.'], $delimiter, $enclosure, $escape);
        }

        fclose($out);
        exit;
    }

    private function exportPopulation($out, $delimiter, $enclosure, $escape): void
    {
        $rows   = $this->model->populationSnapshot();
        $pct    = $this->model->classificationPercentages();

        fputcsv($out, ['ACTUAL JAIL POPULATION DATA'], $delimiter, $enclosure, $escape);
        fputcsv($out, ['Facility: Ipil District Jail'], $delimiter, $enclosure, $escape);
        fputcsv($out, ['As of: ' . date('F j, Y')], $delimiter, $enclosure, $escape);
        fputcsv($out, ['Generated: ' . date('M j, Y g:i A')], $delimiter, $enclosure, $escape);
        fputcsv($out, [], $delimiter, $enclosure, $escape);

        fputcsv($out, ['Classification', 'Male', 'Female', 'Total'], $delimiter, $enclosure, $escape);

        $male = $female = $total = 0;
        foreach ($rows as $r) {
            fputcsv($out, [$r['classification'], $r['male'], $r['female'], $r['total']],
                $delimiter, $enclosure, $escape);
            $male   += $r['male'];
            $female += $r['female'];
            $total  += $r['total'];
        }
        fputcsv($out, ['TOTAL', $male, $female, $total], $delimiter, $enclosure, $escape);

        if (!empty($pct['items'])) {
            fputcsv($out, [], $delimiter, $enclosure, $escape);
            fputcsv($out, ['Percentage Breakdown'], $delimiter, $enclosure, $escape);
            foreach ($pct['items'] as $p) {
                fputcsv($out, [
                    $p['classification'],
                    $p['count'],
                    number_format($p['percentage'], 2) . '%'
                ], $delimiter, $enclosure, $escape);
            }
        }
    }

    private function exportFlow($out, string $month, $delimiter, $enclosure, $escape): void
    {
        $rows = $this->model->flow($month);

        fputcsv($out, ['NUMBER OF PDL COMMITTED AND RELEASED'], $delimiter, $enclosure, $escape);
        fputcsv($out, ['Facility: Ipil District Jail'], $delimiter, $enclosure, $escape);
        fputcsv($out, ['Month: ' . date('F Y', strtotime($month . '-01'))], $delimiter, $enclosure, $escape);
        fputcsv($out, [], $delimiter, $enclosure, $escape);

        fputcsv($out, [
            'Date',
            'Committed M', 'Committed F', 'Committed Total',
            'Released M',  'Released F',  'Released Total',
        ], $delimiter, $enclosure, $escape);

        $cm = $cf = $ct = $rm = $rf = $rt = 0;
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['day'],
                $r['committed_male'], $r['committed_female'], $r['committed_total'],
                $r['released_male'],  $r['released_female'],  $r['released_total'],
            ], $delimiter, $enclosure, $escape);
            $cm += $r['committed_male'];
            $cf += $r['committed_female'];
            $ct += $r['committed_total'];
            $rm += $r['released_male'];
            $rf += $r['released_female'];
            $rt += $r['released_total'];
        }

        fputcsv($out, ['TOTAL', $cm, $cf, $ct, $rm, $rf, $rt], $delimiter, $enclosure, $escape);
    }

    private function exportDrugCases($out, $delimiter, $enclosure, $escape): void
    {
        $rows = $this->model->drugCases();

        fputcsv($out, ['DATA ON PDL WITH DRUG CASES BY CLASSIFICATION'], $delimiter, $enclosure, $escape);
        fputcsv($out, ['Facility: Ipil District Jail'], $delimiter, $enclosure, $escape);
        fputcsv($out, ['As of: ' . date('F j, Y')], $delimiter, $enclosure, $escape);
        fputcsv($out, ['Generated: ' . date('M j, Y g:i A')], $delimiter, $enclosure, $escape);
        fputcsv($out, [], $delimiter, $enclosure, $escape);

        fputcsv($out, ['Classification', 'Male', 'Female', 'Total'], $delimiter, $enclosure, $escape);

        $male = $female = $total = 0;
        foreach ($rows as $r) {
            fputcsv($out, [$r['classification'], $r['male'], $r['female'], $r['total']],
                $delimiter, $enclosure, $escape);
            $male   += $r['male'];
            $female += $r['female'];
            $total  += $r['total'];
        }
        fputcsv($out, ['TOTAL', $male, $female, $total], $delimiter, $enclosure, $escape);
    }

    /* ============================================================
       HELPERS
       ============================================================ */

    private function readMonth(): string
    {
        $m = trim((string) ($_GET['month'] ?? ''));
        if (preg_match('/^\d{4}-\d{2}$/', $m)) {
            return $m;
        }
        return date('Y-m');
    }
}
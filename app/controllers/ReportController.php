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

        if (!Auth::isAdmin()) {
            Response::json(false, 'Reports are restricted to administrators.');
        }

        switch ($action) {
            case 'population':
                $this->population();
                break;
            case 'population-detailed':
                $this->populationDetailed();
                break;
            case 'flow':
                $this->flow();
                break;
            case 'committed-released':
                $this->committedReleased();
                break;
            case 'drug-cases':
                $this->drugCases();
                break;
            case 'drug-cases-detailed':
                $this->drugCasesDetailed();
                break;
            case 'actual-population':
                $this->actualPopulation();
                break;
            case 'escorts':
                $this->escorts();
                break;
            case 'export':
                $this->export();
                break;
            case 'actual-population-by-barangay':
                $this->actualPopulationByBarangay();
                break;
            case 'escorts-by-barangay':
                $this->escortsByBarangay();
                break;
            default:
                Response::json(false, 'Unsupported request.');
        }
    }

        /* ============================================================
       ACTUAL POPULATION — drill-down by barangay
       ============================================================ */

    private function actualPopulationByBarangay(): void
    {
        $municipalityName = trim((string) ($_GET['municipality'] ?? ''));
        if ($municipalityName === '') {
            Response::json(false, 'Missing municipality.');
        }

        $muni = $this->model->findMunicipalityByName($municipalityName);
        if (!$muni) {
            Response::json(false, 'Municipality not found.');
        }

        $rows = $this->model->actualPopulationByBarangay($muni['id']);

        $keys = [
            'detainee_male','detainee_female','detainee_total',
            'municipal_male','municipal_female',
            'city_male','city_female',
            'provincial_male','provincial_female',
            'insular_male','insular_female',
            'sentenced_total','grand_total',
        ];
        $totals = array_fill_keys($keys, 0);
        foreach ($rows as $r) {
            foreach ($totals as $k => $v) $totals[$k] += $r[$k];
        }

        $g = $totals['grand_total'] ?: 1;
        $percents = [
            'detainee'  => round(($totals['detainee_total']  / $g) * 100, 2),
            'sentenced' => round(($totals['sentenced_total'] / $g) * 100, 2),
        ];

        Response::json(true, 'OK', [
            'report'   => 'actual-population-by-barangay',
            'meta'     => [
                'title'     => 'ACTUAL JAIL POPULATION — ' . strtoupper($muni['name']),
                'facility'  => 'Zamboanga Sibugay',
                'subtitle'  => 'By Barangay · As of ' . date('F j, Y'),
                'generated' => date('M j, Y g:i A'),
            ],
            'rows'     => $rows,
            'totals'   => $totals,
            'percents' => $percents,
        ]);
    }

    /* ============================================================
       ESCORTS — drill-down by barangay
       ============================================================ */

    private function escortsByBarangay(): void
    {
        $municipalityName = trim((string) ($_GET['municipality'] ?? ''));
        if ($municipalityName === '') {
            Response::json(false, 'Missing municipality.');
        }

        $muni = $this->model->findMunicipalityByName($municipalityName);
        if (!$muni) {
            Response::json(false, 'Municipality not found.');
        }

        $month = $this->readMonth();
        $rows  = $this->model->escortedByBarangay($muni['id'], $month);

        $keys = [
            'court_male','court_female',
            'hospital_male','hospital_female',
            'drug_exam_male','drug_exam_female',
            'rehab_male','rehab_female',
            'inquest_male','inquest_female',
            'transfer_male','transfer_female',
            'visitation_male','visitation_female',
            'other_male','other_female',
            'total',
        ];
        $totals = array_fill_keys($keys, 0);
        foreach ($rows as $r) {
            foreach ($totals as $k => $v) $totals[$k] += $r[$k];
        }

        Response::json(true, 'OK', [
            'report' => 'escorts-by-barangay',
            'month'  => $month,
            'meta'   => [
                'title'     => 'ESCORTED PDL — ' . strtoupper($muni['name']),
                'facility'  => 'Zamboanga Sibugay',
                'subtitle'  => 'By Barangay · ' . date('F Y', strtotime($month . '-01')),
                'generated' => date('M j, Y g:i A'),
            ],
            'rows'   => $rows,
            'totals' => $totals,
        ]);
    }

    /* ============================================================
       POPULATION — simple
       ============================================================ */

    private function population(): void
    {
        $rows = $this->model->populationSnapshot();
        $pct = $this->model->classificationPercentages();

        $totals = ['male' => 0, 'female' => 0, 'total' => 0];
        foreach ($rows as $r) {
            $totals['male'] += $r['male'];
            $totals['female'] += $r['female'];
            $totals['total'] += $r['total'];
        }

        Response::json(true, 'OK', [
            'report' => 'population',
            'meta' => [
                'title' => 'PDL POPULATION BY CLASSIFICATION',
                'facility' => 'Zamboanga Sibugay',
                'subtitle' => 'As of ' . date('F j, Y'),
                'generated' => date('M j, Y g:i A'),
            ],
            'rows' => $rows,
            'totals' => $totals,
            'percents' => $pct,
        ]);
    }

    /* ============================================================
       POPULATION — detailed
       ============================================================ */

    private function populationDetailed(): void
    {
        $rows = $this->model->populationSnapshotDetailed();

        $keys = [
            'detainee_male',
            'detainee_female',
            'municipal_male',
            'municipal_female',
            'city_male',
            'city_female',
            'provincial_male',
            'provincial_female',
            'insular_male',
            'insular_female',
            'grand_total',
        ];
        $totals = array_fill_keys($keys, 0);
        foreach ($rows as $r) {
            foreach ($totals as $k => $v)
                $totals[$k] += $r[$k];
        }

        Response::json(true, 'OK', [
            'report' => 'population-detailed',
            'meta' => [
                'title' => 'PDL POPULATION — DETAILED',
                'facility' => 'Zamboanga Sibugay',
                'subtitle' => 'As of ' . date('F j, Y'),
                'generated' => date('M j, Y g:i A'),
            ],
            'rows' => $rows,
            'totals' => $totals,
        ]);
    }

    /* ============================================================
       FLOW — per day
       ============================================================ */

    private function flow(): void
    {
        $month = $this->readMonth();
        $rows = $this->model->flow($month);

        $totals = [
            'committed_male' => 0,
            'committed_female' => 0,
            'committed_total' => 0,
            'released_male' => 0,
            'released_female' => 0,
            'released_total' => 0,
        ];
        foreach ($rows as $r) {
            $totals['committed_male'] += $r['committed_male'];
            $totals['committed_female'] += $r['committed_female'];
            $totals['committed_total'] += $r['committed_total'];
            $totals['released_male'] += $r['released_male'];
            $totals['released_female'] += $r['released_female'];
            $totals['released_total'] += $r['released_total'];
        }

        Response::json(true, 'OK', [
            'report' => 'flow',
            'month' => $month,
            'meta' => [
                'title' => 'DAILY COMMITTED AND RELEASED',
                'facility' => 'Zamboanga Sibugay',
                'subtitle' => 'For the month of ' . date('F j, Y', strtotime($month . '-01')),
                'generated' => date('M j, Y g:i A'),
            ],
            'rows' => $rows,
            'totals' => $totals,
        ]);
    }

    /* ============================================================
       COMMITTED / RELEASED — by jail unit
       ============================================================ */

    private function committedReleased(): void
    {
        $month = $this->readMonth();
        $rows = $this->model->committedReleasedByUnit($month);

        $keys = [
            'committed_male',
            'committed_female',
            'committed_total',
            'released_male',
            'released_female',
            'released_total',
            'grand_total',
        ];
        $totals = array_fill_keys($keys, 0);
        foreach ($rows as $r) {
            foreach ($totals as $k => $v)
                $totals[$k] += $r[$k];
        }

        Response::json(true, 'OK', [
            'report' => 'committed-released',
            'month' => $month,
            'meta' => [
                'title' => 'NUMBER OF PDL COMMITTED AND RELEASED',
                'facility' => 'Zamboanga Sibugay',
                'subtitle' => 'For the month of ' . date('F Y', strtotime($month . '-01')),
                'generated' => date('M j, Y g:i A'),
            ],
            'rows' => $rows,
            'totals' => $totals,
        ]);
    }

    /* ============================================================
       DRUG CASES — simple
       ============================================================ */

    private function drugCases(): void
    {
        $rows = $this->model->drugCases();

        $totals = ['male' => 0, 'female' => 0, 'total' => 0];
        foreach ($rows as $r) {
            $totals['male'] += $r['male'];
            $totals['female'] += $r['female'];
            $totals['total'] += $r['total'];
        }

        Response::json(true, 'OK', [
            'report' => 'drug-cases',
            'meta' => [
                'title' => 'PDL WITH DRUG CASES',
                'facility' => 'Zamboanga Sibugay',
                'subtitle' => 'As of ' . date('F j, Y'),
                'generated' => date('M j, Y g:i A'),
            ],
            'rows' => $rows,
            'totals' => $totals,
        ]);
    }

    /* ============================================================
       DRUG CASES — detailed
       ============================================================ */

    private function drugCasesDetailed(): void
    {
        $rows = $this->model->drugCasesDetailed();

        $keys = [
            'r6425_adult_male',
            'r6425_adult_female',
            'r6425_cicl_male',
            'r6425_cicl_female',
            'r9165_adult_male',
            'r9165_adult_female',
            'r9165_cicl_male',
            'r9165_cicl_female',
            'at_r6425_adult_male',
            'at_r6425_adult_female',
            'at_r6425_cicl_male',
            'at_r6425_cicl_female',
            'at_r9165_adult_male',
            'at_r9165_adult_female',
            'at_r9165_cicl_male',
            'at_r9165_cicl_female',
            'total',
        ];
        $totals = array_fill_keys($keys, 0);
        foreach ($rows as $r) {
            foreach ($totals as $k => $v)
                $totals[$k] += $r[$k];
        }

        Response::json(true, 'OK', [
            'report' => 'drug-cases-detailed',
            'meta' => [
                'title' => 'DATA ON PDL WITH DRUG CASES BY CLASSIFICATION',
                'facility' => 'Zamboanga Sibugay',
                'subtitle' => 'As of ' . date('F j, Y'),
                'generated' => date('M j, Y g:i A'),
            ],
            'rows' => $rows,
            'totals' => $totals,
        ]);
    }

    /* ============================================================
       ACTUAL POPULATION
       ============================================================ */

    private function actualPopulation(): void
    {
        $rows = $this->model->actualPopulationByMunicipality();

        $keys = [
            'detainee_male',
            'detainee_female',
            'detainee_total',
            'municipal_male',
            'municipal_female',
            'city_male',
            'city_female',
            'provincial_male',
            'provincial_female',
            'insular_male',
            'insular_female',
            'sentenced_total',
            'grand_total',
        ];
        $totals = array_fill_keys($keys, 0);
        foreach ($rows as $r) {
            foreach ($totals as $k => $v)
                $totals[$k] += $r[$k];
        }

        $g = $totals['grand_total'] ?: 1;
        $percents = [
            'detainee' => round(($totals['detainee_total'] / $g) * 100, 2),
            'sentenced' => round(($totals['sentenced_total'] / $g) * 100, 2),
        ];

        Response::json(true, 'OK', [
            'report' => 'actual-population',
            'meta' => [
                'title' => 'BJMP ACTUAL JAIL POPULATION DATA',
                'facility' => 'Zamboanga Sibugay',
                'subtitle' => 'As of ' . date('F j, Y'),
                'generated' => date('M j, Y g:i A'),
            ],
            'rows' => $rows,
            'totals' => $totals,
            'percents' => $percents,
        ]);
    }

    /* ============================================================
       ESCORTS
       ============================================================ */

    private function escorts(): void
    {
        $month = $this->readMonth();
        $rows = $this->model->escortedByMunicipality($month);

        $keys = [
            'court_male',
            'court_female',
            'hospital_male',
            'hospital_female',
            'drug_exam_male',
            'drug_exam_female',
            'rehab_male',
            'rehab_female',
            'inquest_male',
            'inquest_female',
            'transfer_male',
            'transfer_female',
            'visitation_male',
            'visitation_female',
            'other_male',
            'other_female',
            'total',
        ];
        $totals = array_fill_keys($keys, 0);
        foreach ($rows as $r) {
            foreach ($totals as $k => $v)
                $totals[$k] += $r[$k];
        }

        Response::json(true, 'OK', [
            'report' => 'escorts',
            'month' => $month,
            'meta' => [
                'title' => 'DATA ON ESCORTED PDL',
                'facility' => 'Zamboanga Sibugay',
                'subtitle' => 'For the month of ' . date('F Y', strtotime($month . '-01')),
                'generated' => date('M j, Y g:i A'),
            ],
            'rows' => $rows,
            'totals' => $totals,
        ]);
    }

    /* ============================================================
       CSV EXPORT — routes to the right exporter
       ============================================================ */

    private function export(): void
    {
        $type = trim((string) ($_GET['report'] ?? 'population'));
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

        $d = ',';
        $e = '"';
        $x = '';

        switch ($type) {
            case 'population':
                $this->exportPopulation($out, $d, $e, $x);
                break;
            case 'population-detailed':
                $this->exportPopulationDetailed($out, $d, $e, $x);
                break;
            case 'flow':
                $this->exportFlow($out, $month, $d, $e, $x);
                break;
            case 'committed-released':
                $this->exportCommittedReleased($out, $month, $d, $e, $x);
                break;
            case 'drug-cases':
                $this->exportDrugCases($out, $d, $e, $x);
                break;
            case 'drug-cases-detailed':
                $this->exportDrugCasesDetailed($out, $d, $e, $x);
                break;
            case 'actual-population':
                $this->exportActualPopulation($out, $d, $e, $x);
                break;
            case 'escorts':
                $this->exportEscorts($out, $month, $d, $e, $x);
                break;
            default:
                fputcsv($out, ['Invalid report type.'], $d, $e, $x);
        }

        fclose($out);
        exit;
    }

    private function exportPopulation($out, $d, $e, $x): void
    {
        $rows = $this->model->populationSnapshot();
        $pct = $this->model->classificationPercentages();

        fputcsv($out, ['PDL POPULATION BY CLASSIFICATION'], $d, $e, $x);
        fputcsv($out, ['Region: Zamboanga Sibugay'], $d, $e, $x);
        fputcsv($out, ['As of: ' . date('F j, Y')], $d, $e, $x);
        fputcsv($out, [], $d, $e, $x);
        fputcsv($out, ['Classification', 'Male', 'Female', 'Total'], $d, $e, $x);

        $m = $f = $t = 0;
        foreach ($rows as $r) {
            fputcsv($out, [$r['classification'], $r['male'], $r['female'], $r['total']], $d, $e, $x);
            $m += $r['male'];
            $f += $r['female'];
            $t += $r['total'];
        }
        fputcsv($out, ['TOTAL', $m, $f, $t], $d, $e, $x);

        if (!empty($pct['items'])) {
            fputcsv($out, [], $d, $e, $x);
            fputcsv($out, ['Percentage Breakdown'], $d, $e, $x);
            foreach ($pct['items'] as $p) {
                fputcsv($out, [$p['classification'], $p['count'], number_format($p['percentage'], 2) . '%'], $d, $e, $x);
            }
        }
    }

    private function exportPopulationDetailed($out, $d, $e, $x): void
    {
        $rows = $this->model->populationSnapshotDetailed();

        fputcsv($out, ['PDL POPULATION — DETAILED'], $d, $e, $x);
        fputcsv($out, ['Region: Zamboanga Sibugay'], $d, $e, $x);
        fputcsv($out, ['As of: ' . date('F j, Y')], $d, $e, $x);
        fputcsv($out, [], $d, $e, $x);

        fputcsv($out, [
            'Classification',
            'Detainee M',
            'Detainee F',
            'Municipal M',
            'Municipal F',
            'City M',
            'City F',
            'Provincial M',
            'Provincial F',
            'Insular M',
            'Insular F',
            'Grand Total',
        ], $d, $e, $x);

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['classification'],
                $r['detainee_male'],
                $r['detainee_female'],
                $r['municipal_male'],
                $r['municipal_female'],
                $r['city_male'],
                $r['city_female'],
                $r['provincial_male'],
                $r['provincial_female'],
                $r['insular_male'],
                $r['insular_female'],
                $r['grand_total'],
            ], $d, $e, $x);
        }
    }

    private function exportFlow($out, string $month, $d, $e, $x): void
    {
        $rows = $this->model->flow($month);

        fputcsv($out, ['DAILY COMMITTED AND RELEASED'], $d, $e, $x);
        fputcsv($out, ['Region: Zamboanga Sibugay'], $d, $e, $x);
        fputcsv($out, ['Month: ' . date('F Y', strtotime($month . '-01'))], $d, $e, $x);
        fputcsv($out, [], $d, $e, $x);
        fputcsv($out, ['Date', 'Committed M', 'Committed F', 'Committed Total', 'Released M', 'Released F', 'Released Total'], $d, $e, $x);

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['day'],
                $r['committed_male'],
                $r['committed_female'],
                $r['committed_total'],
                $r['released_male'],
                $r['released_female'],
                $r['released_total'],
            ], $d, $e, $x);
        }
    }

    private function exportCommittedReleased($out, string $month, $d, $e, $x): void
    {
        $rows = $this->model->committedReleasedByUnit($month);

        fputcsv($out, ['NUMBER OF PDL COMMITTED AND RELEASED'], $d, $e, $x);
        fputcsv($out, ['Region: Zamboanga Sibugay'], $d, $e, $x);
        fputcsv($out, ['Month: ' . date('F Y', strtotime($month . '-01'))], $d, $e, $x);
        fputcsv($out, [], $d, $e, $x);

        fputcsv($out, [
            'Jail Unit',
            'Committed M',
            'Committed F',
            'Committed Total',
            'Released M',
            'Released F',
            'Released Total',
            'Grand Total',
        ], $d, $e, $x);

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['jail_unit_name'],
                $r['committed_male'],
                $r['committed_female'],
                $r['committed_total'],
                $r['released_male'],
                $r['released_female'],
                $r['released_total'],
                $r['grand_total'],
            ], $d, $e, $x);
        }
    }

    private function exportDrugCases($out, $d, $e, $x): void
    {
        $rows = $this->model->drugCases();

        fputcsv($out, ['PDL WITH DRUG CASES'], $d, $e, $x);
        fputcsv($out, ['Region: Zamboanga Sibugay'], $d, $e, $x);
        fputcsv($out, ['As of: ' . date('F j, Y')], $d, $e, $x);
        fputcsv($out, [], $d, $e, $x);
        fputcsv($out, ['Classification', 'Male', 'Female', 'Total'], $d, $e, $x);

        $m = $f = $t = 0;
        foreach ($rows as $r) {
            fputcsv($out, [$r['classification'], $r['male'], $r['female'], $r['total']], $d, $e, $x);
            $m += $r['male'];
            $f += $r['female'];
            $t += $r['total'];
        }
        fputcsv($out, ['TOTAL', $m, $f, $t], $d, $e, $x);
    }

    private function exportDrugCasesDetailed($out, $d, $e, $x): void
    {
        $rows = $this->model->drugCasesDetailed();

        fputcsv($out, ['DATA ON PDL WITH DRUG CASES BY CLASSIFICATION'], $d, $e, $x);
        fputcsv($out, ['Region: Zamboanga Sibugay'], $d, $e, $x);
        fputcsv($out, ['As of: ' . date('F j, Y')], $d, $e, $x);
        fputcsv($out, [], $d, $e, $x);

        fputcsv($out, [
            'Classification',
            'Sentenced R.A. 6425 Adult M',
            'Sentenced R.A. 6425 Adult F',
            'Sentenced R.A. 6425 CICL M',
            'Sentenced R.A. 6425 CICL F',
            'Sentenced R.A. 9165 Adult M',
            'Sentenced R.A. 9165 Adult F',
            'Sentenced R.A. 9165 CICL M',
            'Sentenced R.A. 9165 CICL F',
            'Awaited R.A. 6425 Adult M',
            'Awaited R.A. 6425 Adult F',
            'Awaited R.A. 6425 CICL M',
            'Awaited R.A. 6425 CICL F',
            'Awaited R.A. 9165 Adult M',
            'Awaited R.A. 9165 Adult F',
            'Awaited R.A. 9165 CICL M',
            'Awaited R.A. 9165 CICL F',
            'Total',
        ], $d, $e, $x);

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['classification'],
                $r['r6425_adult_male'],
                $r['r6425_adult_female'],
                $r['r6425_cicl_male'],
                $r['r6425_cicl_female'],
                $r['r9165_adult_male'],
                $r['r9165_adult_female'],
                $r['r9165_cicl_male'],
                $r['r9165_cicl_female'],
                $r['at_r6425_adult_male'],
                $r['at_r6425_adult_female'],
                $r['at_r6425_cicl_male'],
                $r['at_r6425_cicl_female'],
                $r['at_r9165_adult_male'],
                $r['at_r9165_adult_female'],
                $r['at_r9165_cicl_male'],
                $r['at_r9165_cicl_female'],
                $r['total'],
            ], $d, $e, $x);
        }
    }

    private function exportActualPopulation($out, $d, $e, $x): void
    {
        $rows = $this->model->actualPopulationByMunicipality();

        fputcsv($out, ['BJMP ACTUAL JAIL POPULATION DATA'], $d, $e, $x);
        fputcsv($out, ['Region: Zamboanga Sibugay'], $d, $e, $x);
        fputcsv($out, ['As of: ' . date('F j, Y')], $d, $e, $x);
        fputcsv($out, [], $d, $e, $x);

        fputcsv($out, [
            'Municipality',
            'Detainee M',
            'Detainee F',
            'Detainee Total',
            'Municipal M',
            'Municipal F',
            'City M',
            'City F',
            'Provincial M',
            'Provincial F',
            'Insular M',
            'Insular F',
            'Total Sentenced',
            'Grand Total',
        ], $d, $e, $x);

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['municipality'],
                $r['detainee_male'],
                $r['detainee_female'],
                $r['detainee_total'],
                $r['municipal_male'],
                $r['municipal_female'],
                $r['city_male'],
                $r['city_female'],
                $r['provincial_male'],
                $r['provincial_female'],
                $r['insular_male'],
                $r['insular_female'],
                $r['sentenced_total'],
                $r['grand_total'],
            ], $d, $e, $x);
        }
    }

    private function exportEscorts($out, string $month, $d, $e, $x): void
    {
        $rows = $this->model->escortedByMunicipality($month);

        fputcsv($out, ['DATA ON ESCORTED PDL'], $d, $e, $x);
        fputcsv($out, ['Region: Zamboanga Sibugay'], $d, $e, $x);
        fputcsv($out, ['Month: ' . date('F Y', strtotime($month . '-01'))], $d, $e, $x);
        fputcsv($out, [], $d, $e, $x);

        fputcsv($out, [
            'Municipality',
            'Court M',
            'Court F',
            'Hospital M',
            'Hospital F',
            'Drug Exam M',
            'Drug Exam F',
            'Rehab M',
            'Rehab F',
            'Inquest M',
            'Inquest F',
            'Transfer M',
            'Transfer F',
            'Visitation M',
            'Visitation F',
            'Other M',
            'Other F',
            'Total',
        ], $d, $e, $x);

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['municipality'],
                $r['court_male'],
                $r['court_female'],
                $r['hospital_male'],
                $r['hospital_female'],
                $r['drug_exam_male'],
                $r['drug_exam_female'],
                $r['rehab_male'],
                $r['rehab_female'],
                $r['inquest_male'],
                $r['inquest_female'],
                $r['transfer_male'],
                $r['transfer_female'],
                $r['visitation_male'],
                $r['visitation_female'],
                $r['other_male'],
                $r['other_female'],
                $r['total'],
            ], $d, $e, $x);
        }
    }

    private function readMonth(): string
    {
        $m = trim((string) ($_GET['month'] ?? ''));
        if (preg_match('/^\d{4}-\d{2}$/', $m)) {
            return $m;
        }
        return date('Y-m');
    }
}
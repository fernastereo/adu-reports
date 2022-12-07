<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Job;
use Illuminate\Http\Request;

class JobController extends Controller
{
    //
    public function jobReport($startDate, $endDate)
    {
        try {
            $from = date($startDate);
            $to = date($endDate);

            $jobs = Job::whereBetween('created_at', [$from, $to])
                ->get()
                ->toArray();

            return response()->json(
                [
                    'success' => true,
                    'params' => [
                        'startDate' => $startDate,
                        'endDate' => $endDate,
                    ],
                    'data' => $jobs,
                    'error' => [
                        'message' => '',
                    ]
                ],
                200
            );
        } catch (Exception $e) {
            $errorCode = $e->getCode();
            $error = "";
            switch ($errorCode) {
                case '42S22':
                    $error = "Cannot query to database ($errorCode)";
                    break;

                default:
                    # code...
                    break;
            }
            return response()->json(
                [
                    'success' => false,
                    'params' => [
                        'startDate' => $startDate,
                        'endDate' => $endDate,
                    ],
                    'data' => [],
                    'error' => [
                        'message' => $error,
                    ]
                ],
                200
            );
        }
    }
}

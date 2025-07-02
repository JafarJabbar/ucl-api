<?php

namespace App\Http\Controllers;

use App\Services\PredictionService;
use App\Traits\Response;
use Illuminate\Http\JsonResponse;

class PredictionController extends Controller
{
    use Response;
    protected PredictionService $predictionEngine;

    public function __construct(PredictionService $predictionEngine)
    {
        $this->predictionEngine = $predictionEngine;
    }

    /**
     * @return JsonResponse
     */
    public function getFinalPredictions(): JsonResponse
    {
        $predictions = $this->predictionEngine->predictFinalTable();


        return $this->success('Final predictions', $predictions);
    }

}

<?php

namespace App\Jobs;

use App\Models\Food;
use App\Services\FoodAiPriorityService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AnalyzeFoodPriorityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $foodId;

    public function __construct($foodId)
    {
        $this->foodId = $foodId;
    }

    public function handle(FoodAiPriorityService $aiService)
    {
        $food = Food::find($this->foodId);

        if (!$food) {
            return;
        }

        $ai = $aiService->analyze($food);

       $food->ai_priority_level = $ai["ai_priority_level"];
$food->ai_priority_score = $ai["ai_priority_score"];
$food->ai_priority_reason = $ai["ai_priority_reason"];
$food->ai_recommended_action = $ai["ai_recommended_action"];
$food->save();
    }
}
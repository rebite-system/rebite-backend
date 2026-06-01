<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class FoodAiPriorityService
{
    public function analyze($food)
{
    return $this->ruleBasedPriority($food);
}
   /* public function analyze($food)
    {
        $apiKey = env('OPENROUTER_API_KEY');

        $ruleBased = $this->ruleBasedPriority($food);

        if (!$apiKey) {
            return $ruleBased;
        }

        $now = Carbon::now();
        $expiry = $food->expiry ? Carbon::parse($food->expiry) : null;
        $hoursLeft = $expiry ? $now->diffInHours($expiry, false) : null;

        $prompt = "
You are an AI priority engine for ReBite.

Classify food priority using ONLY:
1. Quantity
2. Remaining time before expiry

Do NOT use notes.
Do NOT use food type.

Current time: {$now}
Expiry date/time: {$food->expiry}
Hours left before expiry: {$hoursLeft}
Quantity: {$food->quantity}

Rules:
- High: quantity > 50 OR hours left <= 2 OR already expired.
- Medium: quantity between 20 and 50 OR hours left between 2 and 6.
- Low: quantity < 20 AND hours left > 6.

Expiry urgency is more important than quantity.

Return ONLY valid JSON:
{
  \"ai_priority_level\": \"High or Medium or Low\",
  \"ai_priority_score\": 0-100,
  \"ai_priority_reason\": \"short reason based on quantity and time\",
  \"ai_recommended_action\": \"short action\"
}
";

        try {
            $response = Http::withHeaders([
                "Authorization" => "Bearer " . $apiKey,
                "Content-Type" => "application/json",
                "HTTP-Referer" => "https://rebite-frontend.vercel.app",
                "X-Title" => "ReBite",
            ])->timeout(30)->post(
                "https://openrouter.ai/api/v1/chat/completions",
                [
                    "model" => "nvidia/nemotron-3-super-120b-a12b:free",
                    "messages" => [
                        [
                            "role" => "user",
                            "content" => $prompt
                        ]
                    ]
                ]
            );

            $text = $response->json("choices.0.message.content");

            if (!$text) {
                return $ruleBased;
            }

            $text = trim($text);
            $text = str_replace(["```json", "```"], "", $text);

            $data = json_decode($text, true);

            if (!$data) {
                return $ruleBased;
            }

            return [
                "ai_priority_level" => $data["ai_priority_level"] ?? $ruleBased["ai_priority_level"],
                "ai_priority_score" => $data["ai_priority_score"] ?? $ruleBased["ai_priority_score"],
                "ai_priority_reason" => $data["ai_priority_reason"] ?? $ruleBased["ai_priority_reason"],
                "ai_recommended_action" => $data["ai_recommended_action"] ?? $ruleBased["ai_recommended_action"],
            ];
        } catch (\Exception $e) {
            return $ruleBased;
        }
    }*/

  private function ruleBasedPriority($food)
{
    $now = Carbon::now();

    $pickupFrom = $food->pickup_from
        ? Carbon::parse($now->toDateString() . ' ' . $food->pickup_from)
        : null;

    $pickupUntil = $food->pickup_until
        ? Carbon::parse($now->toDateString() . ' ' . $food->pickup_until)
        : null;

    if ($pickupFrom && $pickupUntil && $pickupUntil->lessThan($pickupFrom)) {
        $pickupUntil->addDay();
    }

    $hoursLeft = $pickupUntil ? $now->diffInHours($pickupUntil, false) : null;

    if ($hoursLeft !== null && $hoursLeft <= 3) {
        return [
            "ai_priority_level" => "High",
            "ai_priority_score" => 90,
            "ai_priority_reason" => "Less than 3 hours remaining until pickup deadline.",
            "ai_recommended_action" => "Prioritize immediate pickup.",
        ];
    }

    if ($hoursLeft !== null && $hoursLeft <= 10) {
        return [
            "ai_priority_level" => "Medium",
            "ai_priority_score" => 60,
            "ai_priority_reason" => "Between 4 and 10 hours remaining until pickup deadline.",
            "ai_recommended_action" => "Arrange pickup soon.",
        ];
    }

    return [
        "ai_priority_level" => "Low",
        "ai_priority_score" => 25,
        "ai_priority_reason" => "More than 10 hours remaining until pickup deadline.",
        "ai_recommended_action" => "Normal monitoring.",
    ];
}
}
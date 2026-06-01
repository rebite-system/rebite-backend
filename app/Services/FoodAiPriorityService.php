<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class FoodAiPriorityService
{
    public function analyze($food)
    {
        $apiKey = env('OPENROUTER_API_KEY');

        if (!$apiKey) {
            return $this->fallback();
        }

        $now = Carbon::now('Africa/Cairo');

        $pickupFrom = $food->pickup_from
            ? Carbon::parse($now->toDateString() . ' ' . $food->pickup_from, 'Africa/Cairo')
            : null;

        $pickupUntil = $food->pickup_until
            ? Carbon::parse($now->toDateString() . ' ' . $food->pickup_until, 'Africa/Cairo')
            : null;

        if ($pickupFrom && $pickupUntil && $pickupUntil->lessThan($pickupFrom)) {
            $pickupUntil->addDay();
        }

        $hoursLeft = $pickupUntil
            ? round($now->diffInMinutes($pickupUntil, false) / 60, 1)
            : null;

        $prompt = "
You are an AI priority engine for ReBite.

Your task is to classify food donation priority.

The MOST IMPORTANT factor is the remaining time until pickup deadline.

Current time: {$now}
Pickup from: {$food->pickup_from}
Pickup until: {$food->pickup_until}
Hours remaining: {$hoursLeft}

Quantity: {$food->quantity}

Priority rules:

HIGH:
- Less than 3 hours remaining.

MEDIUM:
- Between 4 and 10 hours remaining.

LOW:
- More than 10 hours remaining.

Ignore food type.
Ignore notes.

Return ONLY valid JSON.

{
  \"ai_priority_level\": \"High or Medium or Low\",
  \"ai_priority_score\": 0-100,
  \"ai_priority_reason\": \"short explanation\",
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
                return $this->fallback();
            }

            $text = trim($text);
            $text = str_replace(["```json", "```"], "", $text);

            $data = json_decode($text, true);

            if (!$data) {
                return $this->fallback();
            }

            return [
                "ai_priority_level" => $data["ai_priority_level"] ?? "Low",
                "ai_priority_score" => $data["ai_priority_score"] ?? 0,
                "ai_priority_reason" => $data["ai_priority_reason"] ?? "AI analysis completed",
                "ai_recommended_action" => $data["ai_recommended_action"] ?? "Normal monitoring",
            ];
        } catch (\Exception $e) {
            return $this->fallback();
        }
    }

    private function fallback()
    {
        return [
            "ai_priority_level" => "Low",
            "ai_priority_score" => 0,
            "ai_priority_reason" => "AI unavailable",
            "ai_recommended_action" => "Normal monitoring",
        ];
    }
}
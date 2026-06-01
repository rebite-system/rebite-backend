<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;


class FoodAiPriorityService
{
    public function analyze($food)
    {
        $apiKey = env('OPENROUTER_API_KEY');

        if (!$apiKey) {
            return $this->fallback();
        }

       $prompt = "
You are an AI priority engine for ReBite, a same-day surplus food donation platform.

Important rule:
All food listings are same-day pickup listings, so do NOT mark everything as High only because the expiry date is today.

Analyze the food listing and classify priority mainly using quantity, pickup urgency, and food type.

Priority rules:
- High: quantity is 100 portions or more, OR notes mention urgent/immediate/spoiled/risk, OR pickup window is very short.
- Medium: quantity is between 30 and 99 portions, OR cooked meals with normal pickup conditions.
- Low: quantity is less than 30 portions and no urgent notes.

Food:
Title: {$food->title}
Category: {$food->category}
Quantity: {$food->quantity}
Expiry date: {$food->expiry}
Pickup from: {$food->pickup_from}
Pickup until: {$food->pickup_until}
Notes: {$food->notes}

Return ONLY valid JSON. No markdown. No explanation.

Return exactly:
{
  \"ai_priority_level\": \"High or Medium or Low\",
  \"ai_priority_score\": 0-100,
  \"ai_priority_reason\": \"short reason\",
  \"ai_recommended_action\": \"short action\"
}

Expected scoring:
High = 80 to 100
Medium = 45 to 79
Low = 0 to 44
";

        try {
            $response = Http::withHeaders([
                "Authorization" => "Bearer " . $apiKey,
                "Content-Type" => "application/json",
                "HTTP-Referer" => "http://localhost:5173",
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
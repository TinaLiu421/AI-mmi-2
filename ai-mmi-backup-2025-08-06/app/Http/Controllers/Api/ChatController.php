<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ChatController extends Controller
{
    public function log(Request $request)
    {
        // 既支持 JSON body 也支持 form
        $q         = $request->input('question', '');
        $a         = $request->input('answer', '');
        $chatMode  = $request->input('chat_mode', 'immigration');
        $relatedId = $request->input('related_id'); // 可为空，表示新会话

        // 用登录用户；若你项目是自定义 session，请改成从 session 拿
        $memberId = auth()->id();
        if (!$memberId) {
            // 兼容：很多项目把 member_id 放 session
            $memberId = (int) session('member_id', 0);
        }
        if (!$memberId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if ($q === '' && $a === '') {
            return response()->json(['message' => 'Empty payload'], 422);
        }

        $now = Carbon::now('UTC');
        $targetDate = (int) date('Ymd', time());

        $savedRelatedId = null;

        DB::beginTransaction();
        try {
            // 插入 ask
            $askId = null;
            if ($q !== '') {
                $askId = DB::table('chat_log')->insertGetId([
                    'member_id'   => $memberId,
                    'related_id'  => 0, // 先置 0，下面再回写
                    'target_date' => $targetDate,
                    'type'        => 'ask',
                    'content'     => $q,
                    'chat_mode'   => $chatMode,
                    'status'      => 1,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
            }

            // 计算 related_id：优先用传入的，其次用新 ask 的 id
            $savedRelatedId = $relatedId ?: ($askId ?: null);

            // 回写 ask 的 related_id
            if ($askId && $savedRelatedId) {
                DB::table('chat_log')->where('id', $askId)->update(['related_id' => $savedRelatedId]);
            }

            // 插入 reply（如果有）
            $replyId = null;
            if ($a !== '') {
                $replyId = DB::table('chat_log')->insertGetId([
                    'member_id'   => $memberId,
                    'related_id'  => $savedRelatedId ?: 0,
                    'target_date' => $targetDate,
                    'type'        => 'reply',
                    'content'     => $a,
                    'chat_mode'   => $chatMode,
                    'status'      => 1,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
            }

            DB::commit();

            // Update user profile asynchronously from the new chat messages
            // This ensures profile stays current as user provides new information
            try {
                \Log::info('Starting profile update after chat', ['member_id' => $memberId]);
                $this->updateUserProfileFromChat($memberId);
                \Log::info('Profile update completed for member', ['member_id' => $memberId]);
            } catch (\Throwable $e) {
                // Log but don't fail the chat operation if profile update fails
                \Log::warning('Profile update failed after chat', [
                    'member_id' => $memberId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }

            return response()->json([
                'ok'          => true,
                'related_id'  => $savedRelatedId,
                'ask_id'      => $askId,
                'reply_id'    => $replyId,
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'ok'      => false,
                'message' => 'DB error',
            ], 500);
        }
    }

    /**
     * Update user profile from latest chat messages
     * Called automatically after each chat to keep profile current
     */
    private function updateUserProfileFromChat($memberId)
    {
        // Extract patterns from the 100 most recent chat messages
        $chatHistory = DB::table('chat_log')
            ->where('member_id', $memberId)
            ->orderBy('id', 'desc')
            ->limit(100)
            ->get()
            ->reverse()
            ->toArray();

        if (empty($chatHistory)) {
            \Log::debug("No chat history found for member {$memberId}");
            return;
        }

        \Log::debug("Extracting profile from {count($chatHistory)} messages for member {$memberId}");

        // Extract profile using Gemini AI
        $profile = $this->extractProfileWithGemini($chatHistory);

        // If no profile extracted, skip saving
        if (empty($profile)) {
            \Log::debug("Gemini extraction returned empty profile for member {$memberId}");
            return;
        }

        \Log::debug("Extracted profile for member {$memberId}: " . json_encode($profile));

        // Save profile to member table (as JSON in profile_data column)
        DB::table('member')->where('id', $memberId)->update([
            'profile_data' => json_encode($profile),
            'profile_updated_at' => now(),
        ]);

        \Log::info("Profile auto-updated for member {$memberId}", [
            'profile' => $profile
        ]);
    }

    /**
     * Extract user profile using Gemini AI for intelligent understanding
     * More accurate than regex patterns for distinguishing real info from context
     */
    private function extractProfileWithGemini($chatHistory): array
    {
        try {
            // Extract only user messages
            $userMessages = [];
            foreach ($chatHistory as $message) {
                $type = is_object($message) ? $message->type : ($message['type'] ?? '');
                $content = is_object($message) ? $message->content : ($message['content'] ?? '');

                if ($type === 'ask') {
                    $userMessages[] = $content;
                }
            }

            if (empty($userMessages)) {
                return [];
            }

            $chatText = implode("\n", $userMessages);

            // Truncate to avoid token limits
            $chatText = mb_strimwidth($chatText, 0, 4000, '...');

            $prompt = <<<PROMPT
You are a migration assistant helping understand user profiles. Extract user personal information from this chat conversation.

CRITICAL INSTRUCTION: Only extract information that the USER explicitly stated about THEMSELVES.
NEVER extract information about visa requirements, eligibility criteria, or system-generated recommendations.
Example: If text contains "must be 45 years old" - this is a REQUIREMENT, not user profile data. Ignore it.

CHAT HISTORY:
$chatText

TASK: Extract ONLY information directly stated by the user about themselves AND their migration interests.
- Ignore all visa requirements, visa criteria, and eligibility information
- Ignore all system messages or bot recommendations
- Only extract personal facts the user disclosed about themselves
- Extract the country and visa type they mentioned being interested in

OUTPUT FORMAT - Return ONLY valid JSON:
{
  "age": "number (18-100) or null",
  "education": "degree field or null (e.g., 'Computer Science', 'Bachelor of Commerce')",
  "occupation": "job title or null (e.g., 'Software Engineer', 'Data Analyst')",
  "experience": "years as number or null (e.g., 5, 10)",
  "english_level": "fluency level or null (e.g., 'Advanced', 'Intermediate', 'Fluent')",
  "ielts_score": "IELTS X.X format or null",
  "toefl_score": "TOEFL XXX format or null",
  "pte_score": "PTE XX format or null",
  "nationality": "country name or null",
  "interested_country": "country name or null (e.g., 'Australia', 'Canada')",
  "interested_visa_type": "visa type name or null (e.g., 'Temporary Graduate', 'Skilled Nominated', 'Work Visa')"
}

Rules:
- Age: Extract ONLY if user explicitly states their age (e.g., "I am 28", "I'm 32 years old"). NEVER extract age from requirement text like "between 18 to 45 years".
- Education: Extract degree/field ONLY if explicitly mentioned (e.g., "Bachelor of Science")
- Occupation: Extract job title ONLY (not location like "Gold Coast")
- Experience: Extract years ONLY if explicitly mentioned by user (e.g., "I have 5 years experience")
- English level: Extract ONLY if user states fluency level about themselves
- Nationality: Extract ONLY if user explicitly states where they are from
- Interested Country: Extract if user mentions wanting to migrate to a specific country (e.g., "I want to move to Australia", "migrate to Canada")
- Interested Visa Type: Extract if user mentions a specific visa type (e.g., "485 visa" → "Temporary Graduate", "190 visa" → "Skilled Nominated", "skilled migration")
- Return null for any field not explicitly stated by the user
- NO markdown, NO explanations, ONLY valid JSON
PROMPT;

            $apiKey = env('GEMINI_API_KEY');
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp:generateContent?key=' . $apiKey;

            $data = [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => ['temperature' => 0.2, 'maxOutputTokens' => 1024]
            ];

            $response = \Http::withHeaders(['Content-Type' => 'application/json'])
                ->post($url, $data);

            if (!$response->successful()) {
                \Log::warning('Gemini profile extraction failed', [
                    'status' => $response->status(),
                    'error' => $response->body()
                ]);
                return [];
            }

            $result = $response->json();

            // Extract text from Gemini response
            if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                $text = $result['candidates'][0]['content']['parts'][0]['text'];

                // Handle markdown code fences (```json ... ```)
                if (preg_match('/```(?:json)?\s*([\s\S]*?)```/', $text, $matches)) {
                    $text = $matches[1];
                }

                // Parse JSON response
                $profile = json_decode(trim($text), true);

                if (is_array($profile)) {
                    // Clean up the profile - remove null values and ensure correct types
                    $cleaned = [];

                    if (!empty($profile['age']) && is_numeric($profile['age'])) {
                        $age = intval($profile['age']);
                        if ($age >= 18 && $age <= 100) {
                            $cleaned['age'] = $age;
                        }
                    }

                    if (!empty($profile['education'])) {
                        $cleaned['education'] = trim(strval($profile['education']));
                    }

                    if (!empty($profile['occupation'])) {
                        $cleaned['occupation'] = trim(strval($profile['occupation']));
                    }

                    if (!empty($profile['experience']) && is_numeric($profile['experience'])) {
                        $years = intval($profile['experience']);
                        if ($years >= 0 && $years <= 60) {
                            $cleaned['experience'] = $years . ' years';
                        }
                    }

                    if (!empty($profile['english_level'])) {
                        $cleaned['english_level'] = trim(strval($profile['english_level']));
                    }

                    if (!empty($profile['ielts_score'])) {
                        $cleaned['ielts_score'] = trim(strval($profile['ielts_score']));
                    }

                    if (!empty($profile['toefl_score'])) {
                        $cleaned['toefl_score'] = trim(strval($profile['toefl_score']));
                    }

                    if (!empty($profile['pte_score'])) {
                        $cleaned['pte_score'] = trim(strval($profile['pte_score']));
                    }

                    if (!empty($profile['nationality'])) {
                        $cleaned['nationality'] = trim(strval($profile['nationality']));
                    }

                    if (!empty($profile['interested_country'])) {
                        $cleaned['interested_country'] = trim(strval($profile['interested_country']));
                    }

                    if (!empty($profile['interested_visa_type'])) {
                        $cleaned['interested_visa_type'] = trim(strval($profile['interested_visa_type']));
                    }

                    return $cleaned;
                }
            }

            return [];
        } catch (\Exception $e) {
            \Log::warning('Gemini profile extraction exception', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Manual endpoint to update user profile
     * Useful for testing or manual refresh
     */
    public function updateProfile(Request $request)
    {
        $memberId = auth()->id();
        if (!$memberId) {
            $memberId = (int) session('member_id', 0);
        }
        if (!$memberId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $this->updateUserProfileFromChat($memberId);
            return response()->json([
                'ok' => true,
                'message' => 'Profile updated successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Failed to update profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}

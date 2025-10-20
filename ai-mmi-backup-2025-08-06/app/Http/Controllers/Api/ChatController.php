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
}

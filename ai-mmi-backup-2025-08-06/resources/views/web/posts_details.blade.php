<?php
$posts = $_page_data['details'];
$qa = $_page_data['qa'] ?? [];
$postSector = (!empty($posts['sector']) && in_array($posts['sector'], ['study', 'migration'], true)) ? $posts['sector'] : 'study';
$postActionLabel = ($postSector === 'migration')
    ? ($_page_lang['chat_robot.talk_to_ai_mmi'] ?? 'Talk to AI-mmi')
    : ($_page_lang['apply'] ?? 'Apply Now !');
$postActionUrl = ($postSector === 'migration') ? ($_page_base_url.'/agent_chat') : ($_page_base_url.'/apply');
$qa = array_merge([
    'items'          => [],
    'can_ask'        => false,
    'member_avatar'  => 'asset/image/icon-member.png',
    'guest_text'     => 'Please log in to ask a question.',
    'placeholder'    => 'Ask AI-mmi anything about this post...',
], $qa);

if (!function_exists('getYoutubeEmbedUrl')) {
function getYoutubeEmbedUrl($url) {
    $shortUrlRegex = '/youtu.be\/([a-zA-Z0-9_-]+)\??/i';
    $longUrlRegex = '/youtube.com\/((?:embed)|(?:watch))((?:\?v\=)|(?:\/))([a-zA-Z0-9_-]+)/i';

    $youtube_id = '';
    if (preg_match($longUrlRegex, (string)$url, $matches)) {
        $youtube_id = $matches[count($matches) - 1];
    }
    if ($youtube_id === '' && preg_match($shortUrlRegex, (string)$url, $matches)) {
        $youtube_id = $matches[count($matches) - 1];
    }

    return 'https://www.youtube.com/embed/' . $youtube_id;
}
}
?>
@extends('web.common')
@section('content')
<style>
.qa-section{margin-top:24px;border:1px solid #e5e7eb;background:#f9fafb;border-radius:12px;padding:16px;}
.qa-header{display:flex;justify-content:space-between;align-items:flex-start;gap:8px;margin-bottom:12px;}
.qa-title{font-size:18px;font-weight:600;color:#0f172a;}
.qa-subtext{font-size:13px;color:#6b7280;}
.qa-list{display:flex;flex-direction:column;gap:12px;}
.qa-item{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:12px;}
.qa-row{display:flex;gap:12px;}
.qa-avatar{width:36px;height:36px;border-radius:9999px;background:#e5e7eb;background-size:cover;background-position:center;flex-shrink:0;}
.qa-avatar.small{width:30px;height:30px;}
.qa-content{flex:1;}
.qa-meta{display:flex;align-items:center;gap:8px;flex-wrap:wrap;font-size:13px;color:#6b7280;}
.qa-name{font-weight:600;font-size:14px;color:#0f172a;}
.qa-badge{background:#e0f2fe;color:#0369a1;padding:2px 8px;border-radius:9999px;font-size:11px;font-weight:600;}
.qa-time{margin-left:auto;font-size:12px;color:#9ca3af;}
.qa-text{margin-top:4px;color:#111827;line-height:1.5;font-size:14px;word-break:break-word;}
.qa-answer{display:flex;gap:12px;margin-top:12px;padding-left:44px;border-left:2px solid #e5e7eb;}
.qa-empty{padding:12px;border:1px dashed #cbd5e1;border-radius:10px;font-size:14px;color:#6b7280;background:#fff;}
.qa-form{margin-top:16px;}
.qa-input{display:flex;gap:12px;align-items:flex-start;}
.qa-input textarea{width:100%;border:1px solid #d1d5db;border-radius:10px;padding:10px;min-height:90px;resize:vertical;font-size:14px;color:#0f172a;background:#fff;}
.qa-actions{display:flex;justify-content:flex-end;margin-top:8px;}
.qa-send{background:#0f766e;color:#fff;border:none;border-radius:10px;padding:10px 16px;font-weight:600;cursor:pointer;transition:background 0.2s;}
.qa-send:hover{background:#0d645d;}
.qa-send:disabled{background:#9ca3af;cursor:not-allowed;}
.qa-guest textarea{width:100%;border:1px dashed #d1d5db;border-radius:10px;padding:10px;min-height:90px;resize:none;background:#f3f4f6;color:#9ca3af;}
.qa-login-link{display:inline-block;margin-top:8px;font-weight:600;color:#0f766e;}
.qa-support{margin-left:auto;font-size:12px;font-weight:600;color:#ef4444;}
@media (max-width: 640px){.qa-section{padding:12px;}.qa-answer{padding-left:32px;}}
</style>
<div class="inner-panel">
    <div class="article-list" data-done="true">
        <div class="post">
            <div>
                <div class="author">
                    <div class="avatar">
                        <a href="<?php echo $_page_base_url.'/account/posts?uid='.$posts['member_id']; ?>">
                            <img src="asset/image/icon-member.png" alt="icon-member"/>
                            <?php if(!empty($posts['avatar'])){ ?>
                            <?php if(file_exists(public_path('upload/member_avatar/'.$posts['avatar']))) { ?>
                            <div style="background-image:url('<?php echo 'upload/member_avatar/'.$posts['avatar']; ?>')"></div>
                            <?php } elseif(file_exists(public_path('upload/member_logo/'.$posts['avatar']))) { ?>
                            <div style="background-image:url('<?php echo 'upload/member_logo/'.$posts['avatar']; ?>')"></div>
                            <?php } ?>
                            <?php } ?>
                        </a>
                    </div>
                    <div class="name">
                        <div><a href="<?php echo $_page_base_url.'/account/posts?uid='.$posts['member_id']; ?>"><?php echo $posts['alias_name']; ?></a></div>
                        <div class="hours">
                            <?php echo date('d/m/Y', strtotime($posts['created_at'])); ?> &#x2022; <img src="asset/image/icon-earth.png" alt="icon-earth" width="16"/>
                        </div>
                    </div>
                </div>

                <?php if(!empty($_current_member) && $_current_member['id'] == $posts['member_id']) { ?>
                <div class="follow">
                    <a id="edit-publish-posts" href="#" data-id="<?php echo $posts['id']; ?>"><i class="fa fa-pencil-square-o"></i></a>
                </div>
                <?php } else { ?>
                <div class="follow" style="display:none;">
                    <a href="#">+ <?php echo $_page_lang['follow']; ?></a>
                </div>
                <?php } ?>

                <div class="clearboth"></div>

                <div class="details">
                    <?php if(!empty($posts['title'])) { ?>
                    <h3><?php echo nl2br($posts['title']); ?></h3>
                    <?php } ?>
                    <div class="iweb-editor">
                        <div class="post-md-body" data-md="<?php echo htmlspecialchars($posts['content'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>

                        <?php if(!empty($posts['photo']) && file_exists('upload/member_posts/'.$posts['photo'])) { ?>
                        <p style="text-align:center;"><img src="<?php echo 'upload/member_posts/'.$posts['photo'];?>"></p>
                        <?php } ?>

                        <?php if(!empty($posts['youtube_url'])) { ?>
                        <div>
                            <div class="iweb-responsive" data-width="1268" data-height="713">
                                <iframe class="youtube-video" src="<?php echo getYoutubeEmbedUrl($posts['youtube_url']); ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                            </div>
                        </div>
                        <?php } ?>
                        <div class="clearboth"></div>
                    </div>
                </div>
                <div class="clearboth"></div>

                <div class="summary">
                    <div class="actions">
                        <?php
                        $_postPresetMsg = '';
                        if (!empty($posts['title'])) {
                            if (stripos($posts['title'], 'NZ as Business Investor') !== false) {
                                $_postPresetMsg = 'Hi, I am interested in moving to NZ as a business investor. Can you let me know more details?';
                            } elseif (stripos($posts['title'], 'New Zealand') !== false) {
                                $_postPresetMsg = 'How can I migrate to New Zealand as a business investor?';
                            }
                        }
                        ?>
                        <a class="do-toapply" data-id="<?php echo $posts['id']; ?>" data-post-title="<?php echo htmlspecialchars($posts['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" data-post-summary="<?php echo htmlspecialchars(strip_tags((string)($posts['content'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>" data-action-url="<?php echo $postActionUrl; ?>" data-sector="<?php echo $postSector; ?>"<?php if ($_postPresetMsg) echo ' data-preset-msg="'.htmlspecialchars($_postPresetMsg, ENT_QUOTES, 'UTF-8').'"'; ?>>
                            <img src="asset/image/icon-apply.png" alt="icon-action"/>
                            <span><?php echo $postActionLabel; ?></span>
                        </a>
                        <?php if (!empty($_current_member) && ((int)($_current_member['type'] ?? 0) !== 3 || strpos(mb_strtolower(trim($_current_member['email'] ?? ''), 'UTF-8'), '@wealthskey.com') !== false)): ?>
                        <a class="do-post-talk-agent" href="javascript:void(0);">
                            <span>Talk to Registered Agent</span>
                        </a>
                        <?php endif; ?>
                        <a class="do-share">
                            <img src="asset/image/icon-share.png" alt="icon-share"/>
                            <span><?php echo $_page_lang['share']; ?></span>
                        </a>
                        <div class="shareto">
                            <div>
                                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($_page_base_url.'/posts/details/'.$posts['id']); ?>" target="_blank">
                                    <img src="asset/image/icon-facebook-48.png" alt="icon-facebook">
                                </a>
                                <a href="https://wa.me/?text=<?php echo urlencode($_page_base_url.'/posts/details/'.$posts['id']); ?>" target="_blank">
                                    <img src="asset/image/icon-whatsapp-48.png" alt="icon-whatsapp">
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="clearboth"></div>

                <div class="qa-section" id="qa-section" style="display:none;">
                    <div class="qa-header">
                        <div>
                            <div class="qa-title">Q&amp;A</div>
                            <div class="qa-subtext">Ask questions about this post. AI-mmi replies just below.</div>
                        </div>
                        <div class="qa-support">AI-mmi Support</div>
                    </div>
                    <div class="qa-list" id="qa-list">
                        <?php if(!empty($qa['items'])) { foreach ($qa['items'] as $qa_item) { ?>
                        <div class="qa-item" data-question-id="<?php echo $qa_item['id']; ?>">
                            <div class="qa-row">
                                <div class="qa-avatar" style="background-image:url('<?php echo $qa_item['member_avatar']; ?>');"></div>
                                <div class="qa-content">
                                    <div class="qa-meta">
                                        <span class="qa-name"><?php echo htmlspecialchars($qa_item['member_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <span class="qa-time"><?php echo htmlspecialchars($qa_item['created_human'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                    <div class="qa-text"><?php echo qa_nl2br($qa_item['content']); ?></div>
                                </div>
                            </div>
                            <?php if(!empty($qa_item['answer']) && trim((string)($qa_item['answer']['content'] ?? '')) !== '') { $ans = $qa_item['answer']; ?>
                            <div class="qa-answer">
                                <div class="qa-avatar small" style="background-image:url('<?php echo $ans['member_avatar']; ?>');"></div>
                                <div class="qa-content">
                                    <div class="qa-meta">
                                        <span class="qa-name"><?php echo htmlspecialchars($ans['member_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php if(!empty($ans['badge'])) { ?><span class="qa-badge"><?php echo htmlspecialchars($ans['badge'], ENT_QUOTES, 'UTF-8'); ?></span><?php } ?>
                                        <span class="qa-time"><?php echo htmlspecialchars($ans['created_human'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                    <div class="qa-text"><?php echo qa_nl2br($ans['content']); ?></div>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                        <?php }} else { ?>
                        <div class="qa-empty">No questions yet. Be the first to ask.</div>
                        <?php } ?>
                    </div>
                    <?php if(!empty($qa['can_ask'])) { ?>
                    <form id="qa-form" class="qa-form" action="<?php echo $_page_base_url.'/posts/'.$posts['id'].'/qa-ask'; ?>" data-post-id="<?php echo $posts['id']; ?>">
                        <div class="qa-input">
                            <div class="qa-avatar" style="background-image:url('<?php echo $qa['member_avatar']; ?>');"></div>
                            <div class="qa-content">
                                <textarea name="question" placeholder="<?php echo $qa['placeholder']; ?>"></textarea>
                                <div class="qa-actions">
                                    <button type="submit" class="qa-send">Send</button>
                                </div>
                            </div>
                        </div>
                    </form>
                    <?php } else { ?>
                    <div class="qa-guest">
                        <textarea disabled placeholder="<?php echo $qa['guest_text']; ?>"></textarea>
                        <a class="qa-login-link" href="<?php echo $_page_base_url.'/account_login'; ?>">Login to ask a question</a>
                    </div>
                    <?php } ?>
                </div>

                <script>
                (function() {
                    var qaForm = $('#qa-form');
                    if (!qaForm.length) return;

                    var qaList = $('#qa-list');
                    var textarea = qaForm.find('textarea[name="question"]');
                    var sendBtn = qaForm.find('.qa-send');

                    function buildAvatarStyle(url) {
                        var safeUrl = url || 'asset/image/icon-member.png';
                        return "background-image:url('" + safeUrl + "');";
                    }

                    function renderQaItem(payload) {
                        var q = payload.question || {};

                        // 非常严格地判断 answer 是否有效
                        var a = null;
                        if (payload && payload.answer && typeof payload.answer === 'object') {
                            var rawContent = payload.answer.content;
                            var trimmed    = (rawContent === undefined || rawContent === null)
                                ? ''
                                : String(rawContent).trim();
                            if (trimmed) {
                                a = payload.answer;
                            }
                        }

                        var owner = q.owner || {};
                        var questionText = escapeHtml(q.content || '').replace(/\n/g, '<br>');
                        var html = '';

                        html += '<div class="qa-item" data-question-id="'+(q.id || '')+'">';
                        html +=   '<div class="qa-row">';
                        html +=     '<div class="qa-avatar" style="'+buildAvatarStyle(owner.avatar)+'"></div>';
                        html +=     '<div class="qa-content">';
                        html +=       '<div class="qa-meta">';
                        html +=         '<span class="qa-name">'+escapeHtml(owner.name || 'User')+'</span>';
                        html +=         '<span class="qa-time">'+escapeHtml(q.created_human || '')+'</span>';
                        html +=       '</div>';
                        html +=       '<div class="qa-text">'+questionText+'</div>';
                        html +=     '</div>';
                        html +=   '</div>';

                        if (a) {
                            var answerOwner = a.owner || {
                                name: 'AI-mmi',
                                avatar: 'asset/image/logo-mmi.png',
                                badge: 'Assistant'
                            };

                            var answerTrim = String(a.content).trim();
                            if (answerTrim) {
                                var answerText = escapeHtml(answerTrim).replace(/\n/g, '<br>');
                                html += '<div class="qa-answer">';
                                html +=   '<div class="qa-avatar small" style="'+buildAvatarStyle(answerOwner.avatar)+'"></div>';
                                html +=   '<div class="qa-content">';
                                html +=     '<div class="qa-meta">';
                                html +=       '<span class="qa-name">'+escapeHtml(answerOwner.name || 'AI-mmi')+'</span>';
                                if (answerOwner.badge || a.badge) {
                                    html += '<span class="qa-badge">'+escapeHtml(answerOwner.badge || a.badge || '')+'</span>';
                                }
                                html +=       '<span class="qa-time">'+escapeHtml(a.created_human || '')+'</span>';
                                html +=     '</div>';
                                html +=     '<div class="qa-text">'+answerText+'</div>';
                                html +=   '</div>';
                                html += '</div>';
                            }
                        }

                        html += '</div>';
                        return html;
                    }

                    qaForm.on('submit', function(e) {
                        e.preventDefault();
                        var text = $.trim(textarea.val());
                        if (!text) {
                            textarea.focus();
                            return;
                        }
                        sendBtn.prop('disabled', true).text('Sending...');
                        $.ajax({
                            method: 'POST',
                            url: qaForm.attr('action'),
                            data: { question: text },
                            success: function(resp) {
                                if (!resp || resp.status !== 200) {
                                    alert(resp && resp.message ? resp.message : 'Unable to send your question.');
                                    return;
                                }
                                qaList.find('.qa-empty').remove();
                                qaList.prepend(renderQaItem(resp));
                                textarea.val('');
                            },
                            error: function(xhr) {
                                if (xhr && xhr.status === 401) {
                                    alert('Please log in to ask a question.');
                                    window.location.href = _page_base_url + '/account_login';
                                    return;
                                }
                                var message = (xhr && xhr.responseJSON && xhr.responseJSON.message)
                                    ? xhr.responseJSON.message
                                    : 'Unable to send your question right now.';
                                alert(message);
                            },
                            complete: function() {
                                sendBtn.prop('disabled', false).text('Send');
                            }
                        });
                    });
                })();
                </script>

                <div class="leavecomment">
                    <div>
                        <div class="reply">
                        </div>
                        <textarea name="message" placeholder="<?php echo $_page_lang['account.enter_comment']; ?>"></textarea>
                        <button type="button" class="btn-send-comment" data-id="<?php echo $posts['id']; ?>"><img src="asset/image/icon-send.png" alt="icon-send"></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
(function() {
    function render() {
        document.querySelectorAll('.post-md-body[data-md]').forEach(function(el) {
            if (el._mdRendered) return;
            el._mdRendered = true;
            try {
                var md = el.getAttribute('data-md') || '';
                if (typeof mdToSafeHtml === 'function') {
                    el.innerHTML = mdToSafeHtml(md);
                } else if (typeof marked !== 'undefined') {
                    marked.setOptions({ gfm: true, breaks: true });
                    var html = marked.parse(md);
                    el.innerHTML = typeof DOMPurify !== 'undefined' ? DOMPurify.sanitize(html, {ADD_TAGS:['mark']}) : html;
                } else { el.textContent = md; }
            } catch(e) { el.textContent = el.getAttribute('data-md') || ''; }
        });
    }
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', render); }
    else { render(); }
})();
</script>
@endpush

@endsection
<?php
if (!function_exists('qa_nl2br')) {
function qa_nl2br($text) {
    return nl2br(htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8'));
}
}

if (!function_exists('time2Units')) {
function time2Units($time, $lang = 1) {
    $year = floor($time / 60 / 60 / 24 / 365);
    $time -= $year * 60 * 60 * 24 * 365;
    $month = floor($time / 60 / 60 / 24 / 30);
    $time -= $month * 60 * 60 * 24 * 30;
    $week = floor($time / 60 / 60 / 24 / 7);
    $time -= $week * 60 * 60 * 24 * 7;
    $day = floor($time / 60 / 60 / 24);
    $time -= $day * 60 * 60 * 24;
    $hour = floor($time / 60 / 60);
    $time -= $hour * 60 * 60;
    $minute = floor($time / 60);
    $time -= $minute * 60;
    $second = max(1, $time);
    $elapse = '';
    if($lang == 2) {
        $unitArr = [
            '年' => 'year',
            '月' => 'month',
            '周' => 'week',
            '天' => 'day',
            '时' => 'hour',
            '分' => 'minute',
            '秒' => 'second',
        ];
    }
    else if($lang == 3) {
        $unitArr = [
            '年' => 'year',
            '月' => 'month',
            '周' => 'week',
            '天' => 'day',
            '时' => 'hour',
            '分' => 'minute',
            '秒' => 'second',
        ];
    }
    else {
        $unitArr = [
            'Y' => 'year',
            'M' => 'month',
            'W' => 'week',
            'D' => 'day',
            'H' => 'hour',
            'Min' => 'minute',
            'Sec' => 'second',
        ];
    }
    foreach ($unitArr as $cn => $u) {
        if ($$u > 0) {
            $elapse = $$u . $cn;
            break;
        }
    }
    return $elapse;
}
}

?>

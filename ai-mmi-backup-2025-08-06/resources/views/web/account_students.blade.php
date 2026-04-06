@extends('web.common')
@section('content')
<?php
$_show_current_member        = $_page_data['show_current_member'];
$_show_current_member_details = (!empty($_page_data['current_member_details'])) ? $_page_data['current_member_details'] : [];
$_institution_profile        = $_page_data['institution_profile'] ?? null;
$_is_edu_institution         = true; // only edu institutions reach this page
$_list_type                  = $_page_data['list_type'] ?? 'matched'; // matched | applied | accepted
$_students                   = $_page_data['students'] ?? [];
$_uid_qs                     = (!empty($_page_get_data['uid'])) ? '?uid='.$_page_get_data['uid'] : '';

$_tab_labels = [
    'matched'  => 'Students Matched',
    'applied'  => 'Students Applied',
    'accepted' => 'Students Accepted',
];
$_page_title = $_tab_labels[$_list_type] ?? 'Students';
?>
<div class="inner-panel full">
    <?php if(!empty($_show_current_member['coverphoto']) && file_exists('upload/member_coverphoto/'.$_show_current_member['coverphoto'])) { ?>
    <div class="banner" style="background-image:url('<?php echo 'upload/member_coverphoto/'.$_show_current_member['coverphoto']; ?>')"></div>
    <?php } else { ?>
    <div class="banner" style="display:none;"></div>
    <?php } ?>
    <div class="basic">
        <div class="photo">
            <img src="asset/image/icon-member.png" alt="icon-member"/>
            <?php if(file_exists('upload/member_avatar/'.$_show_current_member['avatar'])) { ?>
            <div class="avatar" style="background-image:url('<?php echo 'upload/member_avatar/'.$_show_current_member['avatar']; ?>')"></div>
            <?php } else { ?>
            <div class="avatar" style="background-image:url('<?php echo 'upload/member_logo/'.$_show_current_member['avatar']; ?>');background-size:contain;background-color:#fff;border-radius:8px;"></div>
            <?php } ?>
        </div>
        <div class="name">
            <div class="alias">
                <div class="readonly">
                    <span><?php echo $_show_current_member['alias_name']; ?></span>
                </div>
            </div>
            <div class="total-followers">0 followers</div>
        </div>
        <div class="clearboth"></div>
        <div class="tab">
            <a class="posts" href="<?php echo $_page_base_url.'/account/posts'.$_uid_qs; ?>"><?php echo $_page_lang['tab_posts']; ?></a>
            <a class="about" href="<?php echo $_page_base_url.'/account/profile'.$_uid_qs; ?>"><?php echo $_page_lang['tab_about']; ?></a>
            <a class="edu-tab <?php echo $_list_type === 'matched' ? 'selected' : ''; ?>" href="<?php echo $_page_base_url.'/account/students_matched'.$_uid_qs; ?>">Students Matched</a>
            <a class="edu-tab <?php echo $_list_type === 'applied' ? 'selected' : ''; ?>" href="<?php echo $_page_base_url.'/account/students_applied'.$_uid_qs; ?>">Students Applied</a>
            <a class="edu-tab <?php echo $_list_type === 'accepted' ? 'selected' : ''; ?>" href="<?php echo $_page_base_url.'/account/students_accepted'.$_uid_qs; ?>">Students Accepted</a>
            <?php if(empty($_page_data['is_readonly']) && in_array((int)$_show_current_member['type'], [2, 3])): ?>
            <a class="spotlight" href="<?php echo $_page_base_url.'/account/spotlight'; ?>">⭐ Spotlight</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="tab-details blank">
        <div class="edu-student-list">
            <div class="edu-student-list-header">
                <h2><?php echo htmlspecialchars($_page_title, ENT_QUOTES); ?></h2>
                <span class="edu-student-list-count"><?php echo count($_students); ?> student<?php echo count($_students) !== 1 ? 's' : ''; ?></span>
            </div>

            <?php if(empty($_students)): ?>
            <div class="edu-student-empty">
                <div class="edu-student-empty-icon">&#127979;</div>
                <div class="edu-student-empty-title">No students yet</div>
                <div class="edu-student-empty-desc">
                    <?php if($_list_type === 'matched'): ?>
                    Students who match this institution's programs will appear here once the matching feature is live.
                    <?php elseif($_list_type === 'applied'): ?>
                    Students who have applied to this institution will appear here.
                    <?php else: ?>
                    Students who have been accepted by this institution will appear here.
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="edu-student-rows">
                <?php foreach($_students as $_student): ?>
                <div class="edu-student-row">
                    <div class="edu-student-avatar">
                        <?php if(!empty($_student['avatar']) && file_exists('upload/member_avatar/'.$_student['avatar'])): ?>
                        <div style="background-image:url('<?php echo 'upload/member_avatar/'.htmlspecialchars($_student['avatar'], ENT_QUOTES); ?>')"></div>
                        <?php else: ?>
                        <img src="asset/image/icon-member.png" alt="student">
                        <?php endif; ?>
                    </div>
                    <div class="edu-student-info">
                        <div class="edu-student-name"><?php echo htmlspecialchars($_student['alias_name'] ?? $_student['full_name'] ?? '', ENT_QUOTES); ?></div>
                        <?php if(!empty($_student['matched_at'])): ?>
                        <div class="edu-student-date"><?php echo date('d M Y', strtotime($_student['matched_at'])); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="edu-student-actions">
                        <a class="btn-view-profile" href="<?php echo $_page_base_url.'/account/posts?uid='.$_student['id']; ?>">View Profile</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.edu-student-list {
    padding: 30px 35px;
}
.edu-student-list-header {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 24px;
    border-bottom: 1px solid #ececec;
    padding-bottom: 16px;
}
.edu-student-list-header h2 {
    font-size: 20px;
    font-weight: 700;
    color: #012169;
    margin: 0;
}
.edu-student-list-count {
    font-size: 13px;
    color: #888;
    background: #f0f4ff;
    border-radius: 20px;
    padding: 2px 12px;
}
.edu-student-empty {
    text-align: center;
    padding: 60px 20px;
    color: #aaa;
}
.edu-student-empty-icon {
    font-size: 48px;
    margin-bottom: 14px;
}
.edu-student-empty-title {
    font-size: 18px;
    font-weight: 600;
    color: #555;
    margin-bottom: 8px;
}
.edu-student-empty-desc {
    font-size: 14px;
    color: #999;
    max-width: 420px;
    margin: 0 auto;
    line-height: 1.6;
}
.edu-student-rows {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.edu-student-row {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 14px 16px;
    border: 1px solid #e8edf5;
    border-radius: 10px;
    background: #fff;
}
.edu-student-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
    background: #f0f4ff;
}
.edu-student-avatar > div {
    width: 100%;
    height: 100%;
    background-size: cover;
    background-position: center;
}
.edu-student-avatar > img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.edu-student-info {
    flex: 1;
}
.edu-student-name {
    font-size: 15px;
    font-weight: 600;
    color: #1a1a2e;
}
.edu-student-date {
    font-size: 12px;
    color: #999;
    margin-top: 2px;
}
.edu-student-actions {
    flex-shrink: 0;
}
.btn-view-profile {
    display: inline-block;
    padding: 6px 16px;
    border: 1.5px solid #012169;
    border-radius: 6px;
    font-size: 13px;
    color: #012169;
    font-weight: 600;
    text-decoration: none;
}
.btn-view-profile:hover {
    background: #012169;
    color: #fff;
}
</style>
@endsection

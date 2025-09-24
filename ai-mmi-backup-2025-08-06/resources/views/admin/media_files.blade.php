@extends('admin.common')
@section('content')
<?php 
$search_keywords = (!empty($_page_get_data['keywords']))?$_page_get_data['keywords']:''; 
$media_files = (!empty($_page_data['media_files']))?$_page_data['media_files']:false;
?>
<div class="widget thin">
    <div class="filter">
        <form id="searchform" name="searchform" method="get">
            <input type="text" id="keywords" name="keywords" 
                   placeholder="<?php echo $_page_lang['enter_keywords']; ?>"
                   value="<?php echo $search_keywords; ?>">
            <?php if(!empty($_page_get_data['inline'])) { ?>
            <input type="hidden" name="inline" value="1">
            <?php } ?>
            <button type="submit" class="btn btn-green">
                <i class="fa fa-search"></i>
                <span><?php echo $_page_lang['search']; ?></span>
            </button>
        </form>
    </div>
    <div class="controls right">
        <?php if(!empty($_page_setting['can_delete']) && empty($_page_get_data['inline'])) { ?>
        <button type="button" id="btn-delete-files" class="btn btn-red">
            <i class="fa fa-trash"></i>
            <span><?php echo $_page_lang['delete']; ?></span>
        </button>
        <?php } ?>
        
        <?php if(!empty($_page_setting['can_add'])) { ?>
        <button type="button" id="btn-select-files" class="btn btn-yellow">
            <i class="fa fa-cloud-upload"></i>
            <span><?php echo $_page_lang['add']; ?></span>
        </button>
        <?php } ?>
    </div>
    <div class="clearboth"></div>
</div>

<div class="widget">
    <?php if(!empty($media_files['data'])) { ?>
    <div class="list">
        <?php foreach ($media_files['data'] as $data_key => $data) { $extension = pathinfo($data['file_path'], PATHINFO_EXTENSION); ?>
            <div class="block">
                <div class="widget">
                    <?php if(!empty($_page_setting['can_delete']) && empty($_page_get_data['inline'])) { ?>
                    <input type="checkbox" name="media_file_id[]" value="<?php echo $data['id']; ?>">
                    <?php } ?>
                    <?php
                    echo '<div class="photo">';
                        echo '<a class="link" href="'.url($data['file_path']).'" target="_blank">';
                        switch(strtolower($extension)) {
                            case 'pdf':
                                echo '<i class="fa fa-file-pdf-o" style="color:#ef4130;"></i>';
                                break;
                            case 'doc':
                            case 'docx':
                                echo '<i class="fa fa-file-word-o" style="color:#5091cd;"></i>';
                                break;
                            case 'xls':
                            case 'xlsx':
                                echo '<i class="fa fa-file-excel-o" style="color:#66cdaa;"></i>';
                                break;
                            case 'ppt':
                            case 'pptx':
                                echo '<i class="fa fa-file-powerpoint-o" style="color:#f7b002;"></i>';
                                break;
                            case 'txt':
                                echo '<i class="fa fa-file-text-o"></i>';
                                break;
                            case 'jpeg':
                            case 'jpg':
                            case 'gif':
                            case 'png':
                            case 'bmp':
                                echo '<img src="'.((!empty($data['file_thumbnail']))?$data['file_thumbnail']:'').'"/>';
                                break;
                            case 'avi':
                            case 'mov':
                            case 'mp4':
                            case 'ogg':
                            case 'wmv':
                            case 'webm':
                                echo '<i class="fa fa-file-video-o" style="color:#5091cd;"></i>';
                                break;
                            case 'mp3':
                            case 'ogg':
                            case 'wav':
                                echo '<i class="fa fa-file-audio-o" style="color:#66cdaa;"></i>';
                                break;
                            case 'rar':
                            case 'zip':
                                echo '<i class="fa fa-file-zip-o" style="color:#f7b002;"></i>';
                                break;
                            default:
                                echo '<i class="fa fa-file-code-o"></i>';    
                        }
                        echo '</a>';
                        if(!empty($data['is_image'])) {
                            echo '<a class="rotate" data-id="'.$data['id'].'"><i class="fa fa-undo"></i></a>';
                        }
                    echo '</div>';

                    echo '<div class="info">';
                        echo '<div class="name"><strong>'.$data['file_name'].'</strong> | '.$data['file_size_unit'].'</div>';
                        if(empty($_page_get_data['inline'])) {
                            echo '<div class="path">'.url($data['file_path']).'</div>';
                        }
                    echo '</div>';

                    echo '<div class="controls">';
                        if(!empty($_page_get_data['inline'])) {
                            echo '<button class="btn pick" type="button" data-url="'.$data['file_path'].'"><i class="fa fa-thumb-tack"></i></button>';
                        }
                        else {
                            echo '<a class="btn download" href="'.url($_mapping_data['module'].'/media_files/download/'.$data['id']).'" target="_blank"><i class="fa fa-cloud-download"></i></a>';
                        }
                        if(!empty($_page_setting['can_delete']) && empty($_page_get_data['inline'])) {
                            echo '<button class="btn delete" type="button" data-id="'.$data['id'].'"><i class="fa fa-trash"></i></button>';
                        }
                    echo '</div>';
                    ?>
                </div>
            </div>
        <?php } ?>
    </div>
    <?php } else { ?>
    <div class="empty"><?php echo $_page_lang['no_record_found']; ?></div>
    <?php } ?>
</div> 

<?php if(!empty($media_files['data'])) { ?>
<div class="widget thin">
    <?php
    if((int)$media_files['pagination']['total'] > 0) {
        $page_size = (int)$media_files['pagination']['page_size'];
        $total = (int)$media_files['pagination']['total'];
        $current_page = (int)$media_files['pagination']['current_page'];
        $total_page = (int)$media_files['pagination']['total_page'];
        $page_start = (max(0,(($current_page-1)*$page_size)+1));
        $page_end = $current_page * $page_size;
        if($page_end > $total) {
            $page_end = $total;
        }
    }
    ?>
    <div class="list-total"><?php echo str_replace(['{from}','{to}','{num}'], [$page_start, $page_end, $total], $_page_lang['show_total']); ?></div>
    <?php if((int)$total_page > 1) { ?>
    <div class="list-mypage" data-totalpage="<?php echo $total_page; ?>"></div>
    <?php } ?>
    <div class="clearboth"></div>
</div>
<?php } ?>

<?php if(!empty($_page_get_data['inline'])) { ?>
<style>
    main.page-body.full {
        padding: 20px!important;
    }
</style>
<?php } ?>
@endsection
@extends('admin.common')
@section('content')
<?php 
$parent_id = (!empty($_page_get_data['parent_id']))?(int)$_page_get_data['parent_id']:0;
$search_keywords = (!empty($_page_get_data['keywords']))?$_page_get_data['keywords']:'';
$sorting_val = (!empty($_page_get_data['sorting']))?$_page_get_data['sorting']:'';
$list_data = (!empty($_page_data['list']))?$_page_data['list']:false;

$extra_param = array_merge(['parent_id' => $parent_id], (!empty($_page_setting['extra_param']))?$_page_setting['extra_param']:[]);
$revised_extra_params = [];
if(!empty($extra_param)) { foreach ($extra_param as $param_key => $param) { if(!empty($param)) {
    $revised_extra_params[$param_key] = $param;
}}}
$extra_param = $revised_extra_params;

$show_advanced_search = false;
if(!empty($_page_setting['advanced_search']) && is_array($_page_setting['advanced_search'])) {
    foreach ($_page_setting['advanced_search'] as $advanced_search_key => $advanced_search_value) {
        if(!empty($_page_get_data[$advanced_search_value['name']])) {
            $show_advanced_search = true;
            break;
        }
    }
}
?>
<div class="t-list">
    <div class="widget thin top">
        <div class="filter">
            <form id="searchform" name="searchform" method="get" autocomplete="off">
                <?php if(!empty($extra_param)) { foreach ($extra_param as $param_key => $param) { if(!empty($param)) { ?>
                <input type="hidden" name="<?php echo $param_key; ?>" value="<?php echo $param; ?>">
                <?php }}} ?>
                <input type="text" id="keywords" name="keywords" 
                       placeholder="<?php echo $_page_lang['enter_keywords']; ?>"
                       value="<?php echo $search_keywords; ?>">
                <input type="hidden" id="sorting" name="sorting" value="<?php echo $sorting_val;?>">
                <button type="submit" class="btn btn-green">
                    <i class="fa fa-search"></i>
                    <span><?php echo $_page_lang['search']; ?></span>
                </button>
                <?php if(!empty($_page_setting['advanced_search'])) { ?>
                <button class="btn btn-green btn-show-advanced" type="button"><i class="fa fa-indent"></i></button>
                <?php } ?>
            </form>
        </div>
        <div class="controls right">
            <?php if(!empty($_page_setting['extra_action_link'])) {?>
            <div class="extra">
                <?php foreach ($_page_setting['extra_action_link'] as $action) { ?>
                <?php echo $action; ?>
                <?php } ?>
            </div>
            <?php } ?>
            
            <?php if(!empty($_page_setting['can_delete'])) { ?>
            <button type="button" class="btn btn-red btn-delete-item">
                <i class="fa fa-trash"></i>
                <span><?php echo $_page_lang['delete']; ?></span>
            </button>
            <?php } ?>
            
            <?php if(!empty($_page_setting['can_void'])) { ?>
            <button type="button" class="btn btn-red btn-void-item">
                <i class="fa fa-eraser"></i>
                <span><?php echo $_page_lang['void']; ?></span>
            </button>
            <?php } ?>
            
            <?php if(!empty($_page_setting['can_add'])) { ?>
            <a class="btn btn-yellow btn-add-item" href="<?php echo ($_page_setting['list_url'].'/add').((!empty($extra_param))?'?'.http_build_query($extra_param):''); ?>">
                <i class="fa fa-plus"></i>
                <span><?php echo $_page_lang['add']; ?></span>
            </a>
            <?php } ?>
        </div>
        <div class="clearboth"></div>
        
        <?php if(!empty($_page_setting['advanced_search']) && is_array($_page_setting['advanced_search'])) { ?>
        <div class="advanced-search"<?php echo (!empty($show_advanced_search))?' style="display:block"':' style="display:none"'; ?>>
            <form id="advanced-searchform" name="advanced-searchform" method="get" autocomplete="off">
                <div>
                    <?php if(!empty($extra_param)) { foreach ($extra_param as $param_key => $param) { if(!empty($param)) { ?>
                    <input type="hidden" name="<?php echo $param_key; ?>" value="<?php echo $param; ?>">
                    <?php }}} ?>
                    <input type="hidden" id="advanced_keywords" name="keywords" value="<?php echo $search_keywords; ?>">
                </div>
                <?php $w = 1; foreach ($_page_setting['advanced_search'] as $advanced_search_key => $advanced_search_value) { $advanced_search_value['type'] = (!empty($advanced_search_value['type']))?$advanced_search_value['type']:'text'; ?>
                <div class="row <?php echo 'row-'.($w);?>">
                    <label for="<?php echo $advanced_search_value['name'];?>">
                        <?php echo (!empty($advanced_search_value['alias']))?$advanced_search_value['alias']: ucwords(str_replace('_', ' ', $advanced_search_value['name']));?>
                    </label>

                    <?php if(in_array($advanced_search_value['type'],['select']) && !empty($advanced_search_value['options'])) {
                        $selected_value = (!empty($_page_get_data[$advanced_search_value['name']]))?$_page_get_data[$advanced_search_value['name']]:'';
                        if(!is_array($selected_value)) {
                           $selected_value = [$selected_value]; 
                        }
                        ?>
                    <select id="<?php echo $advanced_search_value['name'];?>" name="<?php echo $advanced_search_value['name'];?>">
                        <?php foreach ($advanced_search_value['options'] as $option_key => $option_value) { ?>
                        <option value="<?php echo $option_key;?>"<?php echo (in_array($option_key, $selected_value))?' selected':'';?>><?php echo $option_value; ?></option>
                        <?php } ?>
                    </select>
                    <?php } else if(in_array($advanced_search_value['type'],['select_multi']) && !empty($advanced_search_value['options'])) {
                        $selected_value = (!empty($_page_get_data[$advanced_search_value['name']]))?$_page_get_data[$advanced_search_value['name']]:'';
                        if(!is_array($selected_value)) {
                           $selected_value = [$selected_value]; 
                        }
                        ?>
                    <select id="<?php echo $advanced_search_value['name'];?>" name="<?php echo $advanced_search_value['name'];?>[]" multiple>
                        <?php foreach ($advanced_search_value['options'] as $option_key => $option_value) { ?>
                        <option value="<?php echo $option_key;?>"<?php echo (in_array($option_key, $selected_value))?' selected':'';?>><?php echo $option_value; ?></option>
                        <?php } ?>
                    </select>
                    <?php } else { ?>
                    <input type="text"<?php echo (($advanced_search_value['type']=='date')?' class="datepicker"':''); ?>id="<?php echo $advanced_search_value['name'];?>" name="<?php echo $advanced_search_value['name'];?>" value="<?php echo (!empty($_page_get_data[$advanced_search_value['name']]))?$_page_get_data[$advanced_search_value['name']]:'';?>">
                    <?php } ?>
                </div>
                <?php $w++; } ?>
                <div class="clearboth"></div>
                <input type="hidden" id="sorting" name="sorting" value="<?php echo $sorting_val;?>">
                <div style="padding-top:15px;text-align:right;border-top:2px dotted #e6e6e6;">
                    <button type="submit" class="btn btn-green btn-search" title="<?php echo $_page_lang['search']; ?>">
                        <i class="fa fa-search"></i>
                        <span><?php echo $_page_lang['search']; ?></span>
                    </button>
                </div>
            </form>
            <div class="clearboth"></div>
        </div>
        <?php } ?>
    </div>
    
    <?php if(!empty($_page_setting['customize'])) { foreach ($_page_setting['customize'] as $customize) { ?>
    <div class="widget middle">
        <div><?php echo $customize; ?></div>
    </div>
    <?php }} ?>
    
    <div class="widget middle">
        <?php if(!empty($list_data['data'])) { ?>
        <table class="list">
            <thead>
                <tr>
                    <?php if(!empty($_page_setting['can_delete'])) { ?>
                    <th class="index-col" style="width:40px;text-align:center;">
                        <input type="checkbox" id="select_all">
                    </th>
                    <?php } ?>
                    
                    <?php if(!empty($_page_setting['columns'])) { foreach ($_page_setting['columns'] as $col) { ?>
                    <th<?php echo (!empty($col['style']))?' style="'.$col['style'].'"':''; ?>>
                        <span><?php echo ucwords(strtolower((!empty($col['alias']))?$col['alias']:$col['name'])); ?></span>
                        <?php if($col['sortable'] && empty($_page_setting['can_seq'])) { ?>
                        <div class="sortable">
                            <div>
                                <a class="sorting asc<?php echo ($sorting_val == strtolower($col['name'].'_asc'))?' selected':'';?>" title="ASC" data-value="<?php echo strtolower($col['name'].'_asc');?>"><i class="fa fa-caret-up"></i></a>
                                <a class="sorting desc<?php echo ($sorting_val == strtolower($col['name'].'_desc'))?' selected':'';?>" title="DESC" data-value="<?php echo strtolower($col['name'].'_desc');?>"><i class="fa fa-caret-down"></i></a>
                            </div>
                        </div>
                        <?php } ?>
                    </th>
                    <?php }} ?>
                    
                    <?php if(!empty($_page_setting['can_disabled'])) { ?>
                    <th style="width:70px;text-align:center;"><?php echo $_page_lang['disabled']; ?></th>      
                    <?php } ?>
                    
                    <?php if(!empty($_page_setting['can_seq'])) { ?>
                    <th style="width:70px;text-align:center;"><?php echo $_page_lang['seq']; ?></th>      
                    <?php } ?>
                    
                    <th style="width:70px;text-align:center;"><?php echo $_page_lang['action']; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $list_offset = 1;
                foreach ($list_data['data'] as $list_key => $list) { 
                    $list_offset = ((max(0, ($list_data['pagination']['current_page'] - 1))*$list_data['pagination']['page_size']))+($list_key+1);
                    ?>
                <tr>
                    <?php if(!empty($_page_setting['can_delete'])) { ?>
                    <td class="index-col" style="width:40px;text-align:center;">
                        <input type="checkbox" class="list_index" name="list_index[]" value="<?php echo (!empty($list[$_page_setting['list_index']]))?$list[$_page_setting['list_index']]:0; ?>">
                    </td>
                    <?php } ?>
                    
                    <?php if(!empty($_page_setting['columns'])) { foreach ($_page_setting['columns'] as $col) { ?>
                    <td<?php echo (!empty($col['style']))?' style="'.$col['style'].'"':''; ?>>
                        <?php 
                        $next_url ='';
                        if(!empty($col['next'])) {
                            if(!empty($col['next_mapping']) && !empty($col['next_mapping'][((!empty($list[$_page_setting['list_index']]))?$list[$_page_setting['list_index']]:0)])) {
                                $next_url = $col['next_mapping'][((!empty($list[$_page_setting['list_index']]))?$list[$_page_setting['list_index']]:0)];
                            }
                            if(empty($next_url)) {
                                if(empty($col['max_level']) || (!empty($col['max_level']) && max(0, (int)$col['max_level']) > ((!empty($list['level']))?$list['level']:0))) {
                                    if(!is_bool($col['next'])) {
                                        $next_extra_param = ['ref_id' => ((!empty($list[$_page_setting['list_index']]))?$list[$_page_setting['list_index']]:0)];
                                        $next_url = $col['next'].((!empty($next_extra_param))?'?'.http_build_query($next_extra_param):'');
                                    }
                                    else{
                                        $next_extra_param = array_merge(['parent_id' => 0], $extra_param);
                                        $next_extra_param['parent_id'] = ((!empty($list[$_page_setting['list_index']]))?$list[$_page_setting['list_index']]:0);
                                        $next_url = url(implode('/', array_filter([$_mapping_data['module'], $_mapping_data['class'], (($_mapping_data['function']!='index')?$_mapping_data['function']:'')]))).((!empty($next_extra_param))?'?'.http_build_query($next_extra_param):'');
                                    }
                                }
                            }
                            else if($next_url == '#') {
                                $next_url = '';
                            }
                        }
                        ?>
                        
                        <?php if(!empty($next_url)) { ?>
                        <a href="<?php echo $next_url; ?>" class="next-link">
                        <?php } ?>
       
                        <span>
                            <?php if(!empty($col['options'])) { ?>
                            <?php echo (!empty($col['options'][$list[$col['name']]]))?$col['options'][$list[$col['name']]]:'-'; ?>
                            <?php } else {
                            if($col['type'] == 'date') { 
                                $list[$col['name']] = date(((!empty($col['dateformat']))?$col['dateformat']:'Y-m-d'), strtotime($list[$col['name']])); 
                            }
                            else if($col['type'] == 'number') {
                                $list[$col['name']] = number_format((double)$list[$col['name']], ((!empty($col['decimal']))?(int)$col['decimal']:0));
                            }
                            else if($col['type'] == 'color') { 
                                $list[$col['name']] = '<div style="background:'.$list[$col['name']].';display:inline-block;width:18px;height:18px;border-radius:2px;">&nbsp;</div>';
                            }
                            ?>
                            <?php echo (!empty($col['prefix']))?$col['prefix']:''; ?><?php echo nl2br($list[$col['name']]); ?>
                            <?php } ?>
                        </span>
                        
                        <?php if(!empty($next_url)) { ?>
                        <i class="fa fa-angle-double-right" style="color:#03b3b2;"></i></a>
                        <?php } ?>
                    </td>
                    <?php }} ?>
                    
                    <?php if(!empty($_page_setting['can_disabled'])) { ?>
                    <td style="width:80px;text-align:center;">
                        <a class="set-disabled" data-id="<?php echo (!empty($list[$_page_setting['list_index']]))?$list[$_page_setting['list_index']]:0; ?>">
                            <?php if(!empty($list['disabled'])) { ?>
                            <i class="fa fa-check-circle" style="font-size:18px;color:mediumaquamarine"></i>
                            <?php } else { ?>
                            <i class="fa fa-ban" style="font-size:18px;color:lightpink"></i>
                            <?php } ?>
                        </a>
                    </th>      
                    <?php } ?>
                    
                    <?php if(!empty($_page_setting['can_seq'])) { ?>
                    <td style="width:80px;text-align:center;">
                        <select class="seq_number" data-id="<?php echo (!empty($list[$_page_setting['list_index']]))?$list[$_page_setting['list_index']]:0; ?>" data-offet="<?php echo $list_offset; ?>">
                            <?php $start_num = 1; while ($start_num <= $list_data['pagination']['total']) { ?>
                            <option value="<?php echo $start_num; ?>"<?php echo ($list_offset == $start_num)?' selected':''; ?>><?php echo $start_num; ?></option>
                            <?php $start_num++; } ?>
                        </select>
                    </td>
                    <?php } ?>
                    
                    
                    <?php if(!empty($_page_setting['can_details'])) { ?>
                    <td style="width:70px;text-align:center;">
                        <a href="<?php echo ($_page_setting['list_url'].'/details/'.((!empty($list[$_page_setting['list_index']]))?$list[$_page_setting['list_index']]:0)).((!empty($extra_param))?'?'.http_build_query($extra_param):''); ?>">
                            <i class="fa fa-book" style="color:#5fac82"></i>
                        </a>
                    </td>
                    <?php } else if(!empty($_page_setting['can_edit'])) { ?>
                    <td style="width:70px;text-align:center;">
                        <a href="<?php echo ($_page_setting['list_url'].'/edit/'.((!empty($list[$_page_setting['list_index']]))?$list[$_page_setting['list_index']]:0)).((!empty($extra_param))?'?'.http_build_query($extra_param):''); ?>">
                            <i class="fa fa-pencil" style="color:#5fac82"></i>
                        </a>
                    </td>
                    <?php } ?>
                </tr>
                <?php } ?>
            </tbody>
        </table>
        <?php } else { ?>
        <div class="empty"><?php echo $_page_lang['no_record_found']; ?></div>
        <?php } ?>
    </div>

    <?php if(!empty($list_data['data'])) { ?>
    <div class="widget thin bottom">
        <?php
        if((int)$list_data['pagination']['total'] > 0) {
            $page_size = (int)$list_data['pagination']['page_size'];
            $total = (int)$list_data['pagination']['total'];
            $current_page = (int)$list_data['pagination']['current_page'];
            $total_page = (int)$list_data['pagination']['total_page'];
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
</div>
@endsection
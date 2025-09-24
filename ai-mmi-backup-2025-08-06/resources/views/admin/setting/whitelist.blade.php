@extends('admin.common')
@section('content')
<form id="whitelistform" name="whitelistform" method="post" >
    <div>@csrf</div>
    
    <div class="widget thin fixed-top">
        <div class="controls right">
            <button type="reset" class="btn btn-red">
                <i class="fa fa-undo"></i>
                <span><?php echo $_page_lang['reset']; ?></span>
            </button>
            <button type="submit" class="btn btn-green">
                <i class="fa fa-save"></i>
                <span><?php echo $_page_lang['save']; ?></span>
            </button>
        </div>
    </div>
    
    <div class="widget">
        <div class="widget-body">
            <div class="row">
                <div class="col-sm-12">
                    <div class="form-group">
                        <label for="ip_whitelist"><?php echo str_replace('{you_ip_address}', $_page_data['you_ip_address'], $_page_lang['whitelist_tips']); ?></label>
                        <textarea id="ip_whitelist" name="ip_whitelist" rows="10"><?php echo (!empty($_page_data['ip_whitelist']))?$_page_data['ip_whitelist']:'';?></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
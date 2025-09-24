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
                        <label for="recipient_application"><?php echo $_page_lang['recipient_application']; ?></label>
                        <textarea id="recipient_application" name="recipient_application" rows="10"><?php echo (!empty($_page_data['recipient_application']))?$_page_data['recipient_application']:'';?></textarea>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-sm-12">
                    <div class="form-group">
                        <label for="recipient_contact"><?php echo $_page_lang['recipient_contact']; ?></label>
                        <textarea id="recipient_contact" name="recipient_contact" rows="10"><?php echo (!empty($_page_data['recipient_contact']))?$_page_data['recipient_contact']:'';?></textarea>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-sm-12">
                    <div class="form-group">
                        <label for="recipient_autofill"><?php echo $_page_lang['recipient_autofill']; ?></label>
                        <textarea id="recipient_autofill" name="recipient_autofill" rows="10"><?php echo (!empty($_page_data['recipient_autofill']))?$_page_data['recipient_autofill']:'';?></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
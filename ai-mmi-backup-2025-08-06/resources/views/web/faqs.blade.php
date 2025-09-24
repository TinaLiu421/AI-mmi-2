@extends('web.common')
@section('content')
<div class="inner-panel">
    <h1 class="title"><?php echo $_page_lang['faqs']; ?></h1>
    <div class="underline"></div>
    <div class="clearboth"></div>
    
    <?php if(!empty($_page_data['details']['content'])) { ?>
    <div>&nbsp;</div>
    <div>&nbsp;</div>
    <div class="iweb-editor">
        <?php echo $_page_data['details']['content']; ?>
        <div class="clearboth"></div>
    </div>
    <?php } ?>
   
    <?php if(!empty($_page_data['list'])) { ?>
    <div class="list">
        <?php foreach ($_page_data['list'] as $key => $data) { ?>
        <div class="block">
            <a href="#">
                <h2 class="title"><?php echo $data['title']; ?></h2>
                <i class="fa fa-chevron-right"></i>
            </a>
            <div class="content">
                <div class="iweb-editor">
                    <?php echo $data['content']; ?>
                    <div class="clearboth"></div>
                </div>
            </div>
        </div>
        <?php } ?>
    </div>
    <?php } ?>
</div>
@endsection
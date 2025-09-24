@extends('web.common')
@section('content')
<div class="inner-panel">
    <h1 class="title"><?php echo $_page_data['details']['title']; ?></h1>
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
</div>
@endsection
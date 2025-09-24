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

    <?php if(!empty($_page_data['questions'])) { ?>
    <div class="questions form">
        <form id="questions-form" method="post">
            <div>@csrf</div>
            <?php foreach ($_page_data['questions'] as $questions_key => $questions) { ?>
            <?php if($questions_key > 1) { echo '<div class="next">'; }?>
            <div id="q<?php echo $questions_key; ?>" class="row">
                <label>
                    <?php echo str_pad($questions_key, 2, '0', STR_PAD_LEFT); ?>. <?php echo $questions['title']; ?>
                    <?php if(!empty($questions['remark'])) { ?>
                    <span class="tips"><small>(<?php echo $questions['remark']; ?>)</small></span>
                    <?php } ?>
                </label>
                
                <?php if(is_array($questions['answers'])) { ?>
                <div class="iweb-radiobox-set">
                    <?php foreach ($questions['answers'] as $answers_key => $answers) { ?>
                    <input type="radio" id="answers_<?php echo $questions_key; ?>_<?php echo $answers_key; ?>" name="answers[<?php echo $questions_key; ?>]" value="<?php echo $answers_key; ?>" data-validation="required">
                    <label for="answers_<?php echo $questions_key; ?>_<?php echo $answers_key; ?>"><?php echo $answers; ?></label>
                    <?php } ?>
                </div>
                <?php } else { ?>
                <input type="text" name="answers[<?php echo $questions_key; ?>]" data-validation="required"/>
                <?php } ?>
            </div>
            <?php if($questions_key > 1) { echo '</div>'; }?>
            <?php } ?>
            
            <div class="next">
                <div class="row">
                    <div class="row">
                        <label for="full_name"><?php echo $_page_lang['account.name']; ?></label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo ((!empty($_current_member['full_name']))?$_current_member['full_name']:'') ;?>" data-validation="required">
                    </div>
                    <div class="clearboth"></div>

                    <div class="row">
                        <label for="email"><?php echo $_page_lang['account.email']; ?></label>
                        <input type="text" id="email" name="email" placeholder="<?php echo $_page_lang['account.enter_email']; ?>" value="<?php echo ((!empty($_current_member['email']))?$_current_member['email']:'') ;?>" data-validation="required|email">
                    </div>
                    <div class="clearboth"></div>

                    <div class="row">
                        <label for="telephone"><?php echo $_page_lang['account.telephone']; ?></label>
                        <table class="telephone">
                            <tr>
                                <td><input type="text" id="telephone" name="telephone_code" placeholder="+852" value="+<?php echo preg_replace('/^(\+)(.*)/i', '$2', ((!empty($_current_member['telephone_code']))?$_current_member['telephone_code']:'+852')); ?>" data-validation="required"></td>
                                <td><input type="text" id="telephone" name="telephone_num" placeholder="<?php echo $_page_lang['account.enter_telephone']; ?>" value="<?php echo ((!empty($_current_member['telephone_num']))?$_current_member['telephone_num']:'') ;?>" data-validation="required"></td>
                            </tr>
                        </table>
                    </div>
                    <div class="clearboth"></div>
                </div>
            </div>
            
            <div class="next">
                <div class="row">
                    <button type="submit" class="btn"><?php echo $_page_lang['btn.submit']; ?></button>
                </div>
            </div>
        </form>
    </div>
    <?php } ?>
</div>
@endsection
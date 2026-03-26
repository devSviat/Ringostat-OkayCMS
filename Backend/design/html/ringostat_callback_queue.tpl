{* Черга передзвону: номери з пропущеними вхідними *}
{$meta_title = $btr->sviat__ringostat__callback_queue|escape scope=global}

<div class="main_header">
    <div class="main_header__item">
        <div class="main_header__inner">
            <div class="box_heading heading_page">
                {$btr->sviat__ringostat__callback_queue|escape} — {count($queue_items)}
            </div>
        </div>
    </div>
</div>

<div class="boxed">
    {if $queue_items}
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <form class="fn_form_list" method="post">
                    <input type="hidden" name="session_id" value="{$smarty.session.id}">
                    <div class="okay_list products_list fn_ringostat_callback_list">
                        <div class="okay_list_head hidden-sm-down">
                            <div class="okay_list_heading okay_list_check">
                                <input class="hidden_check fn_check_all" type="checkbox" id="check_all_1" name="" value="" />
                                <label class="okay_ckeckbox" for="check_all_1"></label>
                            </div>
                            <div class="okay_list_heading fn_cq_col_phone">{$btr->sviat__ringostat__callback_queue_phone|escape}</div>
                            <div class="okay_list_heading fn_cq_col_missed hidden-sm-down">{$btr->sviat__ringostat__callback_queue_missed_count|escape}</div>
                            <div class="okay_list_heading fn_cq_col_last hidden-sm-down">{$btr->sviat__ringostat__callback_queue_last_missed|escape}</div>
                            <div class="okay_list_heading">{$btr->sviat__ringostat__callback_queue_actions|escape}</div>
                        </div>
                        <div class="okay_list_body">
                            {foreach $queue_items as $item}
                                <div class="fn_row okay_list_body_item">
                                    <div class="okay_list_row">
                                        <div class="okay_list_boding okay_list_check">
                                            <input class="hidden_check" type="checkbox" id="id_{$item->id}" name="check[]" value="{$item->id}" />
                                            <label class="okay_ckeckbox" for="id_{$item->id}"></label>
                                        </div>
                                        <div class="okay_list_boding fn_ringostat_phone_cell">
                                            <span class="fn_ringostat_phone_line">
                                                {if $item->phone}<a href="tel:{$item->phone|escape}" class="fn_ringostat_tel">{$item->display_phone|escape}</a>{else}—{/if}
                                                {if isset($voicemail_by_phone[$item->phone]) && $voicemail_by_phone[$item->phone]}
                                                <span class="fn_ringostat_voicemail_icon hint-bottom-right-t-info-s-small-mobile hint-anim" data-hint="{$btr->sviat__ringostat__callback_queue_voicemail_record|escape}" title="{$btr->sviat__ringostat__callback_queue_voicemail_record|escape}"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-record-mail"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 12a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" /><path d="M14 12a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" /><path d="M7 15l10 0" /></svg></span>
                                                {/if}
                                            </span>
                                        </div>
                                        <div class="okay_list_boding fn_cq_col_missed hidden-sm-down">
                                            {$item->missed_count|escape}
                                        </div>
                                        <div class="okay_list_boding fn_cq_col_last hidden-sm-down">
                                            {if $item->last_missed_at}
                                                {$item->last_missed_at|date_format:"%d.%m.%Y %H:%M"}
                                            {else}
                                                —
                                            {/if}
                                        </div>
                                        <div class="okay_list_boding fn_cq_actions">
                                            <a href="{$ringostat_calls_base_url|escape}&caller={$item->phone|escape:'url'}" class="btn btn_small btn_blue hint-bottom-right-t-info-s-small-mobile hint-anim" data-hint="{$btr->sviat__ringostat__callback_queue_journal|escape}" target="_blank"><span class="fn_cq_btn_icon">{include file='svg_icon.tpl' svgId='order_list'}</span><span class="hidden-sm-down">{$btr->sviat__ringostat__callback_queue_journal|escape}</span></a>
                                            <button type="submit" name="single_action[{$item->id}]" value="mark_processed" class="btn btn_small btn_success fn_cq_mark_btn hint-bottom-right-t-info-s-small-mobile hint-anim" data-hint="{$btr->sviat__ringostat__callback_queue_mark_processed|escape}"><span class="fn_cq_btn_icon">{include file='svg_icon.tpl' svgId='checked'}</span><span class="hidden-sm-down">{$btr->sviat__ringostat__callback_queue_mark_processed|escape}</span></button>
                                        </div>
                                    </div>
                                </div>
                            {/foreach}
                        </div>
                        <div class="okay_list_footer fn_action_block">
                            <div class="okay_list_foot_left">
                                <div class="okay_list_heading okay_list_check">
                                    <input class="hidden_check fn_check_all" type="checkbox" id="check_all_2" name="" value="" />
                                    <label class="okay_ckeckbox" for="check_all_2"></label>
                                </div>
                                <div class="okay_list_option">
                                    <select name="action" class="selectpicker form-control">
                                        <option value="mark_processed">{$btr->sviat__ringostat__callback_queue_mark_processed|escape}</option>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="btn btn_small btn_blue">
                                {include file='svg_icon.tpl' svgId='checked'}
                                <span>{$btr->general_apply|escape}</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    {else}
        <div class="heading_box mt-1">
            <div class="text_grey">{$btr->sviat__ringostat__callback_queue_empty|escape}</div>
        </div>
    {/if}
</div>

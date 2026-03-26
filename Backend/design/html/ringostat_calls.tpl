{* Журнал дзвінків Ringostat *}
{$meta_title = $btr->sviat__ringostat__calls_journal|escape scope=global}

{* URL з фільтрами для сортування та пагінації *}
{capture name="ringostat_url_params"}date_from={$date_from|escape:'url'}&date_to={$date_to|escape:'url'}&caller={$caller_filter|escape:'url'}&status={$status_filter|escape:'url'}&direction={$direction_filter|escape:'url'}{/capture}
{$ringostat_url_params = $smarty.capture.ringostat_url_params}

<div class="main_header">
    <div class="main_header__item">
        <div class="main_header__inner">
            <div class="box_heading heading_page">
                {$btr->sviat__ringostat__calls_journal|escape} - {$calls_count}
            </div>
        </div>
    </div>
    <div class="main_header__item">
        <div class="main_header__inner">
            <form method="post" class="fn_ringostat_sync_form" action="{$root_url}/backend/index.php?controller=Sviat.Ringostat.RingostatCallsAdmin@syncCalls">
                <input type="hidden" name="session_id" value="{$smarty.session.id}">
                <input type="hidden" name="date_from" value="{$date_from|escape}">
                <input type="hidden" name="date_to" value="{$date_to|escape}">
                <div class="fn_ringostat_cb_wrap">
                    <input id="fn_ringostat_force_full" class="fn_ringostat_cb_input" name="force_full" type="checkbox" value="1">
                    <label for="fn_ringostat_force_full" class="fn_ringostat_cb_label">
                        <span class="fn_ringostat_cb_box"></span>
                        <span class="fn_ringostat_cb_text">{$btr->sviat__ringostat__force_full_sync|escape}</span>
                    </label>
                </div>
                <button type="submit" class="btn btn_small btn_success">{$btr->sviat__ringostat__load_from_ringostat|escape}</button>
            </form>
        </div>
    </div>
</div>

{if $message_success}
    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12">
            <div class="alert alert--center alert--icon alert--success">
                <div class="alert__content">
                    <div class="alert__title">
                        {if $message_success == 'sync_done'}
                            {$btr->sviat__ringostat__sync_done|escape}
                            {if $sync_imported !== null && $sync_updated !== null}
                                ({$btr->sviat__ringostat__sync_imported|escape}: {$sync_imported}, {$btr->sviat__ringostat__sync_updated|escape}: {$sync_updated})
                            {/if}
                        {/if}
                    </div>
                </div>
            </div>
        </div>
    </div>
{/if}
{if $message_error}
    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12">
            <div class="alert alert--center alert--icon alert--error">
                <div class="alert__content">
                    <div class="alert__title">{$message_error|escape}</div>
                </div>
            </div>
        </div>
    </div>
{/if}

<div class="boxed fn_toggle_wrap">
    {* Фільтри *}
    <div class="row">
        <div class="col-lg-12 col-md-12 ">
            <div class="fn_toggle_wrap">
                <div class="heading_box visible_md">
                    {$btr->general_filter|escape}
                    <div class="toggle_arrow_wrap fn_toggle_card text-primary">
                        <a class="btn-minimize" href="javascript:;"><i class="fa fn_icon_arrow fa-angle-down"></i></a>
                    </div>
                </div>
                <div class="boxed_sorting toggle_body_wrap off fn_card fn_ringostat_filter_row">
                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-12">
                            <form method="get" class="fn_form_list fn_ringostat_filters">
                                <input type="hidden" name="controller" value="Sviat.Ringostat.RingostatCallsAdmin">
                                <input type="hidden" name="sort" value="{$sort|escape}">
                                <div class="fn_ringostat_date_range">
                                    <div class="fn_filter_item">
                                        <input type="date" name="date_from" value="{$date_from|escape}" class="form-control" id="fn_ringostat_date_from">
                                    </div>
                                    <span class="fn_filter_sep">—</span>
                                    <div class="fn_filter_item">
                                        <input type="date" name="date_to" value="{$date_to|escape}" class="form-control" id="fn_ringostat_date_to">
                                    </div>
                                </div>
                                <div class="fn_filter_item">
                                    <input type="text" name="caller" value="{$caller_filter|escape}" class="form-control" placeholder="+380...">
                                </div>
                                <div class="fn_filter_item">
                                    <select name="status" class="selectpicker form-control" onchange="this.form.submit();">
                                        <option value="" {if !$status_filter}selected{/if}>{$btr->sviat__ringostat__all_statuses|escape}</option>
                                        <option value="PROPER" {if $status_filter == 'PROPER'}selected{/if}>{$btr->sviat__ringostat__status_proper|escape}</option>
                                        <option value="ANSWERED" {if $status_filter == 'ANSWERED'}selected{/if}>{$btr->sviat__ringostat__status_answered|escape}</option>
                                        <option value="VOICEMAIL" {if $status_filter == 'VOICEMAIL'}selected{/if}>{$btr->sviat__ringostat__status_voicemail|escape}</option>
                                        <option value="MISSED" {if $status_filter == 'MISSED'}selected{/if}>{$btr->sviat__ringostat__status_missed|escape}</option>
                                        <option value="NO ANSWER" {if $status_filter == 'NO ANSWER'}selected{/if}>{$btr->sviat__ringostat__status_no_answer|escape}</option>
                                        <option value="FAILED" {if $status_filter == 'FAILED'}selected{/if}>{$btr->sviat__ringostat__status_failed|escape}</option>
                                        <option value="REPEATED" {if $status_filter == 'REPEATED'}selected{/if}>{$btr->sviat__ringostat__status_repeated|escape}</option>
                                        <option value="BUSY" {if $status_filter == 'BUSY'}selected{/if}>{$btr->sviat__ringostat__status_busy|escape}</option>
                                    </select>
                                </div>
                                <div class="fn_filter_item">
                                    <select name="direction" class="selectpicker form-control" onchange="this.form.submit();">
                                        <option value="" {if !$direction_filter}selected{/if}>{$btr->sviat__ringostat__all_directions|escape}</option>
                                        <option value="in" {if $direction_filter == 'in'}selected{/if}>{$btr->sviat__ringostat__direction_in|escape}</option>
                                        <option value="out" {if $direction_filter == 'out'}selected{/if}>{$btr->sviat__ringostat__direction_out|escape}</option>
                                    </select>
                                </div>
                                <div class="fn_filter_item fn_filter_item_btn">
                                    <button type="submit" class="btn btn_blue">{$btr->sviat__ringostat__filter|escape}</button>
                                    <a href="{$ringostat_calls_base_url|escape}" class="btn btn_border_blue">{$btr->sviat__ringostat__reset_filter|escape}</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {if $calls}
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="calls_list okay_list products_list fn_sort_list">
                    <div class="okay_list_head hidden-sm-down">
                        <div class="okay_list_heading okay_list_calls_date">{$btr->sviat__ringostat__started_at|escape}</div>
                        <div class="okay_list_heading okay_list_calls_direction">{$btr->sviat__ringostat__call_type|escape}</div>
                        <div class="okay_list_heading okay_list_calls_status">{$btr->sviat__ringostat__status|escape}</div>
                        <div class="okay_list_heading okay_list_calls_caller">{$btr->sviat__ringostat__client|escape}</div>
                        <div class="okay_list_heading okay_list_calls_orders">{$btr->general_orders|escape}</div>
                        <div class="okay_list_heading okay_list_calls_callee hidden-lg-down">{$btr->sviat__ringostat__business_number|escape}</div>
                        <div class="okay_list_heading okay_list_calls_employee hidden-md-down">{$btr->sviat__ringostat__manager_department|escape}</div>
                        <div class="okay_list_heading okay_list_calls_waittime_duration hidden-sm-down">{$btr->sviat__ringostat__waittime|escape} / {$btr->sviat__ringostat__duration|escape}</div>
                        <div class="okay_list_heading okay_list_calls_record">{$btr->sviat__ringostat__record|escape}</div>
                    </div>
                    <div class="okay_list_body">
                        {foreach $calls as $call}
                            <div class="okay_list_body_item">
                                <div class="okay_list_row">
                                    <div class="okay_list_boding okay_list_calls_date" data-mobile-label="{$btr->sviat__ringostat__started_at|escape}:">
                                        {$call->started_at|date_format:"%d.%m.%Y %H:%M"}
                                    </div>
                                    <div class="okay_list_boding okay_list_calls_direction" data-mobile-label="{$btr->sviat__ringostat__call_type|escape}:">
                                        {if $call->direction == 'in'}
                                            <span
                                                class="direction-icon direction-icon_in hint-bottom-middle-t-info-s-small-mobile hint-anim"
                                                data-hint="{$btr->sviat__ringostat__direction_in|escape}">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path d="M5 4h4l2 5l-2.5 1.5a11 11 0 0 0 5 5l1.5 -2.5l5 2v4a2 2 0 0 1 -2 2a16 16 0 0 1 -15 -15a2 2 0 0 1 2 -2" />
                                                    <path d="M15 9l5 -5" />
                                                    <path d="M15 5l0 4l4 0" />
                                                </svg>
                                            </span>
                                        {else}
                                            <span
                                                class="direction-icon direction-icon_out hint-bottom-middle-t-info-s-small-mobile hint-anim"
                                                data-hint="{$btr->sviat__ringostat__direction_out|escape}">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path d="M5 4h4l2 5l-2.5 1.5a11 11 0 0 0 5 5l1.5 -2.5l5 2v4a2 2 0 0 1 -2 2c-8.072 -.49 -14.51 -6.928 -15 -15a2 2 0 0 1 2 -2" />
                                                    <path d="M20 4L15 9" />
                                                    <path d="M20 8L20 4L16 4" />
                                                </svg>
                                            </span>
                                        {/if}
                                    </div>
                                    <div class="okay_list_boding okay_list_calls_status fn_status_cell" data-mobile-label="{$btr->sviat__ringostat__status|escape}:">
                                        {if $call->status == 'PROPER'}
                                            <span class="tag tag-success fn_status_label"><span class="fn_status_icon fn_status_icon_success"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 4h4l2 5l-2.5 1.5a11 11 0 0 0 5 5l1.5 -2.5l5 2v4a2 2 0 0 1 -2 2a16 16 0 0 1 -15 -15a2 2 0 0 1 2 -2" /><path d="M15 6l2 2l4 -4" /></svg></span><span class="fn_status_text">{$btr->sviat__ringostat__status_proper|escape}</span></span>
                                        {elseif $call->status == 'ANSWERED'}
                                            <span class="tag tag-success fn_status_label"><span class="fn_status_icon fn_status_icon_success"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 4h4l2 5l-2.5 1.5a11 11 0 0 0 5 5l1.5 -2.5l5 2v4a2 2 0 0 1 -2 2a16 16 0 0 1 -15 -15a2 2 0 0 1 2 -2" /><path d="M15 6l2 2l4 -4" /></svg></span><span class="fn_status_text">{$btr->sviat__ringostat__status_answered|escape}</span></span>
                                        {elseif $call->status == 'VOICEMAIL'}
                                            <span class="tag tag-warning fn_status_label"><span class="fn_status_icon fn_status_icon_warning"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M21 8c-4 0 -7 2 -7 5a3 3 0 0 0 6 0c0 -3 -2.5 -5 -8 -5s-8 2 -8 5a3 3 0 0 0 6 0c0 -3 -3 -5 -7 -5" /></svg></span><span class="fn_status_text">{$btr->sviat__ringostat__status_voicemail|escape}</span></span>
                                        {elseif $call->status == 'NO ANSWER' && $call->direction == 'in'}
                                            <span class="tag tag-danger fn_status_label"><span class="fn_status_icon fn_status_icon_danger"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 4h4l2 5l-2.5 1.5a11 11 0 0 0 5 5l1.5 -2.5l5 2v4a2 2 0 0 1 -2 2c-8.072 -.49 -14.51 -6.928 -15 -15a2 2 0 0 1 2 -2" /><path d="M17 3l4 4" /><path d="M21 3l-4 4" /></svg></span><span class="fn_status_text">{$btr->sviat__ringostat__status_missed|escape}</span></span>
                                        {elseif $call->status == 'NO ANSWER' && $call->direction == 'out'}
                                            <span class="tag tag-default fn_status_label"><span class="fn_status_icon fn_status_icon_default"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 4h4l2 5l-2.5 1.5a11 11 0 0 0 5 5l1.5 -2.5l5 2v4a2 2 0 0 1 -2 2c-8.072 -.49 -14.51 -6.928 -15 -15a2 2 0 0 1 2 -2" /><path d="M17 3l4 4" /><path d="M21 3l-4 4" /></svg></span><span class="fn_status_text">{$btr->sviat__ringostat__status_no_answer|escape}</span></span>
                                        {elseif $call->status == 'FAILED'}
                                            <span class="tag tag-default fn_status_label"><span class="fn_status_icon fn_status_icon_default"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 4h4l2 5l-2.5 1.5a11 11 0 0 0 5 5l1.5 -2.5l5 2v4a2 2 0 0 1 -2 2a16 16 0 0 1 -15 -15a2 2 0 0 1 2 -2" /><path d="M16 4l4 4m0 -4l-4 4" /></svg></span><span class="fn_status_text">{$btr->sviat__ringostat__status_failed|escape}</span></span>
                                        {elseif $call->status == 'CALLING'}
                                            <span class="tag tag-info fn_status_label"><span class="fn_status_icon fn_status_icon_info"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 4h4l2 5l-2.5 1.5a11 11 0 0 0 5 5l1.5 -2.5l5 2v4a2 2 0 0 1 -2 2a16 16 0 0 1 -15 -15a2 2 0 0 1 2 -2" /><path d="M15 7l0 .01" /><path d="M18 7l0 .01" /><path d="M21 7l0 .01" /></svg></span><span class="fn_status_text">{$btr->sviat__ringostat__status_calling|escape}</span></span>
                                        {elseif $call->status == 'IN PROGRESS'}
                                            <span class="tag tag-info fn_status_label"><span class="fn_status_icon fn_status_icon_info"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 4h4l2 5l-2.5 1.5a11 11 0 0 0 5 5l1.5 -2.5l5 2v4a2 2 0 0 1 -2 2a16 16 0 0 1 -15 -15a2 2 0 0 1 2 -2" /><path d="M15 7a2 2 0 0 1 2 2" /><path d="M15 3a6 6 0 0 1 6 6" /></svg></span><span class="fn_status_text">{$btr->sviat__ringostat__status_in_progress|escape}</span></span>
                                        {elseif $call->status == 'REPEATED'}
                                            <span class="tag tag-warning fn_status_label"><span class="fn_status_icon fn_status_icon_warning"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4H9L11 9L8.5 10.5C9.57096 12.6715 11.3285 14.429 13.5 15.5L15 13L20 15V19C20 19.5304 19.7893 20.0391 19.4142 20.4142C19.0391 20.7893 18.5304 21 18 21C14.0993 20.763 10.4202 19.1065 7.65683 16.3432C4.8935 13.5798 3.23705 9.90074 3 6C3 5.46957 3.21071 4.96086 3.58579 4.58579C3.96086 4.21071 4.46957 4 5 4Z" /><path d="M19.3711 8.86713C19.0308 9.29335 18.5803 9.61929 18.068 9.81C17.5558 10.0007 17.001 10.049 16.4632 9.94968C15.9255 9.85036 15.425 9.60718 15.0155 9.24623C14.6061 8.88527 14.303 8.42016 14.1389 7.90077C13.9747 7.38137 13.9557 6.8273 14.0838 6.29796C14.212 5.76862 14.4824 5.28399 14.8662 4.89605C15.25 4.5081 15.7326 4.23147 16.2623 4.09583C17.7306 3.72091 19.2506 4.47338 19.8117 5.87558" /><path d="M20 4V6H18" /></svg></span><span class="fn_status_text">{$btr->sviat__ringostat__status_repeated|escape}</span></span>
                                        {elseif $call->status == 'BUSY'}
                                            <span class="tag tag-default fn_status_label"><span class="fn_status_icon fn_status_icon_default"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 4h4l2 5l-2.5 1.5a11 11 0 0 0 5 5l1.5 -2.5l5 2v4a2 2 0 0 1 -2 2c-8.072 -.49 -14.51 -6.928 -15 -15a2 2 0 0 1 2 -2" /><path d="M17 3v5" /><path d="M21 3v5" /></svg></span><span class="fn_status_text">{$btr->sviat__ringostat__status_busy|escape}</span></span>
                                        {else}
                                            <span class="tag tag-default fn_status_label"><span class="fn_status_icon fn_status_icon_default"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 4h4l2 5l-2.5 1.5a11 11 0 0 0 5 5l1.5 -2.5l5 2v4a2 2 0 0 1 -2 2a16 16 0 0 1 -15 -15a2 2 0 0 1 2 -2" /><path d="M16 4l4 4m0 -4l-4 4" /></svg></span><span class="fn_status_text">{$call->status|escape}</span></span>
                                        {/if}
                                    </div>
                                    <div class="okay_list_boding okay_list_calls_caller" data-mobile-label="{$btr->sviat__ringostat__client|escape}:">
                                        {if $call->is_phone_caller}
                                            <a href="tel:{$call->caller_tel|escape}" class="fn_ringostat_tel fn_ringostat_no_row_click">{$call->display_caller|escape}</a>
                                        {else}
                                            {$call->display_caller|escape}
                                        {/if}
                                    </div>
                                    <div class="okay_list_boding okay_list_calls_orders" data-mobile-label="{$btr->general_orders|escape}:">
                                        {if $call->orders|count > 0}
                                            <div class="fn_ringostat_orders_dropdown fn_ringostat_no_row_click">
                                                <div class="fn_ringostat_orders_inner">
                                                    <span class="fn_ringostat_orders_icon fn_ringostat_orders_trigger" title="{$btr->general_orders|escape}">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-shopping-bag"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6.331 8h11.339a2 2 0 0 1 1.977 2.304l-1.255 8.152a3 3 0 0 1 -2.966 2.544h-6.852a3 3 0 0 1 -2.965 -2.544l-1.255 -8.152a2 2 0 0 1 1.977 -2.304" /><path d="M9 11v-5a3 3 0 0 1 6 0v5" /></svg>
                                                    </span>
                                                    <div class="fn_ringostat_orders_toggle">
                                                        {foreach $call->orders as $ord}
                                                            <div class="notif_item">
                                                                <a href="{$root_url}/backend/index.php?controller=OrderAdmin&amp;id={$ord->id}" target="_blank" rel="noopener" class="l_notif">
                                                                    <span class="notif_title">{$btr->general_order_number|escape}{$ord->id}</span>
                                                                </a>
                                                            </div>
                                                        {/foreach}
                                                    </div>
                                                </div>
                                            </div>
                                        {else}
                                            —
                                        {/if}
                                    </div>
                                    <div class="okay_list_boding okay_list_calls_callee hidden-lg-down">
                                        {if $call->is_phone_callee}
                                            <a href="tel:{$call->callee_tel|escape}" class="fn_ringostat_tel fn_ringostat_no_row_click">{$call->display_callee|escape}</a>
                                        {else}
                                            {$call->display_callee|escape}
                                        {/if}
                                    </div>
                                    <div class="okay_list_boding okay_list_calls_employee hidden-md-down">
                                        {if $call->employee_fio}
                                            <span class="calls_employee_fio">{$call->employee_fio|escape}</span>
                                        {/if}
                                        {if $call->department}
                                            <span class="calls_department">{$call->department|escape}</span>
                                        {/if}
                                        {if !$call->employee_fio && !$call->department}
                                            —
                                        {/if}
                                    </div>
                                    <div class="okay_list_boding okay_list_calls_waittime_duration" data-mobile-label="{$btr->sviat__ringostat__waittime|escape} / {$btr->sviat__ringostat__duration|escape}:">
                                        {if $call->waittime !== null && $call->waittime !== ''}{$call->waittime|escape} с{else}—{/if} / {if $call->duration}{$call->duration|escape} с{else}—{/if}
                                    </div>
                                    <div class="okay_list_boding okay_list_calls_record" data-mobile-label="{$btr->sviat__ringostat__record|escape}:">
                                        {if $call->record_url}
                                            <button type="button" class="btn_close fn_open_record_modal fn_ringostat_no_row_click hint-bottom-right-t-info-s-small-mobile hint-anim" data-hint="{$btr->sviat__ringostat__listen|escape}" data-record-href="{$root_url}/backend/index.php?controller=Sviat.Ringostat.RingostatCallsAdmin@record&amp;id={$call->id}">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 4v16l13 -8l-13 -8" /></svg>
                                            </button>
                                        {else}
                                            —
                                        {/if}
                                    </div>
                                </div>
                            </div>
                        {/foreach}
                    </div>
                </div>
            </div>
            <div class="col-lg-12 col-md-12 col-sm-12 txt_center mt-1">
                {if $pages_count > 1}
                    {* Пагінація *}
                    {$visible_pages = 5}
                    {$page_from = 1}
                    {if $current_page > floor($visible_pages/2)}
                        {$page_from = max(1, $current_page - floor($visible_pages/2) - 1)}
                    {/if}
                    {if $current_page > $pages_count - ceil($visible_pages/2)}
                        {$page_from = max(1, $pages_count - $visible_pages - 1)}
                    {/if}
                    {$page_to = min($page_from + $visible_pages, $pages_count - 1)}
                    <ul class="pagination fn_pagination">
                        {if $current_page > 1}
                            <li class="page-item">
                                <a href="{$ringostat_calls_base_url|escape}&{$ringostat_url_params}&sort={$sort|escape:'url'}&page={$current_page - 1}">&lt;</a>
                            </li>
                        {/if}
                        <li class="page-item {if $current_page == 1}active{/if}">
                            <a class="page-link {if $current_page == 1}selected{else}droppable{/if}" href="{$ringostat_calls_base_url|escape}&{$ringostat_url_params}&sort={$sort|escape:'url'}&page=1">1</a>
                        </li>
                        {section name=pages loop=$page_to start=$page_from}
                            {$p = $smarty.section.pages.index + 1}
                            <li class="page-item {if $p == $current_page}active{/if}">
                                {if ($p == $page_from + 1 && $p != 2) || ($p == $page_to && $p != $pages_count - 1)}
                                    <a class="page-link" href="{$ringostat_calls_base_url|escape}&{$ringostat_url_params}&sort={$sort|escape:'url'}&page={$p}">...</a>
                                {else}
                                    <a class="{if $p != $current_page}droppable{/if}" href="{$ringostat_calls_base_url|escape}&{$ringostat_url_params}&sort={$sort|escape:'url'}&page={$p}">{$p}</a>
                                {/if}
                            </li>
                        {/section}
                        <li class="page-item {if $current_page == $pages_count}active{/if}">
                            <a class="{if $current_page != $pages_count}droppable{/if}" href="{$ringostat_calls_base_url|escape}&{$ringostat_url_params}&sort={$sort|escape:'url'}&page={$pages_count}">{$pages_count}</a>
                        </li>
                        {if $current_page < $pages_count}
                            <li class="page-item">
                                <a href="{$ringostat_calls_base_url|escape}&{$ringostat_url_params}&sort={$sort|escape:'url'}&page={$current_page + 1}">&gt;</a>
                            </li>
                        {/if}
                        <li class="page-item">
                            <a href="{$ringostat_calls_base_url|escape}&{$ringostat_url_params}&sort={$sort|escape:'url'}&page=all">{$btr->pagination_show_all|escape}</a>
                        </li>
                    </ul>
                {/if}
            </div>
        </div>
    {else}
        <div class="heading_box mt-1">
            <div class="text_grey">{$btr->sviat__ringostat__no_calls|escape}</div>
        </div>
    {/if}
</div>

{* Модалка плеєра запису *}
<div id="fn_ringostat_record_modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="card-header">
                <div class="heading_modal">{$btr->sviat__ringostat__listen|escape}</div>
                <button type="button" class="btn_close fn_close_record_modal" data-dismiss="modal" aria-label="Close">
                    {include file='svg_icon.tpl' svgId='delete'}
                </button>
            </div>
            <div class="modal-body text_center">
                <div id="fn_ringostat_waveform" class="ringostat-waveform"></div>
                <div class="ringostat-waveform-controls">
                    <div class="ringostat-controls-left">
                        <span class="ringostat-time fn_ringostat_time_display">0:00 / 0:00</span>
                    </div>
                    <div class="ringostat-controls-center">
                        <button type="button" class="btn btn_blue ringostat-waveform-btn fn_ringostat_play_pause" id="fn_ringostat_play_pause_btn">
                            <span class="ringostat-icon ringostat-icon_play">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-player-play"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 4v16l13 -8l-13 -8" /></svg>
                            </span>
                            <span class="ringostat-icon ringostat-icon_pause">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-player-pause"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 6a1 1 0 0 1 1 -1h2a1 1 0 0 1 1 1v12a1 1 0 0 1 -1 1h-2a1 1 0 0 1 -1 -1l0 -12" /><path d="M14 6a1 1 0 0 1 1 -1h2a1 1 0 0 1 1 1v12a1 1 0 0 1 -1 1h-2a1 1 0 0 1 -1 -1l0 -12" /></svg>
                            </span>
                        </button>
                    </div>
                    <div class="ringostat-controls-right">
                        <div class="ringostat-volume-group">
                            <input type="range" min="0" max="100" value="100" class="ringostat-volume-slider fn_ringostat_volume">
                            <button type="button" class="ringostat-volume-mute fn_ringostat_mute" aria-label="Mute / Unmute">
                                <span class="ringostat-icon ringostat-icon_volume">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-volume"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 8a5 5 0 0 1 0 8" /><path d="M17.7 5a9 9 0 0 1 0 14" /><path d="M6 15h-2a1 1 0 0 1 -1 -1v-4a1 1 0 0 1 1 -1h2l3.5 -4.5a.8 .8 0 0 1 1.5 .5v14a.8 .8 0 0 1 -1.5 .5l-3.5 -4.5" /></svg>
                                </span>
                                <span class="ringostat-icon ringostat-icon_volume_off">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-volume-off"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 8a5 5 0 0 1 1.912 4.934m-1.377 2.602a5 5 0 0 1 -.535 .464" /><path d="M17.7 5a9 9 0 0 1 2.362 11.086m-1.676 2.299a9 9 0 0 1 -.686 .615" /><path d="M9.069 5.054l.431 -.554a.8 .8 0 0 1 1.5 .5v2m0 4v8a.8 .8 0 0 1 -1.5 .5l-3.5 -4.5h-2a1 1 0 0 1 -1 -1v-4a1 1 0 0 1 1 -1h2l1.294 -1.664" /><path d="M3 3l18 18" /></svg>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{$root_url}/Okay/Modules/Sviat/Ringostat/Backend/design/js/wavesurfer-7.12.1.min.js"></script>

<script>
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        var $ = jQuery;
        var $recordModal = $('#fn_ringostat_record_modal');
        if (!$recordModal.length) return;

        var $playPauseBtn = $recordModal.find('.fn_ringostat_play_pause');
        var $timeDisplay = $recordModal.find('.fn_ringostat_time_display');
        var $volumeSlider = $recordModal.find('.fn_ringostat_volume');
        var $muteBtn = $recordModal.find('.fn_ringostat_mute');
        var wavesurfer = null;
        var currentSrc = null;
        var lastVolume = 1;

        function formatTime(seconds) {
            if (!isFinite(seconds) || isNaN(seconds)) return '0:00';
            var m = Math.floor(seconds / 60);
            var s = Math.floor(seconds % 60);
            return m + ':' + (s < 10 ? '0' : '') + s;
        }

        function initWaveSurfer() {
            if (!window.WaveSurfer) return null;
            var container = document.getElementById('fn_ringostat_waveform');
            if (!container) return null;

            var ws = WaveSurfer.create({
                container: container,
                waveColor: '#bcd6ff',
                progressColor: '#1e88e5',
                barWidth: 2,
                barGap: 1,
                barRadius: 1,
                height: 64,
                responsive: true,
                normalize: true,
                interaction: true,
                backend: 'MediaElement'
            });

            var $playIcon = $playPauseBtn.find('.ringostat-icon_play');
            var $pauseIcon = $playPauseBtn.find('.ringostat-icon_pause');
            $playIcon.show();
            $pauseIcon.hide();

            ws.on('play', function() { $playIcon.hide(); $pauseIcon.show(); });
            ws.on('pause', function() { $pauseIcon.hide(); $playIcon.show(); });
            ws.on('finish', function() {
                ws.stop();
                $pauseIcon.hide();
                $playIcon.show();
            });

            if (ws.setVolume) ws.setVolume(1);

            if ($volumeSlider.length) {
                $volumeSlider.off('input change').on('input change', function() {
                    var value = parseInt(this.value, 10);
                    if (isNaN(value)) value = 100;
                    var vol = Math.min(1, Math.max(0, value / 100));
                    if (ws.setVolume) ws.setVolume(vol);
                    if (vol > 0) {
                        lastVolume = vol;
                        $muteBtn.removeClass('ringostat-muted');
                    } else {
                        $muteBtn.addClass('ringostat-muted');
                    }
                });
            }

            if ($muteBtn.length) {
                $muteBtn.off('click').on('click', function() {
                    var currentVol = ws.getVolume ? ws.getVolume() : lastVolume;
                    if (currentVol > 0.001) {
                        lastVolume = currentVol;
                        if (ws.setVolume) ws.setVolume(0);
                        if ($volumeSlider.length) $volumeSlider.val(0);
                        $muteBtn.addClass('ringostat-muted');
                    } else {
                        var restore = lastVolume > 0 ? lastVolume : 1;
                        if (ws.setVolume) ws.setVolume(restore);
                        if ($volumeSlider.length) $volumeSlider.val(Math.round(restore * 100));
                        $muteBtn.removeClass('ringostat-muted');
                    }
                });
            }

            ws.on('audioprocess', function() {
                var current = ws.getCurrentTime();
                var duration = ws.getDuration();
                $timeDisplay.text(formatTime(current) + ' / ' + formatTime(duration));
            });
            ws.on('ready', function() {
                $timeDisplay.text('0:00 / ' + formatTime(ws.getDuration()));
            });
            $playPauseBtn.off('click').on('click', function() { ws.playPause(); });

            return ws;
        }

        function resetPlayerUI() {
            $timeDisplay.text('0:00 / 0:00');
            var $playIcon = $playPauseBtn.find('.ringostat-icon_play');
            var $pauseIcon = $playPauseBtn.find('.ringostat-icon_pause');
            $playIcon.show();
            $pauseIcon.hide();
            if ($volumeSlider.length) $volumeSlider.val(100);
            if ($muteBtn.length) $muteBtn.removeClass('ringostat-muted');
        }

        $(document).on('click', '.fn_open_record_modal', function(e) {
            e.preventDefault();
            var href = $(this).attr('data-record-href');
            if (!href) return;
            currentSrc = href.replace(/&amp;/g, '&');
            resetPlayerUI();
            $recordModal.modal('show');
            if (!wavesurfer) {
                wavesurfer = initWaveSurfer();
            }
            if (wavesurfer) {
                wavesurfer.once('ready', function() { wavesurfer.play(); });
                wavesurfer.load(currentSrc);
            } else {
                $timeDisplay.text('—');
            }
        });

        $recordModal.on('hidden.bs.modal', function() {
            if (wavesurfer) {
                try { wavesurfer.stop(); wavesurfer.destroy(); } catch (err) {}
                wavesurfer = null;
            }
            resetPlayerUI();
            currentSrc = null;
        });
    });
})();
</script>

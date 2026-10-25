{* Налаштування Ringostat *}
{$meta_title = $btr->sviat__ringostat__title|escape scope=global}

<div class="main_header">
    <div class="main_header__item">
        <div class="main_header__inner">
            <div class="box_heading heading_page">{$btr->sviat__ringostat__title|escape}</div>
        </div>
    </div>
    <div class="main_header__item">
        <div class="main_header__inner">
            <a href="{$root_url}/backend/index.php?controller=Sviat.Ringostat.RingostatCallsAdmin"
                class="btn btn_small btn_blue">{$btr->sviat__ringostat__calls_journal|escape}</a>
        </div>
    </div>
</div>

{if $message_success}
    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12">
            <div class="alert alert--center alert--icon alert--success">
                <div class="alert__content">
                    <div class="alert__title">
                        {if $message_success == 'saved'}{$btr->sviat__ringostat__saved|escape}{/if}
                    </div>
                </div>
            </div>
        </div>
    </div>
{/if}

<div id="ringostat_test_result" class="row" style="display: none;">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <div class="alert alert--center alert--icon" id="ringostat_test_alert">
            <div class="alert__content">
                <div class="alert__title" id="ringostat_test_text"></div>
            </div>
        </div>
    </div>
</div>

<div class="boxed fn_toggle_wrap">
    <form method="post" class="fn_form_list" id="ringostat_settings_form"
        data-msg-ok="{$btr->sviat__ringostat__test_ok|escape:'html'}"
        data-msg-failed="{$btr->sviat__ringostat__test_failed|escape:'html'}"
        data-msg-network="{$btr->sviat__ringostat__test_network_error|escape:'html'}">
        <input type="hidden" name="session_id" value="{$smarty.session.id}">

        <div class="row">
            <div class="col-lg-12 col-md-12">
                <div class="alert alert--icon">
                    <div class="alert__content">
                        <div class="alert__title">{$btr->sviat__ringostat__description_title|escape}</div>
                        <p>{$btr->sviat__ringostat__description|escape}</p>
                        <p><a href="https://help.ringostat.com/uk/collections/106988-api" target="_blank"
                                rel="noopener">{$btr->sviat__ringostat__doc_link|escape}</a></p>
                    </div>
                </div>

                <div class="col-lg-6 col-md-6 boxed" id="ringostat_settings_form_block">
                    <div class="alert__title mb-1">{$btr->sviat__ringostat__api_credentials|escape}</div>
                    <p class="text_small mb-1">{$btr->sviat__ringostat__api_credentials_help|escape}</p>
                    <div class="mb-1">
                        <label class="form_label heading_label">Auth-key:</label>
                        <input type="text" class="form-control" name="sviat__ringostat__auth_key"
                            value="{$auth_key|escape}"
                            placeholder="{$btr->sviat__ringostat__auth_key_placeholder|escape}" autocomplete="off">
                    </div>
                    <div class="mb-1">
                        <label class="form_label heading_label">Project id:</label>
                        <input type="text" class="form-control" name="sviat__ringostat__project_id"
                            value="{$project_id|escape}"
                            placeholder="{$btr->sviat__ringostat__project_id_placeholder|escape}">
                    </div>
                    <div class="activity_of_switch_item mb-1">
                        <div class="okay_switch okay_switch--nowrap clearfix">
                            <label class="switch switch-default mr-1">
                                <input class="switch-input" name="sviat__ringostat__sync_on_order" value="1"
                                    type="checkbox" {if $sync_on_order} checked{/if}>
                                <span class="switch-label"></span>
                                <span class="switch-handle"></span>
                            </label>
                            <label class="switch_label">{$btr->sviat__ringostat__sync_on_order|escape}</label>
                        </div>
                    </div>
                    <div class="activity_of_switch_item mb-1">
                        <div class="okay_switch okay_switch--nowrap clearfix">
                            <label class="switch switch-default mr-1">
                                <input class="switch-input" name="sviat__ringostat__sync_contacts_enabled" value="1"
                                    type="checkbox" {if $sync_contacts_enabled} checked{/if}>
                                <span class="switch-label"></span>
                                <span class="switch-handle"></span>
                            </label>
                            <label class="switch_label">{$btr->sviat__ringostat__sync_contacts_enabled|escape}</label>
                        </div>
                    </div>
                    <div class="activity_of_switch_item mb-1">
                        <div class="okay_switch okay_switch--nowrap clearfix">
                            <label class="switch switch-default mr-1">
                                <input class="switch-input" name="sviat__ringostat__show_order_info_in_phone" value="1"
                                    type="checkbox" {if $show_order_info_in_phone} checked{/if}>
                                <span class="switch-label"></span>
                                <span class="switch-handle"></span>
                            </label>
                            <label
                                class="switch_label">{$btr->sviat__ringostat__show_order_info_in_phone|escape}</label>
                        </div>
                    </div>
                    <div class="mb-1">
                        <div class="heading_label">{$btr->sviat__ringostat__contact_name_format|escape}</div>
                        <select name="sviat__ringostat__contact_name_format" class="form-control selectpicker">
                            <option value="name_lastname" {if $contact_name_format == 'name_lastname'}selected{/if}>
                                {$btr->sviat__ringostat__contact_name_format_name_lastname|escape}</option>
                            <option value="name_order_id" {if $contact_name_format == 'name_order_id'}selected{/if}>
                                {$btr->sviat__ringostat__contact_name_format_name_order_id|escape}</option>
                            <option value="order_id" {if $contact_name_format == 'order_id'}selected{/if}>
                                {$btr->sviat__ringostat__contact_name_format_order_id|escape}</option>
                        </select>
                    </div>
                    <div class="col-lg-12 col-md-12">
                        <div class="mt-1">
                            <button type="submit" name="save" value="1"
                                class="btn btn_small btn_success">{$btr->sviat__ringostat__save|escape}</button>
                            <button type="button" id="ringostat_test_btn"
                                class="btn btn_small btn_blue">{$btr->sviat__ringostat__test_connection|escape}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var testBtn = document.getElementById('ringostat_test_btn');
        var resultBlock = document.getElementById('ringostat_test_result');
        var alertBox = document.getElementById('ringostat_test_alert');
        var textEl = document.getElementById('ringostat_test_text');
        var form = document.getElementById('ringostat_settings_form');
        if (!testBtn || !form) return;

        var msgOk = form.getAttribute('data-msg-ok') || '';
        var msgFailed = form.getAttribute('data-msg-failed') || '';
        var msgNetwork = form.getAttribute('data-msg-network') || '';

        testBtn.addEventListener('click', function() {
            var authKey = (form.querySelector('input[name="sviat__ringostat__auth_key"]') || {})
                .value || '';
            var projectId = (form.querySelector('input[name="sviat__ringostat__project_id"]') || {})
                .value || '';
            var sessionId = (form.querySelector('input[name="session_id"]') || {}).value || '';
            var url = '{$root_url}/backend/index.php?controller=Sviat.Ringostat.RingostatAdmin@testConnection';

            testBtn.disabled = true;
            resultBlock.style.display = 'none';

            var body = new FormData();
            body.append('auth_key', authKey);
            body.append('project_id', projectId);
            body.append('session_id', sessionId);

            fetch(url, { method: 'POST', body: body, credentials: 'same-origin' })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    resultBlock.style.display = 'block';
                    alertBox.classList.remove('alert--success', 'alert--error');
                    if (data.success) {
                        alertBox.classList.add('alert--success');
                        textEl.textContent = msgOk;
                    } else {
                        alertBox.classList.add('alert--error');
                        textEl.textContent = msgFailed + (data.error ? ': ' + data.error : '');
                    }
                })
                .catch(function() {
                    resultBlock.style.display = 'block';
                    alertBox.classList.remove('alert--success');
                    alertBox.classList.add('alert--error');
                    textEl.textContent = msgFailed + ': ' + msgNetwork;
                })
                .then(function() { testBtn.disabled = false; });
        });
    });
</script>
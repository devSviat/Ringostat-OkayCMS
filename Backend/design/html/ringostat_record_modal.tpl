{* Спільна модалка плеєра запису Ringostat (журнал + картка замовлення) *}
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

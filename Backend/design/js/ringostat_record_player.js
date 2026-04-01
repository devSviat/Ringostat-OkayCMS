(function () {
    if (window.__sviatRingostatRecordPlayerInit) {
        return;
    }
    window.__sviatRingostatRecordPlayerInit = true;

    document.addEventListener('DOMContentLoaded', function () {
        var $ = window.jQuery;
        if (!$) {
            return;
        }

        var $recordModal = $('#fn_ringostat_record_modal');
        if (!$recordModal.length) {
            return;
        }

        var $playPauseBtn = $recordModal.find('.fn_ringostat_play_pause');
        var $timeDisplay = $recordModal.find('.fn_ringostat_time_display');
        var $volumeSlider = $recordModal.find('.fn_ringostat_volume');
        var $muteBtn = $recordModal.find('.fn_ringostat_mute');
        var wavesurfer = null;
        var currentSrc = null;
        var lastVolume = 1;

        function formatTime(seconds) {
            if (!isFinite(seconds) || isNaN(seconds)) {
                return '0:00';
            }
            var m = Math.floor(seconds / 60);
            var s = Math.floor(seconds % 60);
            return m + ':' + (s < 10 ? '0' : '') + s;
        }

        function initWaveSurfer() {
            if (!window.WaveSurfer) {
                return null;
            }
            var container = document.getElementById('fn_ringostat_waveform');
            if (!container) {
                return null;
            }

            var ws = WaveSurfer.create({
                container: container,
                waveColor: '#bcd6ff',
                progressColor: '#1e88e5',
                cursorColor: '#1565c0',
                cursorWidth: 3,
                barWidth: 2,
                barGap: 1,
                barRadius: 1,
                height: 64,
                responsive: true,
                normalize: true,
                interact: true,
                dragToSeek: { debounceTime: 0 },
                backend: 'MediaElement',
                fillParent: true
            });

            var $playIcon = $playPauseBtn.find('.ringostat-icon_play');
            var $pauseIcon = $playPauseBtn.find('.ringostat-icon_pause');
            $playIcon.show();
            $pauseIcon.hide();

            ws.on('play', function () {
                $playIcon.hide();
                $pauseIcon.show();
            });
            ws.on('pause', function () {
                $pauseIcon.hide();
                $playIcon.show();
            });
            ws.on('finish', function () {
                ws.stop();
                $pauseIcon.hide();
                $playIcon.show();
            });

            if (ws.setVolume) {
                ws.setVolume(1);
            }

            if ($volumeSlider.length) {
                $volumeSlider.off('input change').on('input change', function () {
                    var value = parseInt(this.value, 10);
                    if (isNaN(value)) {
                        value = 100;
                    }
                    var vol = Math.min(1, Math.max(0, value / 100));
                    if (ws.setVolume) {
                        ws.setVolume(vol);
                    }
                    if (vol > 0) {
                        lastVolume = vol;
                        $muteBtn.removeClass('ringostat-muted');
                    } else {
                        $muteBtn.addClass('ringostat-muted');
                    }
                });
            }

            if ($muteBtn.length) {
                $muteBtn.off('click').on('click', function () {
                    var currentVol = ws.getVolume ? ws.getVolume() : lastVolume;
                    if (currentVol > 0.001) {
                        lastVolume = currentVol;
                        if (ws.setVolume) {
                            ws.setVolume(0);
                        }
                        if ($volumeSlider.length) {
                            $volumeSlider.val(0);
                        }
                        $muteBtn.addClass('ringostat-muted');
                    } else {
                        var restore = lastVolume > 0 ? lastVolume : 1;
                        if (ws.setVolume) {
                            ws.setVolume(restore);
                        }
                        if ($volumeSlider.length) {
                            $volumeSlider.val(Math.round(restore * 100));
                        }
                        $muteBtn.removeClass('ringostat-muted');
                    }
                });
            }

            ws.on('audioprocess', function () {
                var current = ws.getCurrentTime();
                var duration = ws.getDuration();
                $timeDisplay.text(formatTime(current) + ' / ' + formatTime(duration));
            });
            ws.on('ready', function () {
                $timeDisplay.text('0:00 / ' + formatTime(ws.getDuration()));
            });
            $playPauseBtn.off('click').on('click', function () {
                ws.playPause();
            });

            return ws;
        }

        function resetPlayerUI() {
            $timeDisplay.text('0:00 / 0:00');
            var $playIcon = $playPauseBtn.find('.ringostat-icon_play');
            var $pauseIcon = $playPauseBtn.find('.ringostat-icon_pause');
            $playIcon.show();
            $pauseIcon.hide();
            if ($volumeSlider.length) {
                $volumeSlider.val(100);
            }
            if ($muteBtn.length) {
                $muteBtn.removeClass('ringostat-muted');
            }
        }

        $(document).on('click', '.fn_open_record_modal', function (e) {
            e.preventDefault();
            var href = $(this).attr('data-record-href');
            if (!href) {
                return;
            }
            currentSrc = href.replace(/&amp;/g, '&');
            resetPlayerUI();
            $recordModal.modal('show');
            if (!wavesurfer) {
                wavesurfer = initWaveSurfer();
            }
            if (wavesurfer) {
                wavesurfer.once('ready', function () {
                    wavesurfer.play();
                });
                wavesurfer.load(currentSrc);
            } else {
                $timeDisplay.text('—');
            }
        });

        $recordModal.on('shown.bs.modal', function () {
            if (wavesurfer) {
                try {
                    wavesurfer.setOptions({ height: 64 });
                } catch (err) {}
            }
        });

        $recordModal.on('hidden.bs.modal', function () {
            if (wavesurfer) {
                try {
                    wavesurfer.stop();
                    wavesurfer.destroy();
                } catch (err) {}
                wavesurfer = null;
            }
            resetPlayerUI();
            currentSrc = null;
        });
    });
})();

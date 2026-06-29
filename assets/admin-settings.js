/**
 * GoFonIA Booking Calendar – Admin Settings JS
 */
jQuery(function($) {
    var ajaxUrl = meetbotCalAdmin.ajaxUrl;
    var nonce = meetbotCalAdmin.nonce;

    if ($('#meetbot_cal_api_key').val()) loadPages();

    $('#meetbot-test-btn').on('click', function() {
        var btn = $(this);
        btn.prop('disabled', true).text('...');
        $.post(ajaxUrl, { action: 'meetbot_cal_test', nonce: nonce, api_key: $('#meetbot_cal_api_key').val() }, function(res) {
            var n = $('#meetbot-notice').show();
            if (res.success) {
                n.html('<div class="notice notice-success"><p>\u2705 ' + res.data.message + '</p></div>');
                loadPages();
            } else {
                n.html('<div class="notice notice-error"><p>\u274c ' + (res.data.message || res.data || 'Error') + '</p></div>');
            }
            btn.prop('disabled', false).text('Connect');
        });
    });

    $('#meetbot-fetch-pages').on('click', loadPages);

    $('#meetbot-configure-meet').on('click', function() {
        var btn = $(this);
        var pageUrl = $('#meetbot_cal_page_url').val();
        if (!pageUrl) { alert('Please select a booking page first.'); return; }
        btn.prop('disabled', true).text('Configuring...');
        var status = $('#meetbot-meet-status');
        $.post(ajaxUrl, {
            action: 'meetbot_cal_configure_meet',
            nonce: nonce,
            page_url: pageUrl,
            enable: '1'
        }, function(res) {
            if (res.success) {
                status.html('\u2705 <span style="color:green;">' + res.data.message + '</span>');
            } else {
                status.html('\u274c <span style="color:red;">' + (res.data.message || 'Error') + '</span>');
            }
            btn.prop('disabled', false).text('Configure Meet.bot page');
        });
    });

    function loadPages() {
        $.post(ajaxUrl, { action: 'meetbot_cal_fetch_pages', nonce: nonce }, function(res) {
            if (res.success && res.data.pages) {
                var sel = $('#meetbot_cal_page_url');
                var cur = sel.data('current') || sel.val() || '';
                sel.empty().append('<option value="">\u2014 select \u2014</option>');
                $.each(res.data.pages, function(i, p) {
                    sel.append('<option value="' + p.url + '"' + (p.url === cur ? ' selected' : '') + '>' + p.title + ' (' + p.duration + ' min) \u2014 ' + p.url + '</option>');
                });
                $('#meetbot-configure-meet').prop('disabled', !sel.val());
            }
        });
    }
});

(function ($) {
    'use strict';

    // Tab switching
    $(document).on('click', '.ajld-tab', function () {
        var tab = $(this).data('tab');
        $('.ajld-tab').removeClass('active');
        $('.ajld-tab-content').removeClass('active');
        $(this).addClass('active');
        $('#ajld-tab-' + tab).addClass('active');
    });

    // Primary page-type schemas are mutually exclusive: a single URL can only be
    // one primary type. Checking one auto-unchecks the others so Google never
    // receives conflicting primary entities for the same page.
    // Non-primary schemas (website, organization, breadcrumb, service, faq,
    // localbusiness, itemlist) are allowed to stack freely.
    var AJLD_PRIMARY_TYPES = ['webpage', 'article', 'blogposting', 'creativework', 'aboutpage', 'contactpage'];

    // Schema card toggle
    $(document).on('change', '.ajld-schema-card input[type="checkbox"]', function () {
        var $input = $(this);
        var card   = $input.closest('.ajld-schema-card');
        var key    = $input.val();
        // Match the primary block (#ajld-fields-service) and any extra blocks
        // (#ajld-fields-service-extra) so all related fields show/hide together.
        var fields = $('[id^="ajld-fields-' + key + '"]');

        if ($input.is(':checked')) {
            card.addClass('active');
            if (fields.length) fields.slideDown(200);

            // Enforce mutual exclusion among primary page types.
            if ($.inArray(key, AJLD_PRIMARY_TYPES) !== -1) {
                $('.ajld-schema-card input[type="checkbox"]').each(function () {
                    var otherKey = $(this).val();
                    if (otherKey !== key && $.inArray(otherKey, AJLD_PRIMARY_TYPES) !== -1 && $(this).is(':checked')) {
                        $(this).prop('checked', false).trigger('change');
                    }
                });
            }
        } else {
            card.removeClass('active');
            if (fields.length) fields.slideUp(200);
        }
    });

    // JSON validator
    $('#ajld-validate-json').on('click', function () {
        var val    = $('#ajld_custom_schema').val().trim();
        var status = $('#ajld-json-status');
        if (!val) { status.css('color', '#888').text('Nothing to validate.'); return; }
        try {
            JSON.parse(val);
            status.css('color', '#46b450').text('Valid JSON!');
        } catch (e) {
            status.css('color', '#dc3232').text('Invalid JSON: ' + e.message);
        }
    });

    // Schema preview loader
    $('#ajld-preview-btn').on('click', function () {
        var url    = $(this).data('url');
        var output = $('#ajld-preview-output');
        if (!url) { output.show().text('Save the post first to generate a preview URL.'); return; }
        output.show().text('Loading...');
        $.get(url, function (html) {
            var match = html.match(/<script type="application\/ld\+json">([\s\S]*?)<\/script>/);
            if (match && match[1]) {
                try {
                    output.text(JSON.stringify(JSON.parse(match[1].trim()), null, 2));
                } catch (e) {
                    output.text(match[1].trim());
                }
            } else {
                output.text('No JSON-LD schema found. Make sure the post is published.');
            }
        }).fail(function () {
            output.text('Could not load page. Make sure the post is published and accessible.');
        });
    });

})(jQuery);

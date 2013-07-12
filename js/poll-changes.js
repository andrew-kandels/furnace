var JobPollChanges = function(el) {
    var module = this;
    this.$el   = $(el);
    this.sched = false;
    this.sched2= false;
    this.ms    = 5000;

    this.refresh = function() {
        var $span = $('#job-refresh-time');
        var sec   = parseInt($span.text());

        if (sec <= 1) {
            clearInterval(module.sched2);
            $span.text('0');
            document.location.reload(true);
            return;
        }

        $span.text(sec - 1);
    };

    this.heartbeat = function() {
        $.ajax(module.$el.data('status-src'), {
            type: 'POST',
            dataType: 'json',
            data: {job:module.$el.data('job')},
            statusCode: {
                205: function() {
                    clearInterval(module.sched);
                    module.$el.before(
                        '<div class="alert alert-info hidden" id="job-refresh">'
                        + '<h4><i class="icon-refresh"></i> Changes Detected</h4>'
                        + '<p>This page has been updated and will be refreshed in <span id="job-refresh-time">5</span> '
                        + 'seconds, or you can <a href="javascript:void();" onclick="document.location.reload(true);">'
                        + 'refresh now</a>.</p>'
                        + '</div>'
                    );
                    $('#job-refresh').show(500);

                    module.sched2 = setInterval(module.refresh, 1000);
                }
            }
        });
    };

    this.initialize = function() {
        module.sched = setInterval(module.heartbeat, module.ms);
    };
};

$(function() {
    var $el = $('#job-active');
    if ($el.size()) {
        (new JobPollChanges($el)).initialize();
    }
});

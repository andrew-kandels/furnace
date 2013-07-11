var JobLog = function(el) {
    var module   = this;
    this.$el     = $(el);
    this.runLock = false;
    this.sched   = false;

    this.heartbeat = function() {
        if (module.$el.hasClass('completed')) {
            if (module.sched) {
                clearInterval(module.sched);
            }
            return;
        }

        if (module.runLock) {
            return;
        }

        module.runLock = true;

        var $spinner = $('#job-spinner').clone().attr('id', 'job-log-spinner');
        var $pre = module.$el.find('pre');
        module.$el.find('legend').append($spinner);

        $.ajax(module.$el.data('src'), {
            type: 'GET',
            statusCode: {
                205: function() {
                    var $stat = $('form').find('.job-status').parents('.control-group').find('.controls .label');
                    $stat.attr('class', 'label label-success').html('Completed');
                    module.$el.addClass('completed');
                }
            }
        })
        .success(function(data) {
            if (data) {
                $pre.html(data);
                $pre.scrollTop($pre.prop('scrollHeight'));
            }
        })
        .fail(function() {
            $pre.css('opacity', '0.5');
        })
        .always(function() {
            setTimeout(function() {
                $('#job-log-spinner').remove();
            }, 500);
        });

        module.runLock = false;
    };

    this.initialize = function() {
        var $pre = module.$el.find('pre');
        module.sched = setInterval(module.heartbeat, 5000);
        $pre.scrollTop($pre.prop('scrollHeight'));
    };
};

$(function() {
    $('.job-log').each(function(i, el) {
        (new JobLog(el)).initialize();
    });
});

var JobRefresh = function() {
    var module = this;
    this.$el = $('#job-refresh');

    this.getSetting = function() {
        return module.$el.find('input:checked').size();
    };

    this.checkTimer = function() {
        if (typeof module.sched != 'undefined') {
            clearTimeout(module.sched);
        }

        if (module.getSetting()) {
            module.sched = setTimeout(function() {
                location.reload(true);
            }, 30000);
        }
    };

    this.onSetting = function(e) {
        var checked = module.getSetting() ? 'yes' : 'no';
        module.$el.find('input').css('opacity', 0.3).attr('disabled', 'disabled');
        $.post(module.$el.data('src'), {checked:checked})
        .fail(function() {
            module.$el.css('color', 'red');
        })
        .always(function() {
            module.$el.find('input').css('opacity', 1.0).removeAttr('disabled');
        });

        module.checkTimer();
    };

    this.initialize = function() {
        module.$el.find('input').change(this.onSetting);
        module.checkTimer();
    };
};

$(function() {
    if ($('#job-refresh').size()) {
        (new JobRefresh()).initialize();
    }
});

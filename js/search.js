var JobSearch = function(el) {
    var module = this;
    this.$el   = $('#search');
    this.sched = null;

    this.heartbeat = function() {
        if (!module.active) {
            return;
        }

        module.active = false;

        var text = $.trim(module.$el.val());

        $('.jobs-list tr').show();

        if (!text.length) {
            return;
        }

        $('.jobs-list tbody tr').each(function(i, el) {
            var val = $.trim($(el).find('td.column-name > a').text());

            if (val.indexOf(text) == -1) {
                $(el).hide();
            }
        });
    };

    this.search = function(e) {
        module.active = true;
    };

    this.initialize = function() {
        module.$el.keyup(module.search);
        module.sched = setInterval(module.heartbeat, 500);
    };
};

$(function() {
    if ($('#search').size()) {
        (new JobSearch()).initialize();
    }
});

var JobMassActions = function() {
    var module = this;

    this.click = function(e) {
        e.preventDefault();

        var arr = [];

        $('.mass-option').each(function(i, el) {
            if ($(el).is(':checked')) {
                arr.push($(el).val());
            }
        });

        document.location.href = $(e.currentTarget).data('src') + '/' + arr.join(',');
    };

    this.selectAll = function(e) {
        var checked = $(this).is(':checked');

        $(this).parents('table').find('.mass-option').each(function(i, el) {
            if (checked) {
                $(el).attr('checked', 'checked');
            } else {
                $(el).removeAttr('checked');
            }
        });
    };

    this.initialize = function() {
        $(document).on('click', '.mass-action', this.click);
        $(document).on('change', '.select-all', this.selectAll);
    };
};

$(function() {
    if ($('.mass-action').size()) {
        (new JobMassActions()).initialize();
    }
});

var JobHistory = function(el) {
    var module = this;
    this.$el   = $(el);
    this.activeNode = null;

    this.setNotes = function(e) {
        e.preventDefault();
        var $link = $(e.currentTarget);

        $.post($link.data('src'), {
            notes: $.trim($('#job-notes').val())
        })
        .done(function(data) {
            module.activeNode.find('.column-notes').html(data.notes);
        })
        .fail(function() {
            module.activeNode.find('.column-notes').html('<span class="label label-important">Failed to update!</span>');
        })
        .always(function() {
            $('#job-notes-modal').modal('hide');
        });
    };

    this.promptForNotes = function(e) {
        e.preventDefault();
        var $link = $(e.currentTarget);
        var $tr = $link.parents('tr').first();
        var $spinner = $('#job-spinner').clone().attr('id', 'job-log-spinner');

        var modal = '<div id="job-notes-modal" class="modal hide fade">'
                + '<div class="modal-header">'
                    + '<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>'
                    + '<h3>Set Notes</h3>'
                + '</div>'
                + '<div class="modal-body">'
                    + '<textarea id="job-notes" class="input-full" rows="3"></textarea>'
                + '</div>'
                + '<div class="modal-footer">'
                    + '<a href="#" class="btn btn-primary job-notes-save">Save</a>'
                    + '<button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>'
                + '</div>'
            + '</div>';

        $('body').append(modal);

        $('#job-notes-modal').on('shown', function() {
            $('#job-notes').focus().select();
        }).modal('show')

        $('#job-notes').val($.trim($tr.find('.column-notes').text()));
        $('.job-notes-save').data('src', $link.data('src'));

        module.activeNode = $tr;
    };

    this.deleteHistory = function(e) {
        e.preventDefault();
        var $link = $(e.currentTarget);
        var $tr = $link.parents('tr').first();
        var $spinner = $('#job-spinner').clone().attr('id', 'job-log-spinner');

        $tr.find('.column-remove').html($spinner);

        $.post($link.data('src'))
        .done(function(data) {
            $tr.remove();
        })
        .fail(function() {
            $tr.find('.column-remove').html('<i class="icon-frown"></i>');
            $tr.find('td').css('color', 'red');
        });
    };

    this.initialize = function() {
        module.$el.on('click', '.job-delete-history', module.deleteHistory);
        module.$el.on('click', '.job-set-history-notes', module.promptForNotes);
        $(document).on('click', '.job-notes-save', module.setNotes);
    };
};

$(function() {
    var $el = $('#job-history');
    if ($el.size()) {
        (new JobHistory($el)).initialize();
    }
});

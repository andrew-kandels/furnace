$(function() {
    $('.job-status-flag').tooltip();

    $('.job-status').click(function(e) {
        e.preventDefault();
        var $link = $(e.currentTarget);

        $.get($link.attr('href'))
        .success(function(data) {
            var modal = '<div id="job-modal" class="modal hide fade">'
                + '<div class="modal-header">'
                + '    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>'
                + '    <h3>Messages</h3>'
                + '</div>'
                + '<div class="modal-body">'
                + data
                + '</div>'
                + '<div class="modal-footer">'
                + '    <button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>'
                + '</div>'
                + '</div>';

            $('body').append(modal);

            $('#job-modal').modal('show');
        });
    });
});

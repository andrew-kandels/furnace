var JobDependency = function() {
    var module = this;
    this.$el = $('#control-group-dependency');


    this.getList = function() {
        var ret = $('#dependencies').val().split(',');

        if (ret.length == 1 && ret[0] === "") {
            return [];
        }

        return ret;
    };

    this.setList = function(arr) {
        $('#dependencies').val(arr.join(','));
        return this;
    };

    this.refreshContainer = function() {
        var $container = module.$el.find('.dependency-container');
        var items = module.getList();

        $container.html('').addClass('hidden');

        if (!items.length) {
            return;
        }

        $(items).each(function(i, el) {
            $container.append('<li class="dependency-item"><a href="#" class="remove">&times;</a> <span>' + el + '</span></li>');
        });

        $container.removeClass('hidden');
    };

    this.add = function(e) {
        e.preventDefault();

        var $input = $('#dependencies');
        var $select = module.$el.find('select');
        var job = $select.val();
        var items = module.getList();

        for (var i = 0; i < items.length; i++) {
            if (items[i] == job) {
                return;
            }
        }

        items.push(job);
        module.setList(items).refreshContainer();
        $select.val('');
    };

    this.remove = function(e) {
        e.preventDefault();
        var job = $(e.currentTarget).next('span').text();
        var items = module.getList();
        var arr = [];

        for (var i = 0; i < items.length; i++) {
            if (items[i] != job) {
                arr.push(items[i]);
            }
        }

        module.setList(arr).refreshContainer();
    };

    this.initialize = function() {
        module.$el.find('.add-dependency').click(module.add);
        module.$el.find('.dependency-container').on('click', '.remove', this.remove);
        module.refreshContainer();
    };
};

$(function() {
    if ($('#control-group-dependency').size()) {
        (new JobDependency()).initialize();
    }
});

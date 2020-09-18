(function($) {
    $(document).ready(function() {
        var advancedSearch = $('#advanced-search');
        $(document).on('change', '[name="item_assignment_action"]', function() {
            var selectedValue = $(this).val();
            if ((selectedValue == 'no_action') || (selectedValue == 'remove_all')) {
                advancedSearch.addClass('inactive');
            } else {
                advancedSearch.removeClass('inactive');
            }
        });

        Omeka.initializeSelector('#site-item-sets', '#item-set-selector');

        $('#resources-preview-button').on('click', function(e) {
            e.preventDefault();
            var url = $(this).data('url');
            var query = $('#site-form').serialize();
            window.open(`${url}?${query}`, '_blank');
        });

    });
})(jQuery)
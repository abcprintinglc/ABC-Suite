(function($) {
    $('.abc-estimator-pro').each(function() {
        var $container = $(this);
        var $search = $container.find('.abc-search');
        var $tbody = $container.find('tbody');

        function renderRows(items) {
            $tbody.empty();
            items.forEach(function(item) {
                var row = '<tr>' +
                    '<td>' + item.invoice + '</td>' +
                    '<td>' + item.status + '</td>' +
                    '<td>' + item.due_date + '</td>' +
                    '</tr>';
                $tbody.append(row);
            });
        }

        $search.on('input', function() {
            var query = $(this).val();
            $.get(abcSuite.ajaxUrl, {
                action: 'abc_search_estimates',
                q: query,
                nonce: abcSuite.nonce
            }).done(function(response) {
                if (response.success) {
                    renderRows(response.data.items);
                }
            });
        });
    });
})(jQuery);

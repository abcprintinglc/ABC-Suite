/* global jQuery, ABCSuiteLogbook */
(function ($) {
  'use strict';

  function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function renderRows(rows) {
    var $tbody = $('#abc-log-results');
    var $noResults = $('#abc-no-results');

    if (!$tbody.length) return;

    $tbody.empty();

    if (!rows || !rows.length) {
      if ($noResults.length) $noResults.show();
      return;
    }

    if ($noResults.length) $noResults.hide();

    rows.forEach(function (row) {
      var due = row.due_date ? escapeHtml(row.due_date) : '—';
      var rush = row.is_rush ? ' <span class="abc-rush">(RUSH)</span>' : '';
      var urgencyClass = row.urgency === 'urgent'
        ? 'abc-row-urgent'
        : (row.urgency === 'warning' ? 'abc-row-warning' : '');
      var clientOrTitle = (row.client && row.client.length) ? row.client : row.title;

      var currentStage = row.stage ? String(row.stage).toLowerCase() : 'estimate';
      var stages = ['estimate', 'pending', 'production', 'completed'];
      var stageSelect = '<select class="abc-quick-status" data-id="' + row.id + '">';
      stages.forEach(function (s) {
        var isSelected = (s === currentStage) ? 'selected' : '';
        var label = s.charAt(0).toUpperCase() + s.slice(1);
        stageSelect += '<option value="' + s + '" ' + isSelected + '>' + label + '</option>';
      });
      stageSelect += '</select>';

      var jobJacketBtn = row.edit_url
        ? '<a href="' + escapeHtml(row.edit_url) + '" class="button" target="_blank" rel="noopener">Job Jacket</a>'
        : '';

      var printBtn = row.print_url
        ? '<a href="' + escapeHtml(row.print_url) + '" class="button" target="_blank" rel="noopener">Print</a>'
        : '';

      var html = '' +
        '<tr class="' + urgencyClass + '">' +
          '<td><strong>' + escapeHtml(row.invoice || '---') + '</strong></td>' +
          '<td>' + escapeHtml(clientOrTitle || '') + '</td>' +
          '<td>' + stageSelect + '</td>' +
          '<td>' + due + rush + '</td>' +
          '<td class="abc-actions">' + jobJacketBtn + ' ' + printBtn + '</td>' +
        '</tr>';

      $tbody.append(html);
    });
  }

  function fetchResults(term) {
    var $spinner = $('#abc-admin-spinner');
    if ($spinner.length) $spinner.addClass('is-active').show();

    return $.post(ABCSuiteLogbook.ajaxUrl, {
      action: 'abc_search_estimates',
      nonce: ABCSuiteLogbook.nonce,
      term: term || ''
    }).done(function (res) {
      if (res && res.success) {
        renderRows(res.data);
      } else {
        renderRows([]);
      }
    }).fail(function () {
      renderRows([]);
    }).always(function () {
      if ($spinner.length) $spinner.removeClass('is-active').hide();
    });
  }

  $(document).ready(function () {
    var $input = $('#abc-log-search');

    if (!$input.length) return;

    var delayTimer;

    $(document).on('change', '.abc-quick-status', function () {
      var $select = $(this);
      var id = $select.data('id');
      var newStatus = $select.val();
      var $row = $select.closest('tr');

      $row.css('opacity', '0.6');
      $select.prop('disabled', true);

      $.post(ABCSuiteLogbook.ajaxUrl, {
        action: 'abc_update_status',
        nonce: ABCSuiteLogbook.nonce,
        id: id,
        status: newStatus
      }).done(function (res) {
        if (!res || !res.success) {
          alert('Error updating status');
        }
      }).always(function () {
        $row.css('opacity', '1');
        $select.prop('disabled', false);
      });
    });

    $input.on('input', function () {
      clearTimeout(delayTimer);
      var term = $(this).val();
      delayTimer = setTimeout(function () {
        fetchResults(term);
      }, 250);
    });

    fetchResults('');
  });
})(jQuery);

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

    if (!Array.isArray(rows) || rows.length === 0) {
      if ($noResults.length) $noResults.show();
      return;
    }

    if ($noResults.length) $noResults.hide();

    rows.forEach(function (row) {
      var stage = (row.stage || 'estimate').toString();
      var urgency = (row.urgency || 'normal').toString();

      var statusClass = 'status-' + stage;
      var trClass = '';
      if (urgency === 'urgent') trClass = 'abc-row-urgent';
      if (urgency === 'warning') trClass = 'abc-row-warning';

      var invoice = escapeHtml(row.invoice || '---');
      var title = escapeHtml(row.client || row.title || '');
      var due = escapeHtml(row.due_date || '');
      var isRush = !!row.is_rush;

      var rushHtml = isRush ? ' <span class="abc-rush">(RUSH)</span>' : '';
      var stageHtml = '<span class="abc-pill ' + escapeHtml(statusClass) + '">' + escapeHtml(stage) + '</span>';

      var actions = '';
      if (row.edit_url) {
        actions += '<a href="' + escapeHtml(row.edit_url) + '" class="button button-small" target="_blank" rel="noopener">Edit</a> ';
      }
      if (row.print_url) {
        actions += '<a href="' + escapeHtml(row.print_url) + '" class="button button-small" target="_blank" rel="noopener">Print</a>';
      }

      var html =
        '<tr class="' + escapeHtml(trClass) + '">' +
          '<td><strong>' + invoice + '</strong></td>' +
          '<td>' + title + '</td>' +
          '<td>' + stageHtml + '</td>' +
          '<td>' + due + rushHtml + '</td>' +
          '<td>' + actions + '</td>' +
        '</tr>';

      $tbody.append(html);
    });
  }

  function fetchResults(term) {
    var $spinner = $('#abc-spinner');
    if ($spinner.length) $spinner.show();

    $.post(ABCSuiteLogbook.ajaxUrl, {
      action: 'abc_search_estimates',
      nonce: ABCSuiteLogbook.nonce,
      term: term || ''
    })
      .done(function (res) {
        if ($spinner.length) $spinner.hide();
        if (res && res.success) {
          renderRows(res.data || []);
        } else {
          renderRows([]);
        }
      })
      .fail(function () {
        if ($spinner.length) $spinner.hide();
        renderRows([]);
      });
  }

  $(function () {
    var $input = $('#abc-frontend-search');
    if (!$input.length) return;

    var delayTimer = null;

    $input.on('input', function () {
      clearTimeout(delayTimer);
      var term = $(this).val();
      delayTimer = setTimeout(function () {
        fetchResults(term);
      }, 300);
    });

    fetchResults('');
  });
})(jQuery);

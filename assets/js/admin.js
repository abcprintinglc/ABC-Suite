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

  function parseSchema(schemaText) {
    if (!schemaText) return { groups: [] };
    try {
      var parsed = JSON.parse(schemaText);
      if (Array.isArray(parsed)) {
        return { groups: parsed };
      }
      if (parsed && typeof parsed === 'object') {
        if (Array.isArray(parsed.groups)) {
          return parsed;
        }
        var groups = [];
        Object.keys(parsed).forEach(function (key) {
          if (Array.isArray(parsed[key])) {
            groups.push({ name: key, values: parsed[key] });
          }
        });
        return { groups: groups };
      }
    } catch (err) {
      return { groups: [] };
    }
    return { groups: [] };
  }

  function buildOptions($container, schema) {
    $container.empty();
    if (!schema || !schema.groups || !schema.groups.length) {
      $container.append('<p class="description">No options configured for this template.</p>');
      return;
    }

    schema.groups.forEach(function (group) {
      if (!group || !group.name || !Array.isArray(group.values)) return;
      var fieldId = 'abc_option_' + group.name.replace(/\s+/g, '_').toLowerCase();
      var html = '<div class="abc-product-library-row">' +
        '<label for="' + fieldId + '"><strong>' + escapeHtml(group.name) + '</strong></label>' +
        '<select id="' + fieldId + '" data-option-name="' + escapeHtml(group.name) + '">';
      group.values.forEach(function (val) {
        html += '<option value="' + escapeHtml(val) + '">' + escapeHtml(val) + '</option>';
      });
      html += '</select></div>';
      $container.append(html);
    });
  }

  function gatherOptions($container) {
    var options = {};
    $container.find('select[data-option-name]').each(function () {
      var $select = $(this);
      var name = $select.data('option-name');
      options[name] = $select.val();
    });
    return options;
  }

  function calcSellPrice(cost, markupType, markupValue) {
    var c = parseFloat(cost || 0);
    var m = parseFloat(markupValue || 0);
    if (markupType === 'multiplier') {
      return c * (m || 0);
    }
    return c + (c * (m / 100));
  }

  function renderLineItems(items) {
    var $container = $('#abc-template-line-items');
    if (!$container.length) return;
    if (!items || !items.length) {
      $container.html('<p class="description">No line items yet.</p>');
      return;
    }

    var html = '<table class="widefat striped"><thead><tr>' +
      '<th>Product</th><th>Qty</th><th>Vendor</th><th>Cost</th><th>Sell</th><th>Options</th>' +
      '</tr></thead><tbody>';
    items.forEach(function (item) {
      var options = item.options_json ? JSON.stringify(item.options_json) : '';
      html += '<tr>' +
        '<td>' + escapeHtml(item.product_label || '') + '</td>' +
        '<td>' + escapeHtml(item.qty || '') + '</td>' +
        '<td>' + escapeHtml(item.vendor || '') + '</td>' +
        '<td>' + escapeHtml(item.cost_snapshot || '') + '</td>' +
        '<td>' + escapeHtml(item.sell_price || '') + '</td>' +
        '<td><code>' + escapeHtml(options) + '</code></td>' +
        '</tr>';
    });
    html += '</tbody></table>';
    $container.html(html);
  }

  function initProductLibrary() {
    var $form = $('#abc-product-library-form');
    var $toggle = $('#abc-toggle-library');
    var $select = $('#abc_template_select');
    var $optionsContainer = $('#abc-template-options');
    var $vendor = $('#abc_template_vendor');
    var $qty = $('#abc_template_qty');
    var $cost = $('#abc_template_cost');
    var $markupType = $('#abc_template_markup_type');
    var $markupValue = $('#abc_template_markup_value');
    var $sellPrice = $('#abc_template_sell_price');
    var $costStatus = $('#abc-template-cost-status');
    var $estimateData = $('#abc_estimate_data');
    var currentMatrixRowId = null;
    var currentLastVerified = '';

    if (!$form.length || !$estimateData.length) return;

    var lineItems = [];
    try {
      var parsedItems = JSON.parse($estimateData.val() || '[]');
      if (Array.isArray(parsedItems)) {
        lineItems = parsedItems;
      }
    } catch (err) {
      lineItems = [];
    }

    renderLineItems(lineItems);

    $toggle.on('click', function () {
      $form.toggle();
    });

    function updateEstimateField() {
      $estimateData.val(JSON.stringify(lineItems));
      renderLineItems(lineItems);
    }

    function requestTemplates() {
      return $.get(ABCSuiteLogbook.ajaxUrl, {
        action: 'abc_get_templates',
        nonce: ABCSuiteLogbook.nonce
      }).done(function (res) {
        if (!res || !res.success) return;
        $select.empty().append('<option value="">Select template</option>');
        res.data.forEach(function (template) {
          var option = $('<option></option>')
            .attr('value', template.id)
            .data('schema', template.option_schema)
            .data('vendor', template.vendor_default)
            .data('markup-type', template.markup_type)
            .data('markup-value', template.markup_value)
            .text(template.title);
          $select.append(option);
        });
      });
    }

    function refreshSellPrice() {
      var sell = calcSellPrice($cost.val(), $markupType.val(), $markupValue.val());
      if (isNaN(sell)) sell = 0;
      $sellPrice.val(sell.toFixed(2));
    }

    function lookupCost() {
      var templateId = $select.val();
      var vendor = $vendor.val();
      var qty = parseInt($qty.val(), 10);
      var options = gatherOptions($optionsContainer);
      var turnaround = options.Turnaround || options.turnaround || '';

      if (!templateId || !vendor || !qty) return;

      $costStatus.text('Looking up cost...');
      $.post(ABCSuiteLogbook.ajaxUrl, {
        action: 'abc_price_lookup',
        nonce: ABCSuiteLogbook.nonce,
        template_id: templateId,
        vendor: vendor,
        qty: qty,
        turnaround: turnaround,
        options_json: JSON.stringify(options)
      }).done(function (res) {
        if (res && res.success) {
          currentMatrixRowId = res.data.id || null;
          currentLastVerified = res.data.last_verified || '';
          $cost.val(res.data.cost);
          $costStatus.text('Matrix match. Last verified: ' + (res.data.last_verified || 'n/a'));
        } else {
          currentMatrixRowId = null;
          currentLastVerified = '';
          $costStatus.text('No matrix match. Enter cost manually.');
        }
      }).fail(function () {
        currentMatrixRowId = null;
        currentLastVerified = '';
        $costStatus.text('No matrix match. Enter cost manually.');
      }).always(function () {
        refreshSellPrice();
      });
    }

    $select.on('change', function () {
      var $selected = $select.find('option:selected');
      var schemaText = $selected.data('schema') || '{}';
      var vendorDefault = $selected.data('vendor') || '';
      var markupType = $selected.data('markup-type') || 'percent';
      var markupValue = $selected.data('markup-value') || 0;
      buildOptions($optionsContainer, parseSchema(schemaText));
      $vendor.val(vendorDefault);
      $markupType.val(markupType);
      $markupValue.val(markupValue);
      refreshSellPrice();
      lookupCost();
    });

    $optionsContainer.on('change', 'select', lookupCost);
    $qty.on('input', lookupCost);
    $vendor.on('change', lookupCost);
    $markupType.on('change', refreshSellPrice);
    $markupValue.on('input', refreshSellPrice);
    $cost.on('input', refreshSellPrice);

    $('#abc-add-line-item').on('click', function () {
      var templateId = $select.val();
      if (!templateId) {
        alert('Select a template first.');
        return;
      }

      var options = gatherOptions($optionsContainer);
      var label = $select.find('option:selected').text();
      var qty = parseInt($qty.val(), 10) || 1;
      var costVal = parseFloat($cost.val() || 0).toFixed(2);
      var markupType = $markupType.val();
      var markupValue = parseFloat($markupValue.val() || 0);
      var sellVal = parseFloat($sellPrice.val() || 0).toFixed(2);

      lineItems.push({
        template_id: parseInt(templateId, 10),
        product_label: label,
        qty: qty,
        options_json: options,
        vendor: $vendor.val(),
        cost_snapshot: costVal,
        markup_type: markupType,
        markup_value: markupValue,
        sell_price: sellVal,
        price_matrix_row_id: currentMatrixRowId,
        cost_last_verified: currentLastVerified
      });

      updateEstimateField();
    });

    requestTemplates();
  }

  function initMatrixEditor() {
    var $buttons = $('.abc-matrix-edit');
    if (!$buttons.length) return;

    $buttons.on('click', function () {
      var $btn = $(this);
      $('#abc_matrix_id').val($btn.data('id') || '');
      $('#abc_matrix_template').val($btn.data('template') || '');
      $('#abc_matrix_vendor').val($btn.data('vendor') || '');
      $('#abc_matrix_qty_min').val($btn.data('qty-min') || '');
      $('#abc_matrix_qty_max').val($btn.data('qty-max') || '');
      $('#abc_matrix_options').val($btn.data('options') || '{}');
      $('#abc_matrix_turnaround').val($btn.data('turnaround') || '');
      $('#abc_matrix_cost').val($btn.data('cost') || '');
      $('#abc_matrix_last_verified').val($btn.data('last-verified') || '');
      $('#abc_matrix_source').val($btn.data('source-note') || '');
      $('html, body').animate({ scrollTop: 0 }, 200);
    });
  }

  $(document).ready(function () {
    var $input = $('#abc-log-search');

    if ($input.length) {
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
    }
    initProductLibrary();
    initMatrixEditor();
  });
})(jQuery);

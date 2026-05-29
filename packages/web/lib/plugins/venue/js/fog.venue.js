/**
 * Venue plugin JavaScript.
 *
 * @author Gavin Williams (https://github.com/gavinwilliams)
 */
(function ($) {
    'use strict';

    // === Utilities (escape, i18n, formatters) ===

    /**
     * HTML-text escape (for content placed between tags).
     */
    function escHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    /**
     * Attribute-value escape (for content placed inside "..." or '...').
     */
    function escAttr(s) {
        return escHtml(s)
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#x27;');
    }

    /**
     * Read the PHP-emitted list of API mapping paths.
     */
    function getApiMappingPaths() {
        var el = document.getElementById('venue-api-mapping-paths');
        if (!el) return [];
        try { return JSON.parse(el.textContent) || []; }
        catch (e) { return []; }
    }

    /**
     * Centralised user-facing strings (i18n-ready).
     */
    var T = {
        error: 'Error',
        success: 'Success',
        validation: 'Validation',
        loading: 'Loading venues...',
        remove: 'Remove',
        cancel: 'Cancel',
        select: 'Select',
        change: 'Change',
        applySelected: 'Apply Selected',
        nothingSelected: 'Please select at least one field.',
        upToDate: 'All mapped config fields already match the API values.',
        failedToReachServer: 'Failed to reach server',
        failedToReachApi: 'Failed to reach API',
        failedToApply: 'Failed to apply changes',
        requestFailed: 'Request failed. Please try again.',
        apiDataChanged: 'API data has changed since preview. Please refresh and try again.',
        keyLabelRequired: 'Key and Label are required.',
        confirmTitle: 'Confirm Deletion',
        confirmDeleteVenue: 'Are you sure you want to delete this venue?',
        confirmDeleteVenues: 'Are you sure you want to delete the selected venues?',
        confirmDeleteConfig: 'Are you sure? Existing venue values using this key will remain.',
        deleteFail: 'Delete Fail',
        deleteSuccess: 'Delete Success',
        done: 'Done',
        result: 'Result',
        upToDateTitle: 'Up to date',
        nothingSelectedTitle: 'Nothing selected',
        validationFixFields: 'Please fix the highlighted fields before saving.',
        selectVenueTitle: 'Select a venue',
        selectVenueMsg: 'Please select a region and venue.'
    };

    // Inject drag-and-drop drop-target highlight CSS once.
    (function injectDropTargetCss() {
        var css = '.venue-row-drop-target { background: #e7f3ff !important;'
            + ' outline: 2px dashed #337ab7; outline-offset: -2px; }';
        var s = document.createElement('style');
        s.textContent = css;
        document.head.appendChild(s);
    })();

    $(function () {
        checkboxToggleSearchListPages();
        validatorOpts = {
            submitHandler: function (form) {
                var $form = $(form);
                var isAdd = $form.find('[name="add"]').length > 0
                    || $form.attr('id') === 'add';
                submitVenueForm($form, isAdd);
            },
            rules: {
                subnet: {
                    required: true
                }
            }
        };
        setupTimeoutElement('#add, #update', '#subnet', 1000);
        /**
         * Generic AJAX form submission helper.
         */
        function submitVenueForm($form, redirectOnSuccess) {
            $.ajax({
                url: $form.attr('action'),
                type: 'post',
                data: $form.serialize(),
                dataType: 'json',
                success: function (data) {
                    var type = data.error
                        ? BootstrapDialog.TYPE_WARNING
                        : BootstrapDialog.TYPE_SUCCESS;
                    var msg = data.error || data.msg;
                    var sleeptime = data.error ? 5000 : 2000;
                    BootstrapDialog.show({
                        title: data.title || (data.error ? 'Error' : 'Success'),
                        message: msg,
                        type: type,
                        onshown: function (dialogRef) {
                            bootstrapdialogopen = setTimeout(function () {
                                dialogRef.close();
                            }, sleeptime);
                        },
                        onhidden: function (dialogRef) {
                            clearTimeout(bootstrapdialogopen);
                            if (!data.error && redirectOnSuccess) {
                                window.location = '?node=venue';
                            } else if (!data.error) {
                                location.reload();
                            }
                        }
                    });
                }
            });
        }
        // Intercept template form submit
        $(document).on('submit', '.venue-template-form', function (e) {
            e.preventDefault();
            submitVenueForm($(this), false);
        });
        // === Template editor (rows, api mapping dropdown) ===
        // --- Inline-editable Config Management table ---
        var typeOptions = $('#tpl-type-options').html() || '';

        /**
         * Build the HTML for an inline-editable row (add or edit).
         */
        function buildEditableRow(data) {
            data = data || {};
            var id = data.id || '';
            var key = data.key || '';
            var label = data.label || '';
            var type = data.type || 'text';
            var group = data.group || '';
            var defVal = data['default'] || '';
            var placeholder = data.placeholder || '';
            var options = data.options || '';
            var required = data.required ? 1 : 0;
            var description = data.description || '';
            var validation = data.validation || '';
            var validationMsg = data['validation-msg'] || data.validationMsg || '';
            var apiMapping = data['api-mapping'] || data.apiMapping || '';
            var order = data.order || 0;

            var html = '<tr class="venue-tpl-editing" style="background:#f0f7fd;border-left:3px solid #5bc0de" data-group="' + escAttr(group) + '">';
            html += '<td></td>';
            html += '<td><input type="text" class="form-control input-sm tpl-e-key" '
                + 'value="' + escAttr(key) + '" placeholder="MY_KEY" '
                + 'pattern="[A-Z0-9_]+" required style="text-transform:uppercase;font-family:monospace"/></td>';
            html += '<td><input type="text" class="form-control input-sm tpl-e-label" '
                + 'value="' + escAttr(label) + '" placeholder="Display Label" required/></td>';
            html += '<td style="width:100px"><select class="form-control input-sm tpl-e-type">'
                + typeOptions + '</select></td>';
            html += '<td style="width:50px" class="text-center"><input type="checkbox" class="tpl-e-required" '
                + (required ? 'checked' : '') + '/></td>';
            html += '<td style="width:90px">'
                + '<div class="btn-group">'
                + '<button class="btn btn-success btn-xs venue-tpl-save" data-id="' + id + '" title="Save">'
                + '<i class="fa fa-check"></i></button>'
                + '<button class="btn btn-default btn-xs venue-tpl-cancel" title="Cancel">'
                + '<i class="fa fa-times"></i></button>'
                + '</div></td>';
            html += '</tr>';
            // Extra fields row — indented with left accent
            html += '<tr class="venue-tpl-editing-extra" style="background:#f7fbfe;border-left:3px solid #5bc0de">';
            html += '<td></td><td colspan="5" style="padding:8px 8px 12px">';
            html += '<div class="row">';
            // Default field — text input for most types, checkbox for type=checkbox.
            // Both inputs are rendered; toggleExtraFields() shows the relevant one.
            var defChecked = (defVal === 'true' || defVal === '1' || defVal === true) ? ' checked' : '';
            html += '<div class="col-sm-3"><label class="small text-muted">Default</label>'
                + '<div class="tpl-e-default-text-wrap">'
                + '<input type="text" class="form-control input-sm tpl-e-default" '
                + 'value="' + escAttr(defVal) + '" placeholder="Default value"/>'
                + '</div>'
                + '<div class="tpl-e-default-checkbox-wrap" style="padding-top:6px">'
                + '<label style="font-weight:normal;cursor:pointer">'
                + '<input type="checkbox" class="tpl-e-default-checkbox"' + defChecked + '/> '
                + '<span class="text-muted small">Checked by default</span>'
                + '</label>'
                + '</div>'
                + '</div>';
            html += '<div class="col-sm-3 tpl-e-placeholder-wrap"><label class="small text-muted">Placeholder</label>'
                + '<input type="text" class="form-control input-sm tpl-e-placeholder" '
                + 'value="' + escAttr(placeholder) + '" placeholder="Hint text"/></div>';
            html += '<div class="col-sm-3 tpl-e-options-wrap"><label class="small text-muted">Options (pipe-delimited)</label>'
                + '<input type="text" class="form-control input-sm tpl-e-options" '
                + 'value="' + escAttr(options) + '" placeholder="Opt1|Opt2|Opt3"/></div>';
            html += '<div class="col-sm-3"><label class="small text-muted">Description</label>'
                + '<input type="text" class="form-control input-sm tpl-e-description" '
                + 'value="' + escAttr(description) + '" placeholder="Help text"/></div>';
            html += '</div>';
            html += '<div class="row" style="margin-top:8px">';
            html += '<div class="col-sm-4"><label class="small text-muted">Validation Regex</label>'
                + '<input type="text" class="form-control input-sm tpl-e-validation" '
                + 'value="' + escAttr(validation) + '" placeholder="^[a-z]+$" style="font-family:monospace"/></div>';
            html += '<div class="col-sm-4"><label class="small text-muted">Validation Message</label>'
                + '<input type="text" class="form-control input-sm tpl-e-validation-msg" '
                + 'value="' + escAttr(validationMsg) + '" placeholder="Error shown on invalid input"/></div>';
            html += '<div class="col-sm-4"><label class="small text-muted">API Mapping <i class="fa fa-globe text-info"></i></label>'
                + '<select class="form-control input-sm tpl-e-api-mapping" style="font-family:monospace">'
                + buildApiMappingOptions(apiMapping)
                + '</select></div>';
            html += '</div>';
            html += '</td></tr>';
            return html;
        }

        /**
         * Build <option> list for the API Mapping dropdown.
         */
        function buildApiMappingOptions(current) {
            var paths = getApiMappingPaths();
            // Ensure an empty "-- None --" entry is always available.
            if (paths.indexOf('') === -1) {
                paths = [''].concat(paths);
            }
            var html = '';
            paths.forEach(function (p) {
                var label = p === '' ? '-- None --' : p;
                var sel = (p === (current || '')) ? ' selected' : '';
                html += '<option value="' + escAttr(p) + '"' + sel + '>' + escHtml(label) + '</option>';
            });
            return html;
        }

        function toggleExtraFields($row) {
            var type = $row.find('.tpl-e-type').val();
            var $extra = $row.next('.venue-tpl-editing-extra');
            // Extras row is always visible — only the per-type irrelevant
            // sub-fields are hidden. Default, description, validation and
            // API mapping are meaningful for every field type. For type=
            // checkbox the Default control swaps to a real checkbox.
            $extra.show();
            var isCheckbox = (type === 'checkbox');
            $extra.find('.tpl-e-default-text-wrap').toggle(!isCheckbox);
            $extra.find('.tpl-e-default-checkbox-wrap').toggle(isCheckbox);
            if (type === 'select') {
                $extra.find('.tpl-e-options-wrap').show();
                $extra.find('.tpl-e-placeholder-wrap').hide();
            } else if (isCheckbox) {
                $extra.find('.tpl-e-options-wrap').hide();
                $extra.find('.tpl-e-placeholder-wrap').hide();
            } else {
                $extra.find('.tpl-e-options-wrap').hide();
                $extra.find('.tpl-e-placeholder-wrap').show();
            }
        }

        // Add Config button — append new editable row
        $(document).on('click', '#venue-tpl-add', function () {
            // Only allow one add row at a time
            if ($('#venue-tpl-table .venue-tpl-editing[data-new]').length) return;
            var html = buildEditableRow({});
            var $rows = $(html);
            $rows.first().attr('data-new', '1');
            $('#venue-tpl-table tbody').append($rows);
            var $row = $rows.first();
            $row.find('.tpl-e-type').val('text');
            toggleExtraFields($row);
            $row.find('.tpl-e-key').focus();
        });

        // Edit button — convert read-only row to editable
        $(document).on('click', '.venue-tpl-edit', function (e) {
            e.preventDefault();
            var $row = $(this).closest('tr');
            var data = $row.data();
            var html = buildEditableRow(data);
            var $newRows = $(html);
            $row.after($newRows).hide().addClass('venue-tpl-original');
            var $editRow = $newRows.first();
            $editRow.find('.tpl-e-type').val(data.type);
            toggleExtraFields($editRow);
        });

        // Cancel button
        $(document).on('click', '.venue-tpl-cancel', function (e) {
            e.preventDefault();
            var $editRow = $(this).closest('tr.venue-tpl-editing');
            var $extraRow = $editRow.next('.venue-tpl-editing-extra');
            var $original = $editRow.prev('.venue-tpl-original');
            if ($original.length) {
                $original.show().removeClass('venue-tpl-original');
            }
            $extraRow.remove();
            $editRow.remove();
        });

        // Type change in editable row — toggle extra fields
        $(document).on('change', '.tpl-e-type', function () {
            var $row = $(this).closest('tr.venue-tpl-editing');
            toggleExtraFields($row);
        });

        // Save button — AJAX submit
        $(document).on('click', '.venue-tpl-save', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var $row = $btn.closest('tr.venue-tpl-editing');
            var $extra = $row.next('.venue-tpl-editing-extra');
            var key = $row.find('.tpl-e-key').val().toUpperCase();
            var label = $row.find('.tpl-e-label').val();
            if (!key || !label) {
                BootstrapDialog.alert({
                    title: T.validation,
                    message: T.keyLabelRequired,
                    type: BootstrapDialog.TYPE_WARNING
                });
                return;
            }
            var postData = {
                templateID: $btn.data('id') || '',
                tplKey: key,
                tplLabel: label,
                tplType: $row.find('.tpl-e-type').val(),
                tplGroup: getRowGroup($row),
                // For checkbox templates the default is a boolean; serialise
                // as the string 'true' (matches existing seed conventions)
                // when checked, empty string otherwise.
                tplDefault: ($row.find('.tpl-e-type').val() === 'checkbox'
                    ? ($extra.find('.tpl-e-default-checkbox').is(':checked') ? 'true' : '')
                    : $extra.find('.tpl-e-default').val()),
                tplPlaceholder: $extra.find('.tpl-e-placeholder').val(),
                tplOptions: $extra.find('.tpl-e-options').val(),
                tplRequired: $row.find('.tpl-e-required').is(':checked') ? '1' : '',
                tplDescription: $extra.find('.tpl-e-description').val(),
                tplValidation: $extra.find('.tpl-e-validation').val(),
                tplValidationMsg: $extra.find('.tpl-e-validation-msg').val(),
                tplApiMapping: $extra.find('.tpl-e-api-mapping').val(),
                tplOrder: '0'
            };
            $btn.prop('disabled', true);
            $.ajax({
                url: '?node=venue&sub=templates',
                type: 'post',
                data: postData,
                dataType: 'json',
                success: function (data) {
                    if (data.error) {
                        BootstrapDialog.alert({
                            title: data.title || T.error,
                            message: data.error,
                            type: BootstrapDialog.TYPE_WARNING
                        });
                        $btn.prop('disabled', false);
                    } else {
                        location.reload();
                    }
                },
                error: function () {
                    BootstrapDialog.alert({
                        title: T.error,
                        message: T.requestFailed,
                        type: BootstrapDialog.TYPE_DANGER
                    });
                    $btn.prop('disabled', false);
                }
            });
        });

        // Delete button
        $(document).on('click', '.venue-tpl-delete', function (e) {
            e.preventDefault();
            var $row = $(this).closest('tr.venue-tpl-row');
            var id = $row.data('id');
            BootstrapDialog.confirm({
                title: 'Delete Config',
                message: T.confirmDeleteConfig,
                type: BootstrapDialog.TYPE_WARNING,
                btnOKLabel: 'Delete',
                btnOKClass: 'btn-danger',
                callback: function (result) {
                    if (!result) return;
                    $.ajax({
                        url: '?node=venue&sub=deletetemplate&templateid=' + id,
                        type: 'get',
                        dataType: 'json',
                        success: function (data) {
                            if (data.error) {
                                BootstrapDialog.alert({
                                    title: data.title || T.error,
                                    message: data.error,
                                    type: BootstrapDialog.TYPE_WARNING
                                });
                            } else {
                                $row.fadeOut(300, function () { $(this).remove(); });
                            }
                        }
                    });
                }
            });
        });
        // === Template editor (drag-drop reorder, group headers as drop targets) ===
        var dragSrcRow = null;
        var dragIsGroup = false;

        // Helper: determine which group a row belongs to (nearest preceding group header)
        function getRowGroup($row) {
            var $prev = $row.prevAll('.venue-tpl-group-header').first();
            return $prev.length ? $prev.data('group') || '' : '';
        }

        // Helper: when dragging a group, the effective drop target is always a group
        // header. If the cursor is over a child row, snap to that row's group header.
        function effectiveTarget($target) {
            if (dragIsGroup && !$target.hasClass('venue-tpl-group-header')) {
                var $header = $target.prevAll('.venue-tpl-group-header').first();
                return $header.length ? $header : $target;
            }
            return $target;
        }

        $(document).on('dragstart', '.venue-tpl-row, .venue-tpl-group-header', function (e) {
            dragSrcRow = this;
            dragIsGroup = $(this).hasClass('venue-tpl-group-header');
            $(this).addClass('venue-tpl-dragging');
            e.originalEvent.dataTransfer.effectAllowed = 'move';
            e.originalEvent.dataTransfer.setData('text/plain', '');
        });

        $(document).on('dragover', '.venue-tpl-row, .venue-tpl-group-header', function (e) {
            e.preventDefault();
            e.originalEvent.dataTransfer.dropEffect = 'move';
            var $tbody = $(this).closest('tbody');
            $tbody.find('.venue-tpl-drop-above,.venue-tpl-drop-below,.venue-row-drop-target')
                .removeClass('venue-tpl-drop-above venue-tpl-drop-below venue-row-drop-target');
            var $indicator = effectiveTarget($(this));
            if (!$indicator.length || $indicator[0] === dragSrcRow) return;
            // For groups, indicator should reflect placement relative to whole group block
            var rect = $indicator[0].getBoundingClientRect();
            var midY = rect.top + rect.height / 2;
            // When dragging a group, use the cursor relative to the *target group block*
            // (header + its children) so the above/below choice is intuitive.
            if (dragIsGroup) {
                var $block = $indicator.add($indicator.nextUntil('.venue-tpl-group-header'));
                var first = $block.first()[0].getBoundingClientRect();
                var last = $block.last()[0].getBoundingClientRect();
                midY = (first.top + last.bottom) / 2;
            }
            if (e.originalEvent.clientY < midY) {
                $indicator.addClass('venue-tpl-drop-above venue-row-drop-target');
            } else {
                $indicator.addClass('venue-tpl-drop-below venue-row-drop-target');
            }
        });

        $(document).on('dragleave', '.venue-tpl-row, .venue-tpl-group-header', function () {
            $(this).removeClass('venue-tpl-drop-above venue-tpl-drop-below venue-row-drop-target');
        });

        $(document).on('drop', '.venue-tpl-row, .venue-tpl-group-header', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var $tbody = $(this).closest('tbody');
            $tbody.find('.venue-tpl-drop-above,.venue-tpl-drop-below,.venue-row-drop-target')
                .removeClass('venue-tpl-drop-above venue-tpl-drop-below venue-row-drop-target');
            if (dragSrcRow === this) return;
            var $src = $(dragSrcRow);
            var $target = effectiveTarget($(this));
            if (!$target.length || $target[0] === dragSrcRow) return;

            if (dragIsGroup) {
                // Moving a group header — move it and all its children, only ever
                // relative to another group block (never inside one).
                var $block = $src.add($src.nextUntil('.venue-tpl-group-header'));
                var $targetBlock = $target.add($target.nextUntil('.venue-tpl-group-header'));
                // Don't drop a group onto itself
                if ($targetBlock.is($src)) return;
                var first = $targetBlock.first()[0].getBoundingClientRect();
                var last = $targetBlock.last()[0].getBoundingClientRect();
                var midY = (first.top + last.bottom) / 2;
                if (e.originalEvent.clientY < midY) {
                    $block.insertBefore($targetBlock.first());
                } else {
                    $block.insertAfter($targetBlock.last());
                }
            } else {
                // Moving a config row — drops relative to whatever row/header is under cursor
                var rect = this.getBoundingClientRect();
                var midY2 = rect.top + rect.height / 2;
                if (e.originalEvent.clientY < midY2) {
                    $src.insertBefore($(this));
                } else {
                    $src.insertAfter($(this));
                }
            }
            saveNewOrder();
        });

        $(document).on('dragend', '.venue-tpl-row, .venue-tpl-group-header', function () {
            $(this).removeClass('venue-tpl-dragging');
            $('#venue-tpl-table tbody').find('.venue-tpl-drop-above,.venue-tpl-drop-below,.venue-row-drop-target')
                .removeClass('venue-tpl-drop-above venue-tpl-drop-below venue-row-drop-target');
            dragSrcRow = null;
            dragIsGroup = false;
        });

        function saveNewOrder() {
            var ids = [];
            var groups = {};
            var currentGroup = '';
            $('#venue-tpl-table tbody').children('tr').each(function () {
                var $tr = $(this);
                if ($tr.hasClass('venue-tpl-group-header')) {
                    currentGroup = $tr.data('group') || '';
                } else if ($tr.hasClass('venue-tpl-row')) {
                    var id = $tr.data('id');
                    ids.push(id);
                    groups[id] = currentGroup;
                }
            });
            $.ajax({
                url: '?node=venue&sub=reordertemplates',
                type: 'post',
                data: { order: ids.join(','), groups: JSON.stringify(groups) },
                dataType: 'json'
            });
        }

        // --- Group Management ---

        // Reseed defaults (idempotent) — two buttons:
        //   #venue-tpl-reseed           = add missing only
        //   #venue-tpl-reseed-overwrite = also overwrite existing rows
        $(document).on('click', '#venue-tpl-reseed,#venue-tpl-reseed-overwrite', function () {
            var overwrite = this.id === 'venue-tpl-reseed-overwrite';
            var confirmMsg = overwrite
                ? 'Reset every default template to the canonical values? Any local edits to default rows will be overwritten. Custom rows you added are not affected.'
                : 'Insert any missing default templates? Existing rows are not modified.';
            BootstrapDialog.confirm({
                title: overwrite ? 'Reset All Defaults' : 'Add Missing Defaults',
                message: confirmMsg,
                type: overwrite ? BootstrapDialog.TYPE_WARNING : BootstrapDialog.TYPE_INFO,
                btnOKLabel: overwrite ? 'Reset' : 'Add',
                btnOKClass: overwrite ? 'btn-warning' : 'btn-success',
                callback: function (ok) {
                    if (!ok) return;
                    $.ajax({
                        url: '?node=venue&sub=reseedtemplates',
                        method: 'POST',
                        data: { overwrite: overwrite ? '1' : '0' },
                        dataType: 'json'
                    }).done(function (resp) {
                        if (resp && resp.error) {
                            BootstrapDialog.alert({
                                title: resp.title || 'Error',
                                message: resp.error,
                                type: BootstrapDialog.TYPE_DANGER
                            });
                            return;
                        }
                        BootstrapDialog.alert({
                            title: resp.title || 'Done',
                            message: resp.msg || 'Reseed complete',
                            type: BootstrapDialog.TYPE_SUCCESS,
                            callback: function () { window.location.reload(); }
                        });
                    }).fail(function (xhr) {
                        BootstrapDialog.alert({
                            title: 'Reseed Failed',
                            message: 'HTTP ' + xhr.status,
                            type: BootstrapDialog.TYPE_DANGER
                        });
                    });
                }
            });
        });

        // Add Group button
        $(document).on('click', '#venue-tpl-add-group', function () {
            BootstrapDialog.show({
                title: 'Add Group',
                message: '<input type="text" class="form-control" id="venue-new-group-name" placeholder="Group name" autofocus/>',
                buttons: [{
                    label: 'Cancel',
                    action: function (d) { d.close(); }
                }, {
                    label: 'Add',
                    cssClass: 'btn-success',
                    action: function (d) {
                        var name = $.trim(d.getModalBody().find('#venue-new-group-name').val());
                        if (!name) return;
                        var html = '<tr class="venue-tpl-group-header" draggable="true" data-group="' + escAttr(name) + '">'
                            + '<td style="cursor:move;color:#999;text-align:center"><i class="fa fa-bars"></i></td>'
                            + '<td colspan="4" style="background:#eaf4fb;font-weight:600;padding:8px 12px">'
                            + '<i class="fa fa-folder-open-o text-info"></i> ' + escHtml(name) + '</td>'
                            + '<td style="background:#eaf4fb">'
                            + '<div class="btn-group">'
                            + '<button class="btn btn-default btn-xs venue-tpl-group-edit" title="Rename">'
                            + '<i class="fa fa-pencil"></i></button>'
                            + '<button class="btn btn-danger btn-xs venue-tpl-group-delete" title="Remove Group">'
                            + '<i class="fa fa-trash"></i></button>'
                            + '</div></td></tr>';
                        $('#venue-tpl-table tbody').append(html);
                        d.close();
                    }
                }],
                onshown: function (d) {
                    d.getModalBody().find('#venue-new-group-name').focus();
                }
            });
        });

        // Rename Group
        $(document).on('click', '.venue-tpl-group-edit', function (e) {
            e.preventDefault();
            var $header = $(this).closest('.venue-tpl-group-header');
            var oldName = $header.data('group');
            BootstrapDialog.show({
                title: 'Rename Group',
                message: '<input type="text" class="form-control" id="venue-rename-group" value="' + escAttr(oldName) + '"/>',
                buttons: [{
                    label: 'Cancel',
                    action: function (d) { d.close(); }
                }, {
                    label: 'Save',
                    cssClass: 'btn-success',
                    action: function (d) {
                        var newName = $.trim(d.getModalBody().find('#venue-rename-group').val());
                        if (!newName) return;
                        $header.data('group', newName).attr('data-group', newName);
                        $header.find('td[colspan]').html(
                            '<i class="fa fa-folder-open-o text-info"></i> ' + escHtml(newName)
                        );
                        saveNewOrder();
                        d.close();
                    }
                }],
                onshown: function (d) {
                    d.getModalBody().find('#venue-rename-group').select();
                }
            });
        });

        // Delete Group — ungroups its configs (moves them before the header, then removes the header)
        $(document).on('click', '.venue-tpl-group-delete', function (e) {
            e.preventDefault();
            var $header = $(this).closest('.venue-tpl-group-header');
            var groupName = $header.data('group');
            BootstrapDialog.confirm({
                title: 'Remove Group',
                message: 'Remove group "' + escHtml(groupName) + '"? Configs will be ungrouped (not deleted).',
                type: BootstrapDialog.TYPE_WARNING,
                btnOKLabel: 'Remove',
                btnOKClass: 'btn-danger',
                callback: function (result) {
                    if (!result) return;
                    var $children = $header.nextUntil('.venue-tpl-group-header');
                    $children.insertBefore($header);
                    $header.remove();
                    saveNewOrder();
                }
            });
        });

        // Inject drag-and-drop styles
        $('<style>').text(
            '.venue-tpl-row, .venue-tpl-group-header { transition: transform 0.1s ease; }'
            + '.venue-tpl-dragging { opacity: 0.4; }'
            + '.venue-tpl-drop-above { box-shadow: 0 -2px 0 0 #5bc0de; }'
            + '.venue-tpl-drop-below { box-shadow: 0 2px 0 0 #5bc0de; }'
            + '.venue-tpl-handle:hover { color: #333 !important; }'
            + '.venue-tpl-group-header { cursor: move; }'
            + '.venue-tpl-group-header td { border-top: 2px solid #d9edf7 !important; }'
        ).appendTo('head');

        // Password field toggle visibility
        $(document).on('click', '.venue-toggle-password', function () {
            var $target = $($(this).data('target'));
            if ($target.attr('type') === 'password') {
                $target.attr('type', 'text');
                $(this).find('i').removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                $target.attr('type', 'password');
                $(this).find('i').removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });

        // --- Inline regex validation on venue config form ---
        $(document).on('blur change', '.venue-config-form input, .venue-config-form select', function () {
            var $input = $(this);
            var $group = $input.closest('.form-group');
            var pattern = $group.data('validation');
            if (!pattern) return;
            var val = $input.val();
            // Skip validation on empty non-required fields
            if (!val && !$input.prop('required')) {
                $group.removeClass('has-error').find('.venue-validation-error').remove();
                return;
            }
            var re = new RegExp(pattern);
            if (val && !re.test(val)) {
                if (!$group.hasClass('has-error')) {
                    var msg = $group.data('validation-msg') || 'Invalid format';
                    $group.addClass('has-error');
                    $input.closest('.col-xs-6').append(
                        '<span class="help-block venue-validation-error text-danger" style="font-size:12px">'
                        + '<i class="fa fa-exclamation-circle"></i> ' + msg + '</span>'
                    );
                }
            } else {
                $group.removeClass('has-error').find('.venue-validation-error').remove();
            }
        });

        // Block config form submission if validation errors exist
        $(document).on('submit', '.venue-config-form', function (e) {
            var $form = $(this);
            // Trigger validation on all fields
            $form.find('.form-group[data-validation]').each(function () {
                $(this).find('input, select').trigger('blur');
            });
            if ($form.find('.has-error').length) {
                e.preventDefault();
                BootstrapDialog.alert({
                    title: 'Validation Error',
                    message: T.validationFixFields,
                    type: BootstrapDialog.TYPE_WARNING
                });
                return false;
            }
            e.preventDefault();
            submitVenueForm($form, false);
        });

        // --- Venue API Association Picker (General tab) ---
        $(document).on('click', '.venue-api-pick', function () {
            var $assoc = $(this).closest('.venue-api-assoc');
            var currentRegion = $assoc.data('region') || '';
            var currentVenueId = $assoc.data('venue-id') || '';

            var body = '<div class="venue-api-picker">'
                + '<div class="row" style="margin-bottom:12px">'
                + '<div class="col-sm-4">'
                + '<label class="small text-muted">Region</label>'
                + '<select class="form-control input-sm venue-lookup-region">'
                + '<option value="">-- Select Region --</option>'
                + '<optgroup label="Production">'
                + '<option value="uk"' + (currentRegion === 'uk' ? ' selected' : '') + '>UK</option>'
                + '<option value="us"' + (currentRegion === 'us' ? ' selected' : '') + '>US</option>'
                + '</optgroup>'
                + '<optgroup label="Development">'
                + '<option value="uk-dev"' + (currentRegion === 'uk-dev' ? ' selected' : '') + '>UK Dev</option>'
                + '<option value="us-dev"' + (currentRegion === 'us-dev' ? ' selected' : '') + '>US Dev</option>'
                + '</optgroup>'
                + '</select></div>'
                + '<div class="col-sm-8">'
                + '<label class="small text-muted">Venue</label>'
                + '<select class="form-control input-sm venue-lookup-venue" disabled>'
                + '<option value="">-- Select venue --</option>'
                + '</select></div>'
                + '</div>'
                + '<div class="venue-lookup-props" style="display:none">'
                + '<p class="text-muted small"><i class="fa fa-info-circle"></i> '
                + 'The FOG venue name will be set to the selected venue\'s name. '
                + 'Config fields with an API Mapping will auto-populate their defaults.</p>'
                + '<table class="table table-condensed table-striped" style="font-size:12px;max-height:300px;overflow-y:auto">'
                + '<thead><tr><th>Property</th><th>Value</th></tr></thead>'
                + '<tbody class="venue-lookup-tbody"></tbody></table>'
                + '</div>'
                + '<div class="venue-lookup-loading text-center text-muted" style="display:none">'
                + '<i class="fa fa-spinner fa-spin"></i> ' + escHtml(T.loading) + '</div>'

            BootstrapDialog.show({
                title: '<i class="fa fa-globe"></i> Select F1 Arcade Venue',
                message: $(body),
                size: BootstrapDialog.SIZE_WIDE,
                closable: true,
                buttons: [{
                    label: T.remove,
                    cssClass: 'btn-warning',
                    action: function (dlg) {
                        $assoc.find('input[name="apiRegion"]').val('');
                        $assoc.find('input[name="apiVenueRef"]').val('');
                        $assoc.data('region', '').data('venue-id', '');
                        // Reset the card to unassociated state
                        $assoc.css({ 'border-color': '#bce8f1', 'background': '#d9edf7' });
                        $assoc.html(
                            '<div style="text-align:center">'
                            + '<p class="text-muted" style="margin:0 0 8px"><i class="fa fa-globe"></i> '
                            + 'Link this FOG venue to an F1 Arcade venue to auto-populate config defaults</p>'
                            + '<button type="button" class="btn btn-info btn-sm venue-api-pick">'
                            + '<i class="fa fa-link"></i> ' + escHtml(T.select) + ' F1 Arcade Venue</button></div>'
                            + '<input type="hidden" name="apiRegion" value=""/>'
                            + '<input type="hidden" name="apiVenueRef" value=""/>'
                        );
                        dlg.close();
                    }
                }, {
                    label: T.select,
                    cssClass: 'btn-info venue-api-confirm',
                    action: function (dlg) {
                        var $btn = dlg.getButton('select-btn');
                        var $picker = dlg.getModalBody().find('.venue-api-picker');
                        var $venueSelect = $picker.find('.venue-lookup-venue');
                        var region = $picker.find('.venue-lookup-region').val();
                        var idx = $venueSelect.val();
                        if (!region || idx === '') {
                            BootstrapDialog.alert({ title: T.selectVenueTitle, message: T.selectVenueMsg, type: BootstrapDialog.TYPE_WARNING });
                            return;
                        }
                        var venues = $venueSelect.data('venues') || [];
                        var venue = venues[parseInt(idx, 10)];
                        if (!venue) return;
                        if ($btn) { $btn.disable().spin(); }
                        // Update hidden inputs
                        $assoc.find('input[name="apiRegion"]').val(region);
                        $assoc.find('input[name="apiVenueRef"]').val(venue.id);
                        $assoc.data('region', region).data('venue-id', venue.id);
                        // Update the card to associated state
                        var regionLabel = region.indexOf('-dev') !== -1
                            ? region.replace('-dev', '').toUpperCase() + ' DEV'
                            : region.toUpperCase();
                        var city = (venue.address && venue.address.city) ? venue.address.city : '';
                        $assoc.css({ 'border-color': '#3c763d', 'background': '#dff0d8' });
                        $assoc.html(
                            '<div style="display:flex;align-items:center;gap:12px">'
                            + '<div style="flex:1">'
                            + '<span class="label label-success" style="font-size:11px">' + escHtml(regionLabel) + '</span> '
                            + '<strong style="font-size:16px">' + escHtml(venue.name) + '</strong>'
                            + (venue.reference ? ' <code style="font-size:11px;color:#666">' + escHtml(venue.reference) + '</code>' : '')
                            + (city ? '<br><small class="text-muted">' + escHtml(city) + '</small>' : '')
                            + '</div>'
                            + '<button type="button" class="btn btn-default btn-sm venue-api-pick">'
                            + '<i class="fa fa-pencil"></i> ' + escHtml(T.change) + '</button></div>'
                            + '<input type="hidden" name="apiRegion" value="' + escAttr(region) + '"/>'
                            + '<input type="hidden" name="apiVenueRef" value="' + escAttr(venue.id) + '"/>'
                        );
                        dlg.close();
                    },
                    id: 'select-btn'
                }, {
                    label: T.cancel,
                }]
            });

            // Auto-load if region is pre-selected
            if (currentRegion) {
                setTimeout(function () {
                    $('.venue-lookup-region').trigger('change');
                }, 200);
            }
        });

        // Region change — fetch venues
        $(document).on('change', '.venue-lookup-region', function () {
            var region = $(this).val();
            var $picker = $(this).closest('.venue-api-picker');
            var $venueSelect = $picker.find('.venue-lookup-venue');
            var $loading = $picker.find('.venue-lookup-loading');
            var $props = $picker.find('.venue-lookup-props');
            $props.hide();
            $venueSelect.html('<option value="">-- Select venue --</option>').prop('disabled', true);
            if (!region) return;
            $loading.show();
            $.ajax({
                url: '?node=venue&sub=venuelookup&region=' + region,
                type: 'get',
                dataType: 'json',
                success: function (resp) {
                    $loading.hide();
                    if (resp.error) {
                        BootstrapDialog.alert({ title: T.error, message: resp.error, type: BootstrapDialog.TYPE_DANGER });
                        return;
                    }
                    var venues = resp.venues || [];
                    $venueSelect.data('venues', venues);
                    venues.forEach(function (v, i) {
                        var label = v.name + (v.address && v.address.city ? ' (' + v.address.city + ')' : '');
                        $venueSelect.append('<option value="' + i + '">' + escHtml(label) + '</option>');
                    });
                    $venueSelect.prop('disabled', false);
                    // Auto-select current venue if editing
                    var currentId = $('.venue-api-assoc').data('venue-id');
                    if (currentId) {
                        venues.forEach(function (v, i) {
                            if (v.id === currentId) {
                                $venueSelect.val(i).trigger('change');
                            }
                        });
                    }
                },
                error: function () {
                    $loading.hide();
                    BootstrapDialog.alert({ title: T.error, message: T.failedToReachApi, type: BootstrapDialog.TYPE_DANGER });
                }
            });
        });

        // Venue selected — show properties
        $(document).on('change', '.venue-lookup-venue', function () {
            var idx = $(this).val();
            var $picker = $(this).closest('.venue-api-picker');
            var $props = $picker.find('.venue-lookup-props');
            var $tbody = $picker.find('.venue-lookup-tbody');
            if (idx === '') { $props.hide(); return; }
            var venues = $(this).data('venues') || [];
            var venue = venues[parseInt(idx, 10)];
            if (!venue) return;
            $tbody.empty();
            var flat = flattenObj(venue);
            Object.keys(flat).forEach(function (key) {
                var val = String(flat[key]);
                $tbody.append(
                    '<tr><td><code>' + escHtml(key) + '</code></td>'
                    + '<td style="word-break:break-all;max-width:400px">' + escHtml(val) + '</td></tr>'
                );
            });
            $props.show();
        });

        // Helper: flatten nested object to dot-notation keys
        function flattenObj(obj, prefix) {
            var result = {};
            prefix = prefix || '';
            for (var k in obj) {
                if (!obj.hasOwnProperty(k)) continue;
                var fullKey = prefix ? prefix + '.' + k : k;
                if (obj[k] !== null && typeof obj[k] === 'object' && !Array.isArray(obj[k])) {
                    var nested = flattenObj(obj[k], fullKey);
                    for (var nk in nested) { result[nk] = nested[nk]; }
                } else if (Array.isArray(obj[k])) {
                    result[fullKey] = obj[k].join(', ');
                } else {
                    result[fullKey] = obj[k];
                }
            }
            return result;
        }

        // === Sync config (preview + apply) ===
        $(document).on('click', '.venue-sync-config', function () {
            var $btn = $(this);
            var venueId = $btn.data('venue-id');
            $btn.prop('disabled', true).find('i').removeClass('fa-refresh').addClass('fa-spinner fa-spin');
            $.ajax({
                url: '?node=venue&sub=syncconfig&id=' + venueId,
                type: 'get',
                dataType: 'json'
            }).always(function () {
                $btn.prop('disabled', false).find('i').removeClass('fa-spinner fa-spin').addClass('fa-refresh');
            }).done(function (resp) {
                if (resp.error) {
                    BootstrapDialog.alert({ title: resp.title || T.error, message: resp.error, type: BootstrapDialog.TYPE_WARNING });
                    return;
                }
                var changes = resp.changes || [];
                var apiHash = resp.apiHash || '';
                if (changes.length === 0) {
                    BootstrapDialog.alert({
                        title: T.upToDateTitle,
                        message: T.upToDate,
                        type: BootstrapDialog.TYPE_SUCCESS
                    });
                    return;
                }
                // Build diff table with checkboxes
                var html = '<p class="text-muted small">The following fields differ from the API. Select which to overwrite:</p>';
                html += '<table class="table table-condensed table-striped" style="font-size:12px">';
                html += '<thead><tr><th style="width:30px"><input type="checkbox" class="sync-select-all" checked/></th>'
                    + '<th>Field</th><th>Current</th><th><i class="fa fa-arrow-right"></i></th><th>API Value</th></tr></thead><tbody>';
                changes.forEach(function (c) {
                    var displayApi = c.type === 'password' ? '********' : escHtml(c.api);
                    var displayCurrent = c.type === 'password' ? '********' : escHtml(c.current);
                    html += '<tr>'
                        + '<td><input type="checkbox" class="sync-item-check" data-key="' + escAttr(c.key) + '" checked/></td>'
                        + '<td><strong>' + escHtml(c.label) + '</strong><br><code style="font-size:10px">' + escHtml(c.key) + '</code></td>'
                        + '<td><span class="text-danger">' + displayCurrent + '</span></td>'
                        + '<td><i class="fa fa-arrow-right text-muted"></i></td>'
                        + '<td><span class="text-success">' + displayApi + '</span></td>'
                        + '</tr>';
                });
                html += '</tbody></table>';

                BootstrapDialog.show({
                    title: '<i class="fa fa-refresh"></i> Sync Config from API',
                    message: $(html),
                    size: BootstrapDialog.SIZE_WIDE,
                    closable: true,
                    buttons: [{
                        label: T.applySelected,
                        cssClass: 'btn-info',
                        action: function (dlg) {
                            var keys = [];
                            dlg.getModalBody().find('.sync-item-check:checked').each(function () {
                                keys.push($(this).data('key'));
                            });
                            if (keys.length === 0) {
                                BootstrapDialog.alert({ title: T.nothingSelectedTitle, message: T.nothingSelected, type: BootstrapDialog.TYPE_WARNING });
                                return;
                            }
                            var $applyBtn = dlg.getButton('apply-btn');
                            $applyBtn.disable().spin();
                            $.ajax({
                                url: '?node=venue&sub=syncconfig&id=' + venueId,
                                type: 'post',
                                data: { keys: JSON.stringify(keys), apiHash: apiHash },
                                dataType: 'json'
                            }).done(function (applyResp) {
                                if (applyResp.error) {
                                    // Surface stale-data errors clearly so the user knows to retry
                                    var isStale = /API data has changed/i.test(String(applyResp.error));
                                    $applyBtn.enable().stopSpin();
                                    BootstrapDialog.alert({
                                        title: applyResp.title || T.error,
                                        message: isStale ? T.apiDataChanged : applyResp.error,
                                        type: BootstrapDialog.TYPE_WARNING,
                                        callback: function () {
                                            if (isStale) { dlg.close(); }
                                        }
                                    });
                                } else {
                                    dlg.close();
                                    BootstrapDialog.show({
                                        title: applyResp.title || T.done,
                                        message: applyResp.msg,
                                        type: BootstrapDialog.TYPE_SUCCESS,
                                        onshown: function (d) { setTimeout(function () { d.close(); }, 2000); },
                                        onhidden: function () { location.reload(); }
                                    });
                                }
                            }).fail(function () {
                                $applyBtn.enable().stopSpin();
                                BootstrapDialog.alert({ title: T.error, message: T.failedToApply, type: BootstrapDialog.TYPE_DANGER });
                            });
                        },
                        id: 'apply-btn'
                    }, {
                        label: T.cancel,
                        action: function (dlg) { dlg.close(); }
                    }]
                });
            }).fail(function () {
                BootstrapDialog.alert({ title: T.error, message: T.failedToReachServer, type: BootstrapDialog.TYPE_DANGER });
            });
        });

        // Select-all toggle for sync dialog
        $(document).on('change', '.sync-select-all', function () {
            var checked = $(this).prop('checked');
            $(this).closest('table').find('.sync-item-check').prop('checked', checked);
        });

        // === Form submission (delete / membership / list-page bulk actions) ===
        // Intercept membership form submit (Re-associate on edit page)
        $(document).on('submit', '.venue-membership-form', function (e) {
            e.preventDefault();
            submitVenueForm($(this), false);
        });
        // Intercept single-venue delete form submit
        $(document).on('submit', '.venue-delete-form', function (e) {
            e.preventDefault();
            var $form = $(this);
            BootstrapDialog.confirm({
                title: T.confirmTitle,
                message: T.confirmDeleteVenue,
                type: BootstrapDialog.TYPE_WARNING,
                btnOKLabel: 'Delete',
                btnOKClass: 'btn-danger',
                callback: function (result) {
                    if (!result) return;
                    $.ajax({
                        url: $form.attr('action'),
                        type: 'post',
                        data: $form.serialize(),
                        dataType: 'json',
                        success: function (data) {
                            var type = data.error
                                ? BootstrapDialog.TYPE_WARNING
                                : BootstrapDialog.TYPE_SUCCESS;
                            var msg = data.error || data.msg;
                            BootstrapDialog.show({
                                title: data.error ? T.deleteFail : T.deleteSuccess,
                                message: msg,
                                type: type,
                                onshown: function (dialogRef) {
                                    bootstrapdialogopen = setTimeout(function () {
                                        dialogRef.close();
                                    }, data.error ? 5000 : 2000);
                                },
                                onhidden: function (dialogRef) {
                                    clearTimeout(bootstrapdialogopen);
                                    if (!data.error) {
                                        window.location = '?node=venue';
                                    }
                                }
                            });
                        }
                    });
                }
            });
        });
        // Toggle storage group select when "create new" is checked
        $('#createSG').on('change', function () {
            if ($(this).is(':checked')) {
                $('#sgSelectWrap').hide();
            } else {
                $('#sgSelectWrap').show();
            }
        }).trigger('change');
        // Toggle host group select when "create new" is checked
        $('#createHG').on('change', function () {
            if ($(this).is(':checked')) {
                $('#hgSelectWrap').hide();
            } else {
                $('#hgSelectWrap').show();
            }
        }).trigger('change');
        // Show/hide venue action boxes based on checkbox selection
        function updateVenueActions() {
            var checked = $('input.toggle-action:checked');
            if (checked.length > 0) {
                $('.venue-actions').show();
            } else {
                $('.venue-actions').hide();
            }
        }
        $(document).on('change', 'input.toggle-action, .toggle-checkboxAction', function () {
            updateVenueActions();
        });
        updateVenueActions();
        // Collect selected venue IDs before form submit
        function getSelectedVenueIDs() {
            var ids = [];
            $('input.toggle-action:checked').each(function () {
                ids.push($(this).val());
            });
            return ids.join(',');
        }
        // Re-associate form via AJAX (list page)
        $(document).on('submit', '.venue-reassociate-form', function (e) {
            e.preventDefault();
            var $form = $(this);
            $form.find('input[name="venueIDArray"]').val(getSelectedVenueIDs());
            $.ajax({
                url: $form.attr('action'),
                type: 'post',
                data: $form.serialize(),
                dataType: 'json',
                success: function (data) {
                    var type = data.error
                        ? BootstrapDialog.TYPE_WARNING
                        : BootstrapDialog.TYPE_SUCCESS;
                    BootstrapDialog.show({
                        title: data.title || 'Result',
                        message: data.error || data.msg,
                        type: type,
                        onshown: function (dialogRef) {
                            setTimeout(function () { dialogRef.close(); },
                                data.error ? 5000 : 3000);
                        },
                        onhidden: function () {
                            if (!data.error) location.reload();
                        }
                    });
                }
            });
        });
        // Delete multi form via AJAX (list page)
        $(document).on('submit', '.venue-deletemulti-form', function (e) {
            e.preventDefault();
            var $form = $(this);
            $form.find('input[name="venueIDArray"]').val(getSelectedVenueIDs());
            BootstrapDialog.confirm({
                title: T.confirmTitle,
                message: T.confirmDeleteVenues,
                type: BootstrapDialog.TYPE_WARNING,
                btnOKLabel: 'Delete',
                btnOKClass: 'btn-danger',
                callback: function (result) {
                    if (!result) return;
                    $.ajax({
                        url: $form.attr('action'),
                        type: 'post',
                        data: $form.serialize(),
                        dataType: 'json',
                        success: function (data) {
                            var type = data.error
                                ? BootstrapDialog.TYPE_WARNING
                                : BootstrapDialog.TYPE_SUCCESS;
                            BootstrapDialog.show({
                                title: data.title || 'Result',
                                message: data.error || data.msg,
                                type: type,
                                onshown: function (dialogRef) {
                                    setTimeout(function () { dialogRef.close(); },
                                        data.error ? 5000 : 2000);
                                },
                                onhidden: function () {
                                    if (!data.error) location.reload();
                                }
                            });
                        }
                    });
                }
            });
        });
    }); // end $(document).ready

})(jQuery);

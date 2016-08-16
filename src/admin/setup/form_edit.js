/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

$(document).ready(function() {
    var esc = function(str) {
        var entityMap = {
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': '&quot;',
            "'": '&#39;',
            "/": '&#x2F;'
        };
        
        return String(str).replace(/[&<>"'\/]/g, function (s) {
            return entityMap[s];
        });
    };
    $('.sortable').sortable({ handle: '.handle' });
    
    var rand_string = function(len) {
        var str = '';
        var alpha = 'abcdefghijklmnopqrstuvwxyz';
        for (var i = 0; i < len; ++i) {
            str += alpha.charAt(Math.floor(Math.random() * alpha.length));
        }
        return str;
    };
    
    var attach_item_properties = function(div) {
        $(div).click(function() {
            var name = $(this).find('input[name*=cols]').val();
            var label = $(this).find('input[name*=label]').val();
            var mandatory = $(this).find('input[name*=mandatory]').val();
            var force_mandatory = $(this).find('input[name*=force_mand]').val();
            var applies = ',' + $(this).find('input[name*=apply]').val() + ',';
            var $container = $(this).closest('form').find('.item-edit');
            var id = rand_string(8);
            var html = '<input type="hidden" name="col" value=":name">';
            html += '<p><strong></strong></p>' +
                '<p class="label"><label for="' + id + '">Label</label></p>' +
                '<p class="input"><input type="text" name="label" id="' + id + '"></p>' +
                '<fieldset><legend>Applies to</legend>' +
                '<p><label><input type="checkbox" name="apply[]" value="add">Add</label> ' +
                '<label><input type="checkbox" name="apply[]" value="edit">Edit<br><span>Normal</span></label> ' +
                '<label><input type="checkbox" name="apply[]" value="edit-view">Edit<br><span>View only</span></label></fieldset>' +
                '<p><label><input type="checkbox" name="mandatory" value="1">Mandatory</label></p>' +
                '<p><input type="button" value="Apply"></p>';
            $container.html(html);
            $container.find('input[name="label"]').val(label);
            $container.find('strong').text('Properties for ' + name);
            $container.find('input[type=checkbox]').each(function() {
                if (applies.match(',' + $(this).val() + ',')) {
                    $(this).attr('checked', 'checked');
                }
            });
            $container.find('input[name=mandatory]').each(function() {
                if (mandatory == '1') $(this).attr('checked', 'checked');
                if (force_mandatory == '1') {
                    $(this).attr('checked', 'checked');
                    $(this).attr('disabled', 'disabled');
                }
            });
            $container.find('input[type=button]').click(function() {
                var $div = $(this).closest('div');
                var name = $div.find('input[type=hidden]').val();
                var label = $div.find('input[name=label]').val();
                var apply = '';
                var mandatory = '';
                $div.find('input[type=checkbox][name*=apply]:checked').each(function() {
                    if (apply.length > 0) apply += ',';
                    apply += $(this).val();
                });
                if (apply.match('edit,edit-view')) {
                    apply = apply.replace('edit,', '');
                }
                $div.find('input[name=mandatory]:checked').each(function() {
                    mandatory = '1';
                });
                
                $(div).find('input[name*=labels]').val(label);
                $(div).find('input[name*=apply]').val(apply);
                $(div).find('input[name*=mandatory]').val(mandatory);
                $div.html('<p>Applied :)</p>');
                window.setTimeout(function() {
                    if ($div.html().match(/:\)/)) {
                        $div.addClass('display-none');
                    }
                }, 800);
            });
            $container.removeClass('display-none');
        });
        
        // prevent loading attributes box when sorting or deleting
        $(div).find('.handle').click(function() {
            return false;
        });
        $(div).find('.delete').click(function() {
            var $div = $(this).closest('div').remove();
            return false;
        });
    };
    
    $('input.faux')
    .click(function() {
        var $select = $(this).closest('p').find('select');
        var name = $select.val();
        if (!name) return false;
        
        var type = $select.find('option:selected').attr('data-class');
        var force_mand = $select.find('option:selected').attr('data-mandatory');
        var $form = $(this).closest('form');
        var $container = $form.find('.sortable');
        $container.append('<div><input type="hidden" name="cols[]" value=":name"><input type="hidden" name="labels[]" value=""><input type="hidden" name="apply[]" value="add,edit"><input type="hidden" name="mandatory[]" value=":mand"><input type="hidden" name="force_mand[]" value=":mand">:name <span class="type">(:type)</span><span class="handle">[===]</span><span class="delete">[DEL]</span></div>'.replace(/:name/g, esc(name)).replace(/:type/g, esc(type)).replace(/:mand/g, esc(force_mand)));
        var div = $container.children().last();
        attach_item_properties(div);
        div.click();
        
        return false;
    })
    .closest('form').find('.sortable div').each(function() {
        attach_item_properties(this);
    });
    
    $('input.submit').click(function() {
        $(this).closest('form').find('.item-edit').addClass('display-none');
        $(this).closest('form').submit();
    });
});

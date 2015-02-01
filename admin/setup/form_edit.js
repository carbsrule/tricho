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
            var applies = ',' + $(this).find('input[name*=apply]').val() + ',';
            var $container = $(this).closest('form').find('.item-edit');
            var id = rand_string(8);
            var html = '<input type="hidden" name="col" value=":name">';
            html += '<p><strong>Properties for :name</strong></p>' +
                '<p class="label"><label for="' + id + '">Label</label></p>' +
                '<p class="input"><input type="text" name="label" value=":label" id="' + id + '"></p>' +
                '<fieldset><legend>Applies to</legend>' +
                '<p><label><input type="checkbox" name="apply[]" value="add">Add</label> ' +
                '<label><input type="checkbox" name="apply[]" value="edit">Edit<br><span>Normal</span></label> ' +
                '<label><input type="checkbox" name="apply[]" value="edit-view">Edit<br><span>View only</span></label></fieldset>' +
                '<p><input type="button" value="Apply"></p>';
            html = html.replace(/:name/g, esc(name));
            html = html.replace(/:label/g, esc(label));
            $container.html(html);
            $container.find('input[type=checkbox]').each(function() {
                if (applies.match(',' + $(this).val() + ',')) {
                    $(this).attr('checked', 'checked');
                }
            });
            $container.find('input[type=button]').click(function() {
                var $div = $(this).closest('div');
                var name = $div.find('input[type=hidden]').val();
                var label = $div.find('input[name=label]').val();
                var apply = '';
                $div.find('input[type=checkbox]:checked').each(function() {
                    if (apply.length > 0) apply += ',';
                    apply += $(this).val();
                });
                if (apply.match('edit,edit-view')) {
                    apply = apply.replace('edit,', '');
                }
                
                var $fieldset = $(this).closest('form').find('fieldset');
                var selector = 'input[type=hidden][value=' + name + ']';
                var $field_div = $fieldset.find(selector).parent();
                $field_div.find('input[name*=labels]').val(esc(label));
                $field_div.find('input[name*=apply]').val(esc(apply));
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
        var $form = $(this).closest('form');
        var $container = $form.find('.sortable');
        $container.append('<div><input type="hidden" name="cols[]" value=":name"><input type="hidden" name="labels[]" value=""><input type="hidden" name="apply[]" value="add,edit">:name <span class="type">(:type)</span><span class="handle">[===]</span><span class="delete">[DEL]</span></div>'.replace(/:name/g, esc(name)).replace(/:type/g, esc(type)));
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

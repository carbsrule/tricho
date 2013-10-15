/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

function has_class (node, class_name) {
    var match = node.className.match ('(^| )' + class_name + '($| )');
    return match;
}

function add_class (node, class_name) {
    if (node.className == '') {
        node.className = class_name;
    } else if (!has_class (node, class_name)) {
        node.className += ' ' + class_name;
    }
}

function rem_class (node, class_name) {
    node.className = node.className.replace (new RegExp('^ *' + class_name + ' *'), '');
    node.className = node.className.replace (new RegExp(' +' + class_name + ' +', 'g'), ' ');
    node.className = node.className.replace (new RegExp(' +' + class_name + '$'), '');
}

/**
 * Edit form helper for updating existing repository details
 *
 * @licstart
 * Copyright Mike Kelly UAL m.f.kelly@arts.ac.uk
 *
 * The JavaScript code in this page is free software: you can
 * redistribute it and/or modify it under the terms of the GNU
 * General Public License (GNU GPL) as published by the Free Software
 * Foundation, either version 3 of the License, or (at your option)
 * any later version.  The code is distributed WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU GPL for more details.
 *
 * As additional permission under GNU GPL version 3 section 7, you
 * may distribute non-source (e.g., minimized or compacted) forms of
 * that code without the copy of the GNU GPL normally required by
 * section 4, provided you include this license notice and a URL
 * through which recipients can access the Corresponding Source.
 * @licend
 */

(function( EditReposManager, $, undefined ) {

    function init() {
        loadCollections();
    }

    // retrieve collections for selected repository
    function loadCollections(e) {
        var pd = {'repository': $('#editrepository_editingrepository').val()};
        sendjsonrequest(config['wwwroot'] + 'export/sword/swordcollections.json.php', pd, 'POST', function (data) {
            if (data != false) {
                rewriteCollectionOptions(data);
            } else {
                $('#messages').html('<div class="error">Couldn\'t connect to the repository to retrieve collections</div>');
            }
        });
    };

    function rewriteCollectionOptions(data) {
        var collectionSelection = document.getElementById('editrepository_defaultcollection');
        collectionSelection.options.length = 0;
        var offset = 0;

        for (i=0; i<data.data.data.length; i++) {
            for (c=0; c<data.data.data[i].collections.length; c++) {
                var coll = data.data.data[i].collections[c];
                var wspacetitle = data.data.data[i].workspacetitle;
                collectionSelection.options[c + offset] = new Option(coll.sac_colltitle + ' (' + wspacetitle + ')', coll.sac_href[0], false, false);
                if (data.data['defaultcollection'] && data.data['defaultcollection'] == coll.sac_href[0]) {
                    $('#editrepository_setdefaultcollection').prop('checked', true);
                    collectionSelection.selectedIndex = c+offset;
                }
            } 
            offset += data.data.data[i].collections.length;
        }
    }

    function resetCollections() {
        var collectionSelection = document.getElementById('editrepository_defaultcollection');
        collectionSelection.options.length = 0;
    }

    $(document).ready(function() {
        init();
    });

}( window.EditReposManager = window.EditReposManager || {}, jQuery ));
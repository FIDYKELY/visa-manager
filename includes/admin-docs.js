jQuery(function($){
  var frame, $list = $('#visa-doc-list');

  // Ajouter des documents
  $(document).on('click', '.add-doc', function(e){
    e.preventDefault();
    if (frame) { frame.open(); return; }

    frame = wp.media({
      title: 'Ajouter des documents',
      button: { text: 'Ajouter' },
      multiple: true
    });

    frame.on('select', function(){
      var selection = frame.state().get('selection').toJSON();

      selection.forEach(function(att){
        var idx  = $list.find('p').length,
            url  = att.url,
            name = att.filename;

        $list.append(
          '<p data-index="'+ idx +'">' +
            '<a href="'+ url +'" target="_blank">'+ name +'</a> ' +
            '<button type="button" class="button-link remove-doc" data-index="'+ idx +'">ï¿½</button>' +
            '<input type="hidden" name="visa_documents[]" value="'+ url +'">' +
          '</p>'
        );
      });
    });

    frame.open();
  });

  // Supprimer un document
  $(document).on('click', '.remove-doc', function(e){
    e.preventDefault();
    // 1. Confirmation
    if ( ! window.confirm('Voulez-vous vraiment supprimer ce document ?') ) {
      return;
    }

    $(this).closest('p').remove();

    // Réindexation facultative
    $('#visa-doc-list p').each(function(i){
      $(this).attr('data-index', i)
             .find('.remove-doc').attr('data-index', i);
    });
  });
});

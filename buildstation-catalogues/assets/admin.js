(function($){
  function openMedia(target,type){
    const frame=wp.media({title:type==='image'?'Choose brand logo':'Choose catalogue PDF',button:{text:'Use this file'},multiple:false,library:{type:type}});
    frame.on('select',function(){
      const item=frame.state().get('selection').first().toJSON();
      $('#bsc_'+target+'_id').val(item.id);
      if(target==='logo') $('#bsc_logo_preview').html('<img src="'+(item.sizes?.medium?.url||item.url)+'" alt="">');
      else $('#bsc_pdf_preview').html('<a href="'+item.url+'" target="_blank" rel="noopener">'+item.filename+'</a>');
    });
    frame.open();
  }
  $(document).on('click','.bsc-select-media',function(){openMedia($(this).data('target'),$(this).data('type'));});
  $(document).on('click','.bsc-remove-media',function(){const target=$(this).data('target');$('#bsc_'+target+'_id').val('');$('#bsc_'+target+'_preview').empty();});
})(jQuery);

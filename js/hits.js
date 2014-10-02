(function($){
	$(document).ready(function(){

		//Update post hits counter
        if( typeof cyb_hits_data.postID !== 'undefined' && cyb_hits_data.postID != "0") {

			var update_hits = function(post_id){

                $.getJSON(cyb_hits_data.ajax_url,{
					action : 'uptdate_hits',
					postID : post_id
                });
                
			};
			
            update_hits(cyb_hits_data.postID);

        }

	
	});
})(jQuery);
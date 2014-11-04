cyb-hits
========

Hits counter for WordPress posts. It uses Ajax, so it is compatible with static page cache. Option to use WP_SHORTINIT method in a direct Ajax request.

The hits counter is stored as `hits` meta field. You can get the number of hits with:

    $post_hits = get_post_meta( $post_id, 'hits', true );

<?php
/**
 * Helper functions for the Staff Picks plugin.
 */
class Staff_Picks_Helper {
  public function __construct() {
    $data_file = file_get_contents(dirname( __FILE__ ) . '/post-type-data.json');
    $this->data = json_decode($data_file, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
      trigger_error('Could not parse invalid JSON');
    }
  }

  /**
   * Returns a suitable title for an archive or taxonomy page.
   */
  public function get_title() {
    $name = $this->data['post_type_data']['labels']['name'];

    if (is_post_type_archive($this->data['post_type'])) {
      return $name;
    }

    foreach($this->data['taxonomies'] as $taxonomy) {
      if (is_tax($taxonomy['taxonomy_name'])) {
        return single_term_title($name . ' ' . $taxonomy['taxonomy_data']['labels']['singular_label'] . ': ', False);
      }

    }

    return Null;
  }

  /**
   * Get a list of term ids from the specified taxonomy associated associated with a given taxonomy term
   */
  public function get_limited_taxonomy_ids($args) {
  	global $wpdb;
    $query = $wpdb->prepare("
    		SELECT DISTINCT terms2.term_id as tag_id
    		FROM
          {$wpdb->prefix}posts as p1
    			LEFT JOIN {$wpdb->prefix}term_relationships as r1 ON p1.ID = r1.object_ID
    			LEFT JOIN {$wpdb->prefix}term_taxonomy as t1 ON r1.term_taxonomy_id = t1.term_taxonomy_id
    			LEFT JOIN {$wpdb->prefix}terms as terms1 ON t1.term_id = terms1.term_id,

          {$wpdb->prefix}posts as p2
    			LEFT JOIN {$wpdb->prefix}term_relationships as r2 ON p2.ID = r2.object_ID
    			LEFT JOIN {$wpdb->prefix}term_taxonomy as t2 ON r2.term_taxonomy_id = t2.term_taxonomy_id
    			LEFT JOIN {$wpdb->prefix}terms as terms2 ON t2.term_id = terms2.term_id
    		WHERE
    			t1.taxonomy = '%s' AND p1.post_status = 'publish' AND terms1.term_id = '%s' AND
    			t2.taxonomy = '%s' AND p2.post_status = 'publish'
    			AND p1.ID = p2.ID
    	",
      array(
        $args['term']['taxonomy'],
        $args['term']['term_id'],
        $args['taxonomy']
      )
    );
  	$category_ids = $wpdb->get_col($query);
  	return $category_ids;
  }
}

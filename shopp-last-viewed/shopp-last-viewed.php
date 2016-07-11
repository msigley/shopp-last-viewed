<?php 
/*
Plugin Name: Shopp Last Viewed Pages
Plugin URI: http://github.com/msigley/
Description: Tracks the last viewed Shopp collections, categories, and pages.
Version: 1.0.0
Author: Matthew Sigley
Author URI: http://github.com/msigley/
License: GPLv3
*/
class ShoppLastViewed {
	public $last_collection_url;
	public $last_collection_name;
	
	function __construct () {
		$this->last_collection_url = '';
		$this->last_collection_name = '';
		$this->collection_history = array();
		
		add_action('shopp_init', array($this, 'update_session_vars'));
		add_action('wp', array($this, 'track_last_viewed'));
		
		//Theme API
		add_filter('shopp_themeapi_catalog_lastcollectionname', 
			array($this, 'last_collection_name'), 10, 3);
		add_filter('shopp_themeapi_catalog_lastcollectionurl', 
			array($this, 'last_collection_url'), 10, 3);
		add_filter('shopp_themeapi_catalog_collectionhistory',
		  array($this, 'collection_history'), 10, 3);
		
	}
	
	public function track_last_viewed($wp) {
		global $wp, $post;
		
		global $landing_pages;
		$landing_pages_by_name = array_flip($landing_pages);
		$is_landing_page = !empty($landing_pages[$post->ID]);
		
		//Wipe out history if this isn't a Shopp page
		if( !is_shopp_page() && !$is_landing_page ) {
			$this->last_collection_url = '';
			$this->last_collection_name = '';
			return;
		}
		
		//Build current url
		$current_url = get_bloginfo('url')."/".$wp->request;
		if (!empty($_GET)) $current_url = add_query_arg($_GET,$current_url);
		$current_url = user_trailingslashit($current_url);
		
		if( is_shopp_collection() || is_shopp_taxonomy() ) {
			$Collection = ShoppCollection();
			$this->last_collection_url = $current_url;
			$this->last_collection_name = $Collection->name;
			if(is_shopp_taxonomy()){
				array_push($this->collection_history, 't'.$Collection->term_taxonomy_id);
			} else {
				array_push($this->collection_history, $post->ID);
			}
			if( !empty($landing_pages_by_name[$Collection->name]) )
				$this->last_collection_name = 'All '.$Collection->name;
		} elseif( $is_landing_page ) {
			$this->last_collection_url = $current_url;
			$this->last_collection_name = $landing_pages[$post->ID];
			array_push($this->collection_history, $post->ID);
		}
	}
	
	public function update_session_vars() {
		//Initialize Shopp Session Variables
		ShoppingObject::store('lastViewedCollectionURL', $this->last_collection_url);
		ShoppingObject::store('lastViewedCollectionName', $this->last_collection_name);
		ShoppingObject::store('lastViewedCollectionHistory', $this->collection_history);
	}
	
	public function last_collection_name($result, $options, $O) {
		return $this->last_collection_name;
	}
	
	public function last_collection_url($result, $options, $O) {
		return $this->last_collection_url;
	}
	
	public function collection_history($result, $options, $O) {
		return $this->collection_history;
	}
}

$ShoppLastViewed = new ShoppLastViewed();

	
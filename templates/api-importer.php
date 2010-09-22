<?php
	
	//require_once(EXTENSIONS . '/apiimporter/lib/class.apiimporter.php');
	
	class APIimporter%s extends APIimporter {
		public function __construct(&$parent) {
			parent::__construct($parent);
		}
		
		public function about() {
			return array(
				'name'			=> %s,
				'author'		=> array(
					'name'			=> %s,
					'website'		=> %s,
					'email'			=> %s
				),
				'description'	=> %s,
				'file'			=> __FILE__,
				'created'		=> %s,
				'updated'		=> %s
			);	
		}
		
		public function options() {
			return array(
				'can-update'		=> %s,
				'fields'			=> %s,
				'included-elements'	=> %s,
				'namespaces'		=> %s,
				'source'			=> %s,
				'headers'			=> %s,
				'parameters'		=> %s,
				'section'			=> %s,
				'unique-field'		=> %s,
				'method'			=> %s,
				'text'				=> %s
			);
		}
		
		public function allowEditorToParse() {
			return true;
		}
	}
	
?>

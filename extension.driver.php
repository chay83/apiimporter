<?php
	
	require_once(EXTENSIONS . '/apiimporter/lib/class.apiimporter.php');
	require_once(EXTENSIONS . '/apiimporter/lib/class.apiimportermanager.php');
	
	class Extension_APIimporter extends Extension {
	/*-------------------------------------------------------------------------
		Definition:
	-------------------------------------------------------------------------*/
		
		public function about() {
			return array(
				'name'			=> 'API Importer',
				'version'		=> '0.10',
				'release-date'	=> '2010-01-10',
				'author'		=> array(
					'name'			=> 'Chay Palmer'
				),
				'description' => 'Import data from api documents directly into Symphony.'
			);
		}
				
		public function getSubscribedDelegates() {
			return array(
				array(
					'page'		=> '/backend/',
					'delegate'	=> 'InitaliseAdminPageHead',
					'callback'	=> 'initializeAdmin'
				)
			);
		}
		
		public function fetchNavigation() {
			return array(
				array(
					'location'	=> 'Blueprints',
					'name'		=> 'API Importers',
					'link'		=> '/importers/'
				)
			);
		}
		
		public function initializeAdmin($context) {
			$page = $context['parent']->Page;
			
			//$page->addStylesheetToHead(URL . '/extensions/issuemanager/assets/form.css', 'screen');
		}
		
	/*-------------------------------------------------------------------------
		Utilities:
	-------------------------------------------------------------------------*/
		
		public function countAPIimporters() {
			$xim = new APIimporterManager($this->_Parent);
			$results = 0;
			
			foreach ($xim->listAll() as $about) {
				$importer = $xim->create($about['handle']);
				
				if (!$importer->allowEditorToParse()) continue;
				
				$results++;
			}
			
			return $results;
		}
		
		public function getAPIimporters($column = 'name', $direction = 'asc', $page = 1, $length = 100000) {
			$xim = new APIimporterManager($this->_Parent);
			$results = array();
			
			foreach ($xim->listAll() as $about) {
				$importer = $xim->create($about['handle']);
				
				if (!$importer->allowEditorToParse()) continue;
				
				$data = $importer->options();
				$data['about'] = $about;
				$results[] = $data;
			}
			
			// Sorting:
			if ($column == 'name') {
				usort($results, array($this, 'getAPIimportersSortByName'));
			}
			
			else if ($column == 'description') {
				usort($results, array($this, 'getAPIimportersSortByDescription'));
			}
			
			else if ($column == 'url') {
				usort($results, array($this, 'getAPIimportersSortByURL'));
			}
			
			else if ($column == 'elements') {
				usort($results, array($this, 'getAPIimportersSortByElements'));
			}
			
			else if ($column == 'modified') {
				usort($results, array($this, 'getAPIimportersSortByModified'));
			}
			
			else if ($column == 'author') {
				usort($results, array($this, 'getAPIimportersSortByAuthor'));
			}
			
			if ($direction != 'asc') {
				$results = array_reverse($results);
			}
			
			// Pagination:
			$results = array_slice($results, ($page - 1) * $length, $length);
			
			return $results;
		}
		
		protected function getAPIimportersSortByName($a, $b) {
			return strcmp($a['about']['name'], $b['about']['name']);
		}
		
		protected function getAPIimportersSortByDescription($a, $b) {
			return strcmp($a['about']['description'], $b['about']['description']);
		}
		
		protected function getAPIimportersSortByURL($a, $b) {
			return strcmp($a['source'], $b['source']);
		}
		
		protected function getAPIimportersSortByElements($a, $b) {
			return strcmp($a['included-elements'], $b['included-elements']);
		}
		
		protected function getAPIimportersSortByModified($a, $b) {
			return strtotime($a['about']['updated']) > strtotime($b['about']['updated']);
		}
		
		protected function getAPIimportersSortByAuthor($a, $b) {
			return strcmp($a['about']['author']['name'], $b['about']['author']['name']);
		}
		
		public function getAPIimporter($name) {
			$xim = new APIimporterManager($this->_Parent);
			$importer = $xim->create($name);
			
			$data = $importer->options();
			$data['about'] = $importer->about();
			
			return $data;
		}
		
		public function setAPIimporter(&$name, &$error, $new) {
			$template = file_get_contents(EXTENSIONS . '/apiimporter/templates/api-importer.php');
			$old = (!empty($name) ? $this->getAPIimporter($name) : array());
			
			// Update author:
			if (!isset($new['about']['author'])) {
				$new['about']['author'] = array(
					'name'		=> $this->_Parent->Author->getFullName(),
					'website'	=> URL,
					'email'		=> $this->_Parent->Author->get('email')
				);
			}
			
			// Update dates:
			$new['about']['created'] = DateTimeObj::getGMT('c', @strtotime($new['about']['created']));
			$new['about']['updated'] = DateTimeObj::getGMT('c');
			
			// New name:
			$name = str_replace('-', '', Lang::createHandle($new['about']['name']));
			
			// Create new file:
			if (strpos(@$new['about']['file'], dirname(__FILE__)) === 0) {
				$rootdir = dirname(__FILE__);
			}
			
			else {
				$rootdir = WORKSPACE;
			}
			
			$filemode = $this->_Parent->Configuration->get('write_mode', 'file');
			$filename = sprintf(
				'%s/api-importers/api-importer.%s.php',
				$rootdir, $name
			);
			$dirmode = $this->_Parent->Configuration->get('write_mode', 'directory');
			$dirname = dirname($filename);
			
			// Make sure the directory exists:
			if (!is_dir($dirname)) {
				General::realiseDirectory($dirname, $dirmode);
			}
			
			// Make sure new file can be written:
			if (!is_writable($dirname) or (file_exists($filename) and !is_writable($filename))) {
				$error = __('Cannot save formatter, path is not writable.');
				return false;
			}
			
			$filedata = sprintf(
				$template,
				
				// Class name:
				str_replace(
					' ', '',
					ucwords(
						str_replace('-', ' ', Lang::createHandle($new['about']['name']))
					)
				),
				
				// Name:
				var_export($new['about']['name'], true),
				
				// Author:
				var_export($new['about']['author']['name'], true),
				
				// Website:
				var_export($new['about']['author']['website'], true),
				
				// Email:
				var_export($new['about']['author']['email'], true),
				
				// Description:
				var_export($new['about']['description'], true),
				
				// Dates:
				var_export($new['about']['created'], true),
				var_export($new['about']['updated'], true),
				
				// Options:
				var_export($new['can-update'], true),
				var_export($new['fields'], true),
				var_export($new['included-elements'], true),
				var_export($new['namespaces'], true),
				var_export($new['source'], true),
				var_export($new['headers'], true),
				var_export($new['parameters'], true),
				var_export($new['section'], true),
				var_export($new['unique-field'], true),
				var_export($new['method'], true),
				var_export($new['text'], true)
			);
			
			// Write file to disk:
			General::writeFile($filename, $filedata, $filemode);
			
			// Cleanup old file:
			if (
				$filename != @$old['about']['html-formatter-file']
				and file_exists($filename) and @file_exists($old['about']['html-formatter-file'])
			) {
				General::deleteFile($old['about']['html-formatter-file']);
			}
			
			return true;
		}
		
		public function truncateValue($value) {
			$max_length = $this->_Parent->Configuration->get('cell_truncation_length', 'symphony');
			$max_length = ($max_length ? $max_length : 75);
			
			$value = General::sanitize($value);
			$value = (strlen($value) <= $max_length ? $value : substr($value, 0, $max_length) . '...');
			
			return $value;
		}
	}
	
?>

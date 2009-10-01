<?php
	
	include_once(TOOLKIT . '/class.sectionmanager.php');
	include_once(TOOLKIT . '/class.textformattermanager.php');
	include_once(TOOLKIT . '/class.entry.php');
	
	Class EntryManager{
		
		var $_Parent;
		var $formatterManager;
		var $sectionManager;
		var $fieldManager;
		var $_fetchSortField;
		var $_fetchSortDirection;
		
		public function __construct($parent){
			$this->_Parent = $parent;
			
			$this->formatterManager = new TextformatterManager($this->_Parent);		
			$this->sectionManager = new SectionManager($this->_Parent);		
			$this->fieldManager = new FieldManager($this->_Parent);
			
			$this->_fetchSortField = NULL;
			$this->_fetchSortDirection = NULL;
			
		}
		
		public function create(){	
			$obj = new Entry($this);
			return $obj;
		}
		
		public function delete($entries){
			
			if(!is_array($entries))	$entries = array($entries);
	
			foreach($entries as $id){
				$e = $this->fetch($id);
				
				if(!is_object($e[0])) continue;
				
				foreach($e[0]->getData() as $field_id => $data){
					$field = $this->fieldManager->fetch($field_id);
					$field->entryDataCleanup($id, $data);
				}
				
				$section = $this->sectionManager->fetch($e[0]->get('section_id'));
				
				if(!is_object($section)) continue;
				
				$associated_sections = $section->fetchAssociatedSections();
				
				if(is_array($associated_sections) && !empty($associated_sections)){
					foreach($associated_sections as $key => $as){
						
						if($as['cascading_deletion'] != 'yes') continue;
						
						$field = $this->fieldManager->fetch($as['child_section_field_id']);

						$search_value = ($associated_sections[$key]['parent_section_field_id'] ? $field->fetchAssociatedEntrySearchValue($e[0]->getData($as['parent_section_field_id'])) : $e[0]->get('id'));

						$associated_entry_ids = $field->fetchAssociatedEntryIDs($search_value);
						
						if(is_array($associated_entry_ids) && !empty($associated_entry_ids)) $this->delete($associated_entry_ids);
						
	%0
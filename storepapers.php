<?php
 
 /**
 *
 * Plugin StorePapers for Joomla! 3.4+
 * Version: 1.5
 *
 * Copyright (C) 2008-2015  Francisco Ruiz (contact@storepapers.com)
 *
 * StorePapers is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 *
 * StorePapers is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

//https://docs.joomla.org/J3.x:Creating_a_search_plugin
//https://docs.joomla.org/Plugin/Events/Content#onContentSearch
 
//Comprobación de seguridad en Joomla!
defined( '_JEXEC' ) or die;
jimport( 'joomla.plugin.plugin' );

class plgSearchStorepapers extends JPlugin{	
	
	/**
	 * Constructor
	 *
	 * @access      protected
	 * @param       object  $subject The object to observe
	 * @param       array   $config  An array that holds the plugin configuration
	 * @since       1.6
	 */
	public function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();
	}
	
	// Define a function to return an array of search areas. Replace 'nameofplugin' with the name of your plugin.
	// Note the value of the array key is normally a language string
	function onContentSearchAreas()
	{
		static $areas = array(
			'storepapers' => 'StorePapers'
		);
		return $areas;
	}

	// The real function has to be created. The database connection should be made. 
	// The function will be closed with an } at the end of the file.
	/**
	 * The sql must return the following fields that are used in a common display
	 * routine: href, title, section, created, text, browsernav
	 *
	 * @param string Target search string
	 * @param string mathcing option, exact|any|all
	 * @param string ordering option, newest|oldest|popular|alpha|category
	 * @param mixed An array if the search it to be restricted to areas, null if search all
	 */
	function onContentSearch( $text, $phrase='', $ordering='', $areas=null ){
		
		$db 	= JFactory::getDBO();
		$user	= JFactory::getUser(); 
		$groups	= implode(',', $user->getAuthorisedViewLevels());		
 
		// If the array is not correct, return it:
		if (is_array( $areas )) {
			if (!array_intersect( $areas, array_keys( $this->onContentSearchAreas() ) )) {
				return array();
			}
		}
 
		// Now retrieve the plugin parameters like this:
		//$nameofparameter = $this->params->get('nameofparameter', defaultsetting );
 
		// Use the PHP function trim to delete spaces in front of or at the back of the searching terms
		$text = trim( $text );
 
		// Return Array when nothing was filled in.
		if ($text == '') {
			return array();
		}
 
		// After this, you have to add the database part. This will be the most difficult part, because this changes per situation.
		// In the coding examples later on you will find some of the examples used by Joomla! 3.1 core Search Plugins.
		//It will look something like this.
		$wheres = array();
		switch ($phrase) {
 
			// Search exact
			case 'exact':
				$text		= $db->Quote( '%'.$db->escape( $text, true ).'%', false );
				$wheres2 	= array();
				$wheres2[] 	= 'LOWER(p.nombre) LIKE '.$text;
				$wheres3 	= array();
				$wheres3[] 	= 'LOWER(p.texto) LIKE '.$text;
				$where 		= '(' . implode( ') OR (', $wheres2 ) . ')';			//Para el título
				$where 		.= ' OR ';
				$where 		.= '(' . implode( ') OR (', $wheres3 ) . ')';			//Para el cuerpo de la publicación
				
				//Para autores
				$whereAutores = '(' . implode( ') OR (', $wheres2 ) . ')';
				break;
 
			// Search all or any
			case 'all':
			case 'any':
 
			// Set default
			default:
				$words 	= explode( ' ', $text );
				$wheres = array();
				$wheres4 = array();
				foreach ($words as $word)
				{
					$word		= $db->Quote( '%'.$db->escape( $word, true ).'%', false );
					$wheres2 	= array();
					$wheres2[] 	= 'LOWER(p.nombre) LIKE '.$word;
					$wheres[] 	= implode( ' OR ', $wheres2 );
					
					$wheres3 	= array();
					$wheres3[] 	= 'LOWER(p.texto) LIKE '.$word;
					$wheres4[] 	= implode( ' OR ', $wheres3 );
				}
				$where = '((' . implode( ($phrase == 'all' ? ') AND (' : ') OR ('), $wheres ) . '))';		//Para el título
				$where .= ' OR ';
				$where .= '((' . implode( ($phrase == 'all' ? ') AND (' : ') OR ('), $wheres4 ) . '))';		//Para el cuerpo de la publicación
				
				//Para autores
				$whereAutores = '(' . implode( ($phrase == 'all' ? ') AND (' : ') OR ('), $wheres2 ) . ')';
				break;
		}
 
		// Ordering of the results
		switch ( $ordering ) {
 
			//Alphabetic, ascending
			case 'alpha':
				$order = 'p.nombre ASC';
				break;
 
			// Oldest first
			case 'oldest':
				$order = 'p.year ASC, p.month ASC';
				break;		
 
			// Newest first
			case 'newest':
				$order = 'p.year DESC, p.month DESC';
				break;
				
			// Popular first
			case 'popular':
 
			// Default setting: alphabetic, ascending
			default:
				$order = 'p.nombre ASC, p.year DESC, p.month DESC';
				break;
		}
		/*
		// Replace nameofplugin
		$section = JText::_( 'Storepapers' );
 
		// The database query; differs per situation! It will look something like this (example from newsfeed search plugin):
		$query	= $this->db->getQuery(true);
		$query->select('a.name AS title, "" AS created, a.link AS text, ' . $case_when."," . $case_when1);
				$query->select($query->concatenate(array($this->db->Quote($section), 'c.title'), " / ").' AS section');
				$query->select('"1" AS browsernav');
				$query->from('#__nameofcomponent AS a');
				$query->innerJoin('#__categories as c ON c.id = a.catid');
				$query->where('('. $where .')' . 'AND a.published IN ('.implode(',', $state).') AND c.published = 1 AND c.access IN ('. $groups .')');
				$query->order($order);
 
		// Set query
		$this->db->setQuery( $query, 0, $limit );
		$rows = $this->db->loadObjectList();
 
		// The 'output' of the displayed link. Again a demonstration from the newsfeed search plugin
		foreach($rows as $key => $row) {
			$rows[$key]->href = 'index.php?option=com_newsfeeds&view=newsfeed&catid='.$row->catslug.'&id='.$row->slug;
		}
		*/
		$db = JFactory::getDBO();
		
		//Esto es para los autores. Solo me quedo con los id de las publicaciones que coincida con el nombre del autor
		$db->setQuery("SELECT ap.idp AS idp
							FROM #__storepapers_autores AS p
							INNER JOIN #__storepapers_autorpubli AS ap ON p.id = ap.ida
							WHERE $whereAutores
								AND p.consultable = 1");
		
		$rows = null;
		$rows = $db->loadObjectList();
		
		//Aqui almaceno las id de las publicaciones para que en la siguiente consulta fuerze a mostrarlo
		$whereAutores = '';
		foreach($rows as $row) {
			$whereAutores .= ' OR p.id = '.$row->idp;
		}
		//Fin		
		
		$db->setQuery("SELECT p.nombre AS pnombre, p.year AS year, p.month AS month, p.id AS idp
							FROM #__storepapers_publicaciones AS p
							INNER JOIN #__storepapers_categorias AS c ON p.idc = c.id
							WHERE $where $whereAutores
								AND p.published = 1
							ORDER BY $order");
		
		$rows = null;
		$rows = $db->loadObjectList();
		
		foreach($rows as $key => $row) {
			$rows[$key]->title		= $row->pnombre;			
			$rows[$key]->catslug	= 0;
			$rows[$key]->slug		= 0;
			$rows[$key]->section	= 0;
			$rows[$key]->browsernav	= 0;
			$rows[$key]->href		= 'index.php?option=com_storepapers&view=search&idp='.$row->idp;
			
			$date = DateTime::createFromFormat("Y-m-d", $row->year."-".$row->month."-01");
			$rows[$key]->created	= $date->getTimestamp();
		}
		
		//Return the search results in an array
		return $rows;
	}
}
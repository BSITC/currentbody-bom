<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Mypagination {
	protected $base_url		= '';
	protected $prefix = '';
	protected $suffix = '';
	protected $total_rows = 0;
	protected $num_links = 2;
	public $per_page = 10;
	public $cur_page = 0;
	protected $use_page_numbers = FALSE;
	protected $first_link = '&lsaquo; First'; 
	protected $next_link = '&gt;';
	protected $prev_link = '&lt;';
	protected $last_link = 'Last &rsaquo;';
	protected $uri_segment = 0;
	protected $full_tag_open = '';
	protected $full_tag_close = '';
	protected $first_tag_open = '';
	protected $first_tag_close = '';
	protected $last_tag_open = '';
	protected $last_tag_close = '';
	protected $first_url = '';
	protected $cur_tag_open = '<a class="paginate_active">';
	protected $cur_tag_close = '</a>';
	protected $next_tag_open = '';
	protected $next_tag_close = '';
	protected $prev_tag_open = '';
	protected $prev_tag_close = '';
	protected $num_tag_open = '';
	protected $num_tag_close = '';
	protected $page_query_string = FALSE;
	protected $query_string_segment = 'per_page';
	protected $display_pages = TRUE;
	protected $_attributes = '';
	protected $_link_types = array();
	protected $reuse_query_string = FALSE;
	protected $use_global_url_suffix = FALSE;
	protected $data_page_attr = 'data-ci-pagination-page';
	protected $CI;

	public function __construct($params = array()){
		$this->CI =& get_instance();
		$this->CI->load->language('pagination');
		foreach (array('first_link', 'next_link', 'prev_link', 'last_link') as $key)
		{
			if (($val = $this->CI->lang->line('pagination_'.$key)) !== FALSE)
			{
				$this->$key = $val;
			}
		}

		$this->initialize($params);
		log_message('info', 'Pagination Class Initialized');
	}

	// --------------------------------------------------------------------

	/**
	 * Initialize Preferences
	 *
	 * @param	array	$params	Initialization parameters
	 * @return	CI_Pagination
	 */
	public function initialize(array $params = array())
	{
		isset($params['attributes']) OR $params['attributes'] = array();
		if (is_array($params['attributes']))
		{
			$this->_parse_attributes($params['attributes']);
			unset($params['attributes']);
		}

		// Deprecated legacy support for the anchor_class option
		// Should be removed in CI 3.1+
		if (isset($params['anchor_class']))
		{
			empty($params['anchor_class']) OR $attributes['class'] = $params['anchor_class'];
			unset($params['anchor_class']);
		}

		foreach ($params as $key => $val)
		{
			if (property_exists($this, $key))
			{
				$this->$key = $val;
			}
		}

		if ($this->CI->config->item('enable_query_strings') === TRUE)
		{
			$this->page_query_string = TRUE;
		}

		if ($this->use_global_url_suffix === TRUE)
		{
			$this->suffix = $this->CI->config->item('url_suffix');
		}

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Generate the pagination links
	 *
	 * @return	string
	 */
	public function create_links()
	{
		// If our item count or per-page total is zero there is no need to continue.
		// Note: DO NOT change the operator to === here!
		if ($this->total_rows == 0 OR $this->per_page == 0)
		{
			return '';
		}

		// Calculate the total number of pages
		$num_pages = (int) ceil($this->total_rows / $this->per_page);

		// Is there only one page? Hm... nothing more to do here then.
		if ($num_pages === 1)
		{
			return '';
		}

		// Check the user defined number of links.
		$this->num_links = (int) $this->num_links;

		if ($this->num_links < 0)
		{
			show_error('Your number of links must be a non-negative number.');
		}

		// Keep any existing query string items.
		// Note: Has nothing to do with any other query string option.
		if ($this->reuse_query_string === TRUE)
		{
			$get = $this->CI->input->get();

			// Unset the controll, method, old-school routing options
			unset($get['c'], $get['m'], $get[$this->query_string_segment]);
		}
		else
		{
			$get = array();
		}

		// Put together our base and first URLs.
		// Note: DO NOT append to the properties as that would break successive calls
		$base_url = trim($this->base_url);
		$first_url = $this->first_url;

		$query_string = '';
		$query_string_sep = (strpos($base_url, '?') === FALSE) ? '?' : '&amp;';

		// Are we using query strings?
		if ($this->page_query_string === TRUE)
		{
			// If a custom first_url hasn't been specified, we'll create one from
			// the base_url, but without the page item.
			if ($first_url === '')
			{
				$first_url = $base_url;

				// If we saved any GET items earlier, make sure they're appended.
				if ( ! empty($get))
				{
					$first_url .= $query_string_sep.http_build_query($get);
				}
			}

			// Add the page segment to the end of the query string, where the
			// page number will be appended.
			$base_url .= $query_string_sep.http_build_query(array_merge($get, array($this->query_string_segment => '')));
		}
		else
		{
			// Standard segment mode.
			// Generate our saved query string to append later after the page number.
			if ( ! empty($get))
			{
				$query_string = $query_string_sep.http_build_query($get);
				$this->suffix .= $query_string;
			}

			// Does the base_url have the query string in it?
			// If we're supposed to save it, remove it so we can append it later.
			if ($this->reuse_query_string === TRUE && ($base_query_pos = strpos($base_url, '?')) !== FALSE)
			{
				$base_url = substr($base_url, 0, $base_query_pos);
			}

			if ($first_url === '')
			{
				$first_url = $base_url.$query_string;
			}

			$base_url = rtrim($base_url, '/').'/';
		}

		// Determine the current page number.
		$base_page = ($this->use_page_numbers) ? 1 : 0;

		// Are we using query strings?
		if ($this->page_query_string === TRUE)
		{
			$this->cur_page = $this->CI->input->get($this->query_string_segment);
		}
		else
		{
			// Default to the last segment number if one hasn't been defined.
			if ($this->uri_segment === 0)
			{
				$this->uri_segment = count($this->CI->uri->segment_array());
			}

			$this->cur_page = $this->CI->uri->segment($this->uri_segment);

			// Remove any specified prefix/suffix from the segment.
			if ($this->prefix !== '' OR $this->suffix !== '')
			{
				$this->cur_page = str_replace(array($this->prefix, $this->suffix), '', $this->cur_page);
			}
		}

		// If something isn't quite right, back to the default base page.
		if ( ! ctype_digit($this->cur_page) OR ($this->use_page_numbers && (int) $this->cur_page === 0))
		{
			$this->cur_page = $base_page;
		}
		else
		{
			// Make sure we're using integers for comparisons later.
			$this->cur_page = (int) $this->cur_page;
		}

		// Is the page number beyond the result range?
		// If so, we show the last page.
		if ($this->use_page_numbers)
		{
			if ($this->cur_page > $num_pages)
			{
				$this->cur_page = $num_pages;
			}
		}
		elseif ($this->cur_page > $this->total_rows)
		{
			$this->cur_page = ($num_pages - 1) * $this->per_page;
		}

		$uri_page_number = $this->cur_page;

		// If we're using offset instead of page numbers, convert it
		// to a page number, so we can generate the surrounding number links.
		if ( ! $this->use_page_numbers)
		{
			$this->cur_page = (int) floor(($this->cur_page/$this->per_page) + 1);
		}

		// Calculate the start and end numbers. These determine
		// which number to start and end the digit links with.
		$start	= (($this->cur_page - $this->num_links) > 0) ? $this->cur_page - ($this->num_links - 1) : 1;
		$end	= (($this->cur_page + $this->num_links) < $num_pages) ? $this->cur_page + $this->num_links : $num_pages;

		// And here we go...
		$output = '';

		// Render the "First" link.
		/* if ($this->first_link !== FALSE && $this->cur_page > ($this->num_links + 1 + ! $this->num_links))
		{
			// Take the general parameters, and squeeze this pagination-page attr in for JS frameworks.
			$attributes = sprintf('%s %s="%d"', $this->_attributes, $this->data_page_attr, 1);

			$output .= $this->first_tag_open.'<a href="'.$first_url.'"'.$attributes.$this->_attr_rel('start').'>'
				.$this->first_link.'</a>'.$this->first_tag_close;
		}
 */
		// Render the "Previous" link.
		if  ($this->first_link !== FALSE AND $this->cur_page != 1)
		{
			$append = $this->suffix;
			$first_url = ($this->first_url == '') ? $this->base_url."1" : $this->first_url;
			$output .= @$this->first_tag_open.'<a class="first" href="'.$first_url.$append.'">'.$this->first_link.'</a>'.@$this->first_tag_close;
		}
		else
		{
			$output .= $this->cur_tag_open.$this->first_link.$this->cur_tag_close;
		}
		
		if ($this->prev_link !== FALSE && $this->cur_page !== 1)
		{
			$i = ($this->use_page_numbers) ? $uri_page_number - 1 : $uri_page_number - $this->per_page;

			$attributes = sprintf('%s %s="%d"', $this->_attributes, $this->data_page_attr, ($this->cur_page - 1));

			if ($i === $base_page)
			{
				// First page
				$append = $this->suffix;
				$output .= $this->prev_tag_open.'<a href="'.$first_url.$append.'"'.$attributes.$this->_attr_rel('prev').'>'
					.$this->prev_link.'</a>'.$this->prev_tag_close;
			}
			else
			{
				$append = $this->prefix.$i.$this->suffix;
				$output .= $this->prev_tag_open.'<a href="'.$base_url.$append.'"'.$attributes.$this->_attr_rel('prev').'>'
					.$this->prev_link.'</a>'.$this->prev_tag_close;
			}

		}
		else
		{
			$output .= $this->cur_tag_open.$this->prev_link.$this->cur_tag_close;
		}
		

		// Render the pages
		if ($this->display_pages !== FALSE)
		{
			// Write the digit links
			for ($loop = $start - 1; $loop <= $end; $loop++)
			{
				$i = ($this->use_page_numbers) ? $loop : ($loop * $this->per_page) - $this->per_page;

				$attributes = sprintf('%s %s="%d"', $this->_attributes, $this->data_page_attr, $loop);

				if ($i >= $base_page)
				{
					if ($this->cur_page === $loop)
					{
						// Current page
						$output .= $this->cur_tag_open.$loop.$this->cur_tag_close;
					}
					elseif ($i === $base_page)
					{
						// First page
						$append = $this->suffix;
						$output .= $this->num_tag_open.'<a href="'.$first_url.$append.'"'.$attributes.$this->_attr_rel('start').'>'
							.$loop.'</a>'.$this->num_tag_close;
					}
					else
					{
						$append = $this->prefix.$i.$this->suffix;
						$output .= $this->num_tag_open.'<a href="'.$base_url.$append.'"'.$attributes.'>'
							.$loop.'</a>'.$this->num_tag_close;
					}
				}
			}
		}

		// Render the "next" link
		if ($this->next_link !== FALSE && $this->cur_page < $num_pages)
		{
			$i = ($this->use_page_numbers) ? $this->cur_page + 1 : $this->cur_page * $this->per_page;

			$attributes = sprintf('%s %s="%d"', $this->_attributes, $this->data_page_attr, $this->cur_page + 1);

			$output .= $this->next_tag_open.'<a href="'.$base_url.$this->prefix.$i.$this->suffix.'"'.$attributes
				.$this->_attr_rel('next').'>'.$this->next_link.'</a>'.$this->next_tag_close;
		}

		// Render the "Last" link
		if ($this->last_link !== FALSE && ($this->cur_page + $this->num_links + ! $this->num_links) < $num_pages)
		{
			$i = ($this->use_page_numbers) ? $num_pages : ($num_pages * $this->per_page) - $this->per_page;

			$attributes = sprintf('%s %s="%d"', $this->_attributes, $this->data_page_attr, $num_pages);

			$output .= $this->last_tag_open.'<a href="'.$base_url.$this->prefix.$i.$this->suffix.'"'.$attributes.'>'
				.$this->last_link.'</a>'.$this->last_tag_close;
		}		
	
  
		
  

		// Kill double slashes. Note: Sometimes we can end up with a double slash
		// in the penultimate link so we'll kill all double slashes.
		$output = preg_replace('#([^:"])//+#', '\\1/', $output);

		// Add the wrapper HTML if exists
		return $this->full_tag_open.$output.$this->full_tag_close;
	}

	// --------------------------------------------------------------------

	/**
	 * Parse attributes
	 *
	 * @param	array	$attributes
	 * @return	void
	 */
	protected function _parse_attributes($attributes)
	{
		isset($attributes['rel']) OR $attributes['rel'] = TRUE;
		$this->_link_types = ($attributes['rel'])
			? array('start' => 'start', 'prev' => 'prev', 'next' => 'next')
			: array();
		unset($attributes['rel']);

		$this->_attributes = '';
		foreach ($attributes as $key => $value)
		{
			$this->_attributes .= ' '.$key.'="'.$value.'"';
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Add "rel" attribute
	 *
	 * @link	http://www.w3.org/TR/html5/links.html#linkTypes
	 * @param	string	$type
	 * @return	string
	 */
	protected function _attr_rel($type)
	{
		if (isset($this->_link_types[$type]))
		{
			unset($this->_link_types[$type]);
			return ' rel="'.$type.'"';
		}

		return '';
	}
	public function getPaginationConfig($baseurl){
		$queryString = '';$status0='';$status1='';$fullyShippedStatus='';
		$perpage = '10';		
		$s = $this->CI->input->get('s');
		$e = $this->CI->input->get('e');
		if((isset($s))&&(isset($e))){
			$queryString .= '?s='.$s.'&e='.$e;
			$perpage = $e;
		}
		else if(isset($s)){
			$queryString .= '?s='.$s;
		}
		else if(isset($e)){
			$queryString .= '?e='.$e;
			$perpage = $e;
		}
		if(@$this->CI->input->get('order')){
			if($queryString){
				$queryString .= "&order=".@$this->CI->input->get('order')."&direction=".@$this->CI->input->get('direction');
			}
			else{ 
				$queryString .= "?order=".@$this->CI->input->get('order')."&direction=".@$this->CI->input->get('direction');
			}
		}
		$method = $this->CI->router->method;
		$methodKey = @array_search($method,$this->CI->uri->segments);
		$page = 0;
		if($methodKey){
			$page =  $this->CI->uri->segment(($methodKey + 1));
			if($page)
			$page = $page - 1;
		}	
		$config = array();
		$config["page"] = $page;		
		$config["base_url"] = $baseurl;		
		$config["per_page"] = $perpage;
		$config["suffix"] = $queryString;
		$config['num_links'] = '3';
		$config['cur_tag_open'] = '&nbsp;<a class="current">';
		$config['cur_tag_close'] = '</a>';
		$config['next_link'] = 'Next';
		$config['prev_link'] = 'Previous';
		$config['first_link'] = "&lt;&lt; First";
		$config['last_link'] = "Last &gt;&gt;";	
		
		$config['use_page_numbers'] = true;	 
		return $config;
	}
	public function getListOrder($order = "id"){
		$direction = @$_REQUEST['direction'];
		$order_browser = (@$_REQUEST['order'])?(@$_REQUEST['order']):'id';
		$order_text = "";
		if( $direction == "ASC" || $direction == "asc" ){
			$direction = "DESC";
		}
		else if( $direction == "DESC" || $direction == "desc" ){
			$direction = "ASC";
		}
		else{
			$direction = "";
		}
		if($order == $order_browser)
			$order_text = ' data-order = "'.$order.'" data-direction = "'.$direction.'"';
		else{
			$order_text = ' data-order = "'.$order.'" data-direction = ""';
		}
		echo $order_text;
		
	}

}

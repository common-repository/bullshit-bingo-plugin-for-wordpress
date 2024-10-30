<?php
// TODO Use nonces in all forms
define('BULLSHITBINGO_ADMIN_LINES', 10);
define('BULLSHITBINGO_ADMIN_STEPS', '5,10,15,20,25,50,100,200');
define('BULLSHITBINGO_ADMIN_FEWPAGES', 3);

class BullshitBingoAdminMenu {
	function BullshitBingoAdminMenu() {
		add_action('admin_menu', array($this, 'add_options_page'), 10, 0);
		// TODO This is not the best place to include the JavaScript, as it will
		//      show on all pagess
		add_action('admin_head', array($this, 'add_header'), 10, 0);
	}

	function add_options_page() {
		add_options_page(__('Bullshit Bingo Settings', 'bullshitbingo'),
			__('Bullshit Bingo', 'bullshitbingo'), 'manage_options',
			'bullshitbingo-adminmenu', array($this, 'options_page'));
	}

	function options_page() {
		global $BullshitBingo;
		global $wpdb;
		
		// Initializations (form field values)
		$input_tag = '';
		$input_buzzword = '';
		$input_url = '';
		$input_active = 1;
		$input_bulkadd_tag = '';
		$input_bulkadd_buzzword = '';
		$input_bulkadd_active = 1;
		
		// Get request variables
		$request_uri = str_replace( '%7E', '~', $_SERVER['REQUEST_URI']);
		$uri = remove_query_arg('bbaction', $request_uri);
		$uri = remove_query_arg('bbsort', $uri);
		$uri = remove_query_arg('bborder', $uri);
		$uri = remove_query_arg('bbpage', $uri);
		$uri = remove_query_arg('bblines', $uri);
		$uri = remove_query_arg('bbtag', $uri);
		$uri = remove_query_arg('bbid', $uri);
		$request_uri = $uri;
		if ((isset($_POST['bbaction'])) && (strlen($_POST['bbaction']) > 0)) {
			$action = $_POST['bbaction'];
		} elseif ((isset($_GET['bbaction'])) && (strlen($_GET['bbaction']) > 0))
		{
			$action = $_GET['bbaction'];
		} else {
			$action = 'manage';
		}
		if ((isset($_POST['bbsort'])) && (strlen($_POST['bbsort']) > 0)) {
			$sort = $_POST['bbsort'];
		} elseif ((isset($_GET['bbsort'])) && (strlen($_GET['bbsort']) > 0))
		{
			$sort = $_GET['bbsort'];
		} else {
			$sort = 'buzzword';
		}
		$request_uri = add_query_arg('bbsort', $sort, $request_uri);
		if ((isset($_POST['bborder'])) && (strlen($_POST['bborder']) > 0)) {
			$order = $_POST['bborder'];
		} elseif ((isset($_GET['bborder'])) && (strlen($_GET['bborder']) > 0))
		{
			$order = $_GET['bborder'];
		} else {
			$order = 'asc';
		}
		$request_uri = add_query_arg('bborder', $order, $request_uri);
		if ((isset($_POST['bbpage'])) && (strlen($_POST['bbpage']) > 0)) {
			$page = $_POST['bbpage'];
		} elseif ((isset($_GET['bbpage'])) && (strlen($_GET['bbpage']) > 0))
		{
			$page = $_GET['bbpage'];
		} else {
			$page = 0;
		}
		$request_uri = add_query_arg('bbpage', $page, $request_uri);
		if ((isset($_POST['bblines'])) && (strlen($_POST['bblines']) > 0)) {
			$lines = $_POST['bblines'];
		} elseif ((isset($_GET['bblines'])) && (strlen($_GET['bblines']) > 0))
		{
			$lines = $_GET['bblines'];
		} else {
			$lines = BULLSHITBINGO_ADMIN_LINES;
		}
		$request_uri = add_query_arg('bblines', $lines, $request_uri);
		if ((isset($_POST['bbtag'])) && (strlen($_POST['bbtag']) > 0)) {
			$tag = $_POST['bbtag'];
		} elseif ((isset($_GET['bbtag'])) && (strlen($_GET['bbtag']) > 0))
		{
			$tag = $_GET['bbtag'];
		} else {
			$tag = ',';
		}
		$request_uri = add_query_arg('bbtag', $tag, $request_uri);
		if ((isset($_POST['bbid'])) && (strlen($_POST['bbid']) > 0)) {
			$id = $_POST['bbid'];
		} elseif ((isset($_GET['bbid'])) && (strlen($_GET['bbid']) > 0))
		{
			$id = $_GET['bbid'];
		} else {
			$id = 0;
		}
		
		// Take neccessary actions
		$error = '';
		$message = '';
		if ($action == 'manage') {
			// When we are in manage mode, we still might have to do bulk
			// actions
			if ((isset($_POST['bullshitbingo_bulk_action']))
			&& ((strlen($_POST['bullshitbingo_bulk_action']) > 0))
			&& (isset($_POST['bullshitbingo_checkbox']))) {
				$bulkaction = $_POST['bullshitbingo_bulk_action'];
				$items = $_POST['bullshitbingo_checkbox'];
				if ((is_array($items)) && (count($items) > 0)) {
					// Prepare the WHERE clause
					$from = $wpdb->prefix . BULLSHITBINGO_DB_TABLE;
					$where = "WHERE ID IN (" . implode(', ', $items) . ")";
					// Construct the query
					$query = '';
					if ($bulkaction == 'delete') {
						$query = "DELETE FROM " . $from . ' ' . $where;
					} elseif ($bulkaction == 'toggle') {
						$query = "UPDATE " . $from . " SET active = 1 - active "
							. $where;
					} elseif (($bulkaction == 'change')
					&& (isset($_POST['bullshitbingo_tag']))) {
						$newtag = $_POST['bullshitbingo_tag'];
						if ($newtag == ',') {
							$newtag = '';
						}
						$query = "UPDATE " . $from . " SET tag = '" . $newtag
							. "' " . $where;
					}
					if (strlen($query) > 0) {
						$result = $wpdb->query($query);
						if ($result === false) {
							$error = __('Database error.', 'bullshitbingo');
						} elseif ($bulkaction == 'delete') {
							$message = sprintf(__('%d buzzword(s) deleted from the database.', 'bullshitbingo'),
								$result);				
						} else {
							$message = sprintf(__('%d buzzword(s) successfully updated.', 'bullshitbingo'),
								$result);				
						}
					}
				}
			}
		} elseif ($action == 'add') {
			// Add a buzzword as entered in the form below the table
			// Next action to take: manage
			$action = 'manage';
			// Verify the nonce
			if ((isset($_POST['bullshitbingo_wpnonce']))
			&& (wp_verify_nonce($_POST['bullshitbingo_wpnonce'],
			'bullshitbingo_submit'))) {
				// Verify input
				// tag field may be left blank
				if ((isset($_POST['bullshitbingo_input_tag']))
				&& (strlen($_POST['bullshitbingo_input_tag']) > 0)) {
					$input_tag = $_POST['bullshitbingo_input_tag'];
				} else {
					$input_tag = '';
				}
				// Strip commas
				$input_tag = str_replace(',', '', $input_tag);
				// buzzword field may not be empty
				if ((!isset($_POST['bullshitbingo_input_buzzword']))
				|| (strlen($_POST['bullshitbingo_input_buzzword']) == 0)) {
					$error = __('Please enter a buzzword', 'bullshitbingo');
				} else {
					$input_buzzword = $_POST['bullshitbingo_input_buzzword'];
				}
				// url field may be left blank
				if ((isset($_POST['bullshitbingo_input_url']))
				&& (strlen($_POST['bullshitbingo_input_url']) > 0)) {
					$input_url = $_POST['bullshitbingo_input_url'];
				} else {
					$input_url = '';
				}
				// active field defaults to 1
				if ((isset($_POST['bullshitbingo_input_active']))
				&& (strlen($_POST['bullshitbingo_input_active']) > 0)) {
					$input_active = $_POST['bullshitbingo_input_active'];
				} else {
					$input_active = 1;
				}
				// If no error occured, attempt to write the buzzword to the
				// database
				if (strlen($error) == 0) {
					$query = "INSERT INTO " . $wpdb->prefix
						. BULLSHITBINGO_DB_TABLE . " (tag, buzzword, ";
					if (strlen($input_url) > 0) {
						$query .= "url, ";
					}
					$query .= "active, inserted) VALUES (%s, %s, "; 
					if (strlen($input_url) > 0) {
						$query .= "%s, ";
					}
					$query .= "%d, NOW())";
					if (strlen($input_url) > 0) {
						$query = $wpdb->prepare($query, $input_tag, $input_buzzword,
							$input_url, (int)$input_active);
					} else {
						$query = $wpdb->prepare($query, $input_tag, $input_buzzword,
							(int)$input_active);
					}
					if ($wpdb->query($query) != 1) {
						$error = sprintf(
							__('The buzzword "%s" already exists within the tag "%s".', 'bullshitbingo'),
							$input_buzzword, $input_tag);
					} else {
						$message = sprintf(
							__('The buzzword "%s" tagged "%s" was successfully added to the database.', 'bullshitbingo'),
							$input_buzzword, $input_tag);
						$input_tag = '';
						$input_buzzword = '';
						$input_url = '';
						$input_active = 1;
					}
				}
			} else {
				$error = __('Insufficient rights to fulfill request.');
			}
		} elseif ($action == 'delete') {
			// Delete a buzzword from the table
			// Next action to take: manage
			$action = 'manage';
			// There must be a positive id
			if ($id > 0) {
				$query = $wpdb->prepare("DELETE FROM " . $wpdb->prefix
					. BULLSHITBINGO_DB_TABLE . " WHERE ID = %d", $id);
				$result = $wpdb->query($query);
				if ($result === false) {
					$error = __('Database error.', 'bullshitbingo');
				} else {
					$message = sprintf(__('%d buzzword(s) deleted from the database.', 'bullshitbingo'),
					$result);
					$id = 0;
				}
			} else {
				$error = __('Nothing to delete.', 'bullshitbingo');
			}
		} elseif ($action == 'edit') {
			// Delete a buzzword from the table
			// Next action to take remains edit for now
			// Determine whether we are displaying the form for the first time
			// or if it was already submitted
			if (isset($_POST['bullshitbingo_wpnonce'])) {
				// The form was submitted
				// TODO This code is similar to the action == add code and thus
				//      might be redundant
				// TODO And by the way, we probably should check the strings
				//      for appropriate length
				// Verify the nonce
				if (wp_verify_nonce($_POST['bullshitbingo_wpnonce'],
				'bullshitbingo_submit')) {
					// nonce verified				
					// Verify input
					// tag field may be left blank
					if ((isset($_POST['bullshitbingo_input_tag']))
					&& (strlen($_POST['bullshitbingo_input_tag']) > 0)) {
						$input_tag = $_POST['bullshitbingo_input_tag'];
					} else {
						$input_tag = '';
					}
					$input_tag = str_replace(',', '', $input_tag);
					// buzzword field may not be empty
					if ((!isset($_POST['bullshitbingo_input_buzzword']))
					|| (strlen($_POST['bullshitbingo_input_buzzword']) == 0)) {
						$error = __('Please enter a buzzword', 'bullshitbingo');
					} else {
						$input_buzzword =
							$_POST['bullshitbingo_input_buzzword'];
					}
					// url field may be left blank
					if ((isset($_POST['bullshitbingo_input_url']))
					&& (strlen($_POST['bullshitbingo_input_url']) > 0)) {
						$input_url = $_POST['bullshitbingo_input_url'];
					} else {
						$input_url = '';
					}
					// active field defaults to 0
					// url field may be left blank
					if ((isset($_POST['bullshitbingo_input_active']))
					&& (strlen($_POST['bullshitbingo_input_active']) > 0)) {
						$input_active = $_POST['bullshitbingo_input_active'];
					} else {
						$input_active = 0;
					}
					// If no error occured, attempt to write the buzzword to the
					// database
					if (strlen($error) == 0) {
						$query = "UPDATE " . $wpdb->prefix
							. BULLSHITBINGO_DB_TABLE . " SET tag = %s, "
							. "buzzword = %s, url = ";
						if (strlen($input_url) > 0) {
							$query .= "%s, ";
						} else {
							$query .= "NULL, ";
						}
						$query .= "active = %d WHERE ID = %d";
						if (strlen($input_url) > 0) {
							$query = $wpdb->prepare($query, $input_tag,
								$input_buzzword, $input_url, $input_active,
								$id);
						} else {
							$query = $wpdb->prepare($query, $input_tag,
								$input_buzzword, $input_active, $id);
						}
						if ($wpdb->query($query) != 1) {
							$error = __('Unable to edit the buzzword.',
								'bullshitbingo');
						} else {
							$message = __(
								'The buzzword was successfully edited.',
								'bullshitbingo');
							$input_tag = '';
							$input_buzzword = '';
							$input_url = '';
							$input_active = 1;
							$action = 'manage';
						}
					}					
				} else {
					$error = __('Insufficient rights to fulfill request.');
					$action = 'manage';
				}
			} else {
				// Initial request on the form
				// Retrieve data and initialize the form
				$query = $wpdb->prepare(
					"SELECT tag, buzzword, url, active FROM " . $wpdb->prefix
						. BULLSHITBINGO_DB_TABLE . " WHERE ID = %d", $id);
				$result = $wpdb->get_results($query, OBJECT);
				$input_tag = $result[0]->tag;
				$input_buzzword = $result[0]->buzzword;
				$input_url = $result[0]->url;
				$input_active = $result[0]->active;
			}
		} elseif ($action == 'bulkadd') {
			// Bulk add buzzwords as entered in the form below the table
			// Next action to take: manage
			$action = 'manage';
			// Verify the nonce
			if ((isset($_POST['bullshitbingo_wpnonce']))
			&& (wp_verify_nonce($_POST['bullshitbingo_wpnonce'],
			'bullshitbingo_bulk_submit'))) {
				// Verify input
				// tag field may be left blank
				if ((isset($_POST['bullshitbingo_bulkadd_tag']))
				&& (strlen($_POST['bullshitbingo_bulkadd_tag']) > 0)) {
					$input_bulkadd_tag = $_POST['bullshitbingo_bulkadd_tag'];
				} else {
					$input_bulkadd_tag = '';
				}
				// Strip commas
				$input_bulkadd_tag = str_replace(',', '', $input_bulkadd_tag);
				// buzzword field may not be empty
				if ((!isset($_POST['bullshitbingo_bulkadd_buzzword']))
				|| (strlen($_POST['bullshitbingo_bulkadd_buzzword']) == 0)) {
					$error = __('Please enter at least one buzzword',
						'bullshitbingo');
				} else {
					$input_bulkadd_buzzword =
						$_POST['bullshitbingo_bulkadd_buzzword'];
				}
				// active field defaults to 1
				if ((isset($_POST['bullshitbingo_bulkadd_active']))
				&& (strlen($_POST['bullshitbingo_bulkadd_active']) > 0)) {
					$input_bulkadd_active =
						$_POST['bullshitbingo_bulkadd_active'];
				} else {
					$input_bulkadd_active = 1;
				}
				// Extract the buzzwords from the textarea
				// PREG_SPLIT_NO_EMPTY should make sure that the array contains
				// no empty strings
				$buzzwords = preg_split("/[\n\r]+/", $input_bulkadd_buzzword,
					-1, PREG_SPLIT_NO_EMPTY);
				$input_bulkadd_buzzword = '';
					
				// If there is no error so far, we attempt to write the
				// buzzwords into the database, one by one. If a write attempt
				// fails, this word will be re-added to the input field.
				if (strlen($error) == 0) {
					foreach ($buzzwords as $word) {
						$query = "INSERT INTO " . $wpdb->prefix
							. BULLSHITBINGO_DB_TABLE
							. " (tag, buzzword, active, inserted) VALUES "
							. "(%s, %s, %d, NOW())";
						$query = $wpdb->prepare($query, $input_bulkadd_tag,
							$word, (int)$input_bulkadd_active);
						if ($wpdb->query($query) != 1) {
							$error .= '<br />' . sprintf(
								__('The buzzword "%s" already exists within the tag "%s".',
								'bullshitbingo'), $word, $input_bulkadd_tag);
							$input_bulkadd_buzzword .= $word . "\r\n";
						} else {
							$message .= '<br />' . sprintf(
								__('The buzzword "%s" tagged "%s" was successfully added to the database.',
								'bullshitbingo'), $word, $input_bulkadd_tag);							
						}
					}
					if (strlen($input_bulkadd_buzzword) == 0) {
						$input_bulkadd_tag = '';
						$input_bulkadd_active = 1;
					}	
				}
			} else {
				$error = __('Insufficient rights to fulfill request.');
			}
		}
		$request_uri = add_query_arg('bbaction', $action, $request_uri);
?>
<!-- Start Bullshit Bingo Plugin for WordPress Output -->
<?php
		if (strlen($error) > 0) {
			if (substr($error, 0, 6) == '<br />') {
				$error = substr($error, 6);
			}
?><div class="error"><p><strong><?php echo $error; ?></strong></p></div><?php
		}
if (strlen($message) > 0) {
			if (substr($message, 0, 6) == '<br />') {
				$message = substr($message, 6);
			}
	?><div class="updated"><p><strong><?php echo $message; ?></strong></p></div><?php
		}
?>
<div class="wrap">
<?php
		// The table will only be displayed if we are not editing an existing
		// item
		if ($action != 'edit') {
?>
<h2><?php _e('Bullshit Bingo Settings', 'bullshitbingo') ?></h2>
<h3><?php _e('Manage buzzwords', 'bullshitbingo') ?></h3><form id="bullshitbingo_form_bulk" name="bullshitbingo_form_bulk" method="post" action="<?php echo $request_uri; ?>">
<div class="tablenav">
<div class="alignleft actions" style="margin-right:10px">
<select id="bullshitbingo_bulk_action" name="bullshitbingo_bulk_action" style="vertical-align:middle; max-width:120px" onchange="javascript:bullshitbingo_disable_enable()">
<option value="none" ><?php _e('Bulk actions', 'bullshitbingo'); ?></option>
<option value="delete"><?php _e('Delete', 'bullshitbingo'); ?></option>
<option value="toggle"><?php _e('Toggle visibility', 'bullshitbingo'); ?></option>
<option value="change"><?php _e('Change tag', 'bullshitbingo'); ?></option>
</select>
<select id="bullshitbingo_tag" name="bullshitbingo_tag" style="vertical-align:middle; max-width:120px" disabled>
<option value=","><?php echo '- ' . __('none', 'bullshitbingo') . ' -'; ?></option>
<?php
			$query = "SELECT DISTINCT tag FROM " . $wpdb->prefix
				. BULLSHITBINGO_DB_TABLE . " ORDER BY tag asc";
			$tags = $wpdb->get_results($query, OBJECT);
			foreach ($tags as $object) {
				if (strlen($object->tag) > 0) {
?>
<option value="<?php echo $object->tag; ?>"><?php echo $object->tag; ?></option>
<?php
				}
			}
?>
</select> 
<input type="submit" value="<?php _e('Apply', 'bullshitbingo'); ?>" class="button-secondary action" />
</div><?php // alignleft actions ?>
<div class="alignleft actions"> 
<span style="color:#666; font-size:11px; white-space:nowrap;"><?php _e('Display', 'bullshitbingo'); ?></span>
<select id="bullshitbingo_lines" name="bullshitbingo_lines" onchange="javascript:bullshitbingo_action(this);"  style="vertical-align:middle;">
<?php
			$steps = explode(',', BULLSHITBINGO_ADMIN_STEPS);
			foreach ($steps as $step) {
?>
<option value="<?php echo add_query_arg('bblines', $step, remove_query_arg('bblines', $request_uri)); ?>"<?php echo ($step == $lines) ? ' selected' : ''; ?>><?php echo $step . ' ' . __('Buzzwords', 'bullshitbingo'); ?></option>
<?php
			} 
?>
</select>
<span style="color:#666; font-size:11px; white-space:nowrap;"><?php _e('from', 'bullshitbingo'); ?></span>
<select id="bullshitbingo_tags" name="bullshitbingo_tags" onchange="javascript:bullshitbingo_action(this);"  style="vertical-align:middle;">
<option value="<?php echo add_query_arg('bbtag', ',', remove_query_arg('bbtag', $request_uri)); ?>"<?php echo (',' == $tag) ? ' selected' : ''; ?>><?php echo '- ' . __('all', 'bullshitbingo') . ' -'; ?></option>
<?php
			foreach ($tags as $object) {
?>
<option value="<?php echo add_query_arg('bbtag', $object->tag, remove_query_arg('bbtag', $request_uri)); ?>"<?php echo ($object->tag == $tag) ? ' selected' : ''; ?>><?php echo $object->tag; ?></option>
<?php
			} 
?>
</select>
<span style="color:#666; font-size:11px; white-space:nowrap;"><?php _e('tag', 'bullshitbingo'); ?></span>
</div><?php // alignleft actions ?>
<?php 
			// Determine the number of pages to display
			$query = "SELECT COUNT(ID) FROM " . $wpdb->prefix
				. BULLSHITBINGO_DB_TABLE;
			if ($tag != ',') {
				$query .= " WHERE tag = '" . $tag . "'";
			}
			$rows = $wpdb->get_var($query);
			$pages = ceil($rows / $lines);
			$previous = '';
			$first = '';
			$navigation = '';
			$last = '';
			$next = '';
			$firstdot = '';
			$lastdot = '';
			for ($i = 1; $i <= $pages; $i++) {
				// If there is only few pages, show all the pages
				if ($pages <= BULLSHITBINGO_ADMIN_FEWPAGES) {
					if ($i == ($page + 1)) {
						// Do not create a link to the current page
						$navigation .= $i;
					} else {
						$navigation .= ' <a href="'
							. add_query_arg('bbpage', $i - 1,
							remove_query_arg('bbpage', $request_uri)) . '">'
							. $i . '</a> ';
					}
				// If there is many pages, only display links adjacent to the
				// current page 
				} else {
					if ($i == ($page + 1)) {
						// Do not create a link to the current page
						$navigation .= $i; // no need to create a link to current page
					} elseif (($i == 1) || ($i == $pages)) {
						// Creating links to first and last page will be handled
						// later
						// $navigation .= '';
					} else {
						if (($i < ($page + 1 + 2)) && ($i > ($page + 1 - 2))) {
							$navigation .= ' <a href="'
								. add_query_arg('bbpage', $i - 1,
								remove_query_arg('bbpage', $request_uri)) . '">'
								. $i . '</a> ';
						} else {
							if (($page + 1) > 3) {
								$firstdot = '&hellip;';
							}
							if (($page + 1) != ($pages -1)) {
								$lastdot = '&hellip;';
							}
						}
					}
				} 
			}
			// Add first and last, next and previous links
			if (($page + 1) > 1) {
				$i  = ($page + 1) - 1;		
				$previous = ' <a href="' . add_query_arg('bbpage', $i - 1,
					remove_query_arg('bbpage', $request_uri)). '">&laquo;</a> ';		
				if ($pages > (BULLSHITBINGO_ADMIN_FEWPAGES)) {
					$first = ' <a href="' . add_query_arg('bbpage', 0,
						remove_query_arg('bbpage', $request_uri)). '">1</a> '
						. $firstdot . ' ';
				}
			} else {
			   $previous  = '&nbsp;';
			   if ($pages > (BULLSHITBINGO_ADMIN_FEWPAGES)) {
			   		$first = '&nbsp;';
			   }
			}
			if (($page + 1) < $pages) {
				$missing = $rows - ($lines * (page + 1));		
				if ($missing > $lines) {
					$missing = $lines;
				}
				$i = ($page + 1) + 1;
				$next = ' <a href="' . add_query_arg('bbpage', $i - 1,
					remove_query_arg('bbpage', $request_uri))
					. '">&raquo;</a> ';
				if ($pages > (BULLSHITBINGO_ADMIN_FEWPAGES)) {
					$last = ' ' . $lastdot . ' <a href="'
						. add_query_arg('bbpage', $pages - 1,
						remove_query_arg('bbpage', $request_uri)). '"> '
						. $pages .'</a> ';
				}
			} else {
				if ($pages > (BULLSHITBINGO_ADMIN_FEWPAGES)) {
					$last = '&nbsp;';
				}
			}
?>
<div class="tablenav-pages">
<span class="displaying-num"><?php echo __('Page', 'bullshitbingo') . ' ' . ($page + 1) . ' ' . __('of', 'bullshitbingo') . ' ' . $pages; ?></span><strong><?php echo $previous . $first . $navigation . $last . $next; ?></strong>
</div><?php // tabelnav-pages ?>
</div><?php // tabelnav ?>
<?php
		// Display the table of buzzwords with edit and delete links
?>
<table id="bullshitbingo_manage" class="widefat">
<thead>
<tr>
<th scope="col" style="padding-left:0; margin-left:0;"><input type="checkbox" style="padding-left:0;" /></th>
<th scope="col" style="white-space: nowrap;"><?php echo $this->create_th('id', $request_uri, $sort, $order); ?></th>
<th scope="col" style="white-space: nowrap;"><?php echo $this->create_th('tag', $request_uri, $sort, $order); ?></th>
<th scope="col" style="white-space: nowrap;"><?php echo $this->create_th('buzzword', $request_uri, $sort, $order); ?></th>
<th scope="col" style="white-space: nowrap;"><?php echo $this->create_th('url', $request_uri, $sort, $order); ?></th>
<th scope="col" style="white-space: nowrap;"><?php echo $this->create_th('active', $request_uri, $sort, $order); ?></th>
<th scope="col" style="white-space: nowrap;"></th>
<th scope="col" style="white-space: nowrap;"></th>
</tr>
</thead>
<tbody>
<?php
			// Retrieve the information from the database
			$query = "SELECT ID, tag, buzzword, url, active FROM "
				. $wpdb->prefix . BULLSHITBINGO_DB_TABLE;
			if ($tag != ',') {
				$query .= " WHERE tag = '" . $tag . "'";
			}
			switch (strtolower($sort)) {
				case 'id':
					$sortby = 'ID';
					break;
				case 'tag':
					$sortby = 'tag';
					break;
				case 'buzzword':
					$sortby = 'buzzword';
					break;
				case 'url':
					$sortby = 'url';
					break;
				case 'active':
					$sortby = 'active';
					break;
				default:
					$sortby = 'ID';
					break;
			}
			switch (strtolower($order)) {
				case 'asc':
					$orderby = 'ASC';
					break;
				case 'desc':
					$orderby = 'DESC';
					break;
				default:
					$orderby = 'ASC';
					break;
			}
			$query .= " ORDER BY " . $sortby . " " . $orderby . " LIMIT "
				. ($page * $lines) . ", " . $lines;
			$result = $wpdb->get_results($query, OBJECT);
			$i = 0;
			foreach ($result as $object) {
?>
<tr<?php echo (($i % 2) == 0) ? ' class="alternate"' : ''; ?>>
<td scope="col" style="white-space:nowrap;"><input id="bullshitbingo_checkbox_<?php echo $object->ID; ?>" name="bullshitbingo_checkbox[]" type="checkbox" value="<?php echo $object->ID; ?>" /></td> 
<th scope="row" style="text-align:right;"><?php echo $object->ID; ?></th>
<td><?php echo $object->tag; ?></td>
<td><?php echo $object->buzzword; ?></td>
<td><?php echo $object->url; ?></td>
<td><?php echo ($object->active > 0) ? __('Yes', 'bullshitbingo') : __('No', 'bullshitbingo'); ?></td>
<td><a href="<?php echo add_query_arg('bbid', $object->ID, add_query_arg('bbaction', 'edit', remove_query_arg('bbaction', $request_uri))); ?>"><?php echo __('Edit', 'bullshitbingo'); ?></a></td>
<td><a href="<?php echo add_query_arg('bbid', $object->ID, add_query_arg('bbaction', 'delete', remove_query_arg('bbaction', $request_uri))); ?>" onclick="if (confirm('<?php echo sprintf(__('You are about to delete the buzzword &quot;%s&quot;.\nClick &quot;Cancel&quot; to keep it or &quot;OK&quot; to delete it.', 'bullshitbingo'), $object->buzzword); ?>')) { return true; } return false;"><?php echo __('Delete', 'bullshitbingo'); ?></a></td>
</tr>
<?php
				$i++;
			}
?>
</tbody>
<tfoot>
<tr>
<th scope="col" style="padding-left:0; margin-left:0;"><input type="checkbox" style="padding-left:0;" /></th>
<th scope="col" style="white-space: nowrap;"><?php echo $this->create_th('id', $request_uri, $sort, $order); ?></th>
<th scope="col" style="white-space: nowrap;"><?php echo $this->create_th('tag', $request_uri, $sort, $order); ?></th>
<th scope="col" style="white-space: nowrap;"><?php echo $this->create_th('buzzword', $request_uri, $sort, $order); ?></th>
<th scope="col" style="white-space: nowrap;"><?php echo $this->create_th('url', $request_uri, $sort, $order); ?></th>
<th scope="col" style="white-space: nowrap;"><?php echo $this->create_th('active', $request_uri, $sort, $order); ?></th>
<th scope="col" style="white-space: nowrap;"></th>
<th scope="col" style="white-space: nowrap;"></th>
</tr>
</tfoot>
</table>
<div class="tablenav">
<div class="tablenav-pages">
<span class="displaying-num"><?php echo __('Page', 'bullshitbingo') . ' ' . ($page + 1) . ' ' . __('of', 'bullshitbingo') . ' ' . $pages; ?></span><strong><?php echo $previous . $first . $navigation . $last . $next; ?></strong>
</div><?php // tabelnav-pages ?>
</div><?php // tabelnav ?>
</form>
<h3><?php _e('Add a new buzzword', 'bullshitbingo') ?></h3><form id="bullshitbingo_form_add" name="bullshitbingo_form_add" method="post" action="<?php echo remove_query_arg('bbaction', $request_uri); ?>"><input name="bbaction" type="hidden" value="add" /><input name="bbid" type="hidden" value="0" />
<?php
		} else {
?>
<h2><?php _e('Edit a buzzword', 'bullshitbingo') ?> (<?php echo __('ID', 'bullshitbingo') . ' ' . $id; ?>)</h2><form id="bullshitbingo_form_add" name="bullshitbingo_form_add" method="post" action="<?php echo remove_query_arg('bbaction', $request_uri); ?>"><input name="bbaction" type="hidden" value="edit" /><input name="bbid" type="hidden" value="<?php echo $id; ?>" />
<?php
		} 
?>
<table class="form-table">
<tr valign="top">
<th scope="row"><?php _e('Tag', 'bullshitbingo'); ?></th>
<td><input type="text" name="bullshitbingo_input_tag" value="<?php echo $input_tag; ?>" /></td>
</tr>
<tr valign="top">
<th scope="row"><?php _e('Buzzword', 'bullshitbingo'); ?></th>
<td><input type="text" name="bullshitbingo_input_buzzword" value="<?php echo $input_buzzword; ?>" /></td>
</tr>
<tr valign="top">
<th scope="row"><?php _e('URL', 'bullshitbingo'); ?></th>
<td><input type="text" name="bullshitbingo_input_url" value="<?php echo $input_url; ?>" /></td>
</tr>
<tr valign="top">
<th scope="row"><?php _e('Visible', 'bullshitbingo'); ?></th>
<td><select name="bullshitbingo_input_active">
<option value="0"<?php echo ($input_active == 0) ? ' selected' : ''; ?>><?php _e('No', 'bullshitbingo'); ?></option>
<option value="1"<?php echo ($input_active != 0) ? ' selected' : ''; ?>><?php _e('Yes', 'bullshitbingo'); ?></option>
</select>
</table>
<p class="submit">
<?php
		if ($action != 'edit') { 
?>
<input type="submit" class="button-primary" value="<?php _e('Add buzzword', 'bullshitbingo') ?>" />
<?php
		} else {
?>
<input type="submit" class="button-primary" value="<?php _e('Apply changes', 'bullshitbingo') ?>" />
<?php
		}
?>
</p>
<?php wp_nonce_field('bullshitbingo_submit', 'bullshitbingo_wpnonce', true, true); ?></form>
<?php
		// Display the bulk upload form if we are not editing an existing item
		if ($action != 'edit') {
?>
<h3><?php _e('Bulk add buzzwords', 'bullshitbingo') ?></h3><form id="bullshitbingo_form_bulkadd" name="bullshitbingo_form_bulkadd" method="post" action="<?php echo remove_query_arg('bbaction', $request_uri); ?>"><input name="bbaction" type="hidden" value="bulkadd" />
<table class="form-table">
<tr valign="top">
<th scope="row"><?php _e('Buzzwords', 'bullshitbingo'); ?><br /><span style="font-style:italic; font-size:90%;"><?php _e('(one word per line)', 'bullshitbingo'); ?></span></th>
<td><textarea name="bullshitbingo_bulkadd_buzzword"><?php echo $input_bulkadd_buzzword; ?></textarea></td>
</tr>
<tr valign="top">
<th scope="row"><?php _e('Tag', 'bullshitbingo'); ?><br /><span style="font-style:italic; font-size:90%;"><?php _e('(applies to all words above)', 'bullshitbingo'); ?></span></th>
<td><input type="text" name="bullshitbingo_bulkadd_tag" value="<?php echo $input_bulkadd_tag; ?>" /></td>
</tr>
<tr valign="top">
<th scope="row"><?php _e('Visible', 'bullshitbingo'); ?><br /><span style="font-style:italic; font-size:90%;"><?php _e('(applies to all words above)', 'bullshitbingo'); ?></span></th>
<td><select name="bullshitbingo_bulkadd_active">
<option value="0"<?php echo ($input_bulkadd_active == 0) ? ' selected' : ''; ?>><?php _e('No', 'bullshitbingo'); ?></option>
<option value="1"<?php echo ($input_bulkadd_active != 0) ? ' selected' : ''; ?>><?php _e('Yes', 'bullshitbingo'); ?></option>
</select>
</table>
<p class="submit">
<input type="submit" class="button-primary" value="<?php _e('Add buzzwords', 'bullshitbingo') ?>" />
</p>
<?php wp_nonce_field('bullshitbingo_bulk_submit', 'bullshitbingo_wpnonce', true, true); ?></form>
<?php
		}
?>
</div>
<!-- End Bullshit Bingo Plugin for WordPress Output -->
<?php
	}
	
	function create_th($column, $uri, $sort, $order) {
		switch($column) {
			case 'id':
				$text = __('ID', 'bullshitbingo');
				break;
			case 'tag':
				$text = __('Tag', 'bullshitbingo');
				break;
			case 'buzzword':
				$text = __('Buzzword', 'bullshitbingo');
				break;
			case 'url':
				$text = __('URL', 'bullshitbingo');
				break;
			case 'active':
				$text = __('Visible', 'bullshitbingo');
				break;
			default:
				$text = '';
				break;
		}
		$th = '';
		if ($column != $sort) {
			$uri = remove_query_arg('bbsort', $uri);
			$uri = add_query_arg('bbsort', $column, $uri);
			$uri = remove_query_arg('bborder', $uri);
			$uri = add_query_arg('bborder', 'asc', $uri);
			$uri = remove_query_arg('bbpage', $uri);
			$uri = add_query_arg('bbpage', 0, $uri);
			$th .= '<a href="' . $uri . '">';
		}
		$th .= $text;
		if ($column != $sort) {
			$th .= '</a>&nbsp;&nbsp;';
		} else {
			$uri = remove_query_arg('bborder', $uri);
			switch ($order) {
				case 'asc':
					$uri = add_query_arg('bborder', 'desc', $uri);
					$arrow = '&darr;';
					break;
				case 'desc':
				default:
					$uri = add_query_arg('bborder', 'asc', $uri);
					$arrow = '&uarr;';
					break;
			}
			$th .= '&nbsp;<a href="' . $uri . '">' . $arrow . '</a>';
		}
		return $th;
	}
	
	function add_header() {
?>
<script  type='text/javascript'><!--
<?php // TODO This function could be AJAXified. ?>
function bullshitbingo_action(select) {
	var i;
	for(i = 0; i < select.options.length; i++) {
		if (select.options[i].selected) {
			if (select.options[i].value != "") {
				window.location.href = select.options[i].value;
			}
			break;
		}
	}
} 

function bullshitbingo_disable_enable() {
	if (document.getElementById('bullshitbingo_bulk_action').value == 'change') {
		document.getElementById('bullshitbingo_tag').disabled = false;
	}
	else {
		document.getElementById('bullshitbingo_tag').disabled = true;
	}
}
--></script>
<?php
	}
}
?>
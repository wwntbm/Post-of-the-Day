<?php
	if(isset($_POST['potd_hidden']) && $_POST['potd_hidden'] == 'Y') {
		// Make sure nonce matches
    	if( !isset( $_POST['potd_nonce'] ) || 
    	!wp_verify_nonce( $_POST['potd_nonce'], 'save_potd_nonce' ) ) {
    		return; 
    	}
 
		// Form data sent
		$potd_post_types = isset($_POST['potd_post_types']) ? $_POST['potd_post_types'] : array();
		$potd_categories = isset($_POST['potd_categories']) ? $_POST['potd_categories'] : array();
		$amount = isset($_POST['potd_amount']) ? $_POST['potd_amount'] : '';
		$interval = isset($_POST['potd_interval']) ? $_POST['potd_interval'] : '';
		
		// Validate
		if ( !is_numeric($amount) ) {
			$notice = '<div class="error"><p>Please enter a number for the interval amount.</p></div>';
		} else {
			$amount = intval($amount);
			update_option('potd_post_types', $potd_post_types);
			update_option('potd_categories', $potd_categories);
			update_option('potd_amount', $amount);
			update_option('potd_interval', $interval);
		}
		
?>
		<?php if ( !isset($notice) ) { ?>
			<div class="updated"><p><strong><?php _e('Post of the Day settings saved' ); ?></strong></p></div>
		<?php } ?>
<?php 
	} else {
		//Normal page display
		if ( get_option('potd_post_types') ) {
			if ( !is_array(get_option('potd_post_types')) ) {
				$potd_post_types = array(get_option('potd_post_types'));
			} else {
				$potd_post_types = get_option('potd_post_types');
			}
		}  else {
			$potd_post_types = array();
		}
		if ( get_option('potd_categories') ) {
			if ( !is_array(get_option('potd_categories')) ) {
				$potd_categories = array(get_option('potd_categories'));
			} else {
				$potd_categories = get_option('potd_categories');
			}
		}  else {
			$potd_categories = array();
		}
		$amount = get_option('potd_amount');
		$interval = get_option('potd_interval');
	}
?>

<?php 
	// Get all post types and categories to choose from
	$post_types = get_post_types('','objects');
	$categories = get_categories();
?>

<div class="wrap">
	<div id="icon-options-general" class="icon32"><br /></div>
	<?php    echo "<h2>" . __( 'Post of the Day Settings', 'potd_trdom' ) . "</h2>"; ?>
	
	<?php if ( isset($notice) ) echo $notice; ?>

	<form name="potd_form" id="potd_form" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
		<input type="hidden" name="potd_hidden" value="Y">
		<?php wp_nonce_field( 'save_potd_nonce', 'potd_nonce' ); ?> 
		
		<table class="form-table">
			<fieldset><legend></legend>
			<tr valign="top">
				<th>
					<label for="potd_post_type"><?php _e("Choose Post Types: " ); ?></label>
				</th>
				<td>
					<?php if ( !empty($post_types) ) { ?>
						<ul>
						<?php foreach ( $post_types as $post_type ) { ?>
							<li>	
								<label for="potd_post_type_<?php echo $post_type->name; ?>">
								<input type="checkbox" name="potd_post_types[]" 
									id="potd_post_type_<?php echo $post_type->name; ?>"
									value="<?php echo $post_type->name; ?>"  
									<?php 
									if ( in_array($post_type->name, $potd_post_types) ) { 
										echo 'checked="checked"';
									}
									?>
								/> <?php echo $post_type->name; ?></label>
							</li>
						<?php } ?>
						</ul>
					<?php } ?>
				</td>
			</tr>
		
			<tr valign="top">
				<th>
					<label for="potd_category"><?php _e("Choose Categories: " ); ?></label>
				</th>
				<td>
					<?php if ( !empty($categories) ) { ?>
						<ul>
						<?php foreach ( $categories as $category ) { ?>
							<li>	
								<label for="potd_category_<?php echo $category->term_id; ?>">
								<input type="checkbox" name="potd_categories[]" 
									id="potd_category_<?php echo $category->term_id; ?>"
									value="<?php echo $category->term_id; ?>"  
									<?php 
									if ( in_array($category->term_id, $potd_categories) ) { 
										echo 'checked="checked"';
									}
									?>
								/> <?php echo $category->name; ?></label>
							</li>
						<?php } ?>
						</ul>
					<?php } ?>
				</td>
			</tr>
		
			<tr>
				<th>
					<label for="potd_amount"><?php _e("Enter an Interval: " ); ?></label>
				</th>
				<td>
					<input type="text" name="potd_amount" id="potd_amount" class="small-text" value="<?php echo $amount; ?>" maxlength="4">
				
					<select name="potd_interval" id="potd_interval">
						<option value="seconds" <?php selected( $interval, 'seconds' ); ?>>Seconds</option>
						<option value="minutes" <?php selected( $interval, 'minutes' ); ?>>Minutes</option>
						<option value="hours" <?php selected( $interval, 'hours' ); ?>>Hours</option>
						<option value="days" <?php selected( $interval, 'days' ); ?>>Days</option>
					</select>
				</td>
			</tr>
			
			</fieldset>
		</table>
			
		<p><input type="submit" name="submit" id="submit" class="button-primary" value="<?php _e('Save Changes', 'potd_trdom' ) ?>" /></p>
	</form>
	
	<h3>How to use the Post of the Day Plugin:</h3>
	<p>Simply place the tag [potd] in the content of the post or page where you want the Post of the Day to appear.</p>
	<p>If you would like to include the Post of the Day in your theme somewhere not in a post or page, paste the 
	following snippet into your template: 
		<pre>&lt;?php echo do_shortcode('[potd]'); ?&gt;</pre>
	</p>
</div>

<?php
/*
Plugin Name: BGMax Screen Combo
Plugin URI: https://www.odesk.com/users/~019c027c426b2c4d50
Description: Background combo image using Maximage Background Jquery plugin, a usefull plugin for a full width theme/website with a full size background in it
Author: Arung Isyadi
Author URI: https://www.odesk.com/users/~019c027c426b2c4d50
Version: 1.0

	Copyright: © 2013 Arung Isyadi (email : arungisyadi@outlook.com)
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

//enqueue the main jquery plugin script 
add_action('wp_enqueue_scripts', 'bgcombo_frontend_scripts');
function bgcombo_frontend_scripts() {	
	if(!is_admin()){
		wp_enqueue_script('jquery');
		wp_enqueue_script('cycle',plugins_url('js/jquery.cycle.all.min.js',__FILE__), array('jquery'));
		wp_enqueue_script('maximage',plugins_url('js/jquery.maximage.min.js',__FILE__), array('jquery'));
		wp_enqueue_style('maximage',plugins_url('css/jquery.maximage.css',__FILE__));
	}
}

class BGMax_Screen_Combo {
	
	
	public function __construct() {
		
		add_action( 'init', array( &$this, 'init' ) );
		
		if ( is_admin() ) {
			add_action( 'admin_init', array( &$this, 'admin_init' ) );
		}
	}
	

	/** Frontend methods ******************************************************/
	
	
	/**
	 * Register the custom post type
	 */
	public function init() {
	    register_post_type( 'bgcombo', array( 'public' => true, 'label' => 'Background', 'supports' => array('title', 'editor'), 'menu_icon' => plugins_url('img/block.png',__FILE__) ) );
	}
	
	
	/** Admin methods ******************************************************/
	
	
	/**
	 * Initialize the admin, adding actions to properly display and handle 
	 * the Book custom post type add/edit page
	 */
	public function admin_init() {
		global $pagenow;
		
		if ( $pagenow == 'post-new.php' || $pagenow == 'post.php' || $pagenow == 'edit.php' ) {
			
			add_action( 'add_meta_boxes', array( &$this, 'meta_boxes' ) );
			add_filter( 'enter_title_here', array( &$this, 'enter_title_here' ), 1, 2 );
			
			add_action( 'save_post', array( &$this, 'meta_boxes_save' ), 1, 2 );
			add_action( 'save_post', array( &$this, 'inpage_meta_boxes_save' ), 1, 2 );
		}
	}
	
	public function add_max_image_to_footer(){
		echo '<p>test putting text to footer</p>';
	}
	
	/**
	 * Save meta boxes
	 * 
	 * Runs when a post is saved and does an action which the write panel save scripts can hook into.
	 */
	public function meta_boxes_save( $post_id, $post ) {
		if ( empty( $post_id ) || empty( $post ) || empty( $_POST ) ) return;
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( is_int( wp_is_post_revision( $post ) ) ) return;
		if ( is_int( wp_is_post_autosave( $post ) ) ) return;
		if ( ! current_user_can( 'edit_post', $post_id ) ) return;
		if ( $post->post_type != 'bgcombo' ) return;
			
		$this->process_bgcombo_meta( $post_id, $post );
	}
	
	public function inpage_meta_boxes_save( $post_id, $post ) {
		  /*
		   * We need to verify this came from the our screen and with proper authorization,
		   * because save_post can be triggered at other times.
		   */
		
		  // Check if our nonce is set.
		  if ( ! isset( $_POST['bgcombo_inpage_meta_box_nonce'] ) )
			return $post_id;
		
		  $nonce = $_POST['bgcombo_inpage_meta_box_nonce'];
		
		  // Verify that the nonce is valid.
		  if ( ! wp_verify_nonce( $nonce, 'bgcombo_inpage_meta_box' ) )
			  return $post_id;
		
		  // If this is an autosave, our form has not been submitted, so we don't want to do anything.
		  if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			  return $post_id;
		
		  // Check the user's permissions.
		  if ( 'page' == $_POST['post_type'] ) {
		
			if ( ! current_user_can( 'edit_page', $post_id ) )
				return $post_id;
		  
		  } else {
		
			if ( ! current_user_can( 'edit_post', $post_id ) )
				return $post_id;
		  }
		
		  /* OK, its safe for us to save the data now. */
		
		  // Sanitize user input.
		  $mydata = sanitize_text_field( $_POST['bgcombo_opt'] );
		
		  // Update the meta field in the database.
		  update_post_meta( $post_id, '_bgcombo_opt', $mydata );
	}
	
	/**
	 * Function for processing and storing all book data.
	 */
	private function process_bgcombo_meta( $post_id, $post ) {
		for($i = 1; $i <= 5; $i++){
			update_post_meta( $post_id, '_image_url_'.$i, $_POST['upload_image_url_'.$i] );
		}
	}
	
	private function process_bgcombo_inpage_meta( $post_id, $post ) {
		update_post_meta( $post_id, '_bgcombo_opt', $_POST['bgcombo_opt'] );
	}
	
	
	/**
	 * Set a more appropriate placeholder text for the New Book title field
	 */
	public function enter_title_here( $text, $post ) {
		if ( $post->post_type == 'bgcombo' ) return __( 'Combo\'s Title' );
		return $text;
	}
	
	
	/**
	 * Add and remove meta boxes from the edit page
	 */
	public function meta_boxes() {
		add_meta_box( 'bgimage', __( 'Chose the images you want to use in this combo from this box' ), array( &$this, 'bgcombo_image_meta_box' ), 'bgcombo', 'normal', 'high' );
		$types = array('page', 'post');
		foreach( $types as $type){
			add_meta_box( 'bginpage', __( 'Chose your background combo from the list below.' ), array( &$this, 'bgcombo_inpage_meta_box' ), $type, 'normal', 'high' );
		}
	}
	
	/**
	 * Display the image meta box
	 */
	public function bgcombo_inpage_meta_box() {
		// Add an nonce field so we can check for it later.
  		wp_nonce_field( 'bgcombo_inpage_meta_box', 'bgcombo_inpage_meta_box_nonce' );
		
		global $post;
	
	  /*
	   * Use get_post_meta() to retrieve an existing value
	   * from the database and use the value for the form.
	   */
	  $value = get_post_meta( $post->ID, '_bgcombo_opt', true );
	
	  echo '<p style="width:100%;"> ';
	  echo '<label for="bgcombo_title_new_field" style="vertical-align: middle;min-width: 130px;display: inline-block;">';
		   _e( "Background combo name:", 'myplugin_textdomain' );
	  echo '</label> ';
	  $bgcombos = new WP_Query( array('post_type'=>'bgcombo', 'order_by'=>'ID', 'order'=>'ASC'));
	  echo '<select name="bgcombo_opt" id="bgcombo_opt">';
	  if ($bgcombos->have_posts()) : while ($bgcombos->have_posts()) : $bgcombos->the_post();
		  $opt_value = $post->ID;
		  $opt_label = get_the_title();
		  if($value == $opt_value){
			  echo '<option value="'.$opt_value.'" selected="selected">'.$opt_label.'</option>';
		  }else{
			  echo '<option value="'.$opt_value.'">'.$opt_label.'</option>';
		  }
	  endwhile;
	  endif;
	  echo '</select>';
	  echo '</p> ';
	}
	
	public function bgcombo_image_meta_box() {
		global $post;
		
		for($i = 1; $i <= 5; $i++){
			$image_src = '';
			
			$image_src = get_post_meta( $post->ID, '_image_url_'.$i, true );
			//$image_src = wp_get_attachment_url( $image_id );
			
			?>
			<img id="bgcombo_image_<?=$i;?>" src="<?php echo $image_src ?>" style="max-width:100%;" />
			<input type="text" width="150" name="upload_image_url_<?=$i;?>" id="upload_image_url_<?=$i;?>" value="<?php echo $image_src; ?>" style="width: 350px;" />
			<p style="border-bottom: #333 solid 1px; margin-bottom: 5px; margin-top: 0; padding: 5px;">
				<a title="<?php esc_attr_e( 'Set image' ) ?>" href="#" id="set-bgcombo-image_<?=$i;?>" class="button button-primary button-large"><?php _e( 'Set image' ) ?></a>
				<a title="<?php esc_attr_e( 'Remove image' ) ?>" href="#" id="remove-bgcombo-image_<?=$i;?>" style="<?php echo ( ! $image_src ? 'display:none;' : '' ); ?>" class="button button-primary button-large"><?php _e( 'Removeimage' ) ?></a>
			</p>
		
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			
			// save the send_to_editor handler function
			window.send_to_editor_default = window.send_to_editor;
			$('#postdivrich').css({'visibility':'hidden', 'position':'absolute'});
			var $x = <?php echo $i ?>;
			var $y = 'attach_image<?php echo $i ?>';
				$('#set-bgcombo-image_'+$x).click(function(){
					
					// replace the default send_to_editor handler function with our own
					window.send_to_editor = window.attach_image<?php echo $i ?>;
					tb_show('', 'media-upload.php?post_id=<?php echo $post->ID ?>&amp;type=image&amp;TB_iframe=true');
										
					return false;
					
				});
				
				$('#remove-bgcombo-image_'+$x).click(function() {
					
					$('#upload_image_url'+$x).val('');
					$('img#bgcombo_image_'+$x).attr('src', '');
					$(this).hide();
					
					return false;
				});
				
				// handler function which is invoked after the user selects an image from the gallery popup.
				// this function displays the image and sets the id so it can be persisted to the post meta
				
				window.attach_image<?php echo $i ?> = function(html) {
					
					// turn the returned image html into a hidden image element so we can easily pull the relevant attributes we need
					$('body').append('<div id="temp_image_<?php echo $i ?>">' + html + '</div>');
						
					var img = $('#temp_image_<?php echo $i ?>').find('img');
					
					imgurl   = img.attr('src');
					imgclass = img.attr('class');
					//imgid    = parseInt(imgclass.replace(/\D/g, ''), 10);
		
					$('#upload_image_url_<?php echo $i ?>').val(imgurl);
					$('#remove-bgcombo-image_<?php echo $i ?>').show();
		
					$('img#bgcombo_image_<?php echo $i ?>').attr('src', imgurl);
					try{tb_remove();}catch(e){};
					$('#temp_image_<?php echo $i ?>').remove();
					//alert('File that supposed to be input is: <?php echo $i ?>, '+imgurl+$y+$x);
					
					// restore the send_to_editor handler function
					window.send_to_editor = window.send_to_editor_default;
					
				}
		});
		</script>
        <?php }
	}
	
}

// finally instantiate our plugin class and add it to the set of globals
$GLOBALS['bgmax_screen_combo'] = new BGMax_Screen_Combo();

function bgcombo_max_footer(){
?>
        <!--BGMax Combo start here-->
        <script type="text/javascript" charset="utf-8">
		$(function(){
			$('#maximage').maximage({
				cycleOptions: {
					fx:'scrollHorz',
					speed: 500,
					timeout: 5000,
					prev: '#arrow_left',
					next: '#arrow_right',
					pause: 1
				},
				verticalCenter: false,
				horizontalCenter: false,
				backgroundSize: 'cover',
				onImagesLoaded: function(){
					jQuery('#maximage').fadeIn();
				},
				cssBackgroundSize: false
			});
		});
		</script>

        <div id="maximage">
			<?php 
			global $post;
			
            $opt_id = get_post_meta( $post->ID, '_bgcombo_opt', true );
            
			if($opt_id != ''){
				//get the image stored
				for ($i = 1; $i <= 5; $i++){
					$img_url = get_post_meta( $opt_id, '_image_url_'.$i, true);
					if($img_url != ''){
						echo '<img alt="" src="'.$img_url.'" />';
					}else{
						echo '';
					}
					
				}
			}else{
				echo 'No Combo Chosen fot this page';
			}
            ?>
        </div>
<?php
}

function bgcombo_footer(){
	if(is_home() || is_category() || is_tax() || is_tag() || is_date() || is_author()){
		bgcombo_max_footer();
	}	
	elseif(is_page() || is_front_page() && !is_home()){
		bgcombo_max_footer();
	}
	elseif(is_single()){
		bgcombo_max_footer();
	}
}
add_action('wp_footer','bgcombo_footer');
?>
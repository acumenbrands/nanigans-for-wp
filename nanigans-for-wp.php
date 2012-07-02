<?php
/*
Plugin Name: Nanigans Tracker for WP
Plugin URI: http://acumenholdings.com/
Description: Ads Nanigans event tracking to a WordPress site
Version: 0.0b
Author: Brian Sage
Author URI: http://twitter.com/briansage

*/


if (!class_exists("NTWP")) {
  class NTWP {

    function NTWP() { // constructor
    }

    function plugin_loaded_action() {
    }


    function wp_head_action(){
      $output = <<<HTML
HTML;
      echo $output;
    }


    function wp_footer_action(){
      $output = "";
      $nanigans_app_id = get_option('NTWP_nanigans_app_id');

      $output .= <<<HTML
        <script type="text/javascript">

          // Makes handling cookie values a little easier
          if(typeof Cookiemonger == 'undefined') {
            Cookiemonger = {

              // Reads the whole document.cookie, and returns result as type Array of keyed Arrays
              load : function(){
                var Cookie = {};
                var cookie_arr = document.cookie.split('; ');
                if (cookie_arr.length){
                  for(var i=0, len = cookie_arr.length; i<len; i++){
                    var pair = Cookie[i].split('=');
                    Cookie[unescape(pair[0])] = unescape(pair[1]);
                  }
                }
                return Cookie;
              },

              // Write a cookie value.
              // expires may be set to null for expiration with session
              save : function(cookie_obj,expires,path) {
                var params = {
                  exp: (typeof expires != 'undefined')? expires : new Date(Date.now() + 94608000000).toUTCString(), // About 3 years worth of msecs
                  p: (typeof path != 'undefined')? path : '/'
                }
                var cookie_key = '';
                var cookie_val = '';
                for(var key in cookie_obj) {
                  cookie_key = escape(key.toString());
                  cookie_val = escape(cookie_obj[key].toString());
                }
                var cookie_str = cookie_key +'='+ cookie_val + ';path=' + params.p + ((params.exp == null)? '' : ';expires=' + params.exp);
                document.cookie = cookie_str;
              },

              // Finds the cookie key given, and returns the result as type String
              find : function(key){
                var Cookie = document.cookie.split('; ');
                if (Cookie.length){
                  for(var i=0, len = Cookie.length; i<len; i++){
                    var pair = Cookie[i].split('=');
                    if(unescape(pair[0]) == key) {
                      return unescape(pair[1]);
                    }
                  }
                }
                return undefined;
              },

              remove : function(key) {
                if (key){
                  var cookiemonger_delete = {};
                  cookiemonger_delete[key] = '';
                  var exp_date = new Date();
                  exp_date.setDate(exp_date.getDate() - 1);
                  this.save(cookiemonger_delete,exp_date);
                }
              }
            }
          }


          // Nanigans Trackers
          if (!Cookiemonger.find('nanigans_session')) {
            // Find/Assign a user id (persists 3 years)
            if (Cookiemonger.find('nanigans_user_id')){
              window.nanigans_user_id = Cookiemonger.find('nanigans_user_id');
            // If nanigans_user_id could not be found, reuse UTMA id
            } else if(typeof Cookiemonger.find('__utma') != 'undefined' && Cookiemonger.find('__utma').split('.').length){
              window.nanigans_user_id = Cookiemonger.find('__utma').split('.')[1];
            // If all else fails, it's a big, long random for you, Mr. User.
            } else {
              window.nanigans_user_id = Math.ceil(Math.random() * 999999999999999);
            }

            Cookiemonger.save({'nanigans_session':'true'}, null ); // Expires with session.

            Cookiemonger.save({'nanigans_user_id':window.nanigans_user_id});

            window.nanigans_tracker = new Image();
            nanigans_tracker.src = "//api.nanigans.com/event.php?app_id={$nanigans_app_id}&type=visit&name=landing&user_id=" + window.nanigans_user_id;
          }


          // Bind to any email submit forms
          $(function(){
            $('form').has('input[type="email"],input[name~="email"]').submit('submit',function(){
              var nanigans_user_id = Cookiemonger.find('nanigans_user_id');
              window.nanigans_tracker = new Image();
              nanigans_tracker.src = "//api.nanigans.com/event.php?app_id={$nanigans_app_id}&type=visit&name=email&user_id=" + nanigans_user_id;
            });
          });
        </script>
HTML;
      echo $output;
    }


    function NTWP_admin_menu () {
      global $NTWP;
      if ( count($_POST) > 0 && isset($_POST['NTWP_settings'])):
          
        // Setup Nanigans Settings Form
        $options = array(
          'nanigans_app_id'
        );
        foreach ( $options as $opt ){
          delete_option ( 'NTWP_'.$opt, $_POST[$opt] );
          add_option ( 'NTWP_'.$opt, $_POST[$opt] );  
        }

      endif;
      add_menu_page('Nanigans Settings', 'Nanigans', 'manage_options', 'ntwp_settings', null, plugins_url('nanigans-for-wp-icon.png', __FILE__));
      add_submenu_page('ntwp_settings', 'Nanigans Settings', 'Nanigans Settings', 'manage_options', 'ntwp_settings', array(&$NTWP,'NTWP_admin_settings'));

    }

    function NTWP_admin_settings() {
    ?>

    <div class="wrap">
      <h2>Nanigans Settings</h2>
      
      <form method="post" action="">

        <div id="col-container">
          <div id="col-left">
            <div class="col-wrap">
              <div class="form-wrap">
                
                <h3>Nanigans <i>Signup</i> Campaign</h3>
                <p>These are the list IDs needed for the <strong>primary squeeze page signup action</strong>.</p>
                
                <div class="form-field">
                  <label for="nanigans_app_id">App ID</label>
                  <input name="nanigans_app_id" type="text" id="nanigans_app_id" value="<?php echo get_option('NTWP_nanigans_app_id'); ?>" />
                  <p>This site's Nanigans <b>App ID</b>.</p>
                </div>
                
                <p class="submit">
                  <input type="submit" name="Submit" class="button-primary" value="Save Changes" />
                  <input type="hidden" name="NTWP_settings" value="save" style="display:none;" />
                </p>
            
              </div>
            </div>
          </div>
        </div>
        
      </form>
    </div>
          
          
    <?php
    }

    function NTWP_warning() {
      echo "<div id='NTWP-warning' class='updated fade'><p><strong>Nanigans Settings are almost ready.</strong> ".sprintf('You must <a href="%1$s">enter an Nanigans App ID</a> for it to work.', "admin.php?page=ntwp_settings")."</p></div>";
    }

  }
}




if (class_exists("NTWP")) {
  $NTWP = new NTWP();
}

if (isset($NTWP)) :
  add_action( 'plugins_loaded', array(&$NTWP,'plugin_loaded_action') );
  add_action( 'wp_head', array(&$NTWP,'wp_head_action') );
  add_action( 'wp_footer', array(&$NTWP,'wp_footer_action') );
  add_action( 'admin_menu', array(&$NTWP,'NTWP_admin_menu') );
  
  // Warnings
  if (!get_option('NTWP_nanigans_app_id') && !get_option('NTWP_nanigans_lid') && !isset($_POST['submit'])):
    add_action('admin_notices', array(&$NTWP,'NTWP_warning') );
  endif;

endif;
